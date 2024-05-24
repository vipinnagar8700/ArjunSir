<?php

/**
 * @since 2.3
 */
class Types_Field_Type_Date extends Types_Field_Abstract {

	/**
	 * @var string
	 */
	protected $placeholder;

	/**
	 * @var Types_Field_Setting_Bool_Interface
	 */
	protected $repeatable;


	/**
	 * @return string
	 */
	public function get_type() {
		return 'date';
	}


	/**
	 * Types_Field_Type_Date constructor.
	 *
	 * @param array $data (see getDefaultProperties() for used keys)
	 */
	public function __construct( $data ) {
		// merge user data with default data
		$data = array_merge( $this->get_default_properties(), $data );

		// slug / title / description / value
		parent::__construct( $data );

		$this->set_placeholder( $data['placeholder'] );
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
			'repeatable' => false,
		);
	}


	/**
	 * @param string|array|mixed $data
	 */
	protected function set_value( $data ) {
		if ( ! is_string( $data ) && ! is_array( $data ) ) {
			return;
		}

		$valid_data = array();

		foreach ( (array) $data as $value ) {
			if ( preg_match( '~^-?[1-9][0-9]*$~', $value ) ) {
				$valid_data[] = $value;
				continue;
			}

			if ( $value = strtotime( $value ) ) {
				$valid_data[] = $value;
			}
		}

		$this->value = ! empty( $valid_data )
			? $valid_data
			: null;
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
