<?php

namespace OTGS\Toolset\Types\Wordpress;

/**
 * Gateway to WordPress post/user/term/comment meta functions.
 *
 * Extend at will, but keep it extremely simple.
 *
 * @since 3.4.2
 * @codeCoverageIgnore
 */
class Meta {

	/**
	 * @see \get_post_meta()
	 *
	 * @param int $post_id
	 * @param string $key
	 * @param bool $single
	 *
	 * @return mixed
	 */
	public function get_post_meta( $post_id, $key = '', $single = false ) {
		return get_post_meta( $post_id, $key, $single );
	}


	/**
	 * @see \update_post_meta()
	 *
	 * @param int $post_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 * @param string $prev_value
	 *
	 * @return bool|int
	 */
	public function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
	}


	/**
	 * @see \get_post_meta_by_id()
	 *
	 * @param int $meta_id
	 * @return bool|object
	 */
	public function get_post_meta_by_id( $meta_id ) {
		// This file may not be loaded in some contexts (e.g. REST requests).
		require_once ABSPATH . 'wp-admin/includes/post.php';

		return get_post_meta_by_id( $meta_id );
	}
}
