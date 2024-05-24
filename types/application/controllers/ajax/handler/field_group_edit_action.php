<?php

use OTGS\Toolset\Common\Relationships\MainController;

/**
 * Handle action on the Field Group Edit page.
 *
 * @since m2m
 */
class Types_Ajax_Handler_Field_Group_Edit_Action extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Entry point of all ajax calls related to Field Group Edit Page ("all" minus legacy calls)
	 *
	 * @inheritdoc
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()
			->ajax_begin(
				array( 'nonce' => $this->get_ajax_manager()->get_action_js_name( Types_Ajax::CALLBACK_FIELD_GROUP_EDIT_ACTION ) )
			);

		// Read and validate input
		$field_action = sanitize_text_field( toolset_getpost( 'field_group_action' ) );

		// route action
		$this->route( $field_action );
	}


	/**
	 * Route different actions
	 *
	 * @param string $action
	 */
	private function route( $action ) {
		switch ( $action ) {
			case 'add_repeatable_field_group':
				$this->add_repeatable_field_group();
				return;
			case 'validate_and_save_post_type_slug_and_label':
				$this->validate_and_save_post_type_slug_and_label();
				return;
			case 'delete_group':
				$this->delete_group();
				return;
			case 'delete_post_reference_field':
				$this->delete_post_reference_field();
				return;
			case 'get_post_reference_field_infos':
				$this->get_post_reference_field_infos();
				return;
			case 'convert_prf_to_relationship':
				$this->convert_prf_to_relationship();
				return;
			case 'create_intermediary_posts':
				$this->create_intermediary_posts();
				return;
			case 'get_rfg_has_items':
				$this->get_rfg_has_items();
				return;
			case 'get_rfg_delete_items':
				$this->get_rfg_delete_items();
				return;
		}
	}


	/**
	 * Adds a new repeatable field group to database and returns the template
	 *
	 * Return: json string by using our ajax manager.
	 */
	private function add_repeatable_field_group() {
		$is_success = true;

		$rfg_service = new Types_Field_Group_Repeatable_Service();

		// add new post for field group
		$post_id = wp_insert_post(
			array(
				'post_type' => Toolset_Field_Group_Post::POST_TYPE,
				'post_status' => 'hidden' // important
			)
		);

		// check for error
		if ( $post_id instanceof WP_Error ) {
			$is_success = false;

			// return error instead of group
			$this->get_ajax_manager()->ajax_finish( array(
				'error' => $post_id->get_error_message(),
			), $is_success );
		}

		// get WP_Post object with insert post_id
		$post = new stdClass();
		$post->ID = $post_id;
		$post->post_title = __( 'Untitled', 'wpcf' );
		$post->post_type = Toolset_Field_Group_Post::POST_TYPE;
		$post = new WP_Post( $post );

		// get Types_Field_Group_Repeatable object by WP_Post
		$group = $rfg_service->get_object_by_id( $post->ID );
		$group->set_purpose( Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP );

		// get backend view of Types_Field_Group_Repeatable
		$view = $rfg_service->get_view_backend_creation( $group );

		// return template (true = is_success)
		$this->get_ajax_manager()->ajax_finish( array(
			'html_group_id' => $group->get_id_with_prefix(),
			'html_group' => $view->render(),
		), $is_success );
	}


	/**
	 * Compare slug of Types_Field_Group_Repeatable instance and a string $new_slug.
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param string $new_slug
	 *
	 * @return bool
	 */
	private function has_group_slug_changed( Types_Field_Group_Repeatable $repeatable_group, $new_slug ) {
		$registered_post_type = $repeatable_group->get_post_type_model();

		return (
			 ! $registered_post_type
			 || $new_slug !== $registered_post_type->get_slug()
		);
	}


	/**
	 * Check that $string has at least $min_length characters
	 *
	 * @param string $string
	 * @param int $min_length
	 *
	 * @return bool
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function valid_min_length( $string, $min_length = 1 ) {
		if ( strlen( $string ) < (int) $min_length ) {
			return false;
		}

		return true;
	}


	/**
	 * Check that $string has a maximum of $max_length characters
	 *
	 * @param string $slug
	 * @param int $max_length
	 *
	 * @return bool
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function valid_max_length( $slug, $max_length = 20 ) {
		if ( strlen( $slug ) > (int) $max_length ) {
			return false;
		}

		return true;
	}


	/**
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param string $slug
	 * @param string $label
	 *
	 * @return bool
	 */
	private function store_group_post_type( Types_Field_Group_Repeatable $repeatable_group, $slug, $label ) {
		if ( $repeatable_group->get_post_type_model() ) {
			// group already has a the post type stored, let's change the slug
			return $this->change_group_post_type_slug( $repeatable_group, $slug );
		}

		$post_types_service = Toolset_Post_Type_Repository::get_instance();

		try {
			$post_type = $post_types_service->create( $slug, $label,
				$label );
			$post_type->set_is_repeating_field_group( true );
			$post_types_service->save( $post_type );
		} catch ( Exception $e ) {
			return false;
		}

		$repeatable_group->set_post_type( $post_type );

		return true;
	}


	/**
	 * This method allows to change the labels of the group post type.
	 * It will return false, if there is no stored post type, or if the label did not change.
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param string $label
	 */
	private function maybe_change_group_post_type_label( Types_Field_Group_Repeatable $repeatable_group, $label ) {
		$registered_post_type = $repeatable_group->get_post_type_model();
		if ( ! $registered_post_type || ! $registered_post_type instanceof IToolset_Post_Type_From_Types ) {
			// no stored post_type, we cannot change the slug (use store_post_type)
			return;
		}

		// re-initialize Toolset_Post_Type_Repository to load changes to custom types
		$post_types_service = new Toolset_Post_Type_Repository(
			Toolset_Naming_Helper::get_instance(),
			new Toolset_Condition_Plugin_Types_Active(),
			new Toolset_Post_Type_Factory()
		);
		$post_types_service->initialize();

		if ( $label !== $registered_post_type->get_label() ) {
			$registered_post_type->set_label( Toolset_Post_Type_Labels::NAME, $label );
			$registered_post_type->set_label( Toolset_Post_Type_Labels::SINGULAR_NAME, $label );
			$post_types_service->save( $registered_post_type );
		}
	}


	/**
	 * This method allows to change the slug of the group post type.
	 * It will return false, if there is already a stored post type.
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param string $slug
	 *
	 * @return bool
	 */
	private function change_group_post_type_slug( Types_Field_Group_Repeatable $repeatable_group, $slug ) {
		if ( ! $repeatable_group->get_post_type_model() ) {
			// no stored post_type, we cannot change the slug (use store_post_type)
			return false;
		}

		// re-initialize Toolset_Post_Type_Repository to load changes to custom types
		$post_types_service = new Toolset_Post_Type_Repository(
			Toolset_Naming_Helper::get_instance(),
			new Toolset_Condition_Plugin_Types_Active(),
			new Toolset_Post_Type_Factory()
		);
		$post_types_service->initialize();

		$relationship_controller = MainController::get_instance();

		try {
			// disable "auto" rename of slug on db table `toolset_type_sets` by relationship controller
			// this is needed because on the rfg save (which runs after this) we need the old slug on `toolset_type_sets`
			$relationship_controller->remove_action_of_wpcf_post_type_renamed();

			// change slug for post types
			$post_type = $repeatable_group->get_post_type_model();
			if ( ! $post_type instanceof IToolset_Post_Type_From_Types ) {
				throw new RuntimeException( 'the post type of the repeatable group is not recognized by Types' );
			}

			$post_types_service->change_slug( $post_type, $slug );

			// re-enable "auto" rename of slug on db table `toolset_type_sets` by relationship controller
			$relationship_controller->add_action_to_wpcf_post_type_renamed();
		} catch ( Exception $e ) {
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( $e->getMessage() );

			// re-enable "auto" rename of slug on db table `toolset_type_sets` by relationship controller
			$relationship_controller->add_action_to_wpcf_post_type_renamed();

			return false;
		}

		if ( $renamed_post_type = $post_types_service->get( $slug ) ) {
			// update post type slug in postmeta of group
			$repeatable_group->set_post_type( $renamed_post_type );

			return true;
		}

		// shouldn't happen, because reaching this point means the renamed post type wasn't stored / refreshed
		// on Toolset_Post_Type_Repository ($post_types_service->change_slug() and $post_type_service->get())
		return false;
	}


	/**
	 * Checks if used post type slug is valid, and maybe update also its label(s)
	 *
	 * Return: json string by using our ajax manager.
	 */
	private function validate_and_save_post_type_slug_and_label() {
		$group_id = (int) toolset_getpost( 'group_id' );
		$user_post_type_slug = strtolower( sanitize_text_field( toolset_getpost( 'post_type_slug' ) ) );
		$user_post_type_label = sanitize_text_field( toolset_getpost( 'post_type_label' ) );
		$field_group_service = new Types_Field_Group_Repeatable_Service();

		$repeatable_group = $field_group_service->get_object_by_id( $group_id );
		if ( ! $repeatable_group ) {
			// some issue in the DOM (shouldn't happen)
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Something went wrong. Please reload the page.', 'wpcf' ) ),
				false
			);
		}

		$this->maybe_change_group_post_type_label( $repeatable_group, $user_post_type_label );

		// Check if slug has changed
		if ( ! $this->has_group_slug_changed( $repeatable_group, $user_post_type_slug ) ) {
			// slug not changed, nothing to do
			$this->get_ajax_manager()->ajax_finish( array() );
			return;
		}

		// Check slug min length
		if ( ! $this->valid_min_length( $user_post_type_slug, 1 ) ) {
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => sprintf( __( 'Please enter at least %d character.', 'wpcf' ), 1 ) ),
				false
			);
			return;
		}

		// Check slug max length
		if ( ! $this->valid_max_length( $user_post_type_slug, 20 ) ) {
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => sprintf( __( 'Please enter no more than %d characters.', 'wpcf' ), '20' ) ),
				false
			);
			return;
		}

		// Check if slug is already used on a post type
		$naming_helper = Toolset_Naming_Helper::get_instance();

		if ( $naming_helper->check_post_type_slug_conflicts( $user_post_type_slug ) ) {
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Slug is already in use.', 'wpcf' ) ),
				false
			);
			return;
		}

		// Check if slug is already used on a field group
		$another_rfg = get_page_by_path( $user_post_type_slug, OBJECT, 'wp-types-group' );
		if (
			$another_rfg
			&& $another_rfg->ID !== $repeatable_group->get_id()
		) {
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Slug is already in use.', 'wpcf' ) ),
				false
			);
			return;
		}

		// Check for unallowed characters
		$valid_post_type_slug = $naming_helper->generate_unique_post_type_slug( $user_post_type_slug );
		if ( $user_post_type_slug !== $valid_post_type_slug ) {
			// abort as the slug contains unallowed characters
			$this->get_ajax_manager()->ajax_finish(
				array(
					'error' =>
						sprintf( __( 'No valid slug. You could use %s.', 'wpcf' ), "<b>$valid_post_type_slug</b>" ),
				),
				false
			);
			return;
		}

		// Try to store the post type with the new slug
		if ( $this->store_group_post_type( $repeatable_group, $user_post_type_slug, $user_post_type_label ) ) {
			// all fine, no message needed
			$this->get_ajax_manager()->ajax_finish( array() );
			return;
		}

		// something went wrong (shouldn't really happen at this point)
		$this->get_ajax_manager()->ajax_finish( array(
			'error' => __( 'Technical issue. The Repeatable Group could not be saved.', 'wpcf' ),
			false
		) );
	}


	/**
	 * Delete a repeatable group
	 * - Repeatable Group Post Entry
	 * - Post Type of the RFG
	 * - All posts
	 */
	private function delete_group() {
		$group_id = (int) toolset_getpost( 'group_id' );
		$field_group_service = new Types_Field_Group_Repeatable_Service();

		$repeatable_group = $field_group_service->get_object_by_id( $group_id );
		if ( ! $repeatable_group ) {
			// some issue in the DOM (shouldn't happen)
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Something went wrong. Please reload the page.', 'wpcf' ) ),
				false // is_success
			);
		}

		// possible not fully registered yet
		if ( ! $repeatable_group->get_post_type_model() ) {
			// just delete post entry for field group
			wp_delete_post( $repeatable_group->get_id(), true );
			$this->get_ajax_manager()->ajax_finish( array() );
		}

		try {
			if ( $field_group_service->delete( $repeatable_group ) ) {
				// all done, no message needed
				$this->get_ajax_manager()->ajax_finish( array() );
			}
		} catch ( RuntimeException $e ) {
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Something went wrong loading Repeatable Field Group.', 'wpcf' ) ),
				false // is_success
			);
		}

		// if something on the deletion happened
		$this->get_ajax_manager()->ajax_finish(
			array( 'error' => __( 'The group could not be deleted. Please reload and try again.', 'wpcf' ) ),
			false // is_success
		);
	}


	/**
	 * Delete a post reference field
	 */
	private function delete_post_reference_field() {
		$field_slug = sanitize_text_field( toolset_getpost( 'field_slug' ) );
		$delete_mode = sanitize_text_field( toolset_getpost( 'delete_mode' ) );

		do_action( 'toolset_do_m2m_full_init' );

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();

		if ( ! $post_reference_definition = $relationship_repository->get_definition( $field_slug ) ) {
			// relationship of field could not be loaded
			$this->get_ajax_manager()->ajax_finish(
				array(
					'error' => sprintf( __( 'The field with the slug "%s" could not be loaded. Reload the page and try again.', 'wpcf' ), $field_slug ),
				),
				false
			);
		}

		$association_query = new Toolset_Association_Query( array(
			Toolset_Association_Query::QUERY_RELATIONSHIP_ID => $post_reference_definition->get_row_id(),
			Toolset_Association_Query::OPTION_RETURN => Toolset_Association_Query::RETURN_ASSOCIATIONS,
		) );

		$associations = $association_query->get_results();

		switch ( $delete_mode ) {
			case 'empty_or_request':
				if ( ! empty( $associations ) ) {
					// associations found - ask user what to do
					$this->get_ajax_manager()->ajax_finish(
						array( 'request' => 1 )
					);
				}
				// No associations created yet -> delete the PRF
				$relationship_repository->remove_definition( $post_reference_definition );

				// delete entire field (also from group to prevent side effects if the user does not save the group)
				wpcf_admin_fields_delete_field( $field_slug );

				break;
			case 'delete_associations':
				// Delete relationship and all associations
				$relationship_repository->remove_definition( $post_reference_definition );

				// delete field
				$factory = Toolset_Field_Definition_Factory::get_factory_by_domain( Toolset_Element_Domain::POSTS );
				if ( ! $definition = $factory->load_field_definition( $field_slug ) ) {
					// field could not be loaded
					$this->get_ajax_manager()->ajax_finish(
						array(
							'error' => sprintf( __( 'The field with the slug "%s" could not be loaded. Reload the page and try again.', 'wpcf' ), $field_slug ),
						),
						false
					);
				}

				if ( ! $factory->delete_definition( $definition ) ) {
					// error while deleting the field
					$this->get_ajax_manager()->ajax_finish(
						array( 'error' => __( 'An issue occurred while deleting the field.', 'wpcf' ) ),
						false
					);
				}

				// delete entire field (also from group to prevent side effects if the user does not save the group)
				wpcf_admin_fields_delete_field( $field_slug );

				break;
		}

		$this->get_ajax_manager()->ajax_finish( array( 'success' => __( 'Field deleted.', 'wpcf' ) ) );
	}


	/**
	 * Create intermediary posts
	 */
	public function create_intermediary_posts() {
		do_action( 'toolset_do_m2m_full_init' );
		$relationship_helper = new Types_Page_Field_Group_Post_Relationship_Helper();
		$relationship_helper->initialize();
		$group_id = (int) toolset_getpost( 'group_id' );
		$remaining_associations_count = $relationship_helper->create_empty_associations_intermediary_posts( $group_id );
		$this->get_ajax_manager()->ajax_finish( array(
			'remaining_elements' => $remaining_associations_count,
		) );
	}


	/**
	 * Returns the labels of child and parent elements.
	 * Required for the dialog "convert prf to 'relationship' (flip parent/child)"
	 */
	private function get_post_reference_field_infos() {
		$field_slug = sanitize_text_field( toolset_getpost( 'field_slug' ) );

		do_action( 'toolset_do_m2m_full_init' );

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();

		$post_reference_definition = $relationship_repository->get_definition( $field_slug );

		if ( ! $post_reference_definition ) {
			// relationship of field could not be loaded
			$this->get_ajax_manager()->ajax_finish(
				array(
					'error' => sprintf(
						__( 'The field with the slug "%s" could not be loaded. Reload the page and try again.', 'wpcf' ),
						$field_slug ),
				),
				false
			);
		}

		// labels for the involed post types
		$parentSingular = $parentPlural = $childSingular = $childPlural = '';

		// get the child type labels
		foreach ( $post_reference_definition->get_parent_type()->get_types() as $parent_slug ) {
			if ( $post_obj = get_post_type_object( $parent_slug ) ) {
				$childSingular = $post_obj->labels->singular_name;
				$childPlural = $post_obj->labels->name;
			}
		}

		// get the parent type labels
		foreach ( $post_reference_definition->get_child_type()->get_types() as $child_slug ) {
			if ( $post_obj = get_post_type_object( $child_slug ) ) {
				$parentSingular = $post_obj->labels->singular_name;
				$parentPlural = $post_obj->labels->name;
			}
		}

		$this->get_ajax_manager()->ajax_finish(
			array(
				'parentSingular' => $parentSingular,
				'parentPlural' => $parentPlural,
				'childSingular' => $childSingular,
				'childPlural' => $childPlural,
			)
		);
	}


	/**
	 * Converts a prf to a m2m relationship
	 */
	private function convert_prf_to_relationship() {
		$field_slug = sanitize_text_field( toolset_getpost( 'field_slug' ) );

		do_action( 'toolset_do_m2m_full_init' );

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();

		$post_reference_definition = $relationship_repository->get_definition( $field_slug );

		if ( ! $post_reference_definition ) {
			// relationship of field could not be loaded
			$this->get_ajax_manager()->ajax_finish(
				array(
					'error' =>
						sprintf( __( 'The field with the slug "%s" could not be loaded. Reload the page and try again.', 'wpcf' ), $field_slug ),
				),
				false
			);
		}

		// delete field
		wpcf_admin_fields_delete_field( $field_slug );

		// this relationship now needs to be handled like relationships created with the wizard
		$post_reference_definition->set_origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD );

		// change cardinality from o2m to m2m
		$cardinality = new Toolset_Relationship_Cardinality(
			Toolset_Relationship_Cardinality::INFINITY,
			Toolset_Relationship_Cardinality::INFINITY
		);

		$post_reference_definition->set_cardinality( $cardinality );

		// save changes
		$relationship_repository->persist_definition( $post_reference_definition );

		$this->get_ajax_manager()->ajax_finish(
			array(
				'urlToRelationship' => admin_url() . 'admin.php?page='
					. Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS
					. '&action=edit&slug=' . $post_reference_definition->get_slug(),
			)
		);
	}


	/**
	 * Check if RFG has items
	 * This is used whenever a RFG is moved to notice the user
	 * that existing items will be removed when the RFG is moved.
	 */
	private function get_rfg_has_items() {
		$group_id = (int) toolset_getpost( 'repeatable_group_id' );
		$field_group_service = new Types_Field_Group_Repeatable_Service();

		$repeatable_group = $field_group_service->get_object_by_id( $group_id );
		if ( ! $repeatable_group ) {
			// some issue in the DOM (shouldn't happen)
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Something went wrong. Please reload the page.', 'wpcf' ) ),
				false // is_success
			);
		}

		global $wpdb;
		$rfg_items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM " . $wpdb->posts . "
				 WHERE post_type = '%s'
				 LIMIT 1",
				$repeatable_group->get_slug()
			)
		);

		$this->get_ajax_manager()->ajax_finish(
			array(
				'rfgHasItems' => count( $rfg_items ),
			)
		);
	}


	/**
	 * Delete items of RFG
	 */
	private function get_rfg_delete_items() {
		$group_id = (int) toolset_getpost( 'repeatable_group_id' );
		$field_group_service = new Types_Field_Group_Repeatable_Service();

		$repeatable_group = $field_group_service->get_object_by_id( $group_id );
		if ( ! $repeatable_group  ) {
			// some issue in the DOM (shouldn't happen)
			$this->get_ajax_manager()->ajax_finish(
				array( 'error' => __( 'Something went wrong. Please reload the page.', 'wpcf' ) ),
				false // is_success
			);
		}

		$field_group_service->delete_items( $repeatable_group );


		$this->get_ajax_manager()->ajax_finish( array() );
	}
}
