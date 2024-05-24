<?php
/**
 * Password hint messages.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP;

if ( ! class_exists( '\PPMWP\PPM_WP_Msgs' ) ) {

	/**
	 * Provides all string messages for forms
	 */
	class PPM_WP_Msgs {

		/**
		 * Error strings used in PHP
		 *
		 * @var array Error strings used in PHP
		 */
		public $error_strings = array();

		/**
		 * Error strings used by the password strength meter
		 *
		 * @var array Error strings used by the password strength meter
		 */
		public $js_error_strings = array();

		/**
		 * Strings indicating password strength. Replaces WP default.
		 *
		 * @var array Strings indicating password strength. Replaces WP default.
		 */
		public $pws_l10n = array();

		/**
		 * Strings for the Password Reset UI. Replaces WP default.
		 *
		 * @var array Strings for the Password Reset UI. Replaces WP default.
		 */
		public $user_profile_l10n = array();

		/**
		 * Our special char code.
		 *
		 * @var string
		 */
		private $special_char_strings = '<code>&#33; &#64; &#35; &#36; &#37; &#94; &#38; &#42; &#40; &#41; &#95; &#63; &#163; &#34; &#45; &#43; &#61; &#126; &#59; &#58; &#8364; &#60; &#62;</code>';

		/**
		 * Instantiate localised strings
		 */
		public function init() {

			$ppm = ppm_wp();

			$options = $ppm->options->users_options;

			/*
			 * Remove any excluded special characters from the list displaed.
			 *
			 * @since 2.1.0
			 */
			$excluded_string = isset( $options->excluded_special_chars ) ? $options->excluded_special_chars : '';
			if ( ! empty( $excluded_string ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $options->rules['exclude_special_chars'] ) ) {
				// decode the characters.
				$excluded_string = html_entity_decode( $excluded_string );

				// reformat the string to array of decoded special chars.
				$decoded_special_chars = explode( ' ', html_entity_decode( preg_replace( '/\<(\/)?code\>/', '', $this->special_char_strings ) ) );

				// split the excluded special chars to an array.
				$excluded_entities = \PPMWP\PPM_MB_String_Helper::mb_split_string( $excluded_string );

				// get the difference and reform the string. Limit max items to 4 for tidyness.
				$this->special_char_strings = '<code>' . implode( ' ', array_slice( array_diff( $decoded_special_chars, $excluded_entities ), 0, 4 ) ) . '</code>';

			}

			$excluded_char_strings = '<code style="letter-spacing: 5px">' . esc_attr( $excluded_string ) . '</code>';

			$this->error_strings = array(
				'strength'              => __( 'Should not use known words.', 'ppm-wp' ),
				'username'              => __( 'Cannot contain the username.', 'ppm-wp' ),
				/* translators: %d: Number of passwords the current password cannot be the same as */
				'history'               => sprintf( __( 'Password cannot be the same as the last %d passwords.', 'ppm-wp' ), $options->password_history ),
				/* translators: %d: Configured minumum password length */
				'length'                => sprintf( __( 'Must be at least %d characters long.', 'ppm-wp' ), $options->min_length ),
				'mix_case'              => \PPMWP\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['mix_case'] ) ? __( 'Must contain both UPPERCASE & lowercase characters.', 'ppm-wp' ) : '',
				'numeric'               => \PPMWP\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['numeric'] ) ? __( 'Must contain numbers.', 'ppm-wp' ) : '',
				/* translators: %d: Characters which cannot be used in a password */
				'special_chars'         => \PPMWP\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['special_chars'] ) ? sprintf( __( 'Must contain special characters such as %s', 'ppm-wp' ), $this->special_char_strings ) : '',
				'exclude_special_chars' => sprintf(
					/* translators: 1 = list of special characters */
					__( 'Password cannot contain any of these special characters: %s', 'ppm-wp' ),
					$excluded_char_strings
				),
			);

			$this->js_error_strings = array(
				'strength'              => array(
					0 => __( 'is very easy to guess. Please avoid using known words in the password.', 'ppm-wp' ),
					1 => __( 'is very easy to guess. Please avoid using known words in the password.', 'ppm-wp' ),
					2 => __( 'is relatively easy to guess. Please avoid using known words in the password.', 'ppm-wp' ),
					3 => __( 'is not strong enough. Please avoid using known words in the password.', 'ppm-wp' ),
				),
				'username'              => __( 'Cannot contain the username.', 'ppm-wp' ),
				'history'               => sprintf(
					/* translators: %d = number of passwords */
					__( 'Password cannot be the same as the last %d passwords.', 'ppm-wp' ),
					$options->password_history
				),
				'length'                => sprintf(
					/* translators: %d = min pw length */
					__( 'Must be at least %d characters long.', 'ppm-wp' ),
					$options->min_length
				),
				'mix_case'              => __( 'Must contain both UPPERCASE & lowercase characters.', 'ppm-wp' ),
				'numeric'               => __( 'Must contain numbers.', 'ppm-wp' ),
				'special_chars'         => sprintf(
					/* translators: %s = special chars */
					__( 'Must contain special characters such as %s', 'ppm-wp' ),
					$this->special_char_strings
				),
				'exclude_special_chars' => sprintf(
					/* translators: 1 = list of special characters */
					__( 'Password cannot contain any of these special characters: %s', 'ppm-wp' ),
					$excluded_char_strings
				),
			);

			$this->pws_l10n = array(
				'unknown'  => __( 'Password strength unknown', 'ppm-wp' ),
				'short'    => __( 'Too short', 'ppm-wp' ),
				'bad'      => __( 'Insecure:', 'ppm-wp' ),
				'good'     => __( 'Insecure:', 'ppm-wp' ),
				'strong'   => __( 'Strong &amp; Secure', 'ppm-wp' ),
				'mismatch' => __( 'Mismatch', 'ppm-wp' ),
				'invalid'  => __( 'Invalid', 'ppm-wp' ),
			);

			$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;

			$this->user_profile_l10n = array(
				'warn'           => __( 'Your new password has not been saved.', 'ppm-wp' ),
				'warnWeak'       => __( 'Confirm use of weak password.', 'ppm-wp' ),
				'show'           => __( 'Show', 'ppm-wp' ),
				'hide'           => __( 'Hide', 'ppm-wp' ),
				'cancel'         => __( 'Cancel' ),
				'ariaShow'       => esc_attr__( 'Show password', 'ppm-wp' ),
				'ariaHide'       => esc_attr__( 'Hide password', 'ppm-wp' ),
				'hintMsg'        => esc_html__( 'Hints for a strong password:', 'ppm-wp' ),
				'hintMsgUserNew' => esc_html__( 'Password tip:', 'ppm-wp' ),
				'hintBefore'     => esc_html__( 'Use a strong password that consists at least of 8 characters, lower and upper case letters, numbers and symbols. Refer to the', 'ppm-wp' ),
				'hintLink'       => esc_html__( 'strong password guidelines', 'ppm-wp' ),
				'hintAfter'      => esc_html__( 'for more information.', 'ppm-wp' ),
				'polyfill'       => array(
					'calledOnNull'        => esc_html__( 'Array.prototype.find called on null or undefined', 'ppm-wp' ),
					'callbackNotFunction' => esc_html__( 'callback must be a function', 'ppm-wp' ),
				),
				'user_id'        => $user_id,
				'nonce'          => wp_create_nonce( 'reset-password-for-' . $user_id ),
			);
		}
	}
}
