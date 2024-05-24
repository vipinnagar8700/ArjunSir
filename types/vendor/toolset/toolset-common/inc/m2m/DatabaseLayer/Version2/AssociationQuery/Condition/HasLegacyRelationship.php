<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;

/**
 * Query associations by the fact whether the relationship they belong to was migrated from the legacy implementation
 * or not.
 *
 * @since 4.0
 */
class HasLegacyRelationship extends RelationshipFlag {

	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_flag_name() {
		return RelationshipTable::NEEDS_LEGACY_SUPPORT;
	}
}
