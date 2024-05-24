<?php

/**
 * Class Types_Wordpress_Embed
 *
 * @since 2.3
 */
class Types_Wordpress_Embed implements Types_Wordpress_Embed_Interface {

	/**
	 * @param string $shortcode
	 *
	 * @return bool
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_embed/run_shortcode/
	 */
	public function run_shortcode( $shortcode ) {
		global $wp_embed;

		if( ! is_object( $wp_embed ) || ! method_exists( $wp_embed, 'run_shortcode' ) ) {
			return false;
		}

		return $wp_embed->run_shortcode( $shortcode );
	}
}
