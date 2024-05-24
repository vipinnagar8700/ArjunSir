<?php
/**
 * Handle PW resets.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;
use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( '\PPMWP\PPM_WP_Reset' ) ) {

	/**
	 * Resets passwords
	 */
	class PPM_WP_Reset {

		/**
		 * Hooks delayed password reset to login if option is checked
		 */
		public function hook() {

			$ppm = ppm_wp();
			// Hook the function only if reset delay is checked in the options.
			if ( $ppm->options->ppm_setting->terminate_session_password ) {
				add_action( 'wp_authenticate', array( $this, 'check_on_login' ), 0, 2 );
			}

			// Customize password reset key expiry time.
			add_filter( 'password_reset_expiration', array( $this, 'customize_reset_key_expiry_time' ) );

			add_filter( 'allow_password_reset', array( $this, 'ppm_is_user_allowed_to_reset' ), 10, 2 );
			add_filter( 'mepr-validate-forgot-password', array( $this, 'mepr_forgot_password' ), 10, 1 );
		}

		/**
		 * Add action link to users bulk actions.
		 *
		 * @param array $bulk_actions - Current actions.
		 * @return array $bulk_actions - modified actions.
		 */
		public static function add_bulk_action_link( $bulk_actions ) {
			$bulk_actions['mls-reset-password'] = __( 'Reset password', 'ppm-wp' );
			return $bulk_actions;
		}

		/**
		 * Handke action.
		 *
		 * @param string $redirect_url - Current URL.
		 * @param string $action - Currnet action.
		 * @param array  $user_ids - IDs to check.
		 * @return string - Resulting URL.
		 */
		public static function handle_bulk_action_link( $redirect_url, $action, $user_ids ) {
			if ( 'mls-reset-password' === $action ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $redirect_url;
				}
				$reset = new \PPMWP\PPM_WP_Reset();
				foreach ( $user_ids as $user_id ) {
					$reset->reset_user( $user_id, false, true );
				}
				$redirect_url = add_query_arg( 'mls-reset-password', count( $user_ids ), $redirect_url );
			}
			return $redirect_url;
		}

		/**
		 * Show notice on bulk actions.
		 *
		 * @return void
		 */
		public static function bulk_action_admin_notice() {
			if ( isset( $_REQUEST['mls-reset-password'] ) && ! empty( $_REQUEST['mls-reset-password'] ) ) {
				$num_changed = (int) $_REQUEST['mls-reset-password'];
				/* translators: %s: User count. */
				printf( '<div id="message" class="updated notice is-dismissable"><p>' . esc_html__( 'Reset %d users.', 'ppm-wp' ) . '</p></div>', esc_attr( $num_changed ) );
			}
		}

		/**
		 * Process global resets.
		 *
		 * @return void
		 */
		public static function process_global_password_reset() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mls_mass_reset' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'ppm-wp' ) );
			}

			$current_user    = wp_get_current_user();
			$current_user_id = $current_user->ID;

			$reset_type    = isset( $_POST['reset_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reset_type'] ) ) : false;
			$role          = isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : false;
			$file_text     = isset( $_POST['file_text'] ) ? wp_unslash( $_POST['file_text'] ) : false;
			$users         = isset( $_POST['users'] ) ? wp_unslash( $_POST['users'] ) : false;
			$include_self  = isset( $_POST['include_self'] ) && 'true'  == sanitize_text_field( wp_unslash( $_POST['include_self'] ) ) ? true : false;
			$send_reset    = isset( $_POST['send_reset'] ) && 'true'    == sanitize_text_field( wp_unslash( $_POST['send_reset'] ) ) ? true : false;
			$kill_sessions = isset( $_POST['kill_sessions'] ) && 'true' == sanitize_text_field( wp_unslash( $_POST['kill_sessions'] ) ) ? true : false;
			$reset         = new \PPMWP\PPM_WP_Reset();

			if ( 'reset-all' === $reset_type ) {
				if ( ! $include_self ) {
					$reset->reset_all( false, $kill_sessions, $send_reset, true );
				} else {
					$reset->reset_all( true, $kill_sessions, $send_reset, true );
				}
			} elseif ( 'reset-role' === $reset_type ) {
				$exempted_users = array();

				if ( ! $include_self ) { // phpcs:ignore
					array_push( $exempted_users, get_current_user_id() );
				}

				// exclude exempted roles and users.
				$user_args = array(
					'role'    => $role,
					'exclude' => $exempted_users,
					'fields'  => array( 'ID' ),
				);
				$users     = get_users( $user_args );

				foreach ( $users as $user ) {
					$reset->reset_user( $user->ID, $kill_sessions, $send_reset );
				}
			} elseif ( 'reset-users' === $reset_type ) {
				foreach ( $users as $user_id ) {
					$reset->reset_user( $user_id, $kill_sessions, $send_reset );
				}
				
			} elseif ( 'reset-csv' === $reset_type ) {
				$users = explode( ',', $file_text );
				foreach ( $users as $user_id ) {
					$user = get_userdata( $user_id );
					if ( false !== $user ) {
						$reset->reset_user( $user_id, $kill_sessions, $send_reset );
					}					
				}
			} else {
				wp_send_json_error( esc_html__( 'No valid reset type given', 'ppm-wp' ) );
			}

			wp_send_json_success( esc_html__( 'Reset complete.', 'ppm-wp' ) );
		}

		/**
		 * Handling reset of individual user.
		 *
		 * @param int     $user_id - ID to reset.
		 * @param boolean $kill_sessions - Destroy session on reset.
		 * @param boolean $send_reset - Send reset email.
		 * @return void
		 */
		public static function reset_user( $user_id, $kill_sessions = false, $send_reset = false ) {
			$reset = new \PPMWP\PPM_WP_Reset();
			$user  = get_user_by( 'id', $user_id );
			$reset->reset( $user->ID, $user->data->user_pass, 'admin', true, $kill_sessions, $send_reset, true );
		}

		/**
		 * Monitor for memberpress password reset requests.
		 *
		 * @param  array $post - Posted data.
		 * @return array $post - Posted data.
		 *
		 * @since 1.1.0
		 */
		public function mepr_forgot_password( $post ) {
			if ( isset( $_POST['mepr_process_forgot_password_form'] ) && isset( $_POST['mepr_user_or_email'] ) ) { // phpcs:ignore
				if ( filter_var( $_POST['mepr_user_or_email'], FILTER_VALIDATE_EMAIL ) ) { // phpcs:ignore
					$user = get_user_by( 'email', $_POST['mepr_user_or_email'] ); // phpcs:ignore
				} else {
					$user = get_user_by( 'login', $_POST['mepr_user_or_email'] ); // phpcs:ignore
				}
			}

			if ( ! isset( $user->ID ) ) {
				return $post;
			}

			$allow = $this->ppm_is_user_allowed_to_reset( true, $user->ID );

			if ( class_exists( '\MeprUtils' ) ) {
				if ( is_wp_error( $allow ) ) {
					if ( ! isset( $mepr_options ) ) {
						$mepr_options = \MeprOptions::fetch();
					}
					$login_url           = \MeprUtils::get_permalink( $mepr_options->login_page_id );
					$login_delim         = \MeprAppCtrl::get_param_delimiter_char( $login_url );
					$forgot_password_url = "{$login_url}{$login_delim}action=forgot_password&error=failed";

					// Handle password reset form.
					if ( isset( $_REQUEST['action'] ) && 'forgot_password' == $_REQUEST['action'] ) {
						$ppm             = ppm_wp();
						$default_options = \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->inherit['master_switch'] ) ? $ppm->options->inherit : array();

						// Get user by ID.
						$get_userdata = get_user_by( 'ID', $user->ID );
						$roles        = $get_userdata->roles;

						$roles = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $roles );
						$roles = reset( $roles );

						$options = get_site_option( PPMWP_PREFIX . '_' . $roles . '_options', $default_options );
						if ( isset( $options['disable_self_reset'] ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $options['disable_self_reset'] ) ) {
							$post['mepr_user_or_email'] = esc_attr( $options['disable_self_reset_message'] );
						}
					} else {
						\MeprUtils::wp_redirect( $forgot_password_url );
					}
				}
			}

			return $post;
		}

		/**
		 * Resets a user's password.
		 *
		 * @global object  $wpdb
		 * @param  integer $user_id The user ID.
		 * @param  string  $current_password - Current password.
		 * @param  string  $by Reset by system, admin or user.
		 * @param  bool    $reset_all - Did reset.
		 * @param  bool    $kill_sessions - Did reset.
		 * @param  bool    $send_reset - Did reset.
		 * @param  bool    $is_global_reset - Did reset.
		 */
		public function reset( $user_id, $current_password, $by = 'system', $reset_all = false, $kill_sessions = false, $send_reset = false, $is_global_reset = false ) {
			$ppm = ppm_wp();

			// we can't reset without a user ID.
			if ( false === $user_id ) {
				return;
			}

			// create a password event.
			$password_event = array(
				'password'  => $current_password,
				'timestamp' => current_time( 'timestamp' ),
				'by'        => $by,
			);

			// push current password to password history of the user.
			\PPMWP\PPM_WP_History::push( $user_id, $password_event );

			if ( in_array( $by, array( 'admin', 'system' ) ) ) {
				if ( $is_global_reset ) {
					if ( $kill_sessions ) {
						update_user_meta( $user_id, PPM_WP_META_PASSWORD_EXPIRED, 1 );
						if ( $send_reset ) {
							$this->send_reset_email( $user_id, $by, false, false, true );
						}
						$ppm->ppm_user_session_destroy( $user_id, false );
					} else {
						$this->delayed_reset( $user_id, $send_reset );
					}
				} else {
					// update user's expired status 1.
					if ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->terminate_session_password ) ) {
						$this->delayed_reset( $user_id );
					} else {
						update_user_meta( $user_id, PPM_WP_META_PASSWORD_EXPIRED, 1 );
						$this->send_reset_email( $user_id, $by );
						// Destroy user session.
						$ppm->ppm_user_session_destroy( $user_id );
					}
				}
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID.
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 * @param bool   $is_delayed     Is delayed or instant.
		 * @param bool   $is_global_reset     Is delayed or instant.
		 */
		public function send_reset_email( $user_id, $by, $return_on_fail = false, $is_delayed = false, $is_global_reset = false ) {

			$ppm = ppm_wp();

			// Check if message has already been sent.
			$email_sent = get_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT, true );

			if ( $email_sent ) {
				// return;
			}

			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login = $user_data->user_login;
			$user_email = $user_data->user_email;
			$key        = get_password_reset_key( $user_data );
			$login_page = \PPMWP\Helpers\OptionsHelper::get_password_reset_page();

			if ( ! is_wp_error( $key ) ) {
				if ( 'admin' === $by ) {
					$by_str = __( 'Your user password was reset by the website administrator. Below are the details:', 'ppm-wp' );
					if ( $is_delayed ) {
						$content = isset( $ppm->options->ppm_setting->user_delayed_reset_email_body ) ? $ppm->options->ppm_setting->user_delayed_reset_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'global_delayed_reset' );
						$message = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
					} else {
						$content = isset( $ppm->options->ppm_setting->user_reset_email_body ) ? $ppm->options->ppm_setting->user_reset_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'password_reset' );
						$message = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
					}
				} else {
					$content = isset( $ppm->options->ppm_setting->user_password_expired_email_body ) ? $ppm->options->ppm_setting->user_password_expired_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'password_expired' );
					$message = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
				}
			}

			if ( 'admin' === $by && $is_delayed ) {
				/* translators: Password reset email subject. 1: Site name */
				$title = \PPMWP\PPM_EmailStrings::replace_email_strings( isset( $ppm->options->ppm_setting->user_delayed_reset_title ) ? $ppm->options->ppm_setting->user_delayed_reset_title : \PPMWP\PPM_EmailStrings::get_default_string( 'user_delayed_reset_title' ), $user_id );
			} else {
				/* translators: Password reset email subject. 1: Site name */
				$title = \PPMWP\PPM_EmailStrings::replace_email_strings( isset( $ppm->options->ppm_setting->user_password_expired_title ) ? $ppm->options->ppm_setting->user_password_expired_title : \PPMWP\PPM_EmailStrings::get_default_string( 'user_password_expired_title' ), $user_id );
			}

			// Update usermeta so we know we have sent a message.
			update_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT, true );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			// Only send the email if allowed in settings.
			if ( $is_delayed && isset( $ppm->options->ppm_setting->send_user_pw_reset_email ) && ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->send_user_pw_reset_email ) ) {
				return;
			} elseif ( ! $is_delayed && isset( $ppm->options->ppm_setting->send_user_pw_expired_email ) && ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->send_user_pw_expired_email ) ) {
				return;
			}

			if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message, $headers ) ) {
				$fail_message = __( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' );
				// Remove flag so we can try again.
				delete_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					wp_die( wp_kses_post( $fail_message ) );
				}
			}
		}

		/**
		 * Send notification email to admins upon global reset.
		 *
		 * @return bool - Result.
		 */
		public function send_admin_email() {
			$user_data = get_userdata( get_current_user_id() );

			$message  = __( 'All passwords have been reset for:', 'ppm-wp' ) . "\r\n\r\n";
			$message .= network_home_url( '/' ) . "\r\n\r\n";

			if ( is_multisite() ) {
				$blogname = get_network()->site_name;
			} else {
				/*
				 * The blogname option is escaped with esc_html on the way into the database
				 * in sanitize_option we want to reverse this for the plain text arena of emails.
				 */
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			}
			/* translators: Password reset email subject. 1: Site name */
			$title = sprintf( __( '[%s] Global Password Reset Complete', 'ppm-wp' ), $blogname );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			if ( $message && ! wp_mail( $user_data->user_email, wp_specialchars_decode( $title ), $message, $headers ) ) {
				wp_die( esc_html__( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . esc_html__( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' ) );
			}
			return true;
		}

		/**
		 * Reset all users.
		 *
		 * @param boolean $skip_self - Skip admin.
		 * @param boolean $kill_sessions - Destroy sessions.
		 * @param boolean $send_reset - Send email.
		 * @param boolean $is_global_reset - Is global.
		 * @return void
		 */
		public function reset_all( $skip_self = true, $kill_sessions = false, $send_reset = false, $is_global_reset = false ) {
			$ppm = ppm_wp();

			$exempted_users = array();

			// Nonce was checked prior to this call via process_reset.
			if ( isset( $_POST['current_user'] ) || ! $skip_self ) { // phpcs:ignore
				array_push( $exempted_users, get_current_user_id() );
			}

			// exclude exempted roles and users.
			$user_args = array(
				'exclude' => $exempted_users,
				'fields'  => array( 'ID' ),
			);

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				$user_args['blog_id'] = 0;
			}

			// Send users for bg processing later.
			$total_users        = count_users();
			$batch_size         = 50;
			$slices             = ceil( $total_users['total_users'] / $batch_size );
			$users              = array();
			$background_process = new \PPMWP\PPM_Reset_User_PW_Process();

			for ( $count = 0; $count < $slices; $count++ ) {
				$user_args['number'] = $batch_size;
				$user_args['offset'] = $count * $batch_size;
				$users               = get_users( $user_args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						$item = array(
							'ID'              => $user->ID,
							'kill_sessions'   => $kill_sessions,
							'send_reset'      => $send_reset,
							'is_global_reset' => $is_global_reset,
						);
						$background_process->push_to_queue( $item );
					}
				}
			}

			// Fire off bg processes.
			$background_process->save()->dispatch();

			return $this->send_admin_email();
		}

		/**
		 * Flag user for reset later.
		 *
		 * @param  int  $user_id - User ID to flag.
		 * @param  bool $send_reset - Send email.
		 * @return void
		 */
		public function delayed_reset( $user_id, $send_reset = true ) {

			$ppm = ppm_wp();

			if ( ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			// Destroy user session.
			// THIS METHOD CAN ONLY BE REACHED IF THE OPTION TO TERMINATE
			// SESSIONS IS _UNCHECKED_. SESSIONS SHOULDN'T BE DESTROYED HERE.
			// $ppm->ppm_user_session_destroy( $user_id );.

			// Update user meta.
			update_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY, true );
			update_user_meta( $user_id, PPM_WP_META_PASSWORD_EXPIRED, 1 );

			if ( $send_reset ) {
				$this->send_reset_email( $user_id, 'admin', false, true );
			}
		}

		/**
		 * Runs on every login request
		 *
		 * @param type $user_login - User login name.
		 * @param type $user_password - User PW.
		 */
		public function check_on_login( $user_login, $user_password ) {
			if ( empty( $user_login ) || empty( $user_password ) ) {
				return;
			}

			$user = get_user_by( 'login', $user_login );
			if ( $user ) {
				$this->maybe_reset( $user->ID );
			}
		}

		/**
		 * Tries to reset the password if needed
		 *
		 * @param type $user_id - User ID.
		 */
		private function maybe_reset( $user_id ) {

			if ( ! $this->should_password_reset( $user_id ) ) {
				return;
			}

			$user_data        = get_userdata( $user_id );
			$current_password = $user_data->user_pass;

			$this->reset( $user_id, $current_password, 'admin' );
			delete_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY );
		}

		/**
		 * Returns whether the password should be reset or not
		 *
		 * @param type $user_id - User ID.
		 * @return boolean
		 */
		private function should_password_reset( $user_id ) {
			return get_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY, true ) == 1;
		}

		/**
		 * Modify the defailt reset expiry time of 24 hours to a setting of the admins choosing.
		 *
		 * @param int $expiration Default expiry time.
		 */
		public function customize_reset_key_expiry_time( $expiration ) {
			$ppm             = ppm_wp();
			$number          = $ppm->options->ppm_setting->password_reset_key_expiry['value'];
			$unit            = ( 'days' === $ppm->options->ppm_setting->password_reset_key_expiry['unit'] ) ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
			$new_expiry_time = $number * $unit;
			return $new_expiry_time;
		}

		/**
		 * Get user reset by user ID.
		 *
		 * @param  object $user User object.
		 * @param  string $meta_key Reset type.
		 * @return object
		 */
		public function ppm_get_user_reset_key( $user, $meta_key ) {
			$verify_reset_key = false;
			$user_id          = $user->ID;
			$user_login       = $user->user_login;

			$usermeta_key = ( 'new-user' === $meta_key ) ? PPM_WP_META_NEW_USER : PPM_WP_META_USER_RESET_PW_ON_LOGIN;

			// User get reset by user ID.
			$reset_key = get_user_meta( $user_id, $usermeta_key, true );

			// If check reset key exists OR not.
			if ( $reset_key ) {
				$verify_reset_key             = check_password_reset_key( $reset_key, $user_login );
				$verify_reset_key->reset_key  = $reset_key;
				$verify_reset_key->user_login = $user_login;
			}
			return $verify_reset_key;
		}

		/**
		 * Check if users is allowed to reset.
		 *
		 * @param  bool $allow - Is currently allowed.
		 * @param  int  $user_id - User ID.
		 * @return bool result.
		 */
		public function ppm_is_user_allowed_to_reset( $allow, $user_id ) {
			$ppm             = ppm_wp();
			$default_options = \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->inherit['master_switch'] ) ? $ppm->options->inherit : array();

			// Get user by ID.
			$get_userdata = get_user_by( 'ID', $user_id );
			$roles        = $get_userdata->roles;

			$roles = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $roles );

			if ( ! $roles || ! is_array( $roles ) ) {
				return true;
			}

			$roles = reset( $roles );

			// If we reach this point with no default options, stop here.
			if ( empty( $default_options ) ) {
				return true;
			}

			// Allow if request is from an admin.
			if ( isset( $_REQUEST['action'] ) && 'resetpassword' == $_REQUEST['action'] || isset( $_REQUEST['action'] ) && 'ppmwp_unlock_inactive_user' == $_REQUEST['action'] || isset( $_REQUEST['from'] ) && isset( $_REQUEST['action'] ) && 'update' == $_REQUEST['action'] && 'profile' == $_REQUEST['from'] || isset( $_REQUEST['action'] ) && 'unlock' == $_REQUEST['action'] && isset( $_REQUEST['page'] ) && 'ppm-locked-users' == $_REQUEST['page'] ) {
				$user          = wp_get_current_user();
				$allowed_roles = array( 'administrator' );
				if ( array_intersect( $allowed_roles, $user->roles ) ) {
					return true;
				}
			}

			// Get option by role name.
			$options = get_site_option( PPMWP_PREFIX . '_' . $roles . '_options', $default_options );
			if ( isset( $options['disable_self_reset'] ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $options['disable_self_reset'] ) ) {
				return new \WP_Error( 'reset_disabled', esc_attr( $options['disable_self_reset_message'] ) );
			}

			return true;
		}
	}
}
