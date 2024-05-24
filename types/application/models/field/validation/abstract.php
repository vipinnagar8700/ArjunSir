<?php

/**
 * Contains field validation rules
 *
 * TODO: To this day, validation rules are managed by legacy code. This class helps new Fields as Post Reference to add validation rules data to its HTML input field.
 *
 * @since 2.3
 */
abstract class Types_Field_Validation {

	/**
	 * Raw validation data
	 *
	 * @var Array
	 * @since 2.3
	 */
	protected $data;


	/**
	 * Validation type
	 *
	 * @var String
	 * @since 2.3
	 */
	protected $type;


	/**
	 * Constructor
	 *
	 * @param String $type Validation rule type: required, ...
	 * @param Array  $data Array of validation rules from the Field creation. Example:
	 *                  array (
	 *                    'active' => '1',
	 *                    'value' => 'true',
	 *                    'message' => 'This field is required',
	 *                  ).
	 *
	 * @since 2.3
	 */
	public function __construct( $type, $data ) {
		$this->type = $type;
		$this->data = $data;
	}

	/**
	 * Returns raw validation data
	 *
	 * @return Array
	 * @since 2.3
	 */
	public function get_data() {
		return array( $this->type => $this->data );
	}
}
