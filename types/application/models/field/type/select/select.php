<?php

/**
 * Class Types_Field_Type_Radio
 *
 * @since 2.3
 */
class Types_Field_Type_Select extends Types_Field_Abstract {
	/**
	 * @var Types_Field_Part_Option[]
	 */
	protected $options;

	/**
	 * @return string
	 */
	public function get_type() {
		return 'select';
	}

	/**
	 * Types_Field_Type_Select constructor.
	 *
	 * @param array $data (see getDefaultProperties() for used keys)
	 */
	public function __construct( $data ) {
		// merge user data with default data
		$data = array_merge( $this->get_default_properties(), $data );

		// slug / title / description / value
		parent::__construct( $data );
	}

	/**
	 * @return array
	 */
	private function get_default_properties() {
		return array(
			'slug' => null,
			'title' => null,
			'description' => null,
			'value' => null
		);
	}

	/**
	 * @param Types_Field_Part_Option $option
	 */
	public function add_option( Types_Field_Part_Option $option ) {
		$this->options[$option->get_id()] = $option;
	}

	/**
	 * @return Types_Field_Part_Option[]
	 */
	public function get_options() {
		return $this->options;
	}
}