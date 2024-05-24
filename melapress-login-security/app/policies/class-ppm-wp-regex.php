<?php
/**
 * Handles regex within plugin.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP;

if ( ! class_exists( '\PPMWP\PPM_WP_Regex' ) ) {

	/**
	 * Provides regexes to check password against
	 */
	class PPM_WP_Regex implements \JsonSerializable {

		/**
		 * Patterns.
		 *
		 * @var array Regexes to check password policies
		 *
		 * NOTE: these are javascript regex patterns and not PCRE.
		 */
		private $rules = array(
			'length'                => '.{$length,}', // the $length placeholder.
			'numeric'               => '[0-9]',
			'upper_case'            => '[A-Z]',
			'lower_case'            => '[a-z]',
			'special_chars'         => '[!@#$%^&*()_?£"\-+=~;:€<>]',
			'exclude_special_chars' => '^((?![{excluded_chars}]).)*$',
		);

		public $length;
		public $numeric;
		public $upper_case;
		public $lower_case;
		public $special_chars;
		public $exclude_special_chars;

		/**
		 * Plugin Options
		 *
		 * @var array Plugin Options
		 */
		private $user_options;

		/**
		 * Initialise rules
		 */
		public function init() {

			global $pagenow;

			// get options.
			$ppm                = ppm_wp();
			$this->user_options = $ppm->options->users_options;

			$allowed_pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );
			if ( ! $this->user_options && ! in_array( $pagenow, $allowed_pages ) && ! isset( $_POST['action'] ) ) {
				return;
			}

			// set minimum length.
			$this->set_min_length();
			// replace the excluded chars placeholder with the values.
			$this->set_excluded_chars();

			// set each property so it can be used conveniently.
			foreach ( $this->user_options->rules as $key => $rule ) {
				if ( \PPMWP\Helpers\OptionsHelper::string_to_bool( $rule ) ) {
					// for eg, $this->length.
					if ( isset( $this->rules[ $key ] ) ) {;
						$this->{$key} = $this->rules[ $key ];
					}
				}

				// If the rule is not enabled in the policy settings,
				// remove it from rules.
				if ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $rule ) ) {
					unset( $this->rules[ $key ] );
				}
			}

		}

		/**
		 * Set minimum length in regex from options
		 */
		private function set_min_length() {
			// replace $length placeholder with actual length.
			$this->rules['length'] = preg_replace( '/\$length/', $this->user_options->min_length, $this->rules['length'] );
		}

		/**
		 * Set the list of excluded chars in the regex.
		 *
		 * @method set_excluded_chars
		 * @since  2.1.0
		 */
		private function set_excluded_chars() {
			// replace $excluded_chars placeholder with actual excluded chars.
			if ( isset( $this->user_options->ui_rules['exclude_special_chars'] )
				 && \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->user_options->ui_rules['exclude_special_chars'] )
				 && ! empty( $this->user_options->excluded_special_chars )
			) {
				$allowed_special_chars = ltrim( rtrim( $this->rules['special_chars'], ']' ), '[' );
				$excluded_chars_arr    = str_split( html_entity_decode( str_replace( '&pound', '£', $this->user_options->excluded_special_chars ), ENT_QUOTES, 'UTF-8' ), 1 );
				foreach ( $excluded_chars_arr as $excluded_char ) {
					$allowed_special_chars = str_replace( $excluded_char, '', $allowed_special_chars );
				}

				if ( '' !== trim( $allowed_special_chars ) ) {
					$this->rules['special_chars'] = "[{$allowed_special_chars}]";
					// Escape dash.
					$this->rules['special_chars'] = str_replace( '-', '\-', $this->rules['special_chars'] );
					$this->rules['special_chars'] = str_replace( '\-+', '-\+', $this->rules['special_chars'] );
				} else {
					unset( $this->rules['special_chars'] );
				}

				$excluded_chars                       = ( preg_quote( $this->user_options->excluded_special_chars ) );
				$this->rules['exclude_special_chars'] = preg_replace( '/{excluded_chars}/', $excluded_chars, $this->rules['exclude_special_chars'] );
			} else {
				unset( $this->rules['exclude_special_chars'] );
			}
		}

		/**
		 * Return rules.
		 *
		 * @inheritDoc
		 */
		 #[\ReturnTypeWillChange]
		public function jsonSerialize() {
			return $this->rules;
		}
	}

}
