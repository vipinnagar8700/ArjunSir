<?php

/**
 * Class Types_Field_Type_Legacy
 *
 * This is for legacy fields which are not ported to the new structure yet.
 *
 * Note: ALL Types fields are ported, but some fields like "Address" field of
 * Toolset Maps not. For those fields nothing will change.
 *
 * @since 2.3
 */
class Types_Field_Type_Legacy extends Types_Field_Abstract {

	/**
	 * @var string
	 */
	private $type;

	/**
	 * We don't care about the data structure for legacy fields
	 * @var array
	 */
	private $data_raw;

	/**
	 * Types_Field_Type_Legacy constructor.
	 *
	 * @param array $data (see getDefaultProperties() for used keys)
	 *
	 * @throws Exception
	 */
	public function __construct( array $data ) {
		if( ! isset( $data['type'] ) ) {
			throw new \Exception( 'No legacy field without a defined "type".' );
		}

		$this->type = $data['type'];
		$this->value = isset( $data['value'] ) ? $data['value'] : array();
		$this->data_raw = $data;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	public function get_data_raw(){
		return $this->data_raw;
	}

}