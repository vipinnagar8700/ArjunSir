<?php

/**
 * Class Types_View_Decorator_Link
 *
 * @since 2.3
 */
class Types_View_Decorator_Link implements Types_Interface_Value_With_Params {

	/**
	 *
	 * @param array|string $value
	 * @param array $params
	 *  'title' => set a custom title for the mailto link
	 *  'class' => add css class
	 *  'style' => add css style
	 *  'no_protocol' => true will remove protocol from title
	 *  'target' => add target attribute to link
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

		if( ! isset( $params['title'] ) && isset( $params['no_protocol'] ) && $params['no_protocol'] ) {
			$title_parts = parse_url( $title );
			$title = isset( $title_parts['host'] ) ? $title_parts['host'] : '';
			$title .= isset( $title_parts['path'] ) ? $title_parts['path'] : '';
			$title .= isset( $title_parts['query'] ) ? '?' . $title_parts['query'] : '';
		}

		$target = isset( $params['target'] ) && ! empty( $params['target'] )
			? ' target="' . $params['target'] . '"'
			: '';

		// Links opening on a new tab should include rel="noopener" attributes.
		// See https://hackernoon.com/prevent-reverse-tabnabbing-attacks-with-proper-noopener-noreferrer-and-nofollow-attribution-z14d3zbh
		$rel = isset( $params['target'] ) && '_blank' === $params['target']
			? ' rel="noopener"'
			: '';

		$css_class = isset( $params['class'] ) && ! empty( $params['class'] )
			? ' class="' . $params['class'] . '"'
			: '';

		$css_style = isset( $params['style'] ) && ! empty( $params['style'] )
			? ' style="' . $params['style'] . '"'
			: '';

		return '<a href="'  . $value . '" '
		       . 'title="'. $title . '"'
			   . $target
			   . $rel
		       . $css_class
		       . $css_style
		       . '>' . $title . '</a>';
	}
}
