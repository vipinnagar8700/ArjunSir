<?php

/**
 * @since 2.3
 */
class Types_Field_Group_Repeatable_Wpml_Post_Md5_Key {

	const POST_META_KEY = '_toolset-rfg-wpml-post-key-addition';

	/**
	 * Collection of generated rfg hash codes by this object
	 * to prevent doing the same generation twice per request.
	 *
	 * @var array
	 */
	private $posts_hash_incl_rfg = array();


	/**
	 * WPML generates a key of post content to identify changes.
	 * We need to add our rfg content to this key.
	 *
	 * @action wpml_post_md5_key
	 *
	 * @param string $key
	 * @param WP_Post $post
	 *
	 * @return bool|string
	 */
	public function modify_key_for_post( $key, $post ) {
		if ( ! $post instanceof WP_Post ) {
			// seems that the filter structure changed, check filter "wpml_post_md5_key"
			return $key;
		}

		if ( isset( $this->posts_hash_incl_rfg[ $post->ID ] ) ) {
			// we already generated the key for this post
			return $this->posts_hash_incl_rfg[ $post->ID ];
		}

		// try to generate the key on post edit page
		if ( $modified_key = $this->modify_key_on_post_edit_page( $key, $post ) ) {
			return $this->posts_hash_incl_rfg[ $post->ID ] = $modified_key;
		}

		// try to generate the key by rfg object
		if ( $modified_key = $this->modify_key_by_loading_rfg_object_tree( $key, $post ) ) {
			return $this->posts_hash_incl_rfg[ $post->ID ] = $modified_key;
		}

		// no adjustments neded for $post
		return $this->posts_hash_incl_rfg[ $post->ID ] = $key;
	}


	/**
	 * On the post edit screen we can easily check the $_POST vars.
	 * This is the most efficent way as we don't need to load all objects.
	 *
	 * @param string $key
	 *
	 * @param WP_Post $post
	 *
	 * @return bool|string
	 */
	private function modify_key_on_post_edit_page( $key, WP_Post $post ) {
		if ( ! isset( $_REQUEST['post_ID'], $_REQUEST['types-repeatable-group'] ) ) {
			return false;
		}

		if ( array_key_exists( $post->ID, $_REQUEST['types-repeatable-group'] ) ) {
			// repeatable group is stored... no need to change the post key
			return $key;
		}

		if ( $rfg_hash = $this->create_rfg_hash( $_REQUEST['types-repeatable-group'] ) ) {
			// hash created, store it and append to $key
			$this->store_rfg_hash( $rfg_hash, $_REQUEST['post_ID'] );
			$key .= $rfg_hash;
		} else {
			// no hash (rfg has no items), delete stored one
			$this->delete_stored_rfg_hash( $_REQUEST['post_ID'] );
		}

		return $key;
	}


	/**
	 * This method does not need more than the $post->ID to get build the modify the key.
	 * BUT it's a very heavy task as all data needs to be loaded.
	 *
	 * @param string $key
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	private function modify_key_by_loading_rfg_object_tree( $key, WP_Post $post ) {
		if ( $rfg_hash = $this->get_stored_rfg_hash( $post->ID ) ) {
			// use cached key
			return $key . $rfg_hash;
		}

		$types_service = new Types_Post_Builder();
		$types_service->set_wp_post( $post );
		$types_service->load_assigned_field_groups( 9999 );
		$types_post = $types_service->get_types_post();

		$rfg_items_array = array();
		foreach ( $types_post->get_field_groups() as $field_group ) {
			if ( ! $rfgs = $field_group->get_repeatable_groups() ) {
				// no rfgs
				continue;
			}
			foreach ( $rfgs as $rfg ) {
				$rfg_items_array = $this->rfg_object_tree_to_array( $rfg_items_array, $rfg );
			}
		}

		if ( empty( $rfg_items_array ) ) {
			// no rfg items
			return $key;
		}

		// return key with rfg hash added
		return $key . $this->create_rfg_hash( $rfg_items_array );
	}


	/**
	 * Function to convert the rfg object tree [group] > [nested group] / [items] to an array
	 *
	 * @param array $rfg_items_array
	 * @param Types_Field_Group_Repeatable $rfg
	 *
	 * @return array
	 */
	private function rfg_object_tree_to_array( $rfg_items_array, Types_Field_Group_Repeatable $rfg ) {
		if ( $nested_rfgs = $rfg->get_repeatable_groups() ) {
			foreach ( $nested_rfgs as $nested_rfg ) {
				$rfg_items_array = $this->rfg_object_tree_to_array( $rfg_items_array, $nested_rfg );
			}
		}
		if ( $rfg->get_posts() ) {
			foreach ( $rfg->get_posts() as $rfg_item ) {
				foreach ( $rfg_item->get_fields() as $field ) {
					$rfg_items_array[ $rfg_item->get_wp_post()->ID ][ $field->get_slug() ] = $field->get_value();
				}
			}
		}

		return $rfg_items_array;
	}


