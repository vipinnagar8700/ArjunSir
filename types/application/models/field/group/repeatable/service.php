<?php

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * (Note: This mainly allows us to extract some stuff in legacy code).
 *
 * @since 2.3
 */
class Types_Field_Group_Repeatable_Service extends Types_Field_Group_Service {


	/**
	 * Get the id of an prefixed string
	 *
	 * @param string $string
	 *
	 * @return int|false
	 */
	public function get_id_from_prefixed_string( $string ) {
		if ( ! is_string( $string ) ) {
			// no string
			return false;
		}

		if ( strpos( $string, Types_Field_Group_Repeatable::PREFIX ) === false ) {
			// no repeatable group
			return false;
		}

		// get id
		$id = str_replace( Types_Field_Group_Repeatable::PREFIX, '', $string );

		if ( empty( $id ) || ! is_numeric( $id ) ) {
			// id is not a number
			return false;
		}

		return (int) $id;
	}


	/**
	 * Group fields are stored on "_wp_types_group_fields" in this format "field_x, _repeatable_group_%ID% , field_y"
	 * This function allow to check if a field is a link to a repeatable group "_repeatable_group_%ID%"
	 * and returns the group %ID%.
	 *
	 * @param string $string
	 * @param null|WP_Post $parent_post
	 * @param int $depth Controls how deep nested rfgs should be loaded.
	 *     Be careful with this. Loading nested groups can involve lots of posts.
	 *
	 * @return false|Types_Field_Group_Repeatable
	 */
	public function get_object_from_prefixed_string( $string, $parent_post = null, $depth = 1 ) {
		if ( ! $id = $this->get_id_from_prefixed_string( $string ) ) {
			return false;
		}

		if ( ! $repeatable_group = $this->get_object_by_id( $id, $parent_post, $depth ) ) {
			// no valid id in string
			return false;
		}

		return $repeatable_group;
	}


	/**
	 * Returns Toolset_Field_Group for the given id of repeatable field group. Retuns false if the ID
	 * is belongs to no post or a post which does not reflect a repeatable field group.
	 *
	 * @param int $id
	 *
	 * @param WP_Post|null $parent_post To load items of RFG the associated post is necessary
	 * @param int $depth Controls how deep nested rfgs should be loaded.
	 *                   Be careful with this. Loading nested groups can involve lots of posts.
	 *
	 * @return false|Types_Field_Group_Repeatable
	 */
	public function get_object_by_id( $id, WP_Post $parent_post = null, $depth = 1 ) {
		do_action( 'toolset_do_m2m_full_init' );

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// m2m relationships not active
			return false;
		}

		$post_object = get_post( $id );

		if ( ! $post_object instanceof WP_Post ) {
			return false;
		}

