<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;

/**
 * Query associations by the is_active value of a relationship they belong to.
 */
class HasActiveRelationship extends RelationshipFlag {

	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_flag_name() {
		return RelationshipTable::IS_ACTIVE;
	}
}
