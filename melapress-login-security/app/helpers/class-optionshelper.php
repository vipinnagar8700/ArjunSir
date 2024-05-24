<?php
/**
 * Helper class to get options within this plugin.
 *
 * @since 2.1.0
 *
 * @package WordPress
 */

namespace PPMWP\Helpers;

use PPMWP\InactiveUsers;

/**
 * Helper class for getting various options for the plugin.
 *
 * @since 2.1.0
 */
class OptionsHelper {

	/**
	 * Checks if inactive users feature should be active.
	 *
	 * This feature has several pre-requesits. It needs to be enabled, the
	 * password expiration feature needs to be enabled and the length on that
	 * passwords expiry needs to be longer than 30 days.
	 *
	 * @method should_inactive_users_feature_be_active
	 * @since  2.1.0
	 * @return bool
	 */
	public static function should_inactive_users_feature_be_active() {
		$ppm = \ppm_wp();

		// return early if the inactive class already is set active.
		if ( isset( $ppm->inactive ) && ! is_bool( $ppm->inactive ) && null !== $ppm->inactive->is_feature_enabled() ) {
			return $ppm->inactive->is_feature_enabled();
		} else {
			// not already determined to be active so assume false till tested.
			$active = false;
		}
		// If accessed early this item can be an array but we always want an
		// object.
		$master_policy = self::get_master_policy_options();
		if ( empty( $master_policy ) || ! isset( $master_policy->inactive_users_enabled ) ) {
			// If empty, then check DB.
			$master_policy = (object) get_site_option( PPMWP_PREFIX . '_options' );
		}

		// check if we are enabled.
		if (
			( isset( $master_policy->inactive_users_enabled ) && self::string_to_bool( $master_policy->inactive_users_enabled ) ||
			isset( $master_policy->failed_login_policies_enabled ) && self::string_to_bool( $master_policy->failed_login_policies_enabled ) )
		) {
			// master policy sets this as active, no need to do farther checks.
			$active = true;
		}

		// if master policy doesn't make this active check individual roles.
		if ( ! $active ) {
			global $wp_roles;
			$roles = $wp_roles->get_names();
			// loop through roles till we are either active or finished.
			foreach ( $roles as $role => $role_name ) {
				// if we got active in the last run break early.
				if ( $active ) {
					break;
				}
				$role_options = self::get_role_options( $role );

				if ( ( isset( $role_options->inherit_policies ) && self::string_to_bool( $role_options->inherit_policies ) ) || ( isset( $role_options->enforce_password ) && self::string_to_bool( $role_options->enforce_password ) ) ) {
					// policy is inherited from master which  didn't activate
					// this role is excluded from policies so continue.
					continue;
				}
				if (
					( isset( $role_options->inactive_users_enabled ) && self::string_to_bool( $role_options->inactive_users_enabled ) ||
					isset( $role_options->failed_login_policies_enabled ) && self::string_to_bool( $role_options->failed_login_policies_enabled ) )
				) {
					$active = true;
				}
			}
		}

		// feature is enabled if this is true, false by default.
		if ( isset( $ppm->inactive ) && ! is_bool( $ppm->inactive ) ) {
			$ppm->inactive->set_feature_enabled( $active );
		}
		return $active;
	}

	/**
	 * Gets the options for the master policy.
	 *
	 * @method get_master_policy_options
	 * @since  2.1.0
	 * @return object
	 */
	public static function get_master_policy_options() {
		$ppm           = ppm_wp();
		$master_policy = ( isset( $ppm->options->inherit ) ) ? $ppm->options->inherit : array();
		return (object) $master_policy;
	}

	/**
	 * Checks global settings in order to extract the plugin enabled status properly
	 *
	 * @return bool
	 */
	public static function get_plugin_is_enabled(): bool {
		$global_settings = self::get_master_policy_options();

		return self::string_to_bool( $global_settings->master_switch );
	}

	/**
	 * Gets the options for a specific role.
	 *
	 * @method get_role_options
	 * @since  2.1.0
	 * @param  string $role a user role to try get options policy for.
	 * @return object
	 */
	public static function get_role_options( $role = '' ) {
		$ppm     = ppm_wp();
		$options = ( isset( ppm_wp()->options ) ) ? ppm_wp()->options->get_role_options( $role ) : array();
		return (object) $options;
	}

