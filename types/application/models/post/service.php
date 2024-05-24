<?php

/**
 * Class Types_Post_Service
 *
 * @since 2.3
 */
class Types_Post_Service {

	/**
	 * Returns WP_POST of $params['item'] | $params['post_id'] | $params['id'].
	 * If all keys not set it will return current WP_POST
	 *
	 * @param array $params
	 *
	 * @return null|WP_Post
	 */
	public function find_by_user_params( $params ) {
		if ( isset( $params['item'] ) ) {
			return $this->return_post_object_by_id( $params['item'] );
		}

		if ( isset( $params['post_id'] ) ) {
			return $this->return_post_object_by_id( $params['post_id'] );
		}

		if ( isset( $params['id'] ) ) {
			return $this->return_post_object_by_id( $params['id'] );
		}

		return $this->return_post_object_by_id( get_the_ID() );
	}

	/**
	 * @param int $id
	 *
	 * @return null|WP_Post
	 */
	public function find_by_id( $id ) {
		return $this->return_post_object_by_id( $id );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return WP_Post
	 */
	public function load_fields_to_post_object( WP_Post $post ) {
		// todo get rid of legacy use here
		if( ! function_exists( 'wpcf_admin_post_get_post_groups_fields' ) ) {
			require_once( WPCF_EMBEDDED_ABSPATH . '/includes/fields-post.php' );
		}

		$groups = wpcf_admin_post_get_post_groups_fields( $post );
		/** @noinspection PhpUndefinedFieldInspection */
		$post->fields = array(); // @phpstan-ignore-line
		foreach ( $groups as $group ) {
			if ( !empty( $group['fields'] ) ) {
				// Process fields
				foreach ( $group['fields'] as $k => $field ) {
					$data = null;
					if ( types_is_repetitive( $field ) ) {
						$data = wpcf_get_post_meta( $post->ID,
							wpcf_types_get_meta_prefix( $field ) . $field['slug'],
							false ); // get all field instances
					} else {
						$data = wpcf_get_post_meta( $post->ID,
							wpcf_types_get_meta_prefix( $field ) . $field['slug'],
							true ); // get single field instance
						// handle checkboxes which are one value serialized
						if ( $field['type'] === 'checkboxes' && !empty( $data ) ) {
							$data = maybe_unserialize( $data );
						}
					}
					if ( !is_null( $data ) ) {
						$post->fields[$k] = $data;
					}
				}
			}
		}

		return $post;
	}

	/**
	 * @param int $id
	 *
	 * @return null|WP_Post
	 */
	private function return_post_object_by_id( $id ) {
		if ( ! $id || ! $post = get_post( $id ) ) {
			return null;
		}

		return $post;
	}
}
