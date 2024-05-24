<?php

/**
 * @since 2.3
 */
class Types_Field_Type_Skype extends Types_Field_Abstract {

	/**
	 * @var string
	 */
	protected $placeholder;

	/**
	 * @var string
	 */
	protected $default_value;

	/**
	 * @var Types_Field_Setting_Bool_Interface
	 */
	protected $repeatable;


	/**
	 * @return string
	 */
	public function get_type() {
		return 'skype';
	}


	/**
	 * Types_Field_Type_Skype constructor.
	 *
	 * @param array $data (see getDefaultProperties() for used keys)
	 */
	public function __construct( $data ) {
		// merge user data with default data
		$data = array_merge( $this->get_default_properties(), $data );

		// slug / title / description / value
		parent::__construct( $data );

		$this->set_placeholder( $data['placeholder'] );
		$this->set_default_value( $data['default_value'] );
		$this->set_repeatable( $data['repeatable'] );
	}


	/**
	 * @return array
	 */
	private function get_default_properties() {
		return array(
			'slug' => null,
			'title' => null,
			'description' => null,
			'value' => null,
			'placeholder' => null,
			'default_value' => null,
			'repeatable' => false,
		);
	}


	/**
	 * @return string
	 */
	public function get_placeholder() {
		return $this->placeholder;
	}


	/**
	 * @param mixed $data
	 */
	private function set_placeholder( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}

		$this->placeholder = $data;
	}


	/**
	 * @return string
	 */
	public function get_default_value() {
		return $this->default_value;
	}


	/**
	 * @param string|mixed $data
	 */
	private function set_default_value( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}

		$this->default_value = $data;
	}


	/**
	 * @return boolean
	 */
	public function is_repeatable() {
		return $this->repeatable->is_true();
	}


	/**
	 * @param Types_Field_Setting_Bool_Interface $data
	 */
	private function set_repeatable( Types_Field_Setting_Bool_Interface $data ) {
		$this->repeatable = $data;
	}
}
