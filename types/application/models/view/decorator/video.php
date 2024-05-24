<?php

/**
 * Class Types_View_Decorator_Video
 *
 * Takes a value, build Wordpress [video] shortcode, and returns the rendered shortcode
 *
 * @since 2.3
 */
class Types_View_Decorator_Video implements Types_Interface_Value_With_Params {

	/**
	 * This function uses our legacy code to build up a wordpress [video] shortcode
	 *
	 * @param array|string $value
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

		$width = isset( $params['width'] )
			? intval( $params['width'] )
			: false;

		if ( ! $width ) {
			global $content_width;
			$width = ! empty( $content_width ) ? $content_width : 450;
		}

		$height = isset( $params['height'] )
			? intval( $params['height'] )
			: false;

		$height = $height
			? ' height="' . $height . '"'
			: '';

		$poster = isset( $params['poster'] )
			? ' poster="' . $params['poster'] . '"'
			: '';

		$loop = isset( $params['loop'] )
			? ' loop="' . $params['loop'] . '"'
			: '';

		$autoplay = isset( $params['autoplay'] )
			? ' autoplay="' . $params['autoplay'] . '"'
			: '';

		$preload = isset( $params['preload'] )
			? ' preload="' . $params['preload'] . '"'
			: '';


		$shortcode = '[video src="' . $value . '" width="' . $width . '"'
		             . $height . $poster . $loop . $autoplay . $preload . ']';
		$output    = do_shortcode( $shortcode );

		return ! empty( $output ) ? $output : '';
	}
}
