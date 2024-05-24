<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence;

use IToolset_Association;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use Toolset_Association_Cleanup_Factory;

/**
 * Handles the persistence of associations.
 *
 * See interface description for further info.
 *
 * @since 4.0
 */
class AssociationPersistence implements \OTGS\Toolset\Common\Relationships\DatabaseLayer\AssociationPersistence {

	/** @var \wpdb */
	private $wpdb;

	/** @var ConnectedElementPersistence */
	private $connected_element_persistence;

	/** @var TableNames */
	private $table_names;

	/** @var \Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var AssociationTranslator */
	private $association_translator;

	/** @var Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;


	/**
	 * AssociationPersistence constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param ConnectedElementPersistence $connected_element_persistence
	 * @param TableNames $table_names
	 * @param \Toolset_Relationship_Definition_Repository $relationship_definition_repository
	 * @param AssociationTranslator $association_translator
	 * @param Toolset_Association_Cleanup_Factory $cleanup_factory
	 */
	public function __construct(
		\wpdb $wpdb,
		ConnectedElementPersistence $connected_element_persistence,
		TableNames $table_names,
		\Toolset_Relationship_Definition_Repository $relationship_definition_repository,
		AssociationTranslator $association_translator,
		Toolset_Association_Cleanup_Factory $cleanup_factory
	) {
		$this->wpdb = $wpdb;
		$this->connected_element_persistence = $connected_element_persistence;
		$this->table_names = $table_names;
		$this->relationship_definition_repository = $relationship_definition_repository;
		$this->association_translator = $association_translator;
		$this->cleanup_factory = $cleanup_factory;
	}


	/**
	 * @inheritDoc
	 */
	public function load_association_by_uid( $association_uid ) {
		$association_uid_column = AssociationTable::ID;

		$row = $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT *
			FROM {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )}
			WHERE {$association_uid_column} = %d",
			$association_uid
		), ARRAY_A );

		try {
			return $this->association_translator->from_database_row_direct( $row );
		} catch( BrokenAssociationException $e ) {
			return null;
		}
	}


	/**
	 * @inheritDoc
	 */
	public function insert_association( IToolset_Association $association ) {
		$row = $this->association_translator->to_database_row( $association );

		$this->wpdb->insert(
			$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
			$row,
			$this->association_translator->get_database_row_formats()
		);

		$row[ AssociationTable::ID ] = $this->wpdb->insert_id;
		$updated_association = $this->association_translator->from_database_row_direct( $row );

		$this->report_inserted_association( $updated_association );
		return $updated_association;
	}


	/**
	 * @inheritDoc
	 */
	public function delete_association( IToolset_Association $association ) {
		$this->report_before_association_delete( $association );
		$cleanup = $this->cleanup_factory->association();

		return $cleanup->delete( $association );
	}


	/**
	 * Do the toolset_association_created action.
	 *
	 * See report_association_change() for action parameter information.
	 *
	 * @param IToolset_Association $association
	 *
	 * @since 2.7
	 */
	private function report_inserted_association( IToolset_Association $association ) {
		// Important, used for cache flushing.
		$this->report_association_change( $association, 'toolset_association_created' );
	}


	/**
	 * Do the toolset_before_association_delete action.
	 *
	 * See report_association_change() for action parameter information.
	 *
	 * @param IToolset_Association $association
	 *
	 * @since 2.7
	 */
	public function report_before_association_delete( IToolset_Association $association ) {
		$this->report_association_change( $association, 'toolset_before_association_delete' );
	}


	/**
	 * Do an action that indicates a change to an association.
	 *
	 * Action parameters:
	 * - (string) $relationship_slug
	 * - (int) $parent_id
	 * - (int) $child_id
	 * - (int) $intermediary_id, zero if there is none.
	 * - (int) $association_uid: An internal identifier for the association. May become useful in the future.
	 *
	 * Note that all element IDs will come in their default language version.
	 *
	 * @param IToolset_Association $association
	 * @param string $action_name Name of the hook.
	 */
	private function report_association_change( IToolset_Association $association, $action_name ) {
		$intermediary_post_id = ( $association->has_intermediary_post()
			? $association->get_element( new \Toolset_Relationship_Role_Intermediary() )->get_default_language_id()
			: 0
		);

		do_action(
			$action_name,
			$association->get_definition()->get_slug(),
			$association->get_element( new \Toolset_Relationship_Role_Parent() )->get_default_language_id(),
			$association->get_element( new \Toolset_Relationship_Role_Child() )->get_default_language_id(),
			$intermediary_post_id,
			$association->get_uid()
		);
	}

}
