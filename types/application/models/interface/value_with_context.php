<?php

interface Types_Interface_Value_With_Context extends Types_Interface_Value {

	/**
	 * @param string $value
	 * @param array $params
	 * @param null|Types_Field_Interface $field
	 * @param boolean $dont_show_name Forces not to show the name
	 * @param boolean $dont_wrap Forces not to wrap the content
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array(), $field = null, $dont_show_name = false, $dont_wrap = false );

}
