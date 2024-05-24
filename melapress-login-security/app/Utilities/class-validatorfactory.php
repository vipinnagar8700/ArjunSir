<?php
/**
 * WPassword
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP\Utilities;

use PPMWP\Validators\Validator;

/**
 * Calls the proper validator method based on provided rules
 *
 * @since 2.5
 */
class ValidatorFactory {

	/**
	 * Calls Validator method based on given rules and returns the result
	 *
	 * Expects the following format for rules @see PPM_WP_Options::$default_options_validation_rules
	 * 'typeRule' => [
	 *               ['number', 'inset' ]
	 *               [ 'min', 'max', 'set' ]
	 *           ]
	 *
	 * @since 2.5
	 *
	 * @param mixed $value - Value to validate.
	 * @param array $rules - Applicable rule.
	 *
	 * @return bool
	 */
	public static function validate( $value, array $rules ) {

		if ( isset( $rules['typeRule'] ) ) {
			if ( 'number' === $rules['typeRule'] ) {
				$min = (int) ( $rules['min'] ?? 0 );
				$max = ( $rules['max'] ?? null );

				return Validator::validate_integer( $value, $min, $max );
			}
			if ( 'inset' === $rules['typeRule'] ) {
				$range = $rules['set'] ?? array();

				return Validator::validate_in_set( $value, $range );
			}
		}

		return true;
	}
}
