<?php

namespace OTGS\Toolset\Common\Relationships\Relationship;

use IToolset_Post_Type_From_Types;
use OTGS\Toolset\Common\Relationships\API\AssociationDatabaseOperations;
use Toolset_Field_Group_Post_Factory;
use Toolset_Post_Type_Repository;

/**
 * Handles cleanup when deleting a relationship definition.
 *
 * @since Types 3.3
 */
class Cleanup {

	/** @var \Toolset_Relationship_Definition */
	private $definition;


	/** @var AssociationDatabaseOperations */
	private $database_operations;


	/** @var \Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;


	/** @var \Toolset_Cron */
	private $cron;


	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/** @var Toolset_Field_Group_Post_Factory */
	private $post_field_group_factory;


	/**
	 * Cleanup constructor.
	 *
	 * @param \Toolset_Relationship_Definition $definition
	 * @param AssociationDatabaseOperations $database_operations
	 * @param \Toolset_Association_Cleanup_Factory $cleanup_factory
	 * @param \Toolset_Cron $cron
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 * @param Toolset_Field_Group_Post_Factory $post_field_group_factory
	 */
	public function __construct(
		\Toolset_Relationship_Definition $definition,
		AssociationDatabaseOperations $database_operations,
		\Toolset_Association_Cleanup_Factory $cleanup_factory,
		\Toolset_Cron $cron,
		Toolset_Post_Type_Repository $post_type_repository,
		Toolset_Field_Group_Post_Factory $post_field_group_factory
	) {
		$this->definition = $definition;
		$this->database_operations = $database_operations;
		$this->cleanup_factory = $cleanup_factory;
		$this->cron = $cron;
		$this->post_type_repository = $post_type_repository;
		$this->post_field_group_factory = $post_field_group_factory;
	}


	/**
	 * Clean up after the given relationship definition.
	 *
	 * @return \Toolset_Result_Set
	 */
	public function do_cleanup() {
		$results = new \Toolset_Result_Set();

		// delete associations of relationship
		$results->add( $this->database_operations->delete_associations_by_relationship(
			$this->definition->get_row_id()
		) );

		$intermediary_post_type_slug = $this->definition->get_intermediary_post_type();
		if ( null === $intermediary_post_type_slug ) {
			// We're done here.
			return $results;
		}

		$intermediary_post_type = $this->post_type_repository->get( $intermediary_post_type_slug );
		if ( ! $intermediary_post_type instanceof IToolset_Post_Type_From_Types ) {
			// The IPT doesn't come from Types, which shouldn't be happening - better bail.
			return $results;
		}

		$groups = $this->post_field_group_factory->get_groups_by_post_type( $intermediary_post_type->get_slug() );
		foreach ( $groups as $group ) {
			wp_delete_post( $group->get_id() );
			/* translators: Output message when a relationship is being deleted. */
			$results->add( true, __( 'Deleted the intermediary post type field group.', 'wpv-views' ) );
		}

		$this->post_type_repository->delete( $intermediary_post_type );
		$results->add(
			true,
			/* translators: Output message when a relationship is being deleted. The placeholder is a post type slug. */
			sprintf( __( 'Deleted the intermediary post type "%s".', 'wpv-views' ), $intermediary_post_type_slug )
		);

		$dip_cleanup = $this->cleanup_factory->dangling_intermediary_posts();
		$dip_cleanup->mark_deletion_by_post_type( $intermediary_post_type_slug );
		$dip_cleanup->do_batch();
		if ( $dip_cleanup->has_remaining_posts() ) {
			$cron_event = $this->cleanup_factory->cron_event();
			$this->cron->schedule_event( $cron_event );
		}

		return $results;
	}

}