	/**
	 * Get all values of an array (no matter how deep the array is), stripslashes their values,
	 * sort the values alphabetical and finally create a md5 hash of them.
	 *
	 * This way two (or more arrays) arrays can be compared for same values.
	 *
	 * Example:
	 *
	 * Input #1
	 * ------------
	 * array(
	 *   '38' => array( 'custom-field-a' => 'Another User Value' ),
	 *   '49' => array( 'custom-field-b' => 'Users\'s value' )
	 * );
	 *
	 * Input #2
	 * ------------
	 * array(
	 *   '49' => array( 'custom-field-b' => array( 0 => 'Users's value' ) ),
	 *   '38' => array( 'custom-field-a' => array( 0 => 'Another User Value' ) )
	 * );
	 *
	 * Same Result for Data #1 and Data #2, because both holding the same values.:
	 * -------
	 * return md5( 'Another User Value Users's value' );
	 *
	 * @param array $input
	 *
	 * @return false|string
	 */
	public function create_md5_hash_of_array_values( array $input ) {
		if ( empty( $input ) ) {
			return false;
		}

		// let's collect all values of the rfg to a single string
		$string_all_values = '';
		array_walk_recursive( $input, static function ( $value ) use ( &$string_all_values ) {
			if ( ! empty( $value ) && ( is_string( $value ) || is_numeric( $value ) ) ) {
				$string_all_values .= '#;#' . ( $value );
			}
		} );

		// remove extra slashes
		$string_all_values = stripslashes( $string_all_values );

		// let's create an array again to sort the elements
		if ( ! empty( $string_all_values ) ) {
			$flat_array = explode( '#;#', $string_all_values );
			asort( $flat_array );

			// finally make a string of the sorted array again
			$string_all_values = implode( ' ', $flat_array );
		}

		// return hash (even if there is no value)
		return md5( $string_all_values );
	}


	/**
	 * Extend $this->create_md5_hash_of_array_values()
	 * by adding prefix 'types-repeatable-groups-' and a ';' to the end.
	 *
	 * @param array $input
	 *
	 * @return false|string
	 */
	private function create_rfg_hash( array $input ) {
		if ( ! $md5_hash_of_array = $this->create_md5_hash_of_array_values( $input ) ) {
			return false;
		}

		return 'types-repeatable-groups-' . $md5_hash_of_array . ';';
	}


	/**
	 * Way to store the generarted rfg hash
	 * for preventing unnecessary heavy lifts
	 *
	 * @param string $rfg_hash
	 * @param int $post_id
	 */
	private function store_rfg_hash( $rfg_hash, $post_id ) {
		update_post_meta( $post_id, self::POST_META_KEY, $rfg_hash );
	}


	/**
	 * Get the stored rfg hash
	 *
	 * @param int $post_id
	 *
	 * @return mixed
	 */
	private function get_stored_rfg_hash( $post_id ) {
		return get_post_meta( $post_id, self::POST_META_KEY, true );
	}


	/**
	 * Delete the stored rfg hash
	 *
	 * @param int $post_id
	 */
	private function delete_stored_rfg_hash( $post_id ) {
		delete_post_meta( $post_id, self::POST_META_KEY );
	}

}
