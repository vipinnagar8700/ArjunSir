<?php
/**
 * Handle PW history.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP;

if ( ! class_exists( '\PPMWP\PPM_WP_History' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class PPM_WP_History {

		const LAST_EXPIRY_TIME_KEY = 'last_expiry_time';

		/**
		 * Hook into WP
		 */
		public function hook() {
			add_action( 'user_register', array( $this, 'user_register' ) );
			add_action( 'invite_user', array( $this, 'ppm_invite_user' ), 10, 3 );
			add_action( 'profile_update', array( $this, 'user_register' ) );

			// Custom additional hook for users with bespoke processes for creating users.
			add_action( 'ppmwp_apply_forced_reset_usermeta', array( $this, 'ppm_apply_forced_reset_usermeta' ) );
		}

		/**
		 * After password reset hook
		 */
		public function ppm_after_password_reset() {
			// update password history when user resets password manually.
			add_action( 'password_reset', array( $this, 'reset_by_user' ), 10, 2 );
			add_action( 'after_password_reset', array( $this, 'reset_by_user' ), 10, 2 );
		}

		/**
		 * Push new password to user's password history
		 *
		 * @param  integer $user_id User ID.
		 * @param  array   $password_event An array of the password and it's timestamp.
		 * @return boolean True on success, false if failed
		 */
		public static function push( $user_id, $password_event ) {

			$ppm = ppm_wp();

			// get the saved history.
			$password_history = get_user_meta( $user_id, PPM_WP_META_KEY, true );

			if ( empty( $password_history ) ) {
				$password_history = array();
			}

			// Creating dummy event array to find if the password was already set.
			$dummy_event = array( $password_event );
			if ( count( array_uintersect( $dummy_event, $password_history, '\PPMWP\PPM_WP_History::compare_passwords' ) ) ) {
				// So the password already exists in history, no need to add it again.
				return true;
			}

			// Ensure we dont store repeat requests.
			foreach ( $password_history as $event ) {
				$diff = abs( $password_event['timestamp'] - $event['timestamp'] );
				if ( $diff < 10 ) {
					return true;
				}
			}

			// push new event to the end of it.
			array_push( $password_history, $password_event );

			// trim to the right size by.
			// we're technically saving the latest password + the required history.
			$length               = $ppm->options->password_history + 1;
			$new_password_history = array_slice( $password_history, -$ppm->options->password_history, $length );

			// save it.
			return update_user_meta( $user_id, PPM_WP_META_KEY, $new_password_history );
		}


		/**
		 * Pushes password reset by user into history
		 *
		 * Reset password by user also happens after password expiration. User clicks "get new password" link.
		 * So the same action happens as he reset password after he forgot his password.
		 *
		 * @param integer $user The user object.
		 * @param string  $new_pass The new password in plain text.
		 */
		public function reset_by_user( $user, $new_pass ) {

			$ppm = ppm_wp();

			// create a password event.
			$password_event = array(
				'password'  => wp_hash_password( $new_pass ),
				'timestamp' => current_time( 'timestamp' ),
				'by'        => 'user',
				'test'      => 'user',
			);

			// push current password to password history of the user.
			self::push( $user->ID, $password_event );

			// Remove password expired flag.
			delete_user_meta( $user->ID, PPM_WP_META_PASSWORD_EXPIRED, '1' );
			delete_user_meta( $user->ID, PPM_WP_META_EXPIRED_EMAIL_SENT );
			delete_user_meta( $user->ID, PPM_WP_META_DELAYED_RESET_KEY, '1' );
			delete_user_meta( $user->ID, PPM_WP_META_NEW_USER );
			delete_user_meta( $user->ID, PPM_WP_META_USER_RESET_PW_ON_LOGIN );
			// clear the key that remembers the last expiry time.
			\PPMWP\Helpers\OptionsHelper::set_user_last_expiry_time( 0, $user->ID );


			// Destroy user session.
			$ppm->ppm_user_session_destroy( $user->ID );
		}

		/**
		 * Pushes password updated on profile to history
		 *
		 * @param int $user_id - User ID.
		 */
		public function user_register( $user_id ) {

			$userdata = get_userdata( $user_id );
			$password = $userdata->user_pass;

			$push_event = ( isset( $_REQUEST['action'] ) && 'lostpassword' === $_REQUEST['action'] ) ? false : true;

			$password_event = array(
				'password'  => $password,
				'timestamp' => current_time( 'timestamp' ),
				'by'        => 'user',
				'pest'      => 'sss',
			);
			if ( $push_event ) {
				self::push( $user_id, $password_event );
			}

			// Apply last active time.
			update_user_meta( $user_id, 'ppmwp_last_activity', current_time( 'timestamp' ) );

			// If check current running action `profile_update` AND that a
			// password is set.
			if ( doing_action( 'profile_update' ) && false !== isset( $_POST['pass1'] ) ) {
				// Remove password expired flags.
				delete_user_meta( $userdata->ID, PPM_WP_META_PASSWORD_EXPIRED, '1' );
				delete_user_meta( $userdata->ID, PPM_WP_META_DELAYED_RESET_KEY, '1' );
				// delete the last expiry time as password is being updated.
				delete_user_meta( $userdata->ID, PPMWP_PREFIX . '_' . self::LAST_EXPIRY_TIME_KEY );
			}

			// If check current running action `profile_update`.
			if ( doing_action( 'user_register' ) ) {
				if ( ! isset( $_POST['send_user_notification'] ) ) {
					// Handle users directly registered using Restrict Content.
					if ( isset( $_POST['action'] ) && 'rc_process_registration_form' === $_POST['action'] ) {
						$key = get_password_reset_key( $userdata );
						if ( ! is_wp_error( $key ) ) {
							update_user_meta( $user_id, PPM_WP_META_NEW_USER, $key );
						}
					} else {
						if ( $this->ppm_get_first_login_policy( $userdata->ID ) ) {
							// Double check we are not doing profile_update to avoid
							// https://github.com/WPWhiteSecurity/password-policy-manager/issues/239.
							if ( ! doing_action( 'profile_update' ) ) {
								$this->ppm_apply_forced_reset_usermeta( $user_id );
							}
						}
					}
				} else {
					add_action( 'retrieve_password_key', array( $this, 'ppm_retrieve_password_key' ), 10, 2 );
				}
			}
		}

		/**
		 * Compare two password event arrays to check if its the same password
		 *
		 * @param array $a Password event array.
		 * @param array $b Password event array.
		 * @return integer Returns 0 if its the same password in both arrays, returns 1 if they are different.
		 */
		private static function compare_passwords( $a, $b ) {
			if ( $a['password'] == $b['password'] ) {
				return 0;
			} else {
				return 1;
			}
		}

		/**
		 * WordPress add new user generate password reset link.
		 *
		 * @param  string $user_login User login.
		 * @param  string $key        Password reset key.
		 */
		public function ppm_retrieve_password_key( $user_login, $key ) {
			$user = get_user_by( 'login', $user_login );
			if ( $this->ppm_get_first_login_policy( $user->ID ) ) {
				update_user_meta( $user->ID, PPM_WP_META_NEW_USER, $key );
			}
		}

		/**
		 * Get first login policy by user ID.
		 *
		 * @param  integer $user_id User's ID.
		 * @param  array   $roles User's role (or roles).
		 * @return bool
		 */
		public function ppm_get_first_login_policy( $user_id = 0, $roles = array() ) {
			$ppm             = ppm_wp();
			$default_options = isset( $ppm->options->inherit['master_switch'] ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->inherit['master_switch'] ) ? $ppm->options->inherit : array();
			if ( ! is_multisite() || ! doing_action( 'invite_user' ) ) {
				// Get user by ID.
				$get_userdata = get_user_by( 'ID', $user_id );
				$roles        = $get_userdata->roles;
			}

			$roles = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $roles );
			$roles = reset( $roles );

			// If we reach this point with no default options, stop here.
			if ( empty( $default_options ) ) {
				return false;
			}

			// Get option by role name.
			$options = get_site_option( PPMWP_PREFIX . '_' . $roles . '_options', $default_options );

			if ( ! empty( $options ) && ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $options['enforce_password'] ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $options['change_initial_password'] ) ) ) {
				return true;
			}
			// If we have no role options, lets use the master policy.
			if ( empty( $options ) && ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $default_options['enforce_password'] ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $default_options['change_initial_password'] ) ) ) {
				return true;
			}
			// Always return false.
			return false;
		}

		/**
		 * Multisite add user to blog.
		 *
		 * @param int    $user_id New user ID.
		 * @param string $role User role.
		 * @param string $newuser_key User key.
		 */
		public function ppm_invite_user( $user_id, $role, $newuser_key ) {
			$userdata       = get_userdata( $user_id );
			$password       = $userdata->user_pass;
			$password_event = array(
				'password'  => $password,
				'timestamp' => current_time( 'timestamp' ),
				'by'        => 'user',
				'pest'      => 'sss',
			);
			self::push( $user_id, $password_event );
			// If check current running action `profile_update`.
			if ( $this->ppm_get_first_login_policy( '', $role ) ) {
				$key = get_password_reset_key( $userdata );
				update_user_meta( $user_id, PPM_WP_META_NEW_USER, $key );
			}
		}

		/**
		 * Applies a reset key to a given users ID.
		 *
		 * @param  int $user_id user ID.
		 */
		public function ppm_apply_forced_reset_usermeta( $user_id ) {
			$userdata = get_userdata( $user_id );
			$key      = get_password_reset_key( $userdata );
			if ( isset( $key ) && ! is_wp_error( $key ) ) {
				update_user_meta( $user_id, PPM_WP_META_NEW_USER, $key );
			}
		}
	}
}
