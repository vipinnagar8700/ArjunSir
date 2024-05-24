<?php
namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;

/**
 * Condition that filters associations by the fact whether they have an intermediary post
 * that can be automatically deleted together with the association (which is a setting of the relationship definition).
 *
 * @since 4.0
 */
class HasAutodeletableIntermediaryPost extends RelationshipFlag {


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf(
			' (
				%1$s.%2$s IS NOT NULL
				AND %1$s.%2$s > 0
				AND ( %3$s )
			) ',
			TableJoinManager::ALIAS_ASSOCIATIONS,
			AssociationTable::INTERMEDIARY_ID,
			parent::get_where_clause()
		);
	}

	/**
	 * Get the name of the column in the relationships table to query by.
	 *
	 * @return string
	 */
	protected function get_flag_name() {
		return RelationshipTable::AUTODELETE_INTERMEDIARY;
	}
}
