<?php

/**
 * This add extra css class to the output
 * Used for types shortcode using attribute "output='html'"
 *
 * Class Types_View_Decorator_Output_HTML
 */
class Types_View_Decorator_Output_HTML implements Types_Interface_Value_With_Context {

	/**
	 * @param string $value
	 * @param array $params
	 * @param null|Types_Field_Interface $field
	 * @param boolean $dont_show_name Forces not to show the name
	 * @param boolean $dont_wrap Forces not to wrap the content
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array(), $field = null, $dont_show_name = false, $dont_wrap = false ) {
		if( ! $field instanceof Types_Field_Interface ) {
			return $value;
		}

		$show_name = '';
		if ( ! $dont_show_name && isset( $params['show_name'] ) && $params['show_name'] ) {
			$class_field_name = 'wpcf-field-name ' .
				'wpcf-field-' . $field->get_type() . '-name ' .
				'wpcf-field-' . $field->get_slug() . '-name';
			$show_name = '<span class="' . $class_field_name . '">' . $field->get_title() . ':</span> ';
		}
		if( ! preg_match( '#(\<[a-z])(.*)(\>)#', $value ) ) {
			// the value does not contain any html
			// (e.g. this wouldn't make sense for fields like 'checkboxes')
			$class_field_value = 'wpcf-field-value ' .
			                     'wpcf-field-' . $field->get_type() . '-value ' .
			                     'wpcf-field-' . $field->get_slug() . '-value';

			$value = '<span class="' . $class_field_value . '">' . $value . '</span>';
		}

		if ( $dont_wrap ) {
			return $show_name . $value;
		}
		return '<div id="wpcf-field-' . $field->get_slug() .
		       '" class="wpcf-field-' . $field->get_type() . ' wpcf-field-' . $field->get_slug() . '">' .
		       $show_name . $value .
		       '</div>';
	}
}
