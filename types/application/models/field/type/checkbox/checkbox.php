<?php

/**
 * Class Types_Field_Type_Checkbox
 *
 * @since 2.3
 */
class Types_Field_Type_Checkbox extends Types_Field_Abstract {

	/**
	 * @var Types_Field_Part_Option
	 */
	protected $option;

	/**
	 * Display mode
	 *
	 * @var string
	 */
	protected $display_mode;

	/**
	 * @return string
	 */
	public function get_type() {
		return 'checkbox';
	}

	/**
	 * Types_Field_Type_Checkbox constructor.
	 *
	 * @param array $data
	 *   parent: 'title', 'slug', 'description', 'value',
	 */
	public function __construct( $data ) {
		$this->display_mode = isset( $data['data']['display'] ) ? $data['data']['display'] : Types_Field_Abstract::DISPLAY_MODE_DB;

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
	public function set_option( Types_Field_Part_Option $option ) {
		$this->option = $option;
	}

	/**
	 * @return Types_Field_Part_Option
	 */
	public function get_option() {
		return $this->option;
	}


	/**
	 * Gets display mode
	 *
	 * @return string
	 */
	public function get_display_mode() {
		return $this->display_mode;
	}
}
