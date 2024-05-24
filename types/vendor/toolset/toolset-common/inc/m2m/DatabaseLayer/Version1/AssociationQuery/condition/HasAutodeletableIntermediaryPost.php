<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

/**
 * Condition that filters associations by the fact whether they have an intermediary post
 * that can be automatically deleted together with the association (which is a setting of the relationship definition).
 *
 * @since Types 3.2
 */
class HasAutodeletableIntermediaryPost extends \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Relationship_Flag {


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf(
			' (
				associations.intermediary_id IS NOT NULL
				AND associations.intermediary_id > 0
				AND ( %s )
			) ',
			parent::get_where_clause()
		);
	}

	/**
	 * Get the name of the column in the relationships table to query by.
	 *
	 * @return string
	 */
	protected function get_flag_name() {
		return 'autodelete_intermediary';
	}
}
