<?php

/**
 * Class Types_View_Decorator_Wysiwyg
 *
 * @since 2.3
 */
class Types_View_Decorator_Wysiwyg implements Types_Interface_Value_With_Params {

	/**
	 * @var Types_Wordpress_Filter_Interface
	 */
	private $filter;


	/**
	 * Types_View_Decorator_Wysiwyg constructor.
	 *
	 * @param Types_Wordpress_Filter_Interface $filter
	 */
	public function __construct(
		Types_Wordpress_Filter_Interface $filter
	) {
		$this->filter = $filter;
	}


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
		while ( is_array( $value ) ) {
			$value = array_shift( $value );
		}

		if ( empty( $value ) ) {
			return '';
		}

		$value = stripslashes( $value );

		remove_shortcode( 'playlist' );

		if ( array_key_exists( 'suppress_filters', $params )
			&& is_bool( $params['suppress_filters'] )
			&& $params['suppress_filters'] ) {
			$output = $this->filter->filter_wysiwyg( $value );
		} else {
			$has_autop_filter = has_filter( 'the_content', 'wpautop' );
			// Wpautop needs to be always applied for the field to make sense.
			// If suppress filters is true the autop is applied in that case.
			// So it also makes more sense to apply it if the filters aren't suppressed.
			if ( false === $has_autop_filter ) {
				add_filter( 'the_content', 'wpautop' );
			}

			$this->filter->filter_state_store( 'the_content' );
			$output = apply_filters( 'the_content', $value );
			$output = $this->keep_3rd_party_functionality( $output, $params );
			$this->filter->filter_state_restore( 'the_content' );

			if ( false === $has_autop_filter ) {
				remove_filter( 'the_content', 'wpautop' );
			}
		}

		$output = $this->legacy_fix_for_playlist_shortcode( $output );

		add_shortcode( 'playlist', 'wp_playlist_shortcode' );

		$css_class = isset( $params['class'] ) && ! empty( $params['class'] )
			? ' class="' . $params['class'] . '"'
			: '';

		$css_style = isset( $params['style'] ) && ! empty( $params['style'] )
			? ' style="' . $params['style'] . '"'
			: '';

		$output = ! empty( $css_class ) || ! empty( $css_style )
			? '<div' . $css_class . $css_style . '>' . $output . '</div>'
			: $output;

		return do_shortcode( $output );
	}


	/**
	 * @param string $output
	 * @param array $params
	 *
	 * @return string
	 */
	private function keep_3rd_party_functionality( $output, $params ) {
		// keep syntaxhighlighting functionaility if it's active
		if ( isset( $params['syntax_highlighter'] )
			&& $params['syntax_highlighter'] instanceof Types_Wordpress_3rd_Interface
			&& $params['syntax_highlighter']->is_active()
			&& strpos( $output, "&amp;#91;" ) !== false
			&& strpos( $output, "&amp;#93;" ) !== false
			&& strpos( $output, "<pre" ) !== false ) {
			//This is a syntax higlighting content
			$output = str_replace( "&amp;#91;", "[", $output );
			$output = str_replace( "&amp;#93;", "]", $output );
		}

		return $output;
	}


	/**
	 * Not sure if this is any longer needed or better: why we doing it for playlist
	 * but for no other shortcode. All the function does is replacing
	 * "&#8221;" (Right Double Quotation Mark) and "&#8243;" (Double Prime) with a single quote '
	 *
	 * (Seemed to be some really specific issue because there lots of cases where this fix wouldn't help)
	 * (issue report was on basecamp)
	 *
	 * @param string $output
	 *
	 * @return string
	 */
	private function legacy_fix_for_playlist_shortcode( $output ) {
		if ( preg_match_all( '/\[playlist[^\]]+\]/', $output, $matches ) ) {
			foreach ( $matches[0] as $one ) {
				$re = preg_replace( '/\[/', '\\[', $one );
				$re = preg_replace( '/\]/', '\\]', $re );
				$re = '/' . $re . '/';
				$one = preg_replace( '/\&\#(8221|8243);/', '\'', $one );
				$output = preg_replace( $re, $one, $output );
			}
		}

		return $output;
	}
}
