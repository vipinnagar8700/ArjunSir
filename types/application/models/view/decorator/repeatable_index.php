<?php

/**
 * Manages repeating fields when displayed using an 'index' attribute in their [types] shortcode,
 * so they need to render a single value.
 *
 * If the index value is not numeric, or is outside of the range of field values, returns an empty string.
 *
 * @since 3.0
 */
class Types_View_Decorator_Index implements Types_Interface_Value_With_Params {

	/**
	 * @param string|string[] $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array() ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$index = toolset_getarr( $params, 'index' );

		if ( ! is_numeric( $index ) ) {
			return '';
		}

		$index = (int) $index;

		if ( isset( $value[ $index ] ) ) {
			return $value[ $index ];
		}

		return '';
	}
}
