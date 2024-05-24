<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use Toolset_Element_Domain;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Relationship_Definition_Repository;
use wpdb;

/**
 * Ensure the relationship data integrity *while* the migration is underway.
 *
 * This is the only problematic situation that we actually need to handle is when one or more associations are being
 * deleted. If they have already been migrated, they would magically reappear after the migration completes.
 *
 * @since 4.0.10
 */
class DuringMigrationIntegrity {

	/** @var IsMigrationUnderwayOption */
	private $is_migration_underway_option;

	/** @var TableNames */
	private $table_names;

	/** @var wpdb */
	private $wpdb;

	/** @var ConnectedElementPersistence */
	private $connected_element_persistence;

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/**
	 * DuringMigrationIntegrity constructor.
	 *
	 * @param IsMigrationUnderwayOption $is_migration_underway_option
	 * @param TableNames $table_names
	 * @param wpdb $wpdb
	 * @param ConnectedElementPersistence $connected_element_persistence
	 * @param Toolset_Relationship_Definition_Repository $relationship_definition_repository
	 */
	public function __construct(
		IsMigrationUnderwayOption $is_migration_underway_option,
		TableNames $table_names,
		wpdb $wpdb,
		ConnectedElementPersistence $connected_element_persistence,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository
	) {
		$this->is_migration_underway_option = $is_migration_underway_option;
		$this->table_names = $table_names;
		$this->wpdb = $wpdb;
		$this->connected_element_persistence = $connected_element_persistence;
		$this->relationship_definition_repository = $relationship_definition_repository;
	}


	/**
	 * Handle a single association being deleted.
	 *
	 * Here, we identify its ID in the new associations table and delete it there, too.
	 *
	 * @param string $relationship_slug
	 * @param int $parent_id
	 * @param int $child_id
	 */
	public function synchronize_deleted_association( $relationship_slug, $parent_id, $child_id ) {
		if ( ! $this->is_migration_underway_option->getOption() ) {
			return;
		}

		$new_associations_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME );
		if ( ! $this->table_names->table_exists( $new_associations_table ) ) {
			return;
		}

		$relationship_definition = $this->relationship_definition_repository->get_definition( $relationship_slug );
		if ( null === $relationship_definition ) {
			return;
		}

		$parent_group = $this->connected_element_persistence->get_element_group_by_element_id( $parent_id, Toolset_Element_Domain::POSTS );
		$child_group = $this->connected_element_persistence->get_element_group_by_element_id( $child_id, Toolset_Element_Domain::POSTS );

		if ( ! $parent_group || ! $child_group ) {
			return;
		}

		$this->wpdb->delete(
			$new_associations_table,
			[
				AssociationTable::RELATIONSHIP_ID => $relationship_definition->get_row_id(),
				AssociationTable::PARENT_ID => $parent_group->get_id(),
				AssociationTable::CHILD_ID => $child_group->get_id(),
			],
			'%d'
		);
	}


	/**
	 * Handle the situation when permanently deleting an element with associations.
	 *
	 * @param int $element_id
	 * @param string $element_domain
	 */
	public function synchronize_deleted_associations_by_element( $element_id, $element_domain ) {
		if ( ! $this->is_migration_underway_option->getOption() ) {
			return;
		}

		$new_associations_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME );
		if ( ! $this->table_names->table_exists( $new_associations_table ) ) {
			return;
		}

		$element_group = $this->connected_element_persistence->get_element_group_by_element_id( $element_id, $element_domain );

		if ( ! $element_group ) {
			return;
		}

		$parent_id_column = AssociationTable::PARENT_ID;
		$child_id_column = AssociationTable::CHILD_ID;
		$intermediary_id_column = AssociationTable::INTERMEDIARY_ID;

		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$domain_column = ConnectedElementTable::DOMAIN;
		$group_id_column = ConnectedElementTable::GROUP_ID;

		$this->wpdb->query( $this->wpdb->prepare(
			"DELETE association
			FROM $new_associations_table AS association
			JOIN $connected_elements_table AS parent_elements
				ON ( association.$parent_id_column = parent_elements.$group_id_column )
			JOIN $connected_elements_table AS child_elements
				ON ( association.$child_id_column = child_elements.$group_id_column )
			LEFT JOIN $connected_elements_table AS intermediary_elements
				ON ( association.$intermediary_id_column = intermediary_elements.$group_id_column )
			WHERE
				( parent_elements.$domain_column = %s AND parent_elements.$group_id_column = %d )
				OR ( child_elements.$domain_column = %s AND child_elements.$group_id_column = %d )
				OR ( intermediary_elements.$domain_column = %s AND intermediary_elements.$group_id_column = %d )",
			$element_domain,
			$element_group->get_id(),
			$element_domain,
			$element_group->get_id(),
			$element_domain,
			$element_group->get_id()
		) );

	}
}
