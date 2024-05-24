<?php
/**
 * WPassword Expire Class.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP;

if ( ! class_exists( '\PPMWP\PPM_WP_Expire' ) ) {

	/**
	 * Declare PPM_WP_Expire class.
	 */
	class PPM_WP_Expire {
		/**
		 * WPassword Options.
		 *
		 * @var $options Option.
		 */
		private $options;

		/**
		 * Desried priority.
		 *
		 * @var integer
		 */
		private $filter_priority = 0;

		/**
		 * Init hooks.
		 */
		public function hook() {
			$ppm = ppm_wp();

			$this->options = $ppm->options;
			// Admin init.
			add_action( 'admin_init', array( $this, 'check_on_load' ) );
			add_action( 'wp_loaded', array( $this, 'check_on_load_front_end' ) );
			// Session expired AJAX.
			add_action( 'wp_ajax_ppm_ajax_session_expired', array( $this, 'ppm_ajax_session_expired' ) );

			$override_needed       = apply_filters( 'mls_override_has_expired_priority', false );
			$this->filter_priority = ( $override_needed && is_int( $override_needed ) ) ? $override_needed : $this->filter_priority;
		}

		/**
		 * Check wp authenticate user
		 */
		public function ppm_authenticate_user() {
			add_filter( 'wp_authenticate_user', array( $this, 'has_expired' ), $this->filter_priority, 2 );
		}

		/**
		 * Session expired dialog box ajax.
		 */
		public function ppm_ajax_session_expired() {
			$user_id = get_current_user_id();
			$this->expire( $user_id );
			exit;
		}

		/**
		 * Check user password expire OR not.
		 *
		 * @return type
		 */
		public function check_on_load() {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return;
			}

			// Get terminate setting.
			$terminate_session_password = \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->ppm_setting->terminate_session_password );

			// Check force terminate setting is enabled.
			if ( ! $terminate_session_password ) {
				// Check user's password expire or not.
				if ( $this->should_password_expire( $user_id ) ) {
					$this->expire( $user_id );
				}
			}
		}

		/**
		 * Check user password expire OR not.
		 *
		 * @return type
		 */
		public function check_on_load_front_end() {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return;
			}

			// Check user's password expire or not.
			if ( $this->should_password_expire( $user_id ) ) {
				$this->expire( $user_id );
			}
		}


		/**
		 * Check user password expired on wp_authenticate_user hook.
		 *
		 * @param bject  $user User Object.
		 * @param string $password Enter password.
		 * @return \WP_Error
		 */
		public function has_expired( $user, $password ) {
			// get the saved history by user.
			$user_password = array();

			if ( is_a( $user, '\WP_User' ) ) {
				// This user is exempt, so lets stop here.
				if ( ppm_is_user_exempted( $user->ID ) ) {
					return $user;
				}
				$password_history = get_user_meta( $user->ID, PPM_WP_META_KEY, true );
			} else {
				$password_history = false;
			}

			// Ensure we dont check a change as its happening within UM.
			if ( isset( $_POST['um_account_nonce_password'] ) ) { // phpcs:ignore 
				return $user;
			}

			// If check user password history exists OR not.
			if ( $password_history ) {
				// Reset by user.
				foreach ( $password_history as $history ) {
					if ( in_array( 'user', $history, true ) ) {
						$user_password[] = $history;
					}
				}
				// Reset by admin.
				if ( empty( $user_password ) ) {
					foreach ( $password_history as $history ) {
						if ( in_array( 'admin', $history, true ) ) {
							$user_password[] = $history;
						}
					}
				}
			}

			// Get user last password.
			$user_password = end( $user_password );
			if ( empty( $user_password ) && is_a( $user, '\WP_User' ) ) {
				$user_password             = array();
				$user_password['password'] = $user->data->user_pass;
			}

			// the password is not okay.
			if ( $password && is_a( $user, '\WP_User' ) && ! wp_check_password( $password, $user_password['password'], $user->ID ) ) {
				return new \WP_Error(
					'incorrect_password',
					sprintf(
						/* translators: %s: user name */
						__( '<strong>ERROR</strong>: The password you entered for the username %s is incorrect.', 'ppm-wp' ),
						'<strong>' . $user->user_login . '</strong>'
					) .
					' <a href="' . wp_lostpassword_url() . '">' .
					__( 'Lost your password?', 'ppm-wp' ) .
					'</a>'
				);
			}


			/* @freetart */
			if ( is_a( $user, '\WP_User' ) ) {
				// check if it password expired flag is existing.
				if ( get_user_meta( $user->ID, PPM_WP_META_PASSWORD_EXPIRED, true ) ) {
					return new \WP_Error(
						'password-expired',
						sprintf(
							/* translators: %s: user name */
							__( '<strong>ERROR</strong>: The password you entered for the username %s has expired.', 'ppm-wp' ),
							'<strong>' . $user->user_login . '</strong>'
						) .
						' <a href="' . wp_lostpassword_url() . '">' .
						__( 'Get a new password.', 'ppm-wp' ) .
						'</a>'
					);
				}
			}
			/* @free:end */

			// Always return user object.
			return $user;
		}

		/**
		 * Resets particular user password & sets password expire flag, which forces user to reset password.
		 *
		 * @param int $user_id User ID.
		 */
		private function expire( $user_id ) {

			if ( ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			if ( ! $this->should_password_expire( $user_id ) ) {
				return;
			}

			$ppm = ppm_wp();

			// reset the password before wp.
			$resetter = new \PPMWP\PPM_WP_Reset();

			// this will reset the password in the system.
			// and the cpassword that the user is trying to enter becomes invalid.
			$user_data        = get_userdata( $user_id );
			$current_password = $user_data->user_pass;
			// Reset user by User ID.
			$resetter->reset( $user_id, $current_password, 'system' );
			// save the last expiry time in an easy to access meta as this is
			// used/modified by the inactive users feature.
			$last_expiry = \PPMWP\Helpers\OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $user_id ); // phpcs:ignore 

		}

		/**
		 * Should Password Expire.
		 *
		 * @param type $user_id User ID.
		 * @return boolean
		 */
		public static function should_password_expire( $user_id ) {
			$ppm = ppm_wp();

			$expiry = $ppm->options->password_expiry;

			// no need to expire if expiry is set to 0 (by default, or by choice).
			if ( $expiry['value'] < 1 ) {
				return false;
			}

			// get the password history.
			$password_history = get_user_meta( $user_id, PPM_WP_META_KEY, true );
			// no password history means that the password was never reset by the system or admin or user.
			if ( empty( $password_history ) ) {
				$last_reset = (int) get_site_option( PPMWP_PREFIX . '_activation' );
			} else {
				// check the last entry.
				$last_password_event = end( $password_history );
				$last_reset          = (int) $last_password_event['timestamp'];
			}

			// get the expiry into a string.
			$expiry_string = implode( ' ', $expiry );

			// if the password hasn't expired.
			if ( current_time( 'timestamp' ) < strtotime( $expiry_string, $last_reset ) ) { // phpcs:ignore 
				return false;
			}

			return true;
		}

	}

}
