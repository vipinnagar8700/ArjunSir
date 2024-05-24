<?php

/**
 * Class Types_Wordpress_Filter
 *
 * @since 2.3
 */
class Types_Wordpress_Filter implements Types_Wordpress_Filter_Interface {

	private $filter_states = array();

	/** @var bool */
	private $filter_state_native_support;


	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_wysiwyg( $content ) {
		$the_content_filters = array(
			'wptexturize',
			'convert_smilies',
			'convert_chars',
			'wpautop',
			'shortcode_unautop',
			'prepend_attachment',
			'capital_P_dangit',
			'do_shortcode',
		);

		foreach ( $the_content_filters as $func ) {
			if ( function_exists( $func ) ) {
				$content = $func( $content );
			}
		}

		return $content;
	}


	/**
	 * Used for recording a current item of the callbacks in $wp_filter[ $tag ] and restoring it
	 * after applying a filter recursively.
	 *
	 * Workaround for https://core.trac.wordpress.org/ticket/17817.
	 *
	 * From WordPress 4.7 above, this does nothing.
	 *
	 * @param string $tag
	 *
	 * @codeCoverageIgnore will never be touched again as it's only used for WP < 4.7
	 *
	 * @return void
	 */
	public function filter_state_store( $tag ) {
		if ( $this->filter_state_native_support() ) {
			return;
		}

		global $wp_filter;

		if ( isset( $wp_filter[ $tag ] ) ) {
			$this->filter_states[ $tag ] = current( $wp_filter[ $tag ] );
		}
	}


	/**
	 * Restore the previously stored state (see filterStateStore( $tag ))
	 *
	 * From WordPress 4.7 above, this does nothing.
	 *
	 * @param string $tag
	 *
	 * @codeCoverageIgnore will never be touched again as it's only used for WP < 4.7
	 *
	 * @return void
	 */
	public function filter_state_restore( $tag ) {
		if ( $this->filter_state_native_support() ) {
			return;
		}

		global $wp_filter;

		if ( ! isset( $wp_filter[ $tag ], $this->filter_states[ $tag ] ) ) {
			return;
		}

		reset( $wp_filter[ $tag ] );
		/** @noinspection TypeUnsafeComparisonInspection */
		while ( $this->filter_states[ $tag ] != current( $wp_filter[ $tag ] ) ) {
			next( $wp_filter[ $tag ] );
		}
	}


	/**
	 * Filter for slug
	 *
	 * @param string $slug
	 *
	 * @return string string
	 */
	public function filter_slug( $slug ) {
		return sanitize_title( $slug );
	}


	/**
	 * @return bool
	 *
	 * @codeCoverageIgnore will never be touched again as it's only used for WP < 4.7
	 */
	private function filter_state_native_support() {
		if ( $this->filter_state_native_support !== null ) {
			return $this->filter_state_native_support;
		}

		global $wp_version;

		if ( version_compare( $wp_version, '4.6.9', '>' ) ) {
			$this->filter_state_native_support = true;

			return true;
		}

		$this->filter_state_native_support = false;

		return false;
	}
}