	/**
	 * Gets the time, in seconds, that the users password was last reset.
	 *
	 * @method get_password_history_expiry_time_in_seconds
	 * @since  2.1.0
	 * @param  integer $value a value to generate a seconds based time from.
	 * @param  string  $unit  the unit to multiply the value by.
	 * @return int
	 */
	public static function get_password_history_expiry_time_in_seconds( $value = 0, $unit = '' ) {
		$expiry_time = 0;
		// if we don't have a unit and value to get time from then get it from master policy.
		if ( empty( $value ) && empty( $unit ) ) {
			$ppm = \ppm_wp();
			// If accessed early this item can be an array but we always want an
			// object.
			$setting_options = ( isset( $ppm->options->setting_options ) ) ? $ppm->options->setting_options : array();
			$setting_options = is_array( $setting_options ) ? (object) $setting_options : $setting_options;
			// if this array doesn't exist we need to bail early.
			// probably means plugin is not yet fully installed.
			if ( ! isset( $setting_options->password_expiry['value'] ) ) {
				return $expiry_time;
			}
			// can get values from an object.
			$value = (int) $setting_options->password_expiry['value'];
			$unit  = ( isset( $setting_options->password_expiry['unit'] ) ) ? $setting_options->password_expiry['unit'] : false;
		}
		// multiply the value by the unit to get a time in seconds.
		switch ( $unit ) {
			case 'hours':
				$expiry_time = $value * HOUR_IN_SECONDS;
				break;
			case 'days':
				$expiry_time = $value * DAY_IN_SECONDS;
				break;
			case 'weeks':
				$expiry_time = $value * WEEK_IN_SECONDS;
				break;
			case 'months':
				$expiry_time = $value * MONTH_IN_SECONDS;
				break;
			default:
				// assume seconds.
				$expiry_time = $value;
		}
		return $expiry_time;
	}

	/**
	 * Gets an expiry time for a given user ID - either from master policy or
	 * from a role specific policy.
	 *
	 * @method get_users_password_history_expiry_time_in_seconds
	 * @since  2.1.0
	 * @param  int $user_id a user id to try get a time for.
	 * @return int
	 */
	public static function get_users_password_history_expiry_time_in_seconds( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			return 0;
		}

