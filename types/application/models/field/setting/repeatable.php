<?php

/**
 * @since 2.3
 */
class Types_Field_Setting_Repeatable implements Types_Field_Setting_Bool_Interface {

	private $repeatable = false;


	/**
	 * Types_Field_Setting_Repeatable constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		if ( ! isset( $data['repeatable'] ) ) {
			return;
		}

		$this->set_repeatable( $data['repeatable'] );
	}


	/**
	 * @param string|bool|int|mixed $is_repeatable
	 */
	private function set_repeatable( $is_repeatable ) {
		if ( ! is_string( $is_repeatable ) && ! is_bool( $is_repeatable ) && ! is_int( $is_repeatable ) ) {
			return;
		}

		$this->repeatable = (bool) $is_repeatable;
	}


	/**
	 * @return bool
	 */
	public function is_true() {
		return $this->repeatable;
	}
}
