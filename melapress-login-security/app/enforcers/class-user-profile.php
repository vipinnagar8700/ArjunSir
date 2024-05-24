<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * PPM New User Register
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;
use \PPMWP\Helpers\PPM_EmailStrings;

// If check class exists OR not.
if ( ! class_exists( '\PPMWP\PPM_User_Profile' ) ) {
	/**
	 * Declare PPM_User_Profile Class
	 */
	class PPM_User_Profile {

		/**
		 * Init hooks.
		 */
		public function init() {
			global $pagenow;
			if ( 'profile.php' !== $pagenow || 'user-edit.php' !== $pagenow ) {
				add_action( 'show_user_profile', array( $this, 'reset_user_password' ) );
				add_action( 'edit_user_profile', array( $this, 'reset_user_password' ) );
				add_action( 'personal_options_update', array( $this, 'save_profile_fields' ) );
				add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
			}
			add_action( 'wp_login', array( $this, 'ppm_reset_pw_on_login' ), 10, 2 );
		}

		/**
		 * Handle reset of individual password.
		 *
		 * @param WP_User $user - user to reset.
		 * @return void
		 */
		public function reset_user_password( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			$reset = get_user_meta( $user->ID, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );

			// If the profile was recently updated, one of those updates could be a new password,
			// so if the user is set to reset on next login, lets generate a fresh reset key
			// to avoid "invalid reset link" when logging in next time.
			if ( isset( $_REQUEST['updated'] ) && ! empty( $reset ) ) {
				$this->generate_new_reset_key( $user->ID );
			}

			if ( current_user_can( 'manage_options' ) ) { ?>
				<table class="form-table" role="presentation">
					<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="reset_password"><?php esc_html_e( 'Reset password on next login', 'ppm-wp' ); ?></label></th>
						<td>
							<label for="reset_password_on_next_login">
								<input name="reset_password_on_next_login" type="checkbox" id="reset_password_on_next_login" <?php checked( ! empty( $reset ) ); ?>>
								<?php esc_html_e( 'Reset password on next login', 'ppm-wp' ); ?>
								<?php wp_nonce_field( 'ppm_wp_reset_on_next_login', 'ppm_wp_user_profile_nonce' ); ?>
							</label>
							<br>
						</td>
						</tr>
					</tbody>
				</table>
				<?php
			}
		}

		/**
		 * Handles saving of user profile fields.
		 *
		 * @param  int $user_id - user ID.
		 * @return void
		 */
		public function save_profile_fields( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) || isset( $_POST['ppm_wp_user_profile_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ppm_wp_user_profile_nonce'] ) ), 'ppm_wp_reset_on_next_login' ) ) {
				return;
			}

			if ( isset( $_POST['reset_password_on_next_login'] ) ) {
				$reset = get_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );
				if ( empty( $reset ) ) {
					$this->generate_new_reset_key( $user_id );
				}
			} else {
				// Remove any reset on login keys if admin has disabled it for this user.
				delete_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN );
			}
		}

		/**
		 * Generates a new password reset key and also saves it to our own meta field.
		 *
		 * @param int $user_id - Current ID.
		 * @since 2.5.0
		 */
		private function generate_new_reset_key( $user_id ) {
			$userdata = get_user_by( 'id', $user_id );
			$key      = get_password_reset_key( $userdata );
			if ( ! is_wp_error( $key ) ) {
				update_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $key );
			}
		}

		/**
		 * Send user for further processing in central function.
		 *
		 * @param  string  $user_login - User logging in.
		 * @param  WP_User $user - User object.
		 * @return void
		 */
		public function ppm_reset_pw_on_login( $user_login, $user ) {
			$this->ppm_handle_login_based_resets( $user_login, $user, 'reset-on-login' );
		}

		/**
		 * Redirect user to reset page if needed.
		 *
		 * @param  string  $user_login - User logging in.
		 * @param  WP_User $user - User object.
		 * @param  string  $reset_type - Where did they come from.
		 * @return void
		 */
		public function ppm_handle_login_based_resets( $user_login, $user, $reset_type = 'reset-on-login' ) {
			// Get user reset key.
			$reset = new \PPMWP\PPM_WP_Reset();
			$ppm   = ppm_wp();

			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, $reset_type );

			// If check reset key exists OR not.
			if ( $verify_reset_key && ! $verify_reset_key->errors ) {
				// Handle users directly registered using Restrict Content.
				if ( isset( $_REQUEST['action'] ) && 'rc_process_registration_form' === $_REQUEST['action'] ) {
					$ppm->handle_user_redirection( $verify_reset_key, true );
				} else {
					$ppm->handle_user_redirection( $verify_reset_key );
				}
			} elseif ( isset( $verify_reset_key->errors['expired_key'] ) && ! empty( $verify_reset_key->errors['expired_key'] ) && 'new-user' === $reset_type ) {

				// If a user has reached this point, they have a valid key in the correct place,
				// but they have taken too long to reset, so we reset the key and send them back to login.

				// Create new reset key for this user.
				$key = get_password_reset_key( $user );

				if ( ! is_wp_error( $key ) ) {
					// Update user with new key information.
					$update = update_user_meta( $user->ID, PPM_WP_META_NEW_USER, $key );
				}

				$ppm->handle_user_redirection( $verify_reset_key );
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID.
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 */
		public function send_reset_next_login_email( $user_id, $by, $return_on_fail = false ) {

			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login    = $user_data->user_login;
			$user_email    = $user_data->user_email;
			$key           = get_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );
			$login_page    = OptionsHelper::get_password_reset_page();
			$email_content = false;

			if ( 'admin' === $by ) {
				$content       = isset( $ppm->options->ppm_setting->user_reset_next_login_email_body ) ? $ppm->options->ppm_setting->user_reset_next_login_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'reset_next_login' );
				$email_content = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
			}

			$title = \PPMWP\PPM_EmailStrings::replace_email_strings( isset( $ppm->options->ppm_setting->user_reset_next_login_title ) ? $ppm->options->ppm_setting->user_reset_next_login_title : \PPMWP\PPM_EmailStrings::get_default_string( 'user_reset_next_login_title' ), $user_id );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			if ( $email_content && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $email_content, $headers ) ) {
				$fail_message = __( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					wp_die( wp_kses_post( $fail_message ) );
				}
			}
		}
	}
}
