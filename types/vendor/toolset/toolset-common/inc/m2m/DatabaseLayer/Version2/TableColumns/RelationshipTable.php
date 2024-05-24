<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns;


use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKeyColumn;

/**
 * Holds names of columns of the relationship table.
 *
 * This is the only place within the DatabaseLayer\Version2 namespace where these values may be hardcoded.
 *
 * @since 4.0
 */
final class RelationshipTable {

	const CURRENT_VERSION = 1;

	const ID = PrimaryKeyColumn::COLUMN_NAME;

	const SLUG = 'slug';

	const IS_ACTIVE = 'is_active';

	const NEEDS_LEGACY_SUPPORT = 'needs_legacy_support';

	const PARENT_DOMAIN = 'parent_domain';

	const PARENT_TYPES = 'parent_types';

	const CHILD_DOMAIN = 'child_domain';

	const CHILD_TYPES = 'child_types';

	const DISPLAY_NAME_PLURAL = 'display_name_plural';

	const DISPLAY_NAME_SINGULAR = 'display_name_singular';

	const DRIVER = 'driver';

	const INTERMEDIARY_TYPE = 'intermediary_type';

	const OWNERSHIP = 'ownership';

	const CARDINALITY_PARENT_MAX = 'cardinality_parent_max';

	const CARDINALITY_PARENT_MIN = 'cardinality_parent_min';

	const CARDINALITY_CHILD_MIN = 'cardinality_child_min';

	const CARDINALITY_CHILD_MAX = 'cardinality_child_max';

	const IS_DISTINCT = 'is_distinct';

	const SCOPE = 'scope';

	const ORIGIN = 'origin';

	const ROLE_NAME_PARENT = 'role_name_parent';

	const ROLE_NAME_CHILD = 'role_name_child';

	const ROLE_NAME_INTERMEDIARY = 'role_name_intermediary';

	const ROLE_LABEL_PARENT_SINGULAR = 'role_label_parent_singular';

	const ROLE_LABEL_CHILD_SINGULAR = 'role_label_child_singular';

	const ROLE_LABEL_PARENT_PLURAL = 'role_label_parent_plural';

	const ROLE_LABEL_CHILD_PLURAL = 'role_label_child_plural';

	const AUTODELETE_INTERMEDIARY = 'autodelete_intermediary';


	const COLUMN_TYPE_DOMAIN = 'domain';
	const COLUMN_TYPE_TYPES = 'types';

	const COLUMNS_PER_ROLE = [
		self::COLUMN_TYPE_DOMAIN => [
			\Toolset_Relationship_Role::PARENT => self::PARENT_DOMAIN,
			\Toolset_Relationship_Role::CHILD => self::CHILD_DOMAIN,
		],
		self::COLUMN_TYPE_TYPES => [
			\Toolset_Relationship_Role::PARENT => self::PARENT_TYPES,
			\Toolset_Relationship_Role::CHILD => self::CHILD_TYPES,
			\Toolset_Relationship_Role::INTERMEDIARY => self::INTERMEDIARY_TYPE,
		],
	];

	public static function role_to_column( RelationshipRole $role, $column_type ) {
		$columns_per_role = self::COLUMNS_PER_ROLE;
		$column_name = toolset_getnest( $columns_per_role, [ $column_type, $role->get_name() ], null );

		if( empty( $column_name ) ) {
			throw new \InvalidArgumentException( 'Unsupported colum name requested.' );
		}

		return $column_name;
	}
}
