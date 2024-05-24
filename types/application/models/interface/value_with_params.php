<?php

interface Types_Interface_Value_With_Params extends Types_Interface_Value {

	/**
	 * @param string $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array() );

}
