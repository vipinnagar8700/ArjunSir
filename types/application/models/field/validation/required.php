<?php

/**
 * Required field validation rules
 *
 * @since 2.3
 */
class Types_Field_Validation_Required extends Types_Field_Validation {

	/**
	 * Constructor
	 *
	 * @param String $type Validation rule type: required, ...
	 * @param Array  $data Array of validation rules from the Field creation.
	 *
	 * @since 2.3
	 */
	public function __construct( $type, $data ) {
		$formatted_data = $data;
		// Because of the differce of the data received and the data formated in the HTML input, some changes are needed.
		if ( isset( $data['active'] ) && isset( $data['value'] ) ) {
			$formatted_data['args'] = array( $data['active'] => $data['value'] );
			unset( $formatted_data['active'] );
			unset( $formatted_data['value'] );
		}
		parent::__construct( $type, $formatted_data );
	}

}
