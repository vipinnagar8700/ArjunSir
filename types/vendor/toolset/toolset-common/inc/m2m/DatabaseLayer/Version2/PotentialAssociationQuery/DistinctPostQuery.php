<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\PotentialAssociationQuery;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\WpQueryAdjustment;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;

/**
 * Augments WP_Query to check whether posts are associated with a particular other element ($for_element),
 * and dismisses those posts.
 *
 * This is used in OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\PostQuery to handle distinct
 * relationships - to prevent connecting the same pair of elements twice.
 *
 * Both before_query() and after_query() methods need to be called as close to the actual
 * querying as possible, otherwise things will get broken.
 *
 * How this works specifically: We join a set of tables:
 * - connected elements table (while accounting for translatability) on the post ID
 * - associations table on the target role, with a specific restriction to rows where $for_element is connected
 *   on the opposite role
 *
 * Then we just need to check that there is no association row JOINed (that means $for_element isn't there yet
 * and we're free to create a new association with it).
 *
 * @since 4.0
 */
class DistinctPostQuery extends WpQueryAdjustment {

	/**
	 * @inheritDoc
	 */
	protected function is_actionable() {
		return $this->relationship->is_distinct();
	}


	/**
	 * @inheritDoc
	 */
	public function add_join_clauses( $join ) {
		$this->join_manager->register_join( JoinManager::JOIN_ASSOCIATIONS_TABLE );

		return $join;
	}


	/**
	 * @inheritDoc
	 */
	public function add_where_clauses( $where ) {
		$associations_table_alias = JoinManager::ALIAS_ASSOCIATIONS;
		$association_id_column = AssociationTable::ID;
		return "$where AND ( {$associations_table_alias}.{$association_id_column} IS NULL )";
	}


}
