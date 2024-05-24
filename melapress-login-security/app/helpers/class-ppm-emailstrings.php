<?php
/**
 * PPM Email Settings
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;

if ( ! class_exists( '\PPMWP\PPM_EmailStrings' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class PPM_EmailStrings {

		/**
		 * Array of setting names and default strings for each.
		 *
		 * @var array
		 */
		public static $default_strings = array(
			'user_unlocked_email_title'             => '[{blogname}] Account Unlocked',
			'user_unlocked_email_reset_message'     => 'Please visit the following URL to reset your password:',
			'user_unlocked_email_continue_message'  => 'You may continue to login as normal',
			'user_unblocked_email_title'            => '[{blogname}] Account logins unblocked',
			'user_unblocked_email_reset_message'    => 'Please visit the following URL to reset your password:',
			'user_unblocked_email_continue_message' => 'You may continue to login as normal',
			'user_reset_next_login_title'           => '[{blogname}] Password Reset',
			'user_delayed_reset_title'              => '[{blogname}] Password Expired',
			'user_password_expired_title'           => '[{blogname}] Password Expired',
		);

		/**
		 * Get default string for desired setting.
		 *
		 * @param string $wanted - Desired string.
		 * @return string|bool - Located string, or false.
		 */
		public static function get_default_string( $wanted ) {
			return isset( self::$default_strings[ $wanted ] ) ? self::$default_strings[ $wanted ] : false;
		}

		/**
		 * Neat holder for default email body texts.
		 *
		 * @param string] $template - Desired template.
		 * @return string - Message text.
		 */
		public static function default_message_contents( $template ) {

			$message = '';

			if ( 'user_unlocked' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user account has been unlocked by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n";
				$message .= "\n" . '{reset_or_continue}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'user_unblocked' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user account has been unblocked from further login attempts by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n";
				$message .= "\n" . '{reset_or_continue}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'reset_next_login' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user password was reset by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'You will be asked to reset your password when you next login. Otherwise, you can visit the following URL to reset your password: ', 'ppm-wp' ) . '{reset_url}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'global_delayed_reset' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user password was reset by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'Please be aware your password has been reset by the sites administrator and you will be required to provide a new one upon next login. If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'password_expired' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your password for the user {user_login_name} on the website {home_url} has expired.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password: {reset_url}', 'ppm-wp' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on {admin_email}.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'password_reset' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password: {reset_url}', 'ppm-wp' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on {admin_email}.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";
			}

			return $message;

		}

		/**
		 * Replace our tags with the relevent data when sending the email.
		 *
		 * @param string $input - Original text.
		 * @param string $user_id - Applicable user ID.
		 * @param array  $args - Extra args.
		 * @return string $final_output - Final message text.
		 */
		public static function replace_email_strings( $input = '', $user_id = '', $args = array() ) {

			$ppm  = ppm_wp();
			$user = get_userdata( $user_id );

			if ( ! is_a( $user, '\WP_User' ) ) {
				return $input;
			}

			// Prepare email details.
			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );

			// These are the strings we are going to search for, as well as there respective replacements.
			$replacements = array(
				'{home_url}'          => esc_url( get_bloginfo( 'url' ) ),
				'{site_name}'         => sanitize_text_field( get_bloginfo( 'name' ) ),
				'{user_login_name}'   => sanitize_text_field( $user->user_login ),
				'{user_first_name}'   => sanitize_text_field( $user->firstname ),
				'{user_last_name}'    => sanitize_text_field( $user->lastname ),
				'{user_display_name}' => sanitize_text_field( $user->display_name ),
				'{admin_email}'       => $from_email,
				'{blogname}'          => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'{reset_or_continue}' => ( ! empty( $args ) && isset( $args['reset_or_continue'] ) ) ? sanitize_text_field( $args['reset_or_continue'] ) : '',
				'{reset_url}'         => ( ! empty( $args ) && isset( $args['reset_url'] ) ) ? sanitize_text_field( $args['reset_url'] ) : '',
			);

			$final_output = str_replace( array_keys( $replacements ), array_values( $replacements ), $input );
			return $final_output;
		}
	}
}
