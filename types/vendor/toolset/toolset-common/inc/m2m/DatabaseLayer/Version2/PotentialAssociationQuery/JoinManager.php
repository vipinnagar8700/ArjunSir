<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\PotentialAssociationQuery;

use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Handle the MySQL JOIN clause construction when augmenting the WP_Query in
 * OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\PostQuery.
 *
 * Make sure that JOINs come in the right order and are not duplicated.
 *
 * Note that hook() and unhook() must be called around the WP_Query usage for proper function.
 *
 * @since 4.0
 */
class JoinManager implements \OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\JoinManager {

	// Values that can be used as parameters of the register_join() method.
	//
	//
	const JOIN_CONNECTED_ELEMENT_TABLE_TARGET_ROLE = 'join_connected_element_table_target';

	const JOIN_ASSOCIATIONS_TABLE = 'join_associations_table';


	// Table aliases of JOINed tables that can be used in WHERE clauses.
	//
	//
	const ALIAS_CONNECTED_ELEMENTS_TARGET_ROLE = 'toolset_pa_connected_elements_target_role';

	const ALIAS_ASSOCIATIONS = 'toolset_pa_associations';


	/** @var string[] Keywords determining what needs to be joined */
	private $tables_to_join = [];

	/**
	 * @var \IToolset_Element The element for which are querying other elements that can be associated with it.
	 */
	private $for_element;

	/**
	 * @var RelationshipRoleParentChild Role, in which the queried elements are supposed to be connected
	 *        to the $for_element.
	 */
	private $target_role;

	/** @var \IToolset_Relationship_Definition */
	private $relationship;

	/** @var WpmlService */
	private $wpml_service;

	/** @var TableNames */
	private $table_names;

	/** @var \wpdb */
	private $wpdb;


	/**
	 * JoinManager constructor.
	 *
	 * @param RelationshipRoleParentChild $target_role
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param \IToolset_Element $for_element
	 * @param \wpdb $wpdb
	 * @param WpmlService $wpml_service
	 * @param TableNames $table_names
	 */
	public function __construct(
		RelationshipRoleParentChild $target_role,
		\IToolset_Relationship_Definition $relationship,
		\IToolset_Element $for_element,
		\wpdb $wpdb,
		WpmlService $wpml_service,
		TableNames $table_names
	) {
		$this->target_role = $target_role;
		$this->relationship = $relationship;
		$this->for_element = $for_element;
		$this->wpdb = $wpdb;
		$this->wpml_service = $wpml_service;
		$this->table_names = $table_names;
	}


	/**
	 * @inheritDoc
	 */
	public function hook() {
		// The priority is later so that we can add all the JOINs necessary at once.
		\add_filter( 'posts_join', array( $this, 'add_join_clauses' ), 20 );
	}


	/**
	 * @inheritDoc
	 */
	public function unhook() {
		\remove_filter( 'posts_join', array( $this, 'add_join_clauses' ), 20 );
	}


	/**
	 * @inheritDoc
	 */
	public function register_join( $table_keyword ) {
		if ( in_array( $table_keyword, $this->tables_to_join, true ) ) {
			return;
		}

		// Handle dependencies between joins.
		switch ( $table_keyword ) {
			case self::JOIN_ASSOCIATIONS_TABLE:
				$this->register_join( self::JOIN_CONNECTED_ELEMENT_TABLE_TARGET_ROLE );
				break;
		}

		$this->tables_to_join[] = $table_keyword;
	}


	/**
	 * @inheritDoc
	 */
	public function add_join_clauses( $join ) {
		foreach ( array_unique( $this->tables_to_join ) as $table_keyword ) {
			$join .= ' ' . $this->build_single_join( $table_keyword ) . ' ';
		}

		return $join;
	}


	private function build_single_join( $table_keyword ) {
		switch ( $table_keyword ) {
			case self::JOIN_CONNECTED_ELEMENT_TABLE_TARGET_ROLE:
				return $this->join_connected_element_table_for_target_role();
			case self::JOIN_ASSOCIATIONS_TABLE:
				return $this->join_association_table();
		}

		throw new \InvalidArgumentException( 'Invalid table keyword' );
	}


	/**
	 * Join the connected element table to the target element, while taking into account
	 * WPML presence - by using TRID or post ID, whichever is available.
	 *
	 * @return string
	 */
	private function join_connected_element_table_for_target_role() {
		$icl_translations = $this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS );
		$icl_translations_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_element_type = IclTranslationsTable::ELEMENT_TYPE;
		$icl_translations_trid = IclTranslationsTable::TRID;

		$wp_posts = $this->wpdb->posts;

		$connected_elements = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$connected_elements_trid = ConnectedElementTable::WPML_TRID;
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_domain = ConnectedElementTable::DOMAIN;
		$alias_connected_elements = self::ALIAS_CONNECTED_ELEMENTS_TARGET_ROLE;

		$post_domain = \Toolset_Element_Domain::POSTS;

		if ( $this->wpml_service->is_wpml_active_and_configured() ) {
			return
				"LEFT JOIN {$icl_translations} AS toolset_icl_translations
					ON (
						toolset_icl_translations.{$icl_translations_element_id} = {$wp_posts}.ID
						AND toolset_icl_translations.{$icl_translations_element_type} LIKE 'post_%'
					)
				LEFT JOIN {$connected_elements} AS {$alias_connected_elements}
					ON (
						(
							{$alias_connected_elements}.{$connected_elements_trid} = toolset_icl_translations.{$icl_translations_trid}
							OR {$alias_connected_elements}.{$connected_elements_element_id} = {$wp_posts}.ID
						)
						AND {$alias_connected_elements}.{$connected_elements_domain} = '{$post_domain}'
					)";
		}

		return
			"LEFT JOIN {$connected_elements} AS {$alias_connected_elements}
				ON (
					{$alias_connected_elements}.{$connected_elements_element_id} = {$wp_posts}.ID
					AND {$alias_connected_elements}.{$connected_elements_domain} = '{$post_domain}'
				)";
	}


	/**
	 * Join the association table on associations to the target element in the given relationship.
	 *
	 * It also adds a condition on the JOIN clause that requires the $for_element to be on the opposite role.
	 * Note that we're using the group ID of $for_element directly to prevent having multiple rows for the same
	 * post in the results.
	 *
	 * @return string
	 */
	private function join_association_table() {
		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$associations_alias = self::ALIAS_ASSOCIATIONS;
		$associations_relationship_id = AssociationTable::RELATIONSHIP_ID;

		$connected_elements_target_role_alias = self::ALIAS_CONNECTED_ELEMENTS_TARGET_ROLE;
		$connected_elements_group_id = ConnectedElementTable::GROUP_ID;

		$target_role_column = AssociationTable::role_to_column( $this->target_role );
		$current_relationship_id = (int) $this->relationship->get_row_id();

		$for_element_role_column = AssociationTable::role_to_column( $this->target_role->other() );
		$for_element_group_id = (int) $this->for_element->get_connected_group_id( false );

		return
			"LEFT JOIN {$associations_table} AS {$associations_alias}
				ON (
					{$connected_elements_target_role_alias}.{$connected_elements_group_id} = {$associations_alias}.{$target_role_column}
					AND {$associations_alias}.{$associations_relationship_id} = {$current_relationship_id}
					AND {$associations_alias}.{$for_element_role_column} = {$for_element_group_id}
				)";
	}
}
