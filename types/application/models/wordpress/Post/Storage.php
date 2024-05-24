<?php

namespace OTGS\Toolset\Types\Wordpress\Post;

use WP_Post;
use wpdb;

/**
 * Gives some extra functioniality regarding loading/manipulating posts.
 */
class Storage {

	/** @var wpdb */
	private $wpdb;


	/**
	 * Storage constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}


	/**
	 * Get post by id
	 *
	 * @param int $id
	 *
	 * @return WP_Post|null
	 */
	public function getPostById( $id ) {
		return get_post( $id );
	}


	/**
	 * Get post by GUID
	 *
	 * @param string $guid
	 *
	 * @return WP_Post|null
	 */
	public function getPostByGUID( $guid ) {
		$wpdb = $this->wpdb;

		// On creation WP uses &#038; for &, but on WP Export/Import & becomes &amp;
		$guid_amp = str_replace( '&#038;', '&amp;', $guid );
		$guid_038 = str_replace( '&amp;', '&#038;', $guid );
		$guid_no_html = html_entity_decode( $guid );

		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare( "
				SELECT ID
				FROM $wpdb->posts
				WHERE guid LIKE %s
				OR guid LIKE %s
				OR guid LIKE %s
				OR guid LIKE %s
				LIMIT 1",
				sanitize_text_field( $guid ),
				sanitize_text_field( $guid_amp ),
				sanitize_text_field( $guid_038 ),
				sanitize_text_field( $guid_no_html )
			)
		);

		if ( ! $post_id ) {
			// no id by the guid found...
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			// this would be really strange after we successfully get the ID
			return null;
		}

		return $post;
	}


	/**
	 * Get post by title
	 *
	 * @param string $title
	 * @param string $post_type
	 *
	 * @return null|WP_Post
	 */
	public function getPostByTitle( $title, $post_type = 'page' ) {
		$posts = get_posts(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		if ( ! empty( $posts ) ) {
			return $posts[0];
		}
		return null;
	}


	/**
	 * Wrapper for wp_delete_post()
	 *
	 * @param int $post_id
	 */
	public function deletePostById( $post_id ) {
		wp_delete_post( $post_id );
	}
}
