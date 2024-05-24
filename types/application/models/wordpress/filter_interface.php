<?php

/**
 * Interface Types_Wordpress_Filter_Interface
 *
 * @since 2.3
 */
interface Types_Wordpress_Filter_Interface {

	/**
	 * @param string $tag
	 *
	 * @return void
	 */
	public function filter_state_store( $tag );


	/**
	 * @param string $tag
	 *
	 * @return void
	 */
	public function filter_state_restore( $tag );


	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_wysiwyg( $content );
}