		$user = get_userdata( $user_id );
		if ( is_a( $user, '\WP_User' ) ) {
			$user_roles = self::prioritise_roles( $user->roles );
			foreach ( $user_roles as $user_role ) {
				$role_options = self::get_role_options( $user_role );
				if ( ! isset( $role_options->password_expiry['value'] ) || ! isset( $role_options->password_expiry['unit'] ) ) {
					// skip this as the policy doesn't have a hostory expiry time.
					continue;
				}
				$history_expiry_time = self::get_password_history_expiry_time_in_seconds( $role_options->password_expiry['value'], $role_options->password_expiry['unit'] );
				// break from loop early if we have an expiry from one of the roles.
				if ( $history_expiry_time ) {
					break;
				}
			}
		}
		return $history_expiry_time;

	}

	/**
	 * Get the inactive users array.
	 *
	 * @method get_inactive_users
	 * @since  2.1.0
	 */
	public static function get_inactive_users() {
		$users = get_site_option( PPMWP_PREFIX . '_inactive_users', array() );
		// if for some reason we have invalid values use empty array.
		if ( ! is_array( $users ) ) {
			$users = array();
		}
		return $users;
	}

	/**
	 * Gets the users last history timestamp from user meta.
	 *
	 * @method get_users_last_history_time
	 * @since  2.1.0
	 * @param  int $user_id a user id.
	 * @return int
	 */
	public static function get_users_last_history_time( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return 0;
		}

		$password_history = get_user_meta( $user_id, 'ppmwp_last_activity', true );
		return (int) $password_history;
	}

	/**
	 * Runs a check to see if a user that is inactive can still reset due to
	 * them being reset by an admin withing the timeframe.
	 *
	 * NOTE: assumes they ARE allowed to reset by default.
	 *
	 * @method is_inactive_user_allowed_to_reset
	 * @since  2.1.0
	 * @param  int $user_id user ID to use.
	 */
	public static function is_inactive_user_allowed_to_reset( $user_id = 0 ) {
		$reset_time    = self::get_users_last_history_time( $user_id );
		$reset_allowed = true;
		// If we have a last reset or history time then check it.
		if ( $reset_time ) {
			// If the last reset time + dormancy period is more than current
			// time user is allowed to reset.
			if ( (int) $reset_time + apply_filters( 'ppmwp_adjust_dormancy_period', \PPMWP\InactiveUsers::DORMANCY_PERIOD ) < current_time( 'timestamp' ) ) {
				$reset_allowed = false;
			}
		}

		return $reset_allowed;

	}

	/**
	 * Gets the time a users password last 'expired'.
	 *
	 * NOTE: this value may also be the time user was last reset by admin.
	 *
	 * @method get_user_last_expiry_time
	 * @since  2.1.0
	 * @param  int $user_id user ID to use.
	 */
	public static function get_user_last_expiry_time( $user_id = 0 ) {
		$time = get_user_meta( $user_id, 'ppmwp_last_activity', true );
		// if we have a time return it otherwise return 0.
		return ( isset( $time ) ) ? $time : 0;
	}

	/**
	 * Sets the users last expiry time - or deletes the key when time === 0;
	 *
	 * @method set_user_last_expiry_time
	 * @since  2.1.0
	 * @param  int/null $time a timestamp to save - when 0 we delete the meta.
	 * @param  integer  $user_id a user ID.
	 */
	public static function set_user_last_expiry_time( $time, $user_id = 0 ) {
		// if there is no user ID or time to work with bail early.
		if ( empty( $user_id ) ) {
			return;
		}
		// if the user is inactive exempt then delete their expiry and bail.
		if ( self::is_user_inactive_exempted( $user_id ) ) {
			delete_user_meta( $user_id, PPMWP_PREFIX . '_' . \PPMWP\PPM_WP_History::LAST_EXPIRY_TIME_KEY );
			return;
		}
		// if time is zero then delete the key otherwise update with new value.
		if ( 0 === $time ) {
			delete_user_meta( $user_id, PPMWP_PREFIX . '_' . \PPMWP\PPM_WP_History::LAST_EXPIRY_TIME_KEY );
		} else {
			update_user_meta( $user_id, PPMWP_PREFIX . '_' . \PPMWP\PPM_WP_History::LAST_EXPIRY_TIME_KEY, $time );
		}

	}

	/**
	 * Sets a user as inactive.
	 *
	 * @method set_user_inactive
	 * @since  2.1.0
	 * @param  int $user_id user ID to use.
	 */
	public static function set_user_inactive( $user_id = 0 ) {
		// sets this user metakey to true as a inactive flag on the user.
		update_user_meta( $user_id, PPMWP_PREFIX . '_' . InactiveUsers::DORMANT_USER_FLAG_KEY, true );
		update_user_meta( $user_id, PPMWP_PREFIX . '_' . InactiveUsers::DORMANT_SET_TIME, current_time( 'timestamp' ) );
	}

	/**
	 * Gets a timestamp of the time when a user was set inactive.
	 *
	 * @method get_inactive_user_time
	 * @since  2.1.0
	 * @param  integer $user_id A user id to work with.
	 * @return string/bool
	 */
	public static function get_inactive_user_time( $user_id = 0 ) {
		$blocked_time = get_user_meta( $user_id, 'ppmwp_blocked_since', true );
		return ( $blocked_time ) ? $blocked_time : get_user_meta( $user_id, 'ppmwp_last_activity', true );
	}

	/**
	 * Checks if a user is exempted from the inactive user feature.
	 *
	 * @param integer $user_id User ID.
	 * @param array   $roles An array of user roles that the ID belongs to.
	 * @return boolean
	 */
	public static function is_user_inactive_exempted( $user_id = 0, $roles = array() ) {
		// assume not exempt to start.
		$exempt = false;
		$ppm    = ppm_wp();

		// if no user is supplied, assume they are not exempted.
		if ( false === $user_id ) {
			return false;
		}

		// get the list of exempt users.
		$inactive_exempt_users = ( isset( $ppm->options->ppm_setting->inactive_exempted['users'] ) ) ? $ppm->options->ppm_setting->inactive_exempted['users'] : array();
		if ( is_array( $inactive_exempt_users ) && ! empty( $inactive_exempt_users ) ) {
			// check if this particular user is exempted.
			if ( array_key_exists( $user_id, $inactive_exempt_users ) ) {
				$exempt = true;
			}
		}

		// if the user is not in exempt list check if their roles are somehow
		// exempt.
		if ( ! $exempt ) {
			// if no role based policy is found we need to check master at the
			// setup NOT to skip unless a valid policy is checked.
			$skip_master = false;
			// if no roles were passed then get them.
			if ( empty( $roles ) ) {
				$userdata = get_userdata( $user_id );
				$roles    = self::prioritise_roles( $userdata->roles );
			}
			foreach ( $roles as $role ) {
				if ( is_object( $ppm->inactive ) ) {
					if ( isset( $ppm->inactive ) && $ppm->inactive->is_role_exempt( $role ) ) {
						$exempt = true;
					}
				}
				
				// exit the loop early if one of the roles is considered exempt.
				if ( $exempt ) {
					break;
				}
				$role_options = self::get_role_options( $role );

				// if the policy is inherited then we should skip.
				if ( isset( $role_options->inherit_policies ) && self::string_to_bool( $role_options->inherit_policies ) ) {
					continue;
				}
				// if policies are not enforced for this role they are exempt.
				if ( isset( $role_options->enforce_password ) && self::string_to_bool( $role_options->enforce_password ) ) {
					$exempt = true;
				} else {
					// here the policy is not inherited and enforcement for the
					// role is not disabled.
					if (
						( ! isset( $role_options->password_expiry['value'] ) || ! isset( $role_options->inactive_users_enabled ) ) ||
						( ! $role_options->password_expiry['value'] >= 1 && ! self::string_to_bool( $role_options->inactive_users_enabled ) )
					) {
						// feature is disabled for this role - user is exempt.
						$exempt = true;
					} else {
						// since we got a policy that was not inherited and
						// enforcement was not disabled don't check master
						// policy at the end.
						$skip_master = true;
					}
				}
				// if this role was deemed as exempt store that role incase we
				// need to check that same role type again.
				if ( isset( $ppm->inactive ) && $exempt ) {
					$ppm->inactive->add_exempt_role( $role );
				}
			}
			// if user or role is has still not determined any exempt states we
			// need to fallback and check again against the master policy.
			if ( ! $exempt ) {
				// in cases where a role takes priority master is skipped.
				if ( isset( $skip_master ) && ! $skip_master ) {
					$master_policy = self::get_master_policy_options();
					if (
						( ! isset( $master_policy->password_expiry['value'] ) || ! isset( $master_policy->inactive_users_enabled ) ) ||
						( ! $master_policy->password_expiry['value'] >= 1 && ! self::string_to_bool( $master_policy->inactive_users_enabled ) )
					) {
						// feature is disabled enabled for this role.
						$exempt = true;
					}
				}
			}
		}
		return $exempt;
	}


	/**
	 * Adds the initial user that enabled inactive users feature to the list of
	 * users exempt from the checking. This prevents a complete site lockout in
	 * a situation where all user accounts would be inactive locked.
	 *
	 * @method add_initial_user_to_inactive_exempt_list
	 * @since  2.1.0
	 * @param  \WP_User $user a user object to maybe be added to inactive exempt list.
	 */
	public static function add_initial_user_to_inactive_exempt_list( $user ) {
		$added                 = false;
		$ppm                   = ppm_wp();
		$inactive_exempt_users = isset( $ppm->options->ppm_setting->inactive_exempted['users'] ) ? $ppm->options->ppm_setting->inactive_exempted['users'] : array();
		// if we have an empty list then add this user.
		if ( empty( $inactive_exempt_users ) ) {
			$inactive_exempt_users[ $user->ID ] = $user->data->user_login;
			// update the inactive exempt list adding user that enabled feature.
			$ppm->options->ppm_setting->inactive_exempted['users'] = $inactive_exempt_users;
			if ( $ppm->options->_ppm_setting_save( (array) $ppm->options->ppm_setting ) ) {
				$added = true;
			}
		}
		return $added;
	}

	/**
	 * Get dormancy perior  for a specific role.
	 *
	 * @param  int $user_id - User ID.
	 * @return string Time.
	 */
	public static function get_role_specific_dormancy_period( $user_id ) {
		$user_data = get_userdata( $user_id );
		$roles     = self::prioritise_roles( $user_data->roles );
		foreach ( $roles as $user_role ) {
			$role_options = self::get_role_options( $user_role );

			if ( ! isset( $role_options->inactive_users_expiry['value'] ) || ! isset( $role_options->inactive_users_expiry['unit'] ) ) {
				continue;
			}
			$inactive_expiry_time = $role_options->inactive_users_expiry['value'] . ' ' . $role_options->inactive_users_expiry['unit'];
			// break from loop early if we have an expiry from one of the roles.
			if ( $inactive_expiry_time ) {
				break;
			}
		}

		if ( ! isset( $inactive_expiry_time ) ) {
			$options              = get_site_option( PPMWP_PREFIX . '_options' );
			$inactive_expiry_time = $options['inactive_users_expiry']['value'] . ' ' . $options['inactive_users_expiry']['unit'];
		}

		$inactive_expiry_time = strtotime( $inactive_expiry_time, 0 );
		return $inactive_expiry_time;
	}

	/**
	 * Converts a string to a bool.
	 *
	 * @since 4.1.3
	 * @param bool $string String to convert.
	 * @return string Result.
	 */
	public static function string_to_bool( $string ) {
		return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string || 'on' === $string || 'enable' === $string );
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @since 4.1.3
	 * @param bool $bool String to convert.
	 * @return string
	 */
	public static function bool_to_string( $bool ) {
		if ( ! is_bool( $bool ) ) {
			$bool = self::string_to_bool( $bool );
		}
		return true === $bool ? 'yes' : 'no';
	}

	/**
	 * Takes the array of roles a user has and sorts them into our own priority.
	 *
	 * @param array $roles - Rule array.
	 * @return array - Sorted array.
	 */
	public static function prioritise_roles( $roles = array() ) {
		$ppm = ppm_wp();

		if ( ! isset( $ppm->options->ppm_setting->multiple_role_order ) ) {
			return $roles;
		}

		$preferred_roles = $ppm->options->ppm_setting->multiple_role_order;

		if ( empty( $preferred_roles ) ) {
			return $roles;
		}

		$preferred_roles = array_map(
			function ( $role ) {
				return str_replace( ' ', '_', strtolower( $role ) );
			},
			$preferred_roles
		);

		$processing_needed = self::string_to_bool( $ppm->options->ppm_setting->users_have_multiple_roles );
		// Only do this if we want to.
		if ( $processing_needed && count( $roles ) > 1 ) {
			// Sort roles given into the order we want, then trim the unwanted roles leftover.
			$roles = array_intersect( array_replace( $roles, $preferred_roles ), $roles );
		}

		return $roles;
	}

	/**
	 * Sort roles and return options for prefered role.
	 *
	 * @param array $roles - Roles array.
	 * @return array - Options for role.
	 */
	public static function get_preferred_role_options( $roles ) {
		$roles     = self::prioritise_roles( $roles );
		$user_role = reset( $roles );

		return self::get_role_options( $user_role );
	}

	/**
	 * SReturn filterable redirect URL.
	 *
	 * @return string - Reset page.
	 */
	public static function get_password_reset_page() {
		$standard_page = 'wp-login.php';
		return apply_filters( 'ppmwp_reset_reset_pw_login_page', $standard_page );
	}


	/**
	 * Recursive argument parsing
	 *
	 * This acts like a multi-dimensional version of wp_parse_args() (minus
	 * the querystring parsing - you must pass arrays).
	 *
	 * Values from $a override those from $b; keys in $b that don't exist
	 * in $a are passed through.
	 *
	 * This is different from array_merge_recursive(), both because of the
	 * order of preference ($a overrides $b) and because of the fact that
	 * array_merge_recursive() combines arrays deep in the tree, rather
	 * than overwriting the b array with the a array.
	 *
	 * The implementation of this function is specific to the needs of
	 * BP_Group_Extension, where we know that arrays will always be
	 * associative, and that an argument under a given key in one array
	 * will be matched by a value of identical depth in the other one. The
	 * function is NOT designed for general use, and will probably result
	 * in unexpected results when used with data in the wild. See, eg,
	 * http://core.trac.wordpress.org/ticket/19888
	 *
	 * @param array $a - Array 1.
	 * @param array $b - Array 2.
	 * @param array $remove_orphans - remove empties..
	 * @return array
	 */
	public static function recursive_parse_args( &$a, $b, $remove_orphans = false ) {
		$a          = (array) $a;
		$b          = (array) $b;
		$r          = $b;
		$do_removal = false;

		if ( $remove_orphans ) {
			// Items which used to exist in $b but dont in the new settings.
			$orphaned_keys = array_diff_key( $b, $a );
			if ( ! empty( $orphaned_keys ) ) {
				foreach ( $orphaned_keys as $key => $val ) {
					unset( $r[ $key ] );
				}
			}
		}

		foreach ( $a as $k => &$v ) {
			if ( 'users' === $k ) {
				$do_removal = true;
			}

			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = self::recursive_parse_args( $v, $r[ $k ], $do_removal );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}
}
