<?php

/**
 * Creates a Types_Field_Validation object depending on validation rule type
 *
 * @since 2.3
 */
class Types_Field_Validation_Factory {

	/**
	 * Returns the field validation object
	 *
	 * TODO: Add missing validations rules. To this day, only required.
	 *
	 * @param String $type Validation rule type: required, ...
	 * @param Array $data Array of validation rules from the Field creation.
	 *
	 * @return mixed
	 * @throws InvalidArgumentException If the validation rules is not handled by an object.
	 * @since 2.3
	 */
	public static function get_validator_by_type( $type, $data ) {
		$class_name = 'Types_Field_Validation_' . ucwords( $type );
		if ( ! class_exists( $class_name ) ) {
			throw new RuntimeException( sprintf( __( 'Validation rule "%s" not implemented.', 'wpcf' ), $class_name ) );
		}
		return new $class_name( $type, $data );
	}
}
