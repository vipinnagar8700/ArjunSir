<?php

use OTGS\Toolset\Common\Result\ResultSet;

/**
 * Relationship definition viewmodel.
 *
 * The layer between a relationship definition and the Relationships page. Handles the translation between what the
 * relationship definition provides and what the page logic needs.
 *
 * Explore the MVVM pattern if this sounds unfamiliar to you.
 *
 * @since m2m
 */
class Types_Viewmodel_Relationship_Definition {


	/**
	 * @var IToolset_Relationship_Definition
	 */
	private $relationship;


	private $definition_repository;

	/** @var Types_Admin_Menu */
	private $admin_menu;

	/** @var Toolset_Field_Group_Post_Factory */
	private $post_group_factory;

	/** @var Types_Page_Post_Type_Post_Relationship_Helper */
	private $post_type_page_helper;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/**
	 * Types_Viewmodel_Relationship_Definition constructor.
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition The relationship definition.
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di For testing purposes.
	 * @param Types_Admin_Menu|null $admin_menu_di For testing purposes.
	 * @param Toolset_Field_Group_Post_Factory|null $post_group_factory_di For testing purposes.
	 * @param Types_Page_Post_Type_Post_Relationship_Helper|null $post_type_page_helper_di For testing purposes.
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di For testing purposes.
	 *
	 * @throws InvalidArgumentException If wrong relationship.
	 */
	public function __construct(
		$relationship_definition,
		Toolset_Relationship_Definition_Repository $definition_repository_di = null,
		Types_Admin_Menu $admin_menu_di = null,
		Toolset_Field_Group_Post_Factory $post_group_factory_di = null,
		Types_Page_Post_Type_Post_Relationship_Helper $post_type_page_helper_di = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null
	) {
		if ( ! $relationship_definition instanceof IToolset_Relationship_Definition ) {
			throw new InvalidArgumentException();
		}

		$this->relationship = $relationship_definition;
		$this->definition_repository = (
		null === $definition_repository_di
			? Toolset_Relationship_Definition_Repository::get_instance()
			: $definition_repository_di
		);
		$this->admin_menu = ( null === $admin_menu_di ? Types_Admin_Menu::get_instance() : $admin_menu_di );
		$this->post_group_factory = (
		null === $post_group_factory_di
			? Toolset_Field_Group_Post_Factory::get_instance()
			: $post_group_factory_di
		);
		$this->post_type_page_helper = (
		null === $post_type_page_helper_di
			? new Types_Page_Post_Type_Post_Relationship_Helper( $this->admin_menu )
			: $post_type_page_helper_di
		);
		$this->post_type_repository = (
		null === $post_type_repository_di
			? Toolset_Post_Type_Repository::get_instance()
			: $post_type_repository_di
		);
	}


	public function get_slug() {
		return $this->relationship->get_slug();
	}


	/**
	 * Build an associative array with relationship data that can be passed to JS on the Relationships page.
	 *
	 * @param boolean $translate If it has to be translated.
	 *
	 * @return array
	 * @since m2m
	 * @since 3.0.3 New parameter $translate
	 */
	public function to_array( $translate = true ) {

		$intermediary_post_type = (
		$this->relationship instanceof Toolset_Relationship_Definition
			? $this->relationship->get_intermediary_post_type()
			: null
		);

		return array(
			'slug' => $this->relationship->get_slug(),
			'displayName' => $this->relationship->get_display_name(),
			'displayNameSingular' => $this->relationship->get_display_name_singular(),
			'cardinality' => $this->relationship->get_cardinality()->to_array(),
			'types' => array(
				'parent' => array(
					'domain' => $this->relationship->get_parent_domain(),
					'types' => $this->relationship->get_parent_type()->get_types(),
				),
				'child' => array(
					'domain' => $this->relationship->get_child_domain(),
					'types' => $this->relationship->get_child_type()->get_types(),
				),
				'intermediary' => array(
					'exists' => $this->relationship->has_association_field_definitions(),
					'type' => ( null === $intermediary_post_type ? '' : $intermediary_post_type ),
					'editFieldGroupUrl' => $this->get_association_field_edit_url(),
					'addFieldGroupUrl' => $this->get_relationship_field_url_no_groups(),
					'editPostTypeUrl' => $this->get_intermediary_post_type_edit_url(),
				),
			),
			'isActive' => $this->relationship->is_active(),
			'isAutodeletingIntermediaryPosts' => $this->relationship->is_autodeleting_intermediary_posts(),
			'isDisplayingIntermediaryInAdminMenu' => $this->is_intermediary_displayed_in_menu(),
			'postTypeDisabledNames' => $this->get_post_type_disabled_names(),
			'associationFields' => $this->get_association_fields(),
			'needsLegacySupport' => $this->relationship->needs_legacy_support(),
			'roleNames' => $this->relationship->get_role_names(),
			'roleLabelsSingular' => $this->relationship->get_role_labels_singular( $translate ),
			'roleLabelsPlural' => $this->relationship->get_role_labels_plural( $translate ),
			'defaultLabels' => $this->relationship->get_default_labels(),
		);
	}


