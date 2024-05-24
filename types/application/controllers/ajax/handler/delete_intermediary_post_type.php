<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;

/**
 * Handle deleting IPT and its data on the Relationships page.
 *
 * @since m2m
 */
class Types_Ajax_Handler_Delete_Intermediary_Post_Type extends Toolset_Ajax_Handler_Abstract {

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;

	/** @var Toolset_Field_Group_Post_Factory */
	private $field_group_factory;

	/** @var string */
	private $relationship_slug;

	/** @var IToolset_Relationship_Definition */
	private $current_definition;

	/** @var Toolset_Association_Cleanup_Post_Type */
	private $clean_up;


	/**
	 * Types_Ajax_Handler_Delete_Intermediary_Post_Type constructor.
	 *
	 * Includes dependency injection arguments which can be used only with mocks.
	 *
	 * @param Types_Ajax $ajax_manager Ajas manager.
	 * @param Toolset_Relationship_Definition_Repository|null $relationship_definition_repository
	 * @param Toolset_Post_Type_Repository|null $post_type_repository
	 * @param Toolset_Field_Group_Post_Factory|null $field_group_factory
	 * @param Toolset_Association_Cleanup_Post_Type|null $clean_up
	 */
	public function __construct(
		Types_Ajax $ajax_manager,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository = null,
		Toolset_Post_Type_Repository $post_type_repository = null,
		Toolset_Field_Group_Post_Factory $field_group_factory = null,
		Toolset_Association_Cleanup_Post_Type $clean_up = null
	) {
		parent::__construct( $ajax_manager );

		$this->relationship_definition_repository = $relationship_definition_repository;

		$this->post_type_repository = $post_type_repository;

		$this->field_group_factory = $field_group_factory;

		$this->clean_up = $clean_up;
	}


	/**
	 * @return Toolset_Association_Cleanup_Post_Type
	 */
	private function get_post_type_clean_up() {
		if ( null === $this->clean_up ) {
			do_action( 'toolset_do_m2m_full_init' );
			$this->clean_up = new Toolset_Association_Cleanup_Post_Type();
		}

		return $this->clean_up;
	}


	/**
	 * @return Toolset_Relationship_Definition_Repository
	 */
	private function get_relationship_definition_repository() {
		if ( null === $this->relationship_definition_repository ) {
			do_action( 'toolset_do_m2m_full_init' );
			$this->relationship_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->relationship_definition_repository;
	}


	/**
	 * @return Toolset_Post_Type_Repository
	 */
	private function get_post_type_repository() {
		if ( null === $this->post_type_repository ) {
			do_action( 'toolset_do_m2m_full_init' );
			$this->post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}

		return $this->post_type_repository;
	}


	/**
	 * @return Toolset_Field_Group_Post_Factory
	 */
	private function get_field_group_factory() {
		if ( null === $this->field_group_factory ) {
			do_action( 'toolset_do_m2m_full_init' );
			$this->field_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
		}

		return $this->field_group_factory;
	}


	/**
	 * @return IToolset_Relationship_Definition|null
	 */
	private function get_current_definition() {
		$this->relationship_definition_repository = $this->get_relationship_definition_repository();

		return $this->relationship_definition_repository->get_definition( $this->relationship_slug );
	}


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$am = $this->get_ajax_manager();

		$am->ajax_begin( array(
			'nonce' => $am->get_action_js_name( Types_Ajax::CALLBACK_DELETE_INTERMEDIARY_POST_TYPE_ACTION ),
		) );

		$this->relationship_slug = toolset_getarr( $_POST, 'relationship' );

		if ( ! $this->relationship_slug ) {
			$am->ajax_finish( array(
				'messages' => __( 'There has been an error when deleting intermediary posts', 'wpcf' ),
			),
				false
			);
		}

		$this->current_definition = $this->get_current_definition();

		$post_type_data = toolset_getarr( $_POST, 'post_type' );

		if ( ! $post_type_data ) {
			$am->ajax_finish(
				array(
					'messages' => __( 'There has been an error when deleting intermediary posts', 'wpcf' ),
				),
				false
			);
		}

		$post_type = toolset_getarr( $post_type_data, 'type' );

		if ( ! $post_type ) {
			$am->ajax_finish(
				array(
					'messages' => __( 'There has been an error when deleting intermediary posts', 'wpcf' ),
				),
				false
			);
		}

		if ( $this->current_definition->get_intermediary_post_type() !== $post_type ) {
			$am->ajax_finish(
				array(
					'messages' => sprintf( __( 'There is an inconsistency between the post_type defined in the relationship: %s and that you want to delete: %s', 'wpcf' ), $this->current_definition->get_intermediary_post_type(), $post_type ),
				),
				false
			);
		}

		$toolset_results = array();

		// Cleaning up IPT associations.
		$toolset_results['post_type_associations'] = $this->clean_up_associations();

		if ( ! $toolset_results['post_type_associations']['remaining_associations'] ) {
			$this->clean_up = $this->get_post_type_clean_up();

			$toolset_results['post_type_posts'] = $this->clean_up->clean_up_posts( $post_type );

			$post_type_repository = $this->get_post_type_repository();

			$post_type_object = $post_type_repository->get( $post_type );

			// if post type still exists then delete it with its data.
			if (
				$post_type_object instanceof IToolset_Post_Type_From_Types
				&& $toolset_results['post_type_posts']['total_posts'] <= Constants::DELETE_POSTS_PER_BATCH
			) {
				$toolset_results['post_type_data'] = $this->delete_post_type_and_data( $post_type_object );
			}
		} else {
			$toolset_results['post_type_posts'] = array(
				// Same number as number of associations.
				'total_posts' => $toolset_results['post_type_associations']['total_associations'],
				'deleted_posts' => 0,
			);
		}

		$am->ajax_finish(
			array(
				'messages' => sprintf( __( 'Post type "%s" and its data have been deleted.', 'wpcf' ), $post_type ),
				'results' => $toolset_results,
			)
		);
	}


