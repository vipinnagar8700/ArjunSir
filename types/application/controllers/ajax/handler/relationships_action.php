<?php

use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Handle actions with relationship definitions on the Relationships page.
 *
 * @since m2m
 */
class Types_Ajax_Handler_Relationships_Action extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @var Types_Viewmodel_Relationship_Definition_Factory
	 */
	private $definition_viewmodel_factory;


	/**
	 * @var Toolset_Relationship_Definition_Repository|null
	 */
	private $definition_repository;


	/**
	 * Types_Ajax_Handler_Relationships_Action constructor.
	 *
	 * Includes dependency injection arguments which can be used only with mocks.
	 * One of the reasons is that in normal execution, the m2m API would not have been initialized yet.
	 *
	 * @param Types_Ajax $ajax_manager Ajas manager.
	 * @param Types_Viewmodel_Relationship_Definition_Factory|null $relationship_definition_viewmodel_factory_di Testing
	 *     purposes.
	 * @param Toolset_Relationship_Definition_Repository|null $relationship_definition_repository_di Testing purposes.
	 */
	public function __construct(
		Types_Ajax $ajax_manager,
		Types_Viewmodel_Relationship_Definition_Factory $relationship_definition_viewmodel_factory_di = null,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository_di = null
	) {
		parent::__construct( $ajax_manager );

		$this->definition_viewmodel_factory = $relationship_definition_viewmodel_factory_di;

		$this->definition_repository = $relationship_definition_repository_di;
	}


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$am = $this->get_ajax_manager();

		$am->ajax_begin( array(
			'nonce' => $am->get_action_js_name( Types_Ajax::CALLBACK_RELATIONSHIPS_ACTION ),
		) );

		do_action( 'toolset_do_m2m_full_init' );

		$relationship_action = sanitize_text_field( toolset_getpost( 'relationship_action' ) );
		$relationship_definitions = toolset_getpost( 'relationship_definitions' );

		if ( ! is_array( $relationship_definitions ) || empty( $relationship_definitions ) ) {
			$am->ajax_finish( array( 'message' => __( 'No relationships have been selected.', 'wpcf' ) ), false );
		}

		// will be sanitized when/if used by the action-specific method
		//$action_specific_data = toolset_getpost( 'action_specific', array() );

		$results = new ResultSet();
		$updated_definitions = array();
		$deleted_definitions = array();

		foreach ( $relationship_definitions as $relationship_definition ) {
			$update_result = $this->single_relationship_action( $relationship_action, $relationship_definition );

			// Override the standard return mechanism (this is just a workaround to avoid a larger refactoring)
			if ( $update_result->has_custom_return_data() ) {
				$this->ajax_finish( $update_result->get_custom_return_data(), $update_result->get_result()
					->is_success() );
			}

			$results->add( $update_result->get_result() );
			if ( $update_result->has_updated_definition() ) {
				if ( $update_result->is_deleted_definition() ) {
					$deleted_definitions[] = $update_result->get_definition_viewmodel()->get_slug();
				} else {
					$updated_definitions[] = $update_result->get_definition_viewmodel()->to_array( false );
				}
			}
		}

		$am->ajax_finish(
			array(
				'messages' => $results->get_messages(),
				'updated_definitions' => $updated_definitions,
				'deleted_definitions' => $deleted_definitions,
			),
			$results->is_complete_success()
		);
	}


	/**
	 * Perform an operation on a single relationship definition.
	 *
	 * @param string $operation Operation name.
	 * @param array $relationship_model Model data coming from the client. It should have the structure that
	 *     Types_Viewmodel_Relationship_Definition uses.
	 *
	 * @return Types_Relationship_Operation_Result
	 *
	 * @since m2m
	 */
	private function single_relationship_action( $operation, $relationship_model ) {

		switch ( $operation ) {
			case 'update':
				return $this->update_relationship( $relationship_model );
			case 'delete':
				return $this->delete_relationship( $relationship_model );
			case 'create':
				return $this->create_relationship( $relationship_model );
			case 'cardinality':
				return $this->advanced_cardinality( $relationship_model );
			default:
				$message = sprintf(
					__( 'Undefined operation "%s" with a relationship definition.', 'wpcf' ),
					$operation
				);

				return new Types_Relationship_Operation_Result( new SingleResult( false, $message ) );
		}
	}


	/**
	 * Update a single relationship definition.
	 *
	 * @param array $relationship_model Model data coming from the client. It should have the structure that
	 *     Types_Viewmodel_Relationship_Definition uses.
	 *
	 * @return Types_Relationship_Operation_Result
	 *
	 * @since m2m
	 */
	private function update_relationship( $relationship_model ) {
		$relationship_display_name = $this->get_display_name_from_js_model( $relationship_model );

		// Checks cardinality limits depending on number of associations.
		$definition_repository = $this->get_definition_repository();
		$definition = $definition_repository->get_definition( $relationship_model['slug'] );

		foreach ( Toolset_Relationship_Role::parent_child() as $role ) {
			foreach ( array( Toolset_Relationship_Cardinality::MAX, Toolset_Relationship_Cardinality::MIN ) as $limit )
			{
				$this->sanitize_cardinality_limit( $role, $limit, $relationship_model, $definition );
			}
		}

		try {
			$viewmodel = $this->get_viewmodel( $relationship_model );
		} catch ( Exception $e ) {
			return new Types_Relationship_Operation_Result(
				new SingleResult(
					$e,
					sprintf( __( 'An error when updating a relationship "%s"', 'wpcf' ), $relationship_display_name )
				)
			);
		}

		$result = $viewmodel->apply_array( $relationship_model );

		if ( $result->has_errors() ) {
			return new Types_Relationship_Operation_Result( $result->aggregate() );
		}

		try {
			$this->get_definition_repository()->persist_definition( $viewmodel->get_model() );
		} catch ( RuntimeException $e ) {
			return new Types_Relationship_Operation_Result(
				new SingleResult(
					$e,
					sprintf( __( 'An error when updating a relationship "%s"', 'wpcf' ), $relationship_display_name )
				)
			);
		}

		return new Types_Relationship_Operation_Result(
			new SingleResult(
				true,
				sprintf( __( 'Relationship "%s" has been updated successfully.', 'wpcf' ), $relationship_display_name )
			),
			$viewmodel
		);
	}


	/**
	 * Sanitize one cardinality value.
	 *
	 * Make sure that the relationship model doesn't contain a value that would break data integrity.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child $role
	 * @param string $limit
	 * @param array $relationship_model
	 * @param IToolset_Relationship_Definition $definition
	 */
	private function sanitize_cardinality_limit(
		RelationshipRoleParentChild $role, $limit, &$relationship_model, IToolset_Relationship_Definition $definition
	) {

		if ( $limit !== Toolset_Relationship_Cardinality::MAX ) {
			return; // Nothing to do.
		}

		$minimal_value_for_max_limit = $definition->get_max_associations( $role->other()->get_name() );
		$new_max_limit = (int) $relationship_model['cardinality'][ $role->get_name() ][ $limit ];

		if ( Toolset_Relationship_Cardinality::INFINITY !== $new_max_limit
			&& $minimal_value_for_max_limit > 1
		) {
			// There is a specific cardinality limit AND a post with more than one association in this relationship.
			// We need to make sure that we won't save a lower value than the number of associations.
			$relationship_model['cardinality'][ $role->get_name() ][ $limit ] = max(
				$minimal_value_for_max_limit,
				$new_max_limit
			);
		}
	}


	/**
	 * Create a single relationship definition.
	 *
	 * @param array $relationship_model Model.
	 *
	 * @return Types_Relationship_Operation_Result
	 * @since m2m
	 */
	private function create_relationship( $relationship_model ) {
		try {
			$parent_type = Toolset_Relationship_Element_Type::build_for_post_type( sanitize_title( $relationship_model['types']['parent']['types'][0] ) );
			$child_type = Toolset_Relationship_Element_Type::build_for_post_type( sanitize_title( $relationship_model['types']['child']['types'][0] ) );

			$repository = $this->get_definition_repository();

			/** @var Toolset_Relationship_Definition $definition */
			$definition = $repository->create_definition( $relationship_model['slug'], $parent_type, $child_type );

			$definition->set_display_name( $relationship_model['displayName'] );
			$definition->set_display_name_singular( $relationship_model['displayNameSingular'] );

			$cardinality = new Toolset_Relationship_Cardinality( $relationship_model['cardinality'] );
			$definition->set_cardinality( $cardinality );

			$definition->is_distinct( true );
			$definition->set_legacy_support_requirement( false );
			$definition->is_autodeleting_intermediary_posts( 'true'
				=== $relationship_model['isAutodeletingIntermediaryPosts'] );

			if ( isset( $relationship_model['intermediary'] ) && 'true' === $relationship_model['intermediary'] ) {
				$is_visible = isset( $relationship_model['visible'] ) && 'true' === $relationship_model['visible'];
				$definition->get_driver()->create_intermediary_post_type( $relationship_model['slug'], $is_visible );
				if ( $cardinality->is_many_to_many() ) {
					// Saving intermediary post fields for m2m relationship.
					$this->save_intermediary_fields( $relationship_model, $definition );
				}
			}
			// Role aliases.
			$setter_names = array(
				'roleNames' => 'set_role_name',
				'roleLabelsPlural' => 'set_role_label_plural',
				'roleLabelsSingular' => 'set_role_label_singular',
			);
			$role_types = Toolset_Relationship_Role::parent_child_role_names();
			foreach ( $setter_names as $property => $method ) {
				foreach ( $role_types as $role_name ) {
					$role_value = isset( $relationship_model[ $property ][ $role_name ] )
						? $relationship_model[ $property ][ $role_name ]
						: '';
					$definition->$method( $role_name, $role_value );
				}
			}
			$repository->persist_definition( $definition );
		} catch ( Exception $e ) {
			return new Types_Relationship_Operation_Result(
				new SingleResult(
					false,
					sprintf(
					// translators: error.
						__( 'Could not create relationship definition because an error happened: %s', 'wpcf' ),
						$e->getMessage()
					)
				)
			);
		} // End try().

		try {
			// Updates relationships.
			$this->get_definition_repository()->load_definitions();
			// Slugs needs to be updated in case there was another relationship with the same slug.
			$relationship_model['slug'] = $definition->get_slug();
			$viewmodel = $this->get_viewmodel( $relationship_model );
		} catch ( Exception $e ) {
			return new Types_Relationship_Operation_Result(
				new SingleResult(
					$e,
					// translators: the name of the relationship.
					sprintf( __( 'An error when creating a relationship "%s"', 'wpcf' ), $relationship_model['displayName'] )
				)
			);
		}

		return new Types_Relationship_Operation_Result(
			new SingleResult(
				true,
				// translators: The name of the relationship created.
				sprintf( __( 'Relationship "%s" has been created successfully.', 'wpcf' ), $relationship_model['displayName'] )
			),
			$viewmodel
		);
	}


	/**
	 * Returns advanced cardinality, lower limits are restricted to numer of associations created.
	 *
	 * @param array $relationship_model Relationship pseudo-model only contains the slug.
	 *
	 * @return Types_Relationship_Operation_Result
	 * @since m2m
	 */
	private function advanced_cardinality( $relationship_model ) {
		$definition_repository = $this->get_definition_repository();
		$definition = $definition_repository->get_definition( $relationship_model['slug'] );
		if ( ! $definition ) {
			return new Types_Relationship_Operation_Result( new SingleResult( false, __( 'Definition does not exist', 'wpcf' ) ) );
		}

		// Cardinality.
		$min_limits = array(
			'child' => $definition->get_max_associations( Toolset_Relationship_Role::PARENT ),
			'parent' => $definition->get_max_associations( Toolset_Relationship_Role::CHILD ),
		);

		$cardinality = $definition->get_cardinality()->to_array();
		$cardinality['parent']['min'] = $min_limits['parent'];
		$cardinality['child']['min'] = $min_limits['child'];

		// Result.
		$data = array(
			'cardinality' => $cardinality,
			'postTypesWithAssociations' => $this->get_post_types_with_associations( $definition ),
			'strings' => array(
				'minimumLimitWarning' => array(
					'parent' => sprintf( __( 'Minimum limit cannot be lower than %d because there are associations with this amount of items.', 'wpcf' ), $min_limits['parent'] ),
					'child' => sprintf( __( 'Minimum limit cannot be lower than %d because there are associations with this amount of items.', 'wpcf' ), $min_limits['child'] ),
				),
			),
		);

		return new Types_Relationship_Operation_Result(
			new SingleResult( true ), null, false, $data
		);
	}


	/**
	 * Saving intermediary fields
	 *
	 * @param array $relationship_model Intemediary data from $_POST.
	 * @param Toolset_Relationship_Definition $definition Relationship definition.
	 *
	 * @since m2m
	 */
	private function save_intermediary_fields( $relationship_model, $definition ) {
		if ( isset( $relationship_model['wpcf'] ) ) {
			$field_group = Types_Field_Group_Viewmodel::get_instance( Toolset_Field_Group_Post::POST_TYPE );
			// Adding extra data not received from the page.
			$relationship_model['group'] = array(
				'name' => sprintf(
				// translators: field group name.
					__( '%s Field Group', 'wpcf' ),
					$relationship_model['displayName']
				),
				'supports' => array(
					$definition->get_driver()->get_intermediary_post_type(),
				),
			);

			$field_group->load_data( $relationship_model );
			$field_group->save( Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS );
		}
	}


	/**
	 * Get a PHP viewmodel from the JS relationship definition model.
	 *
	 * @param array $relationship_model The relationship model.
	 *
	 * @return Types_Viewmodel_Relationship_Definition
	 * @throws InvalidArgumentException If the slug is missing or if the relationship definition doesn't exist.
	 *
	 * @since m2m
	 */
	private function get_viewmodel( $relationship_model ) {
		$slug = toolset_getarr( $relationship_model, 'slug' );
		if ( ! is_string( $slug ) || empty( $slug ) ) {
			throw new InvalidArgumentException();
		}

		return $this->get_definition_viewmodel_factory()->get_viewmodel_by_slug( $slug );
	}


	/**
	 * Get the display name from the relationship defintion JS model, for the purpose of generating
	 * result messages. Use the slug if display name is not available.
	 *
	 * @param array $relationship_model
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_display_name_from_js_model( $relationship_model ) {
		return sanitize_text_field(
			toolset_getarr( $relationship_model, 'displayName', toolset_getarr( $relationship_model, 'slug' ) )
		);
	}


	private function get_definition_repository() {

		if ( null === $this->definition_repository ) {
			$this->definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->definition_repository;
	}


	private function get_definition_viewmodel_factory() {
		if ( null === $this->definition_viewmodel_factory ) {
			$this->definition_viewmodel_factory = new Types_Viewmodel_Relationship_Definition_Factory();
		}

		return $this->definition_viewmodel_factory;
	}


	private function delete_relationship( $relationship_model ) {

		$relationship_display_name = $this->get_display_name_from_js_model( $relationship_model );

		try {
			$viewmodel = $this->get_viewmodel( $relationship_model );
		} catch ( Exception $e ) {
			return new Types_Relationship_Operation_Result(
				new SingleResult(
					$e,
					sprintf( __( 'An error when deleting a relationship "%s"', 'wpcf' ), $relationship_display_name )
				)
			);
		}

		$repository = $this->get_definition_repository();
		$result = $repository->remove_definition( $viewmodel->get_model(), true );

		return new Types_Relationship_Operation_Result(
			$result->aggregate(),
			$viewmodel,
			true
		);
	}


	/**
	 * Get the list of post types of the relationship and if it is involve in an association
	 *
	 * @param IToolset_Relationship_Definition $definition Relationship definition.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_post_types_with_associations( $definition ) {
		$post_types = array();
		foreach ( Toolset_Relationship_Role::parent_child() as $role ) {
			$role_name = $role->get_name();
			$post_types[ $role_name ] = array();
			foreach ( $definition->get_element_type( $role )->get_types() as $type ) {
				$query = new Toolset_Association_Query_V2();
				$query->add( $query->relationship( $definition ) )
					->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $type, $role ) );
				if ( $query->get_found_rows_directly() > 0 ) {
					$post_types[ $role_name ][] = $type;
				}
			}
		}

		return $post_types;
	}
}
