<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;
use wpdb;

/**
 * Shared functionality for all element selector implementations.
 *
 * @since 4.0
 */
abstract class AbstractSelector
	implements ElementSelectorInterface {


	/** @var UniqueTableAlias */
	protected $table_alias;


	/** @var TableJoinManager */
	protected $join_manager;


	/** @var wpdb */
	protected $wpdb;


	/** @var WpmlService */
	protected $wpml_service;


	/** @var RelationshipRole[] */
	protected $requested_roles = array();


	/** @var bool */
	private $requested_association_and_relationship = false;


	/** @var bool */
	private $requested_distinct_query = false;


	/** @var bool Indicates whether intermediary post column can be skipped for the result transformation. */
	private $skip_intermediary_posts = false;


	/** @var TableNames */
	protected $table_names;


	/**
	 * @param UniqueTableAlias $table_alias
	 * @param TableJoinManager $join_manager
	 * @param wpdb $wpdb
	 * @param WpmlService $wpml_service
	 * @param TableNames $table_names
	 */
	public function __construct(
		UniqueTableAlias $table_alias,
		TableJoinManager $join_manager,
		wpdb $wpdb,
		WpmlService $wpml_service,
		TableNames $table_names
	) {
		$this->table_alias = $table_alias;
		$this->join_manager = $join_manager;
		$this->wpml_service = $wpml_service;
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}


	/**
	 * @inheritdoc
	 */
	public function initialize() {
		// Nothing to do here.
	}


	/**
	 * @inheritdoc
	 *
	 * @param RelationshipRole $role
	 */
	public function request_element_in_results( RelationshipRole $role ) {
		$this->requested_roles[ $role->get_name() ] = $role;
	}


	/**
	 * @inheritdoc
	 */
	public function request_association_and_relationship_in_results() {
		$this->requested_association_and_relationship = true;
	}


	/**
	 * Get the select clauses for association and relationship IDs if they have been requested.
	 *
	 * @return string[]
	 */
	protected function maybe_get_association_and_relationship() {
		if ( ! $this->requested_association_and_relationship ) {
			return [];
		}
		return [
			sprintf(
				'%1$s.%2$s as %3$s',
				TableJoinManager::ALIAS_ASSOCIATIONS,
				AssociationTable::ID,
				SelectedColumnAliases::FIXED_ALIAS_ID
			),
			sprintf(
				'%1$s.%2$s as %3$s',
				TableJoinManager::ALIAS_ASSOCIATIONS,
				AssociationTable::RELATIONSHIP_ID,
				SelectedColumnAliases::FIXED_ALIAS_RELATIONSHIP_ID
			),
		];
	}


	/**
	 * @inheritdoc
	 *
	 * @since 2.6.1
	 */
	public function request_distinct_query() {
		$this->requested_distinct_query = true;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 * @since 2.6.1
	 */
	public function maybe_get_distinct_modifier() {
		return ( $this->requested_distinct_query ? 'DISTINCT' : '' );
	}


	/**
	 * @inheritDoc
	 */
	public function get_requested_element_roles() {
		return $this->requested_roles;
	}


	/**
	 * @inheritDoc
	 *
	 * @param bool $skip
	 */
	public function skip_intermediary_posts( $skip = true ) {
		$this->skip_intermediary_posts = (bool) $skip;
	}


	/**
	 * @inheritDoc
	 */
	public function should_skip_intermediary_posts() {
		return $this->skip_intermediary_posts;
	}


	/**
	 * @inheritDoc
	 */
	public function get_element_trid_value( RelationshipRole $for_role ) {
		return null;
	}


}
