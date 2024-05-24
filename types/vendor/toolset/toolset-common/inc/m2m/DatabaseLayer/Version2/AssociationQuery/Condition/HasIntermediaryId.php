<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;

/**
 * Condition to query associations by a specific intermediary post (row) ID.
 */
class HasIntermediaryId extends AbstractCondition {


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf(
			' ( %1$s.%2$s IS NOT NULL AND %1$s.%2$s > 0 ) ',
			TableJoinManager::ALIAS_ASSOCIATIONS,
			AssociationTable::INTERMEDIARY_ID
		);
	}
}
