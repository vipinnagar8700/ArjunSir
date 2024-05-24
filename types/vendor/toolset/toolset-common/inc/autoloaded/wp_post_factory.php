<?php

namespace OTGS\Toolset\Common;

/**
 * Factory for WP_Post, to be used in dependency injection.
 *
 * @since 3.0.4
 * @package OTGS\Toolset\Common
 */
class WpPostFactory {

	/**
	 * @param int $post_id
	 *
	 * @return false|\WP_Post
	 */
	public function load( $post_id ) {
		return \WP_Post::get_instance( $post_id );
	}

}