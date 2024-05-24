<?php

/**
 * Class Types_View_Decorator_Embed
 *
 * Takes a value, build Wordpress [embed] shortcode, and returns the rendered shortcode
 *
 * @since 2.3
 */
class Types_View_Decorator_Embed implements Types_Interface_Value_With_Params {

	/**
	 * @var Types_Wordpress_Embed_Interface
	 */
	private $wp_embed;

	/**
	 * Types_View_Decorator_Embed constructor.
	 *
	 * @param Types_Wordpress_Embed_Interface $embed
	 */
	public function __construct(
		Types_Wordpress_Embed_Interface $embed
	) {
		$this->wp_embed = $embed;
	}

	/**
	 * This function uses our legacy code to build up a wordpress [audio] shortcode
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

		$output = $this->wp_embed->run_shortcode( '[embed width="' . $width . '"' . $height . ']' . $value . '[/embed]' );

		return ! empty( $output ) ? $output : '';
	}
}