	/**
	 * Returns if the intermediary post type is public.
	 *
	 * @return bool
	 * @since 3.4.2
	 */
	private function is_intermediary_displayed_in_menu() {
		$intermediary_post_type = $this->post_type_repository->get_from_types( $this->relationship->get_intermediary_post_type() );
		if ( null !== $intermediary_post_type ) {
			return $intermediary_post_type->has_show_in_menu();
		}

		return false;
	}


	private function get_association_field_edit_url() {

		// This relationship doesn't support association fields (at least not via intermediary post type).
		if ( ! $this->relationship instanceof Toolset_Relationship_Definition ) {
			return null;
		}

		$result_url = $this->admin_menu->get_page_url( Types_Admin_Menu::LEGACY_PAGE_EDIT_POST_FIELD_GROUP );

		$field_groups = $this->post_group_factory->get_groups_by_post_type(
			$this->relationship->get_intermediary_post_type()
		);

		// If there is (for a strange reason) no field group present, the link we have so far
		// will create a new one.
		if ( ! empty( $field_groups ) ) {

			// This post type should have always exactly one field group.
			$field_groups = array_slice( $field_groups, 0, 1 );
			/** @var Toolset_Field_Group_Post $field_group_id */
			$field_group_id = array_shift( $field_groups );

			$result_url = add_query_arg(
				array( 'group_id' => $field_group_id->get_id() ),
				$result_url
			);
		}

		$intermediary_post_type = $this->relationship->get_intermediary_post_type();
		$intermediary_group_field = $intermediary_post_type
			? Toolset_Field_Group_Post_Factory::get_instance()->get_groups_by_post_type( $intermediary_post_type )
			: null;
		if ( ! $intermediary_post_type || ( $intermediary_post_type && empty( $intermediary_group_field ) ) ) {
			$result_url = add_query_arg(
				array( 'relationship_id' => $this->relationship->get_row_id() ),
				$result_url
			);
		}

		return $result_url;
	}

	private function get_relationship_field_url_no_groups() {
		// This relationship doesn't support association fields (at least not via intermediary post type).
		if ( ! $this->relationship instanceof Toolset_Relationship_Definition ) {
			return null;
		}

		$result_url = $this->admin_menu->get_page_url( Types_Admin_Menu::LEGACY_PAGE_EDIT_POST_FIELD_GROUP );

		return add_query_arg(
			array( 'relationship_id' => $this->relationship->get_row_id() ),
			$result_url
		);
	}


	private function get_association_fields() {
		$field_definitions = $this->relationship->get_association_field_definitions();
		$results = array();
		foreach ( $field_definitions as $field_definition ) {
			$results[] = array(
				'slug' => $field_definition->get_slug(),
				'type' => $field_definition->get_type()->get_slug(),
				'displayName' => $field_definition->get_display_name(),
			);
		}

		return $results;
	}


	private function get_intermediary_post_type_edit_url() {
		// This relationship doesn't support association fields (at least not via intermediary post type).
		if ( ! $this->relationship instanceof Toolset_Relationship_Definition ) {
			return null;
		}

		$post_type_slug = $this->relationship->get_intermediary_post_type();

		if (
			! $this->post_type_repository->has( $post_type_slug )
			|| ! $this->post_type_repository->get( $post_type_slug )->is_from_types()
		) {
			$post_type_slug = null;
		}

		return $this->post_type_page_helper->get_url_to_edit_intermediary_post_type(
			$post_type_slug,
			$this->relationship->get_slug()
		);
	}


	/**
	 * @return IToolset_Relationship_Definition The underlying model.
	 * @since m2m
	 */
	public function get_model() {
		return $this->relationship;
	}


