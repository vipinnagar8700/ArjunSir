<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * PPM New User Register
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP;

// If check class exists OR not.
if ( ! class_exists( '\PPMWP\PPM_New_User_Register' ) ) {
	/**
	 * Declare PPM_New_User_Register Class
	 */
	class PPM_New_User_Register {

		/**
		 * Init hooks.
		 */
		public function init() {
			// Redirect login page.
			add_action( 'validate_password_reset', array( $this, 'ppm_validate_password_reset' ), 10, 2 );
			add_action( 'wp_login', array( $this, 'ppm_first_time_login' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'ppm_new_user_errors' ), 10, 3 );
			add_filter( 'login_redirect', array( $this, 'override_login_redirects' ), 1000, 3 );
		}

		/**
		 * Redirect user after successful.
		 *
		 * @param object $user_login Current user login.
		 * @param object $user User object.
		 */
		public function ppm_first_time_login( $user_login, $user ) {
			$user_profile = new \PPMWP\PPM_User_Profile();
			$user_profile->ppm_handle_login_based_resets( $user_login, $user, 'new-user' );
		}

		/**
		 * Override login_redirect to ensure we are not taken to a custom page.
		 *
		 * @param  string  $redirect_to - Current redirect.
		 * @param  string  $requested_redirect_to - Requested redirected.
		 * @param  WP_User $user - User to redirect.
		 * @return string - New redirect.
		 */
		public function override_login_redirects( $redirect_to, $requested_redirect_to, $user ) {
			if ( ! empty( $redirect_to ) && is_a( $user, '\WP_User' ) ) {

				$reset            = new \PPMWP\PPM_WP_Reset();
				$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );
				$ppm              = \PPM_WP::_instance();

				if ( $verify_reset_key && ! $verify_reset_key->errors ) {
					$ppm->handle_user_redirection( $verify_reset_key, false, true );
				}
			}

			return $redirect_to;
		}

		/**
		 * Change reset password form message.
		 *
		 * @param object $error WP_Error object.
		 * @param object $user User object.
		 */
		public function ppm_validate_password_reset( $error, $user ) {
			// Get user reset key.
			$reset            = new \PPMWP\PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

			// Ignore nonce check as we are only using this as a flag.
			// If check reset key exists OR not.
			if ( ( $verify_reset_key && ! $verify_reset_key->errors ) && ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] ) ) { // phpcs:ignore 
				// Logout current user.
				wp_logout();
				// Login notice.
				add_filter( 'login_message', array( $this, 'ppm_retrieve_password_message' ) );
			}
		}

		/**
		 * Customize retrieve password message.
		 *
		 * @param string $message Retrive password message.
		 * @return string message
		 */
		public function ppm_retrieve_password_message( $message ) {
			return wp_sprintf( '<p class="message reset-pass">%s</p>', __( 'To ensure you use a strong password, you are required to change your password before you login for the first time.', 'ppm-wp' ) );
		}

		/**
		 * Adds our error messages to the reset message.
		 *
		 * @param  WP_Error $errors - Current login errors.
		 * @param  bool     $update - Is update.
		 * @param  WP_User  $user - User.
		 * @return WP_Error $errors - Appended errors.
		 */
		public function ppm_new_user_errors( $errors, $update, $user ) {

			// Ignore nonce check as we are only using this as a flag.
			if ( isset( $_POST['from'] ) && 'profile' === $_POST['from'] ) { // phpcs:ignore 
				return;
			}

			$ppm     = ppm_wp();
			$options = $ppm->options->users_options;

			$user_settings = $ppm->options->users_options;
			$role_setting  = $ppm->options->setting_options;

			$options_master_switch    = \PPMWP\Helpers\OptionsHelper::string_to_bool( $options->master_switch );
			$settings_master_switch   = \PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = \PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {

				$pwd_check          = new \PPMWP\PPM_WP_Password_Check();
				$post_array         = filter_input_array( INPUT_POST );
				$does_violate_rules = $pwd_check->does_violate_rules( $post_array['pass1'] );

				if ( $does_violate_rules ) {
					$errors->add( 'ppm_password_error', __( 'Password does not meet policy requirments.' ) );
				}
			}

			return $errors;
		}

	}
}