		try {
			$rfg_mapper = new Types_Field_Group_Repeatable_Mapper_Legacy();
			$group = $rfg_mapper->find_repeatable_group_by_post(
				$post_object,
				$depth,
				$parent_post,
				$this->get_wpml(),
				Toolset_Post_Type_Repository::get_instance()
			);

			// Missing field group. This means a problem with database integrity but let's not fail ungracefully here.
			if ( false === $group ) {
				return false;
			}

			// PHP 5.5 compatibility.
			$slug = $group->get_slug();
			if ( empty( $slug ) ) {
				// invalid group. there shouldn't be a group without a slug.
				return false;
			}

			return $group;
		} catch ( Exception $e ) {
			// just log the error and return false
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( 'Error while fetching rfg with id "' . $id . '": ' . $e->getMessage() );

			return false;
		}
	}


	/**
	 * Deletes a repeatable field group.
	 * Includes:
	 * - post for rfg
	 * - post type of rfg
	 * - posts of the post type
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param boolean $convert_to_relationship if true: converts the RFG to a relationship, else delete
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function delete( Types_Field_Group_Repeatable $repeatable_group, $convert_to_relationship = false ) {
		// Get Fields to delete possible nested repeatable group fields
		$fields = get_post_meta( $repeatable_group->get_id(), Toolset_Field_Group::POSTMETA_FIELD_SLUGS_LIST, true );
		$fields = explode( ',', $fields );

		foreach ( $fields as $field_slug ) {
			if ( ! $nested_repeatable_group = $this->get_object_from_prefixed_string( $field_slug ) ) {
				// no repeatabale group
				continue;
			}

			// delete repeatable group
			$this->delete( $nested_repeatable_group, $convert_to_relationship );
		}

		// Delete Post type
		$custom_types = get_option( Toolset_Post_Type_Repository::POST_TYPES_OPTION_NAME, array() );

		if ( ! isset( $custom_types[ $repeatable_group->get_slug() ] ) ) {
			// the cpt of the group does not exist
			return false;
		}

		// Deletes or converts relationship.
		$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$relationship = $definition_repository->get_definition( $repeatable_group->get_slug() );
		if ( null === $relationship ) {
			throw new RuntimeException( 'Error loading Relationship Definition' );
		}

		if ( $convert_to_relationship ) {
			if ( isset( $custom_types[ $repeatable_group->get_slug() ]['is_repeating_field_group'] ) ) {
				// remove the "RFG" mark from the CPT
				unset( $custom_types[ $repeatable_group->get_slug() ]['is_repeating_field_group'] );
			}

			// make cpt visible in the admin menues
			$custom_types[ $repeatable_group->get_slug() ]['public'] = 'public';
			$custom_types[ $repeatable_group->get_slug() ]['show_ui'] = '1';
			$custom_types[ $repeatable_group->get_slug() ]['show_in_menu'] = '1';

			// enable cpt title and editor
			$custom_types[ $repeatable_group->get_slug() ]['supports']['title'] = 1;
			$custom_types[ $repeatable_group->get_slug() ]['supports']['editor'] = 1;

			// store cpt changes
			update_option( Toolset_Post_Type_Repository::POST_TYPES_OPTION_NAME, $custom_types, true );

			$relationship->set_display_name( $repeatable_group->get_display_name() );
			$relationship->set_display_name_singular( $repeatable_group->get_display_name() );
			$relationship->set_origin( new Toolset_Relationship_Origin_Wizard() );
			$definition_repository->persist_definition( $relationship );
		} else {
			$definition_repository->remove_definition( $relationship, true );

			// delete group cpt
			unset( $custom_types[ $repeatable_group->get_slug() ] );
			update_option( Toolset_Post_Type_Repository::POST_TYPES_OPTION_NAME, $custom_types, true );

			// delete exisiting posts
			$this->delete_items_posts( $repeatable_group );

			// delete field group entry
			if ( ! wp_delete_post( $repeatable_group->get_id(), true ) ) {
				return false;
			}
		}

		// remove rfg from field group
		$this->unassign_field_from_all_field_groups( $repeatable_group->get_id_with_prefix() );

		// rfg completly deleted
		return true;
	}


	/**
	 * Deletes all items of a RFG
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function delete_items( Types_Field_Group_Repeatable $repeatable_group ) {
		// Get relationship definition of RFG
		$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$relationship = $definition_repository->get_definition( $repeatable_group->get_slug() );
		if ( null === $relationship ) {
			throw new RuntimeException( 'Error loading Relationship Definition' );
		}

		// delete associations
		$relationships_factory = new Factory();
		$relationships_factory->database_operations()
			->delete_associations_by_relationship( $relationship->get_row_id() );

		// delete exisiting posts
		$this->delete_items_posts( $repeatable_group );

		return true;
	}


	/**
	 * Delete posts of RFG
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 */
	private function delete_items_posts( Types_Field_Group_Repeatable $repeatable_group ) {
		global $wpdb;
		$rfg_items = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM " . $wpdb->posts . "
				 WHERE post_type = '%s'",
				$repeatable_group->get_slug()
			)
		);

		foreach ( $rfg_items as $id ) {
			wp_delete_post( $id );
		}
	}


	/**
	 * Create one to many relationship between groups
	 * Supports:
	 *  Field Group -> RFG
	 *  RFG -> RFG
	 *
	 * @param Toolset_Field_Group $parent_group
	 * @param Types_Field_Group_Repeatable $child_group
	 *
	 * @return false|IToolset_Relationship_Definition
	 */
	public function create_relationship_one_to_many_between_groups(
		Toolset_Field_Group $parent_group,
		Types_Field_Group_Repeatable $child_group
	) {
		if ( ! $parent_slug = $this->get_slug_of_group( $parent_group ) ) {
			// something went wrong
			return false;
		}

		$child_slug = $child_group->get_post_type_model()->get_slug();
		do_action( 'toolset_do_m2m_full_init' );

		// create relationship definition
		$parent = Toolset_Relationship_Element_Type::build_for_post_type( $parent_slug );
		$child = Toolset_Relationship_Element_Type::build_for_post_type( $child_slug );
		$repository = Toolset_Relationship_Definition_Repository::get_instance();

		$definition = $repository->create_definition(
			$child_slug,
			$parent,
			$child
		);
		// set definition plural name = rfg title
		$definition->set_display_name( $child_group->get_post_type_model()->get_label() );
		$cardinality = new Toolset_Relationship_Cardinality( 1, Toolset_Relationship_Cardinality::INFINITY );
		$definition->set_cardinality( $cardinality );
		$definition->set_origin( new Toolset_Relationship_Origin_Repeatable_Group() );
		$repository->persist_definition( $definition );

		return $definition;
	}


	/**
	 * Returns slug of a group
	 * Can handle Toolset_Field_Group or a Types_Field_Group_Repeatable (extends Toolset_Field_Group)
	 *
	 * @param Toolset_Field_Group|Types_Field_Group_Repeatable $group
	 *
	 * @return bool|string
	 */
	protected function get_slug_of_group( Toolset_Field_Group $group ) {
		if ( $group instanceof Types_Field_Group_Repeatable ) {
			// group is a repeatable field group
			return $group->get_post_type_model()->get_slug();
		}

		$parent_post_type = $this->get_unique_assigned_post_type( $group );
		if ( $parent_post_type ) {
			// group is a field group
			return $parent_post_type->get_slug();
		}

		// something went wrong
		return false;
	}


	/**
	 * This delete the relationship definition of the given repeatable group
	 *
	 * @param Types_Field_Group_Repeatable $group
	 * @param null|string $previous_slug If not null it will be used to find relationship
	 *                                   (this way we don't need to make the slug changeable on the object)
	 *
	 * @return false|IToolset_Relationship_Definition[]
	 */
	public function delete_relationship_of_group(
		Types_Field_Group_Repeatable $group,
		$previous_slug = null
	) {
		do_action( 'toolset_do_m2m_full_init' );

		$child_slug = $previous_slug !== null
			? sanitize_text_field( $previous_slug )
			: $group->get_post_type_model()->get_slug();

		$repository = Toolset_Relationship_Definition_Repository::get_instance();
		$definitions = $repository->get_definitions();

		/**
		 * Normally there can just be one definition per RFG, but due to a bug (types-1677),
		 * we have orphan RFGs. This way we getting rid of these orphans.
		 *
		 * @var IToolset_Relationship_Definition[] $deleted_definitions
		 */
		$deleted_definitions = array();

		foreach ( $definitions as $definition ) {
			$child_types = $definition->get_child_type()->get_types();
			if ( in_array( $child_slug, $child_types ) ) {
				// definition found
				$repository->remove_definition( $definition, false );
				$deleted_definitions[] = $definition;
			}
		}

		// return array of deleted definitions or false if no definition was deleted
		return ! empty( $deleted_definitions ) ? $deleted_definitions : false;
	}


	/**
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param string $fields_rendered
	 *
	 * @return Types_Field_Group_Repeatable_View_Backend_Creation
	 */
	public function get_view_backend_creation( Types_Field_Group_Repeatable $rfg, $fields_rendered = '' ) {
		return new Types_Field_Group_Repeatable_View_Backend_Creation( $rfg, new Types_Helper_Twig(), $fields_rendered );
	}


	/**
	 * @param int $user_id
	 * @param string $post_type
	 *
	 * @return Types_Field_Group_Repeatable_View_Backend_Post
	 */
	public function get_view_backend_post( $user_id, $post_type ) {
		return new Types_Field_Group_Repeatable_View_Backend_Post( $user_id, $post_type );
	}


	/**
	 * Delete a item of a repeatable group
	 *
	 * - deletes post
	 * - deletes translations
	 * - deletes associations
	 *
	 * @param WP_Post $item
	 *
	 * @return bool
	 */
	public function delete_item( WP_Post $item ) {
		try {
			do_action( 'toolset_do_m2m_full_init' );

			$rfg_mapper = new Types_Field_Group_Repeatable_Mapper_Legacy();
			$post_type_repository = Toolset_Post_Type_Repository::get_instance();
			$relationship_service = new Toolset_Relationship_Service();

			if ( $rfg_mapper->delete_item_by_post(
				$item,
				$post_type_repository,
				$relationship_service,
				$this->get_wpml() )
			) {
				// all as expected
				return true;
			}
		} catch ( Exception $e ) {
			// just log the error and return false
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( 'Error while deleting rfg item with id "' . $item->ID . '": ' . $e->getMessage() );

			return false;
		}

		// no error, but also not deleted
		return false;
	}


	/**
	 * Update item title
	 * This will NOT trigger update_post hook.
	 *
	 * @param WP_Post $item
	 * @param string $title Optional. If not set $item->post_title will be used
	 *
	 * @return bool
	 */
	public function update_item_title( WP_Post $item, $title = null ) {
		try {
			do_action( 'toolset_do_m2m_full_init' );
			$rfg_mapper = new Types_Field_Group_Repeatable_Mapper_Legacy();

			return $rfg_mapper->update_item_title( $item, $title );
		} catch ( Exception $e ) {
			// just log the error and return false
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( 'Error while updating rfg item title with id "' . $item->ID . '": ' . $e->getMessage() );

			return false;
		}
	}


	/**
	 * Get global WPML (sitepress) class
	 *
	 * @return null|SitePress
	 */
	private function get_wpml() {
		global $sitepress;

		return $sitepress instanceof SitePress ? $sitepress : null;
	}


	/**
	 * Modifies the fields list of Field Groups to rewrite links to repeatable groups.
	 * Replaces the ID of the field group by it's slug.
	 *
	 * called on Types export
	 *
	 * @param string $fields_string
	 *
	 * @return string
	 */
	public function on_export_fields_string( $fields_string ) {
		if ( strpos( $fields_string, Types_Field_Group_Repeatable::PREFIX ) === false ) {
			// no repeatable group
			return $fields_string;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$fields = explode( ',', $fields_string );

		foreach ( $fields as $field_index => $field_slug ) {
			if ( $rfg = $this->get_object_from_prefixed_string( $field_slug ) ) {
				$fields[ $field_index ] = Types_Field_Group_Repeatable::PREFIX . $rfg->get_slug();
			}
		}

		return implode( ',', $fields );
	}


	/**
	 * Reverts 'on_export_fields_string'
	 *
	 * called on Types import
	 *
	 * @param string $fields_string
	 *
	 * @return string
	 */
	public function on_import_fields_string( $fields_string ) {
		if ( strpos( $fields_string, Types_Field_Group_Repeatable::PREFIX ) === false ) {
			// no repeatable group
			return $fields_string;
		}

		$fields = explode( ',', $fields_string );

		foreach ( $fields as $field_index => $field_slug ) {
			try {
				if ( $rfg = $this->get_object_from_prefixed_import_string( $field_slug ) ) {
					$fields[ $field_index ] = Types_Field_Group_Repeatable::PREFIX . $rfg->ID;
				}
			} catch ( Exception $e ) {
				// there is a RFG slug, but the RFG could not be found, this can happen if the client
				// decides to import a Field Group but decided agains importing the inherit RFG
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					/** @noinspection ForgottenDebugOutputInspection */
					error_log( $e->getMessage() );
				}

				// we must remove the rfg from the field list
				unset( $fields[ $field_index ] );
			}
		}

		return implode( ',', $fields );
	}


	/**
	 * @param string $fields_string
	 *
	 * @return Types_Field_Group_Repeatable[]
	 */
	public function get_rfgs_by_fields_string( $fields_string ) {
		if ( strpos( $fields_string, Types_Field_Group_Repeatable::PREFIX ) === false ) {
			// no repeatable group
			return array();
		}

		do_action( 'toolset_do_m2m_full_init' );

		$fields = explode( ',', $fields_string );

		$rfg_posts = array();

		foreach ( $fields as $field_slug ) {
			if ( $rfg = $this->get_object_from_prefixed_string( $field_slug ) ) {
				$rfg_posts[] = $rfg;
			}
		}

		return $rfg_posts;
	}


	/**
	 * Check if group contais a rfg or prf
	 *
	 * @param int $group_id
	 *
	 * @return bool
	 */
	public function group_contains_rfg_or_prf( $group_id ) {
		$group_post = get_post( $group_id );
		if ( ! $group_post instanceof WP_Post ) {
			return false;
		}
		$group_object = new Toolset_Field_Group_Post( $group_post );

		// get all fields
		$group_fields = $group_object->get_field_definitions();
		$fields_type = array();

		foreach ( $group_fields as $field ) {
			$fields_type[ $field->get_slug() ] = $field->get_type()->get_slug();
		}

		// get all field slugs (required because get_field_definitions won't return rfgs)
		$group_field_slugs = $group_object->get_field_slugs();

		foreach ( (array) $group_field_slugs as $field_slug ) {
			if ( strpos( $field_slug, Types_Field_Group_Repeatable::PREFIX ) !== false ) {
				// rfg
				return true;
			}

			if ( isset( $fields_type[ $field_slug ] ) && $fields_type[ $field_slug ] === 'post' ) {
				// prf
				return true;
			}
		}

		// no rfg and no prf
		return false;
	}


	/**
	 * @param string $rfg_string
	 *
	 * @return false|WP_Post false = no link to rfg, WP_Post = RFG
	 * @throws Exception we have a link to rfg but the rfg cannot be found
	 */
	private function get_object_from_prefixed_import_string( $rfg_string ) {
		if ( strpos( $rfg_string, Types_Field_Group_Repeatable::PREFIX ) === false ) {
			// no repeatable group
			return false;
		}

		// get slug
		$slug = str_replace( Types_Field_Group_Repeatable::PREFIX, '', $rfg_string );

		$rfg = @get_posts( array(
			'name' => $slug,
			'post_type' => 'wp-types-group',
			'post_status' => 'hidden',
			'posts_per_page' => 1,
			'suppress_filters' => true,
		) );

		if ( ! empty( $rfg ) ) {
			return $rfg[0];
		}

		throw new RuntimeException( 'No Repeatable Field Group with slug "' . $slug . '" found.' );
	}
}