	/**
	 * Apply an array with relationship data from JS on the underlying relationship definition object.
	 *
	 * The structure of the array is the same of what is generated in to_array(), but not all elements
	 * have to be present.
	 *
	 * WIP
	 *
	 * @param array $updated_values
	 *
	 * @return ResultSet
	 * @since m2m
	 */
	public function apply_array( $updated_values ) {

		// Note about sanitization: IToolset_Relationship_Definition keeps its own data integrity
		// and must not allow any invalid value to be set.

		$relationship = $this->relationship;

		$results = new ResultSet();

		if ( array_key_exists( 'displayName', $updated_values ) ) {
			$relationship->set_display_name( $updated_values['displayName'] );
		}

		if ( array_key_exists( 'displayNameSingular', $updated_values ) ) {
			$relationship->set_display_name_singular( $updated_values['displayNameSingular'] );
		}

		if ( array_key_exists( 'isActive', $updated_values ) ) {
			$relationship->is_active( 'true' === $updated_values['isActive'] );
		}

		if ( array_key_exists( 'isAutodeletingIntermediaryPosts', $updated_values ) ) {
			$relationship->is_autodeleting_intermediary_posts(
				'true' === $updated_values['isAutodeletingIntermediaryPosts']
			);
		}

		// Complex or potentially destructive operations
		$is_slug_renamed = (
			array_key_exists( 'newSlug', $updated_values )
			&& $updated_values['newSlug'] !== $updated_values['slug']
		);
		if ( $is_slug_renamed ) {
			$slug_update_result = $this->definition_repository->change_definition_slug( $relationship, $updated_values['newSlug'] );

			$results->add( $slug_update_result );

			if ( $slug_update_result->is_error() ) {
				return $results;
			}
		}

		if ( array_key_exists( 'cardinality', $updated_values ) ) {
			$cardinality = new Toolset_Relationship_Cardinality( $updated_values['cardinality'] );
			if ( ! $cardinality->is_many_to_many() && $relationship->get_cardinality()->is_many_to_many() ) {
				$this->delete_relationship_fields();
			}
			$relationship->set_cardinality( $cardinality );
		}

		if ( array_key_exists( 'types', $updated_values ) ) {
			foreach ( Toolset_Relationship_Role::parent_child_role_names() as $element_role ) {
				// The GUI supports only a single post type.
				$post_type = $updated_values['types'][ $element_role ]['types'][0];
				// We're always dealing with post relationships for now.
				$type = Toolset_Relationship_Element_Type::build_for_post_type( $post_type );

				// We're assuming Toolset_Relationship_Definition instead of its interface.
				// todo This is wrong and dangerous, and should be handled properly in the final release of m2m.

				/** @noinspection PhpUndefinedMethodInspection */
				$relationship->set_element_type( $element_role, $type );
			}

			if ( array_key_exists( 'intermediary', $updated_values['types'] ) ) {
				$intermediary_post_type_slug = toolset_getnest(
					$updated_values, array(
					'types',
					'intermediary',
					'type',
				), ''
				);
				$intermediary_post_type = $this->post_type_repository->get_from_types( $intermediary_post_type_slug );

				if ( array_key_exists( 'isDisplayingIntermediaryInAdminMenu', $updated_values ) ) {
					$is_displayed_in_admin_menu = 'true' === $updated_values['isDisplayingIntermediaryInAdminMenu'];
				} else {
					$is_displayed_in_admin_menu = $intermediary_post_type && $intermediary_post_type->has_show_in_menu();
				}

				$relationship->set_intermediary_post_type( $intermediary_post_type );

				if ( $intermediary_post_type ) {
					$intermediary_post_type->set_show_in_menu( $is_displayed_in_admin_menu );
					$this->post_type_repository->save( $intermediary_post_type );
				}
			}
		}

		// Erase the connection to association fields when the cardinality doesn't support it.
		// In the final version, when we get to this point, the user would have already confirmed that they
		// want to do this.
		if ( ! $relationship->get_cardinality()->is_many_to_many() ) {
			$relationship->set_intermediary_post_type( null );
		}

		// Role aliases.
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			if ( isset( $updated_values['roleNames'][ $role ] ) ) {
				$relationship->set_role_name( $role, $updated_values['roleNames'][ $role ] );
			} else {
				$relationship->set_role_name( $role, '' );
			}
		}
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			if ( isset( $updated_values['roleLabelsSingular'][ $role ] ) ) {
				$relationship->set_role_label_singular( $role, $updated_values['roleLabelsSingular'][ $role ] );
			} else {
				$relationship->set_role_label_singular( $role, '' );
			}
		}
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			if ( isset( $updated_values['roleLabelsPlural'][ $role ] ) ) {
				$relationship->set_role_label_plural( $role, $updated_values['roleLabelsPlural'][ $role ] );
			} else {
				$relationship->set_role_label_plural( $role, '' );
			}
		}

		return $results;
	}


	/**
	 * Checks if any post type included in the parent and child types is disabled
	 *
	 * @return false|array
	 * @since m2m
	 */
	private function get_post_type_disabled_names() {
		$types = array_merge( $this->relationship->get_parent_type()->get_types(), $this->relationship->get_child_type()
			->get_types() );
		$disabled_types = array();
		foreach ( $types as $type ) {
			$post_type = $this->post_type_repository->get( $type );
			if ( ! $post_type ) {
				$disabled_types[] = $type;
			} elseif ( $post_type instanceof IToolset_Post_Type_From_Types) {
				$post_type_definition = $post_type->get_definition();
				if ( toolset_getarr( $post_type_definition, 'disabled' ) ) {
					$disabled_types[] = $post_type->get_label( 'name' );
				}
			}
		}

		return ! empty( $disabled_types ) ? $disabled_types : false;
	}


	/**
	 * Deletes relationship fields in case the relationship is not many-to-many anymore
	 *
	 * @since m2m
	 */
	private function delete_relationship_fields() {
		$field_groups = $this->post_group_factory->get_groups_by_post_type(
			$this->relationship->get_intermediary_post_type()
		);
		if ( count( $field_groups ) ) {
			wp_delete_post( reset( $field_groups )->get_id(), true );
		}
	}
}
