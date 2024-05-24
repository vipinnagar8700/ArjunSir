<?php

/**
 * Class Types_View_Decorator_Email
 *
 * @since 2.3
 */
class Types_View_Decorator_Email implements Types_Interface_Value_With_Params {

	/**
	 *
	 * @param array|string $value
	 * @param array $params
	 *  'title' => set a custom title for the mailto link
	 *  'class' => add css class
	 *  'style' => add css style
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array() ) {
		while( is_array( $value ) ) {
			$value = array_shift( $value );
		}

		if ( empty( $value ) ) {
			return '';
		}

		$title = isset( $params['title'] ) && ! empty( $params['title'] )
			? $params['title']
			: $value;

		$css_class = isset( $params['class'] ) && ! empty( $params['class'] )
			? ' class="' . $params['class'] . '"'
			: '';

		$css_style = isset( $params['style'] ) && ! empty( $params['style'] )
			? ' style="' . $params['style'] . '"'
			: '';

		return '<a href="mailto:'  . $value . '" '
		       . 'title="'. $title . '"'
		       . $css_class
		       . $css_style
		       . '>' . $title . '</a>';
	}
}
