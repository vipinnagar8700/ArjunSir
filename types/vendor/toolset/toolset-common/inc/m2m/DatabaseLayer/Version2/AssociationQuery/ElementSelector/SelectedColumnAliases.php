<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;

/**
 * Pseudo-enum for standardized aliases of columns in the SELECT clause of association queries.
 *
 * FIXED_* values can be relied on throughout the database layer. Element ID column aliases should be
 * obtained exclusively via the element selector object.
 *
 * Do not mix with actual column names defined in \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns.
 *
 * This is the only place where these aliases may be defined.
 *
 * @since 4.0
 */
class SelectedColumnAliases {

	const FIXED_ALIAS_ID = 'id';
	const FIXED_ALIAS_RELATIONSHIP_ID = 'relationship_id';

	const PARENT_ID = 'selected_parent_id';
	const CHILD_ID = 'selected_child_id';
	const INTERMEDIARY_ID = 'selected_intermediary_id';


	/**
	 * Translate a role name to a preferred column alias.
	 *
	 * @param RelationshipRole $for_role
	 * @return string
	 */
	public static function role_to_name( RelationshipRole $for_role ) {
		switch( $for_role->get_name() ) {
			case \Toolset_Relationship_Role::PARENT:
				return self::PARENT_ID;
			case \Toolset_Relationship_Role::CHILD:
				return self::CHILD_ID;
			case \Toolset_Relationship_Role::INTERMEDIARY:
				return self::INTERMEDIARY_ID;
		}

		throw new \InvalidArgumentException();
	}

}
