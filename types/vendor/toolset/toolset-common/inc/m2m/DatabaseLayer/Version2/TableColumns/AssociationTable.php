<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKeyColumn;

/**
 * Holds names of columns of the association table.
 *
 * This is the only place within the DatabaseLayer\Version2 namespace where these values may be hardcoded.
 *
 * @since 4.0
 */
final class AssociationTable {

	const CURRENT_VERSION = 1;

	const ID = PrimaryKeyColumn::COLUMN_NAME;

	const RELATIONSHIP_ID = 'relationship_id';

	const PARENT_ID = 'parent_id';

	const CHILD_ID = 'child_id';

	const INTERMEDIARY_ID = 'intermediary_id';


	/**
	 * @param RelationshipRole $role
	 *
	 * @return string
	 */
	public static function role_to_column( RelationshipRole $role ) {
		switch ( $role->get_name() ) {
			case \Toolset_Relationship_Role::PARENT:
				return self::PARENT_ID;
			case \Toolset_Relationship_Role::CHILD:
				return self::CHILD_ID;
			case \Toolset_Relationship_Role::INTERMEDIARY:
				return self::INTERMEDIARY_ID;
		}

		throw new \InvalidArgumentException( 'Unsupported colum name requested.' );
	}

}
