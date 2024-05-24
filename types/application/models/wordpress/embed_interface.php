<?php

/**
 * Interface Types_Wordpress_Embed_Interface
 *
 * @since 2.3
 */
interface Types_Wordpress_Embed_Interface {

	/**
	 * @param string $shortcode
	 *
	 * @return mixed
	 */
	public function run_shortcode( $shortcode );

}
