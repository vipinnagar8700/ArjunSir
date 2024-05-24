<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\PotentialAssociationQuery;

use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\WpQueryAdjustment;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Augments WP_Query to check whether the posts can accept another association according to the relationship
 * cardinality.
 *
 * This is used in OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\PostQuery.
 *
 * Both before_query() and after_query() methods need to be called as close to the actual
 * querying as possible, otherwise things will get broken.
 *
 * How this works specifically: We join the connected elements table for the target role on post IDs (while
 * taking into account translatability) and then count how many associations of the given relationship
 * exist for each post. If it's more than the allowed cardinality limit, the post is excluded. Obviously,
 * this is done only when there actually is a limit.
 *
 * @since 4.0
 */
class CardinalityPostQuery extends WpQueryAdjustment {


	/** @var TableNames */
	private $table_names;


	/**
	 * CardinalityPostQuery constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param \IToolset_Element $for_element
	 * @param JoinManager $join_manager
	 * @param WpmlService $wpml_service
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct(
		\IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		\IToolset_Element $for_element,
		JoinManager $join_manager,
		WpmlService $wpml_service,
		\wpdb $wpdb,
		TableNames $table_names
	) {
		parent::__construct( $relationship, $target_role, $for_element, $join_manager, $wpml_service, $wpdb );

		$this->table_names = $table_names;
	}


	/**
	 * @inheritDoc
	 */
	protected function is_actionable() {
		return true;
	}


	/**
	 * @inheritDoc
	 */
	public function add_join_clauses( $join ) {
		$this->join_manager->register_join( JoinManager::JOIN_CONNECTED_ELEMENT_TABLE_TARGET_ROLE );

		return $join;
	}


	/**
	 * @inheritDoc
	 */
	public function add_where_clauses( $where ) {
		if( $this->needs_cardinality_limit_check() ) {
			$where .= ' ' . $this->build_where_clause() . ' ';
		}

		return $where;
	}


	private function build_where_clause() {
		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$relationship_id_column = AssociationTable::RELATIONSHIP_ID;
		$element_group_id_column = AssociationTable::role_to_column( $this->target_role );
		$connected_elements_alias = JoinManager::ALIAS_CONNECTED_ELEMENTS_TARGET_ROLE;
		$connected_elements_group_id = ConnectedElementTable::GROUP_ID;

		return $this->wpdb->prepare(
			"AND ( %d > (
				SELECT COUNT(*) FROM {$associations_table} AS associations
				WHERE (
					associations.{$relationship_id_column} = %d
					AND associations.{$element_group_id_column} = {$connected_elements_alias}.{$connected_elements_group_id}
				)
			) )",
			$this->get_for_element_max_cardinality(),
			$this->relationship->get_row_id()
		);
	}


	private function get_for_element_max_cardinality() {
		return $this->relationship->get_cardinality()->get_limit( $this->target_role->other() );
	}


	private function needs_cardinality_limit_check() {
		if( ! $this->is_actionable() ) {
			return false;
		}

		if( \Toolset_Relationship_Cardinality::INFINITY === $this->get_for_element_max_cardinality() ) {
			return false;
		}

		return true;
	}



}
