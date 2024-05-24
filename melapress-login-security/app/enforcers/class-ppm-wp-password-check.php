<?php
/**
 * Validator class.
 *
 * @since 2.5.0
 *
 * @package WordPress
 */

namespace PPMWP;

use \PPMWP\Validators\Validator;

/**
 * Handles password checks.
 *
 * @package WordPress
 * @subpackage wpassword
 */
if ( ! class_exists( '\PPMWP\PPM_WP_Password_Check' ) ) {

	/**
	 * Checks given password against policies
	 */
	class PPM_WP_Password_Check {

		/**
		 * Instance of the main plugin class.
		 *
		 * @since 2.1.0
		 * @var   PPM_WP
		 */
		public $ppm;

		/**
		 * Possible violations for check.
		 *
		 * @var array Array with violations against rules
		 */
		private $violations = array();

		/**
		 * Plugin options.
		 *
		 * @var object Instance of PPM_WP_Options
		 */
		private $options;

		/**
		 * Text strings.
		 *
		 * @var object Instance of PPM_WP_Msgs
		 */
		private $msgs;

		/**
		 * Regex tests.
		 *
		 * @var object Instance of PPM_WP_Regex
		 */
		private $regex;

		/**
		 * Initialise instances of policy classes
		 */
		public function __construct() {
			$ppm = ppm_wp();

			$this->options = $ppm->options->users_options;
			$this->msgs    = $ppm->msgs;
			$this->regex   = $ppm->regex;
		}

		/**
		 * Sets up WP hooks
		 */
		public function hook() {
			$ppm = ppm_wp();
			if ( isset( $ppm->options->ppm_setting->enable_wp_reset_form ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->enable_wp_reset_form ) ) {
				// hook into password reset.
				add_action( 'validate_password_reset', array( $this, 'validate_reset' ), 0, 2 );
			}

			if ( isset( $ppm->options->ppm_setting->enable_wp_profile_form ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->enable_wp_profile_form ) ) {
				// hook into user profile edit, new screens.
				add_action( 'user_profile_update_errors', array( $this, 'edit_user' ), 0, 3 );
			}
		}

		/**
		 * Validates reset password
		 *
		 * @see wp-login.php
		 *
		 * @param WP_Error $errors Errors in policy validation.
		 * @param WP_User  $user User to check.
		 */
		public function validate_reset( \WP_Error $errors, \WP_User $user ) {
			// if the user is exempted, don't validate.
			if ( ppm_is_user_exempted( $user->ID ) ) {
				return;
			}

			// get the password from form submission.
			$post_array = filter_input_array( INPUT_POST );
			$password   = isset( $post_array['pass1'] ) ? $post_array['pass1'] : false;

			// no password submitted, bail.
			if ( empty( $password ) ) {
				return;
			}

			// no reason to not validate.
			return $this->validate_for_user( $user->ID, $password, 'reset-form', $errors );
		}

		/**
		 * Validate when profile is updated
		 *
		 * @see edit_user() in wp-admin/includes/user.php
		 *
		 * @param WP_Error $errors errors.
		 * @param boolean  $update If the password is being updated.
		 * @param object   $user The user whose password is being updated.
		 */
		public function edit_user( \WP_Error $errors, $update, $user ) {

			// While creating user, $user->ID is not set, return early in this case.
			if ( ! isset( $user->ID ) ) {
				return;
			}

			// if the user is exempted, don't validate.
			if ( ppm_is_user_exempted( $user->ID ) ) {
				return;
			}

			// If password is not set, then don't validate.
			if ( ! isset( $user->user_pass ) ) {
				return;
			}

			// get password from the user object.
			$password = $user->user_pass;

			// validate.
			return $this->validate_for_user( $user->ID, $password, 'user-edit', $errors );
		}

		/**
		 * Validate a given password for a given user
		 *
		 * @param int      $user_id The user's id.
		 * @param string   $password Password to validate.
		 * @param string   $context The context of validation, reset or profile update.
		 * @param WP_Error $errors Errors.
		 */
		public function validate_for_user( $user_id, $password, $context, &$errors ) {

			// if the user is exempted, or not password is supplied. don't validate.
			if ( ! $password || ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			// if the password validates with the rules, all good, return.
			if ( $this->is_password_ok( $password, $user_id ) ) {
				return;
			}

			// by now, the is_password_ok would have populated violations.
			// if the policy check fails, if not, bail.
			if ( empty( $this->violations ) ) {
				return;
			}

			// we're only interested in the keys so we can get the strings from PPM_WP_Msgs.
			$this->violations = array_keys( $this->violations );

			// whether we are restting the password or updating a profile.
			switch ( $context ) {

				// resetting password, format errors differently.
				case 'reset-form':
				case 'reset-form-return':
					foreach ( $this->violations as $violation ) {
						$errors->add( 'password-strength-issue-' . $violation, $this->msgs->error_strings[ $violation ] );
					}
					break;

				// updating profile.
				case 'user-edit':
					foreach ( $this->violations as $violation ) {
						/* translators: %s: Current violations for desired password. */
						$errors->add( 'pass', sprintf( __( '<strong>ERROR</strong>: New password %s', 'ppm-wp' ), lcfirst( $this->msgs->error_strings[ $violation ] ) ), array( 'form-field' => 'pass1' ) );
					}
					break;
			}

			if ( 'reset-form-return' === $context ) {
				return $this->violations;
			}
		}

		/**
		 * Check if a password is valid.
		 *
		 * @param string $password The password string.
		 * @param int    $user_id - Current user ID.
		 * @return boolean
		 */
		public function is_password_ok( $password, $user_id = false ) {

			// if no user is supplied, assume current user.
			if ( false === $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( ! Validator::validate_password_not_contain_username( $password, $user_id ) ) {
				$this->violations['username'] = true;

				return false;
			}

			$is_old_password = self::is_old_password( $password, $user_id );
			// password is ok if no rules are violated.
			if ( ! $this->does_violate_rules( $password )
				// and the password is not one from users' history.
				&& ! $is_old_password ) {

				return true;
			}

			// set history violation.
			if ( true === $is_old_password ) {
				$this->violations['history'] = true;
			}

			// otherwise, password's not ok :(.
			return false;
		}

		/**
		 * Check if password violates rules.
		 *
		 * @param string $password - PW to check.
		 * @param bool   $return_failures - Just return if needed.
		 * @return mixed - Return of violation test.
		 */
		public function does_violate_rules( $password, $return_failures = false ) {
			// match password with regex rules.
			$regex_results = $this->match_rules( $password );

			// filter regex results to clean up array and get violations.
			$successful_regexes = array_filter( $regex_results, array( $this, 'is_violation' ) );

			if ( ! isset( $this->options->rules ) ) {
				$ppm_options   = new \PPMWP\PPM_WP_Options();
				$policy        = $ppm_options->user_role_policy();
				$this->options = $policy;
			}

			if ( ! isset( $this->options->rules ) ) {
				return;
			}

			// the regexes that the password failed to match against.
			$failed_regexes = array();
			foreach ( $this->options->rules as $key => $rule ) {
				if ( \PPMWP\Helpers\OptionsHelper::string_to_bool( $rule ) && ! isset( $successful_regexes[ $key ] ) ) {
					$failed_regexes[ $key ] = true;
				}
			}

			// since the regex check earlier was skipped then we need to check.
			// excluded special characters here to ensure we don't have any.
			if ( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->rules['exclude_special_chars'] ) && isset( $this->options->excluded_special_chars ) ) {
				$excluded_chars_array = str_split( html_entity_decode( str_replace( '&pound', '£', $this->options->excluded_special_chars ), null, 'UTF-8' ), 1 );
				foreach ( $excluded_chars_array as $char ) {
					// remove any chars from the allowed list.
					$matched = stripos( $password, $char );
					if ( $matched ) {
						$failed_regexes['exclude_special_chars'] = true;
						break;
					}
				}
			}

			/**
			 * Edge case when all special characters are excluded in the excluded characters
			 * can return false positive when new password is set.
			 */
			if ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->rules['special_chars'] ) && isset( $failed_regexes['special_chars'] ) ) {
				unset( $failed_regexes['special_chars'] );
			}

			// upper case fail = mix case fail.
			if ( isset( $failed_regexes['upper_case'] ) ) {
				$failed_regexes['mix_case'] = true;
				unset( $failed_regexes['upper_case'] );
			}

			// lower case fail = mix case fail.
			if ( isset( $failed_regexes['lower_case'] ) ) {
				$failed_regexes['mix_case'] = true;
				unset( $failed_regexes['lower_case'] );
			}

			// length fallback.
			if ( isset( $failed_regexes['length'] ) && ( isset( $_POST['mepr-confirm-password'] ) || isset( $_POST['mepr_user_password'] ) ) ) { // phpcs:ignore 
				$min = $this->options->min_length;
				if ( strlen( $password ) < $min ) {
					$failed_regexes['length'] = true;
				} else {
					unset( $failed_regexes['length'] );
				}
			}

			// no violations.
			if ( empty( $failed_regexes ) ) {
				return false;
			}

			// improper format means nothing failed.
			if ( ! is_array( $failed_regexes ) ) {
				return false;
			}

			// populate violations for $this->validate_for_user to use.
			$this->violations = $failed_regexes;

			if ( $return_failures ) {
				return $failed_regexes;
			}

			return true;
		}

		/**
		 * Filters Pattern Matching results to create boolean violations.
		 *
		 * @param array $regex_result - Violations check result.
		 * @return boolean
		 */
		private function is_violation( $regex_result ) {

			// pattern matching failed, so violation.
			if ( empty( $regex_result ) ) {
				return false;
			}

			if ( ! is_array( $regex_result ) ) {
				return false;
			}

			$actual_result = $regex_result[0];

			if ( empty( $actual_result ) ) {
				return false;
			}

			if ( ! is_array( $actual_result ) ) {
				return false;
			}

			// we're not interested in the result, only that there WAS any result.
			return true;
		}

		/**
		 * Match Password with each rule pattern.
		 *
		 * @param  string $password - PW to check.
		 * @return array  The result of the pattern matching.
		 */
		private function match_rules( $password ) {

			$result = array();

			// convert regexes to an array.
			$rules = json_decode( wp_json_encode( $this->regex ), true );

			foreach ( $rules as $rule => $regex ) {

				// Since this is a explicitly in a JS lookahead format it will
				// always fail here so exclude from this test by faking.
				if ( 'exclude_special_chars' === $rule ) {
					$result[ $rule ] = array( explode( 'a', 'fake' ) );
					continue;
				}

				if ( ! isset( $this->options->rules ) ) {
					$ppm_options   = new \PPMWP\PPM_WP_Options();
					$policy        = $ppm_options->user_role_policy();
					$this->options = $policy;
				}

				// only check if the policy is enabled.
				if ( isset( $this->options->rules ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->rules[ $rule ] ) ) {

					$matches = array();
					// set the result of pattern matching to a new element with the rule namespace as key.
					preg_match_all( '/' . $regex . '/', $password, $matches );

					$result[ $rule ] = $matches;
				}
			}
			return $result;
		}


		/**
		 * Check if a password has been used by user in the past
		 *
		 * @param string $new_pass Password to check.
		 * @param int    $user_id User to perform check for.
		 * @return boolean
		 */
		public static function is_old_password( $new_pass, $user_id ) {

			// get the saved history.
			$password_history = get_user_meta( $user_id, PPM_WP_META_KEY, true );

			// no history, no need to check.
			if ( empty( $password_history ) ) {
				return false;
			}

			$ppm                  = ppm_wp();
			$new_password_history = array_slice( array_reverse( $password_history ), 0, $ppm->options->password_history + 1 );

			foreach ( $new_password_history as $event ) {
				// check against old password.
				$match = wp_check_password( $new_pass, $event['password'], $user_id );

				if ( $match ) {
					return true;
				}
			}

			return false;
		}

	}

}
