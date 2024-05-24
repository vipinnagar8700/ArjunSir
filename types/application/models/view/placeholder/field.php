<?php

/**
 * @since 2.3
 */
class Types_View_Placeholder_Field implements Types_View_Placeholder_Interface {

	/**
	 * @param string|string[]|mixed $string
	 * @param null|Types_Field_Interface $field
	 * @param string $field_single_value
	 * @param array $user_params
	 *
	 * @return string|string[]|mixed
	 */
	public function replace( $string = '', $field = null, $field_single_value = null, $user_params = array() ) {
		if (
			( ! is_string( $string ) && ! is_array( $string ) )
			|| ! $field instanceof Types_Field_Interface
		) {
			return $string;
		}

		if ( $field_single_value === null ) {
			$field_single_value = $field->get_value_filtered( $user_params );
			$field_single_value = is_array( $field_single_value )
				? array_shift( $field_single_value )
				: $field_single_value;
		}

		$field_title = $field->get_title();

		if ( function_exists( 'wpcf_translate' ) ) {
			$field_title = wpcf_translate( 'field ' . $field->get_slug() . ' name', $field_title );
		}

		$supported_replacements = array(
			'FIELD_NAME' => $field_title,
			'FIELD_VALUE' => $field_single_value,
		);

		return str_replace(
			array_keys( $supported_replacements ),
			array_values( $supported_replacements ),
			$string
		);
	}
}
