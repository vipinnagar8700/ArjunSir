<?php

/**
 * @since 2.3
 */
class Types_View_Decorator_Audio implements Types_Interface_Value_With_Params {

	/**
	 * This function uses our legacy code to build up a wordpress [audio] shortcode
	 *
	 * @param string|string[] $value
	 * @param array $params
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

		$shortcode = sprintf( '[audio src="%s"', $value );

		// options loop, autoplay
		foreach ( array( 'loop', 'autoplay' ) as $key ) {
			if ( ! empty( $params[ $key ] ) && preg_match( '/^(on|1|true)$/', $params[ $key ] ) ) {
				$shortcode .= sprintf( ' %s="on"', $key );
			}
		}

		// option preload
		if ( ! empty( $params['preload'] ) ) {
			if ( preg_match( '/^(on|1|true|auto)$/', $params['preload'] ) ) {
				$shortcode .= ' preload="auto"';
			} elseif ( $params['preload'] === 'metadata' ) {
				$shortcode .= ' preload="metadata"';
			}
		}

		$shortcode .= ']';

		return do_shortcode( $shortcode );
	}
}