	/**
	 * @param IToolset_Post_Type_From_Types $post_type_object
	 *
	 * @return array
	 */
	private function delete_post_type_and_data( IToolset_Post_Type_From_Types $post_type_object ) {
		$slug = $post_type_object->get_slug();
		$groups_count = $this->delete_group_data( $slug );
		$this->delete_post_type( $post_type_object );
		$toolset_results = array();
		$toolset_results['deleted_groups'] = $groups_count;

		return $toolset_results;
	}


	/**
	 * @param string $slug
	 *
	 * @return int
	 */
	private function delete_group_data( $slug ) {
		$this->field_group_factory = $this->get_field_group_factory();

		$groups = $this->field_group_factory->get_groups_by_post_type( $slug );
		$groups_count = 0;

		add_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );

		foreach ( $groups as $group ) {
			wp_delete_post( $group->get_id() );
			$groups_count ++;
		}

		remove_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );

		return $groups_count;
	}


	/**
	 * @param IToolset_Post_Type_From_Types $post_type_object
	 */
	private function delete_post_type( IToolset_Post_Type_From_Types $post_type_object ) {

		$post_type_object->unset_as_intermediary();

		$post_type_repository = $this->get_post_type_repository();

		$post_type_repository->delete( $post_type_object );

		$this->current_definition->set_intermediary_post_type( null );

		$this->relationship_definition_repository->persist_definition( $this->current_definition );

	}


	/**
	 * Clean IPTs from associations
	 *
	 * @since m2m
	 */
	private function clean_up_associations() {
		$result = array();
		$intermediary_post_persistence = new Toolset_Association_Intermediary_Post_Persistence( $this->current_definition );
		$result['updated_associations'] = $intermediary_post_persistence->remove_associations_intermediary_posts( Toolset_Association_Intermediary_Post_Persistence::DEFAULT_LIMIT );
		$result['remaining_associations'] = $this->count_remaining_associations();
		$result['total_associations'] = $this->count_total_associations();

		return $result;
	}


	/**
	 * Get number of associations with IPT
	 *
	 * @return int
	 * @since 3.0
	 */
	private function count_remaining_associations() {
		return $this->count_associations( true );
	}


	/**
	 * Get number of associations
	 *
	 * @return int
	 * @since 3.0
	 */
	private function count_total_associations() {
		return $this->count_associations();
	}


	/**
	 * Get number of associations with IPT
	 *
	 * @param boolean $with_ipt Count only associations with IPT.
	 *
	 * @return int
	 * @since 3.0
	 */
	private function count_associations( $with_ipt = false ) {
		$query = new Toolset_Association_Query_V2();
		$query
			->add(
				$query->relationship( $this->current_definition )
			);
		if ( $with_ipt ) {
			$query->add( $query->has_intermediary_id() );
		}

		return $query->get_found_rows_directly();
	}
}
