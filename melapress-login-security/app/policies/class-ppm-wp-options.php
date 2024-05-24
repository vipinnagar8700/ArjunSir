<?php
/**
 * The plugins main options class.
 *
 * @package WPassword
 */

namespace PPMWP;

use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( '\PPMWP\PPM_WP_Options' ) ) {

	/**
	 * Provides options used at run time
	 */
	class PPM_WP_Options {

		/**
		 * The plugins main options.
		 *
		 * @var array plugin options
		 */
		private $options = array();

		/**
		 * Inherit Password Policies
		 *
		 * NOTE: this holds the master policy.
		 *
		 * @var $inherit array
		 */
		public $inherit = array();

		/**
		 * Setting options
		 *
		 * @var array plugin setting
		 */
		public $setting_options = array();

		/**
		 * Get option by role
		 *
		 * @var $users_options array
		 */
		public $users_options = array();

		/**
		 * PPM settings.
		 *
		 * @var $ppm_setting array
		 */
		public $ppm_setting = array();

		/**
		 * Stores an object with the individual roles specific options.
		 *
		 * NOTE: not filled by default, use $this->get_role_options() to access
		 * and fill. Returns an object with the options.
		 *
		 * @var array
		 */
		public $role_options = array();

		/**
		 * The default options used for the policies.
		 *
		 * @var array Default plugin options
		 *
		 * NOTE: The regex tester for passwords uses the 'rules' key from here
		 *       but setting page and save method looks at the `ui_rules` key.
		 *       The 'refactor' method clones the `ui_rules` into `rules` for
		 *       use with any password checks.
		 */
		public $default_options = array(
			'master_switch'                          => 'no',
			'enforce_password'                       => 'no',
			'min_length'                             => 8,
			'password_history'                       => 1,
			'inherit_policies'                       => 'yes',
			'password_expiry'                        => array(
				'value' => 0,
				'unit'  => 'months',
			),
			'ui_rules'                               => array(
				'history'               => 'yes',
				'username'              => 'yes',
				'length'                => 'yes',
				'numeric'               => 'yes',
				'mix_case'              => 'yes',
				'special_chars'         => 'yes',
				'exclude_special_chars' => 'no',
			),
			'rules'                                  => array(
				'length'                => 'yes',
				'numeric'               => 'yes',
				'upper_case'            => 'yes',
				'lower_case'            => 'yes',
				'special_chars'         => 'yes',
				'exclude_special_chars' => 'no',
			),
			'change_initial_password'                => 'no',
			'timed_logins'                           => 'no',
			'timed_logins_schedule'                  => array(
				'monday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'tuesday'   => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'wednesday' => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'thursday'  => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'friday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'saturday'  => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'sunday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
			),
			'inactive_users_enabled'                 => 'no',
			'inactive_users_expiry'                  => array(
				'value' => 30,
				'unit'  => 'days',
			),
			'inactive_users_reset_on_unlock'         => 'yes',
			'failed_login_policies_enabled'          => 'no',
			'failed_login_attempts'                  => 5,
			'failed_login_reset_attempts'            => 1440,
			'failed_login_unlock_setting'            => 'unlock-by-admin',
			'failed_login_reset_hours'               => 60, // Mins @since 3.0.0.
			'failed_login_reset_on_unblock'          => 'yes',
			'disable_self_reset'                     => 'no',
			'disable_self_reset_message'             => '',
			'deactivated_account_message'            => '',
			'timed_login_message'                    => '',
			'locked_user_disable_self_reset'         => 'no',
			'locked_user_disable_self_reset_message' => '',
			'restrict_login_ip'                      => 'no',
			'restrict_login_ip_count'                => 3,
			'restrict_login_message'                 => '',
		);

		public static function get_default_account_deactivated_message() {
			$admin_email = get_site_option( 'admin_email' );
			$email_link  = '<a href="mailto:' . sanitize_email( $admin_email ) . '">' . __( 'website administrator', 'ppm-wp' ) . '</a>';
			return sprintf( __( 'Your WordPress user has been deactivated. Please contact the %1s to activate back your user.', 'ppm-wp' ), $email_link );
		}

		/**
		 * Validator rules for default options
		 *
		 * @var array
		 */
		public static $default_options_validation_rules = array(
			'min_length'               => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
			'password_expiry'          => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'months',
						'days',
						'hours',
						'seconds',
					),
				),
			),
			'password_history'         => array(
				'typeRule' => 'number',
				'min'      => '0',
				'max'      => '100',
			),
			'inactive_users_expiry'    => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'months',
						'days',
						'hours',
						'seconds',
					),
				),
			),
			'failed_login_attempts'    => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
			'failed_login_reset_hours' => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
		);

		/**
		 * Set plugin setting options.
		 *
		 * @var array
		 */
		public $default_setting = array(
			'send_summary_email'         => 'yes',
			'exempted'                   => array(
				'users' => array(),
			),
			'from_email'                 => '',
			'terminate_session_password' => 'no',
			'users_have_multiple_roles'  => 'no',
			'multiple_role_order'        => '',
			'clear_history'              => 'no',
			'excluded_special_chars'     => '',
			'password_reset_key_expiry'  => array(
				'value' => 24,
				'unit'  => 'hours',
			),
			'enable_wp_reset_form'       => 'yes',
			'enable_wp_profile_form'     => 'yes',
			'enable_wc_pw_reset'         => 'no',
			'enable_wc_checkout_reg'     => 'no',
			'enable_bp_register'         => 'no',
			'enable_bp_pw_update'        => 'no',
			'enable_ld_register'         => 'no',
			'enable_um_register'         => 'no',
			'enable_um_pw_update'        => 'no',
			'enable_bbpress_pw_update'   => 'no',
			'enable_mepr_register'       => 'no',
			'enable_mepr_pw_update'      => 'no',
			'custom_login_url'           => '',
			'custom_login_redirect'      => '',
			'send_user_unlocked_email'   => 'yes',
			'send_user_unblocked_email'  => 'yes',
			'send_user_pw_reset_email'   => 'yes',
			'send_user_pw_expired_email' => 'yes',
			'login_geo_method'           => 'default',
			'login_geo_action'           => 'deny_to_url',
			'login_geo_countries'        => '',
			'login_geo_redirect_url'     => '',
			'login_geo_blocked_message'  => '',
			'iplocate_api_key'           => '',
		);

		/**
		 * Validator rules for settings options
		 *
		 * @var array
		 */
		public static $settings_options_validation_rules = array(
			'password_reset_key_expiry' => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'days',
						'hours',
					),
				),
			),
		);

		/**
		 * PPM main class object
		 *
		 * @var Object
		 */
		private $ppm;

		/**
		 * Get options from database and merge with default
		 */
		public function init() {

			// Store ppm class object.
			$this->ppm = ppm_wp();
			// Default policy.
			$this->inherit                     = get_site_option( PPMWP_PREFIX . '_options', $this->default_options );
			$this->inherit['inherit_policies'] = 'yes';
			$this->inherit                     = wp_parse_args( $this->inherit, $this->default_options );

			// PPM setting option.
			$this->ppm_setting = get_site_option( PPMWP_PREFIX . '_setting', $this->default_setting );
			if ( $this->ppm_setting ) {
				$this->ppm_setting = (object) wp_parse_args( $this->ppm_setting, $this->default_setting );
			}

			/*
			 * Setting options.
			 */
			$tab_role = ! empty( $_GET['role'] ) ? '_' . sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

			$ppm_default_policy = $this->inherit;

			$settings_tab          = get_site_option( PPMWP_PREFIX . $tab_role . '_options', $ppm_default_policy );
			$this->setting_options = (object) wp_parse_args( $settings_tab, $ppm_default_policy );

			/**
			 * Get user ID Default 0.
			 */
			$user_id = 0;
			// If check user resetpassword key exists OR not.
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {
				// Get user reset password cookie.
				$username = strstr( sanitize_text_field( wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ), ':', true );
				// Get user by user_login.
				$user_by_login = get_user_by( 'login', $username );
				if ( $user_by_login ) {
					$user_id = $user_by_login->ID;
				}
			} else {
				$user_id = get_current_user_id();
			}

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				// Get user by ID.
				$blog_id    = $this->ppm->ppm_mu_get_blog_by_user_id( $user_id );
				$user_by_id = $this->ppm->ppm_mu_user_by_blog_id(
					$blog_id,
					array(
						'include' => $user_id,
					)
				);
				// Get included user.
				$included_user = reset( $user_by_id );
				// Get user role.
				$roles     = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $included_user->roles );
				$user_role = reset( $roles );
			} else {
				// Get userdata by user id.
				$userdata = get_userdata( $user_id );

				$user_role = '';
				if ( isset( $userdata->roles ) ) {
					// Get user role.
					$roles     = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $userdata->roles );
					$user_role = reset( $roles );
				}
			}

			// Get current role in user edit page.
			$current_role = ! empty( $user_role ) ? '_' . $user_role : '';

			$settings = get_site_option( PPMWP_PREFIX . $current_role . '_options', $this->inherit );

			// Get current user setting.
			$this->options = wp_parse_args( $settings, $this->inherit );

			// Init user role settings.
			$this->user_role_policy();
		}

		/**
		 * Get options for a specific user role.
		 *
		 * @method get_role_options
		 * @since  2.1.0
		 * @param  string $role A role to get options for.
		 * @return object
		 */
		public function get_role_options( $role = '' ) {
			if ( empty( $this->role_options[ $role ] ) ) {
				$inherit = $this->inherit;
				$options = get_site_option( PPMWP_PREFIX . '_' . $role . '_options', $inherit );
				// Ensure we have something passed.
				$options = ( ! $options || empty( $options ) ) ? get_site_option( PPMWP_PREFIX . '_options', $inherit ) : $options;
				// ensure that we have an object and not an array.
				$options = (object) wp_parse_args( $options, $this->default_options );
				// store the fetched values in property so we don't need to
				// fetch again.
				$this->role_options[ $role ] = $options;
			}
			return $this->role_options[ $role ];
		}

		/**
		 * Current user role policy.
		 *
		 * @return     object|array
		 */
		public function user_role_policy() {

			global $pagenow;

			/**
			 * When generate password button is clicked (JS Ajax) @see user-profile.js line 261
			 * WP does not have user set in the globals
			 * But the form for resseting the password holds the hidden field with login name
			 * We pass that and check it against DB in order to extract proper user_id
			 *
			 * @todo That entire method depends on the user id in order to extract the proper rules
			 * but it does not accepts any parameters and relies on the globals - refactoring everything
			 * into controller and separate everything would be better approach
			 */
			if ( isset( $_POST['ppm_usr'] ) ) {

				$user = get_user_by( 'login', sanitize_user( wp_unslash( $_POST['ppm_usr'] ) ) );
				if ( false !== $user ) {
					$_REQUEST['user_id'] = $user->ID;
				}
			}

			/**
			 * Tries to exctract the proper user id - that is called when forms are submitted
			 */
			$get_user_id = isset( $_REQUEST['user_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) : get_current_user_id();

			/**
			 * Get user ID Default 0.
			 */
			$user_id = 0;
			/**
			 * The following logic happens when user is using password reset link, WP extracts parameter from
			 * the link and then stores login username in temporarily cookie.
			 * This tries to extract it (user login and ID) from what is stored there
			 *
			 * If there is no such cookie present, falls back to value stored in $get_user_id and continues
			 */
			// If check user resetpassword key exists OR not.
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {

				$username = strstr( sanitize_text_field( wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ), ':', true );

				// Get user data by login.
				$user_obj = get_user_by( 'login', $username );
				if ( $user_obj ) {
					$user_id = $user_obj->ID;
				}
			} else {

				$user_id = (int) $get_user_id;

			}

			// If check user ID.
			if ( ! $user_id ) {
				// If we have no ID, grab the default settings.
				$this->users_options = (object) get_site_option( PPMWP_PREFIX . '_options', $this->default_options );
				return $this->users_options;
			}

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				// Get user by ID.
				$blog_id    = $this->ppm->ppm_mu_get_blog_by_user_id( $user_id );
				$user_by_id = $this->ppm->ppm_mu_user_by_blog_id(
					$blog_id,
					array(
						'include' => $user_id,
					)
				);
				// Get included user.
				$included_user = reset( $user_by_id );
				// Get user role.
				$roles     = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $included_user->roles );
				$user_role = reset( $roles );
			} else {
				// Get userdata by user id.
				$userdata = get_userdata( $user_id );
				// Get user role.
				$roles     = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $userdata->roles );
				$user_role = ( is_array( $roles ) ) ? reset( $roles ) : array();
			}

			// Get current role in user edit page.
			$current_role = ! empty( $user_role ) ? '_' . $user_role : '';

			// Override current role if this is being called via the user-new.php admin screen
			// This means we can then apply the policy for the role submitted, rather than current_user.
			if ( isset( $_POST['action'] ) && 'createuser' === $_POST['action'] && isset( $_POST['role'] ) ) {
				$post_array   = filter_input_array( INPUT_POST );
				$current_role = ! empty( $post_array['role'] ) ? '_' . $post_array['role'] : '';
			}

			$settings = get_site_option( PPMWP_PREFIX . $current_role . '_options' );
			if ( ! empty( $settings ) && 0 == \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings['master_switch'] ) || 'user-new.php' === $pagenow ) {

				// Get current user setting.
				$this->users_options = (object) wp_parse_args( $settings, $this->inherit );

			} else {

				$settings = get_site_option( PPMWP_PREFIX . '_options' );

				if ( ! empty( $settings ) ) {
					if ( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings['master_switch'] ) ) {
						// Get current user setting.
						$this->users_options = (object) wp_parse_args( $settings, $this->inherit );
					} else {
						$this->users_options = (object) wp_parse_args( $settings, $this->inherit );

						$this->users_options->enforce_password = 1;
					}
				} else {
					$this->users_options = (object) wp_parse_args( $settings, $this->inherit );
				}
			}

			return $this->users_options;
		}

		/**
		 * Save plugin options in the db and the options object
		 *
		 * @param array $options The options array to save.
		 */
		public function _ppm_setting_save( $options ) {

			if ( isset( $options['from_email'] ) && $options['from_email'] ) {
				$options['from_email'] = sanitize_email( $options['from_email'] );
			}

			if ( isset( $options['custom_login_url'] ) && $options['custom_login_url'] ) {
				$options['custom_login_url'] = esc_attr( rtrim( $options['custom_login_url'], '/' ) );
			}
			if ( isset( $options['custom_login_redirect'] ) && $options['custom_login_redirect'] ) {
				$options['custom_login_redirect'] = esc_attr( rtrim( $options['custom_login_redirect'], '/' ) );
			}

			$ppm_setting = wp_parse_args( $options, $this->ppm_setting );

			$role_order = ( empty( $ppm_setting['multiple_role_order'] ) ) ? array() : $ppm_setting['multiple_role_order'];

			// Correct bool values.
			$ppm_setting['terminate_session_password'] = \PPMWP\Helpers\OptionsHelper::bool_to_string( $ppm_setting['terminate_session_password'] );
			$ppm_setting['send_summary_email']         = \PPMWP\Helpers\OptionsHelper::bool_to_string( $ppm_setting['send_summary_email'] );
			$ppm_setting['users_have_multiple_roles']  = \PPMWP\Helpers\OptionsHelper::bool_to_string( $ppm_setting['users_have_multiple_roles'] );
			$ppm_setting['multiple_role_order']        = array_map( 'esc_attr', $role_order );
			$ppm_setting['clear_history']              = \PPMWP\Helpers\OptionsHelper::bool_to_string( $ppm_setting['clear_history'] );

			$this->ppm_setting = (object) $ppm_setting;

			return update_site_option( PPMWP_PREFIX . '_setting', $ppm_setting );
		}

		/**
		 * Save plugin options in the db and the options object
		 *
		 * @param array $options The options array to save.
		 */
		public function _save( $options ) {

			$options = $this->refactor( $options );
			// We need options, not default options here in wp_parse_args.
			$this->options = wp_parse_args( $options, $this->options );
			// Get current tab role.
			$tab_role = ! empty( $this->options['ppm-user-role'] ) ? '_' . $this->options['ppm-user-role'] : '';

			$this->setting_options = $this->options;

			return update_site_option( PPMWP_PREFIX . $tab_role . '_options', $this->options );
		}

		/**
		 * Refactor options submitted through settings form
		 *
		 * @param array $options The options.
		 * @return array
		 */
		private function refactor( $options ) {

			if ( isset( $options['ui_rules'] ) ) {
				$options['rules']['upper_case'] = $options['ui_rules']['mix_case'];
				$options['rules']['lower_case'] = $options['ui_rules']['mix_case'];
				if ( isset( $options['min_length'] ) && $options['min_length'] > 0 ) {
					$options['rules']['length']    = true;
					$options['ui_rules']['length'] = true;
				}
				$options['rules']['numeric']               = $options['ui_rules']['numeric'];
				$options['rules']['special_chars']         = $options['ui_rules']['special_chars'];
				$options['rules']['exclude_special_chars'] = $options['ui_rules']['exclude_special_chars'];
			}
			if ( isset( $options['excluded_special_chars'] ) && ! empty( $options['excluded_special_chars'] ) ) {
				$options['excluded_special_chars'] = htmlentities( $options['excluded_special_chars'], 0, 'UTF-8' );
			}

			return $options;
		}

		/**
		 * Magic getter for option keys
		 *
		 * @param string $name The name of the option, same as the key in $options.
		 * @return boolean| mixed False, if can't find or the value of the key
		 */
		public function __get( $name ) {

			if ( array_key_exists( $name, $this->options ) ) {
				return $this->options[ $name ];
			}

			return false;
		}
	}

}
