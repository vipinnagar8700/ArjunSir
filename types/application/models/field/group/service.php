<?php

class Types_Field_Group_Service {

	const OPTION_FIELDS = '_wp_types_group_fields';


	/**
	 * Detects group assigned Post Type
	 *  - for Field Group it will return the assigned post type
	 *  - for Repeatable Group it will return the post type (->get_post_type())
	 *  - if there are multiple post types assigned, the method returns null
	 *
	 * @param Toolset_Field_Group $group
	 *
	 * @return null|IToolset_Post_Type
	 */
	public function get_unique_assigned_post_type( Toolset_Field_Group $group ) {
		$post_type_service = Toolset_Post_Type_Repository::get_instance();

		if ( $group instanceof Toolset_Field_Group_Post ) {
			// field group (not a repeatable group)
			$assigned_post_type = $group->get_assigned_to_types();
			if ( $assigned_post_type ) {
				// field group (not a repeatable group)
				if ( count( $assigned_post_type ) !== 1 ) {
					return null;
				}

				$assigned_post_type = array_shift( $assigned_post_type );

				if ( $post_type = $post_type_service->get( $assigned_post_type ) ) {
					return $post_type;
				}

				return null;
			}
		}

		if ( $group instanceof Types_Field_Group_Repeatable ) {
			// repeatable group
			return $post_type_service->get( $group->get_post_type() );
		}

		return null;
	}


	/**
	 * This function will unnasign a field from all field groups.
	 *
	 * @param string $field_slug
	 */
	public function unassign_field_from_all_field_groups( $field_slug ) {
		global $wpdb;

		$field_slug = sanitize_text_field( $field_slug );

		$postmeta_with_slug = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->postmeta WHERE `meta_key` = %s AND `meta_value` LIKE %s",
				self::OPTION_FIELDS,
				'%' . $field_slug . '%'
			)
		);

		foreach ( $postmeta_with_slug as $postmeta ) {
			$fields = array_filter( explode( ',', $postmeta->meta_value ) );

			if ( ! $key = array_search( $field_slug, $fields, true ) ) {
				// field could not be found (shouldn't happen - see mysql query)
				continue;
			}

			// remove field
			unset( $fields[ $key ] );

			// update post meta
			update_post_meta( $postmeta->post_id, $postmeta->meta_key, implode( ',', $fields ) );
		}
	}


	/**
	 * This function is used to get the difference between all fields (post, user, term) and the given $fields.
	 * This function respects nested groups, if it's part of $fields.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function diff_of_all_and_given_fields( $fields ) {
		$all_slugs = array();

		$all_fields = array_merge(
			get_option( Toolset_Field_Definition_Factory_Post::FIELD_DEFINITIONS_OPTION, array() ),
			get_option( Toolset_Field_Definition_Factory_User::FIELD_DEFINITIONS_OPTION, array() ),
			get_option( Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION, array() )
		);

		if ( ! empty( $all_fields ) ) {
			foreach ( $all_fields as $field ) {
				$all_slugs[] = $field['slug'];
			}
		}

		if ( ! is_array( $fields ) ) {
			return $all_slugs;
		}

		return $this->diff_of_all_and_given_fields_loop( $fields, $all_slugs );
	}


	/**
	 * Note: This method is implemented on the subclass Types_Field_Group_Repeatable_Service
	 * but assumed here as well. Keeping it this way to avoid complicated refactoring right now
	 * and make PHPStan happy.
	 *
	 * @param mixed $field
	 *
	 * @return false|Types_Field_Group_Repeatable
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function get_object_from_prefixed_string( $field ) {
		return false;
	}


	private function diff_of_all_and_given_fields_loop( $fields, $all_slugs ) {
		foreach ( $fields as $field ) {
			if ( $repeatable_group = $this->get_object_from_prefixed_string( $field ) ) {
				// repeatable group
				$repeatable_group_fields = get_post_meta( $repeatable_group->get_id(), self::OPTION_FIELDS, true );
				$repeatable_group_fields = explode( ',', trim( $repeatable_group_fields, ',' ) );

				$all_slugs = $this->diff_of_all_and_given_fields_loop( $repeatable_group_fields, $all_slugs );
				continue;
			}
			// field
			$all_slugs = array_diff( $all_slugs, array( $field ) );
		}

		return $all_slugs;
	}
}
