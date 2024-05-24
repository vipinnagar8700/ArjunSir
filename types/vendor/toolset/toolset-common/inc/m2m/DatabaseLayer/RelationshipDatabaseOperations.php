<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use Toolset_Result;


/**
 * Database operations related to relationship definitions.
 *
 * @since 4.0
 */
class RelationshipDatabaseOperations extends \Toolset_Wpdb_User {

	// Columns in the relationships table
	const COLUMN_DOMAIN = '_domain';
	const COLUMN_TYPES = '_types';
	const COLUMN_CARDINALITY_MAX = 'cardinality_%s_max';
	const COLUMN_CARDINALITY_MIN = 'cardinality_%s_min';


	/** @var \Toolset_Relationship_Table_Name */
	private $table_name;


	public function __construct( \wpdb $wpdb = null, \Toolset_Relationship_Table_Name $table_name = null ) {
		parent::__construct( $wpdb );
		$this->table_name = $table_name ?: new \Toolset_Relationship_Table_Name();
	}


	/**
	 * For a given role name, return the corresponding column in the relationships table.
	 *
	 * @param string|RelationshipRole $role
	 * @param string $column
	 *
	 * @return string
	 * @deprecated Use RelationshipTable::role_to_column() instead.
	 */
	public function role_to_column( $role, $column ) {

		if( $role instanceof RelationshipRole ) {
			$role_name = $role->get_name();
		} else {
			$role_name = $role;
		}

		// Special cases
		if( in_array( $column, array( self::COLUMN_CARDINALITY_MAX, self::COLUMN_CARDINALITY_MIN ) ) ) {
			return sprintf( $column, $role_name );
		}

		return $role_name . $column;
	}


	public function load_all_relationships() {
		$relationship_table = $this->table_name->relationship_table();
		$type_set_table = $this->table_name->type_set_table();

		// The query is so complex because it needs to bring in data from the type set tables. But
		// those two joins are very cheap because we don't expect many records here.
		$query = "
			SELECT {$this->get_standard_relationships_select_clause()}
			FROM {$relationship_table} AS relationships
				{$this->get_standard_relationships_join_clause( $type_set_table )}
			GROUP BY {$this->get_standards_relationship_group_by_clause()}";

		return toolset_ensarr( $this->wpdb->get_results( $query ) );
	}


	/**
	 * Build the part of the SELECT clause that is required for proper loading of a relationship definition.
	 *
	 * @param string $relationships_table_alias
	 * @param string $parent_types_table_alias
	 * @param string $child_types_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standard_relationships_select_clause(
		$relationships_table_alias = 'relationships',
		$parent_types_table_alias = 'parent_types_table',
		$child_types_table_alias = 'child_types_table'
	) {
		return "
			$relationships_table_alias.id AS id,
			$relationships_table_alias.slug AS slug,
			$relationships_table_alias.display_name_plural AS display_name_plural,
			$relationships_table_alias.display_name_singular AS display_name_singular,
			$relationships_table_alias.driver AS driver,
			$relationships_table_alias.parent_domain AS parent_domain,
			$relationships_table_alias.child_domain AS child_domain,
			$relationships_table_alias.intermediary_type AS intermediary_type,
			$relationships_table_alias.ownership AS ownership,
			$relationships_table_alias.cardinality_parent_max AS cardinality_parent_max,
			$relationships_table_alias.cardinality_parent_min AS cardinality_parent_min,
			$relationships_table_alias.cardinality_child_max AS cardinality_child_max,
			$relationships_table_alias.cardinality_child_min AS cardinality_child_min,
			$relationships_table_alias.is_distinct AS is_distinct,
			$relationships_table_alias.scope AS scope,
			$relationships_table_alias.origin AS origin,
			$relationships_table_alias.role_name_parent AS role_name_parent,
			$relationships_table_alias.role_name_child AS role_name_child,
			$relationships_table_alias.role_name_intermediary AS role_name_intermediary,
			$relationships_table_alias.role_label_parent_singular AS role_label_parent_singular,
			$relationships_table_alias.role_label_child_singular AS role_label_child_singular,
			$relationships_table_alias.role_label_parent_plural AS role_label_parent_plural,
			$relationships_table_alias.role_label_child_plural AS role_label_child_plural,
			$relationships_table_alias.needs_legacy_support AS needs_legacy_support,
			$relationships_table_alias.is_active AS is_active,
			$relationships_table_alias.autodelete_intermediary AS autodelete_intermediary,
			$relationships_table_alias.parent_types AS parent_types_set_id,
			$relationships_table_alias.child_types AS child_types_set_id,
			GROUP_CONCAT(DISTINCT $parent_types_table_alias.type) AS parent_types,
			GROUP_CONCAT(DISTINCT $child_types_table_alias.type) AS child_types";
	}


	/**
	 * Build the part of the JOIN clause that is required for proper loading of a relationship definition.
	 *
	 * @param $type_set_table_name
	 * @param string $relationships_table_alias
	 * @param string $parent_types_table_alias
	 * @param string $child_types_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standard_relationships_join_clause(
		$type_set_table_name,
		$relationships_table_alias = 'relationships',
		$parent_types_table_alias = 'parent_types_table',
		$child_types_table_alias = 'child_types_table'
	) {
		return "
			JOIN {$type_set_table_name} AS {$parent_types_table_alias}
				ON ({$relationships_table_alias}.parent_types = {$parent_types_table_alias}.set_id )
			JOIN {$type_set_table_name} AS {$child_types_table_alias}
				ON ({$relationships_table_alias}.child_types = {$child_types_table_alias}.set_id )";
	}


	/**
	 * Build the part of the GROUP BY clause that is required for proper loading of a relationship definition.
	 *
	 * @param string $relationships_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standards_relationship_group_by_clause( $relationships_table_alias = 'relationships' ) {
		return "{$relationships_table_alias}.id";
	}


	/**
	 * Update 'type' on 'toolset_type_sets'
	 *
	 * @param string $new_type
	 * @param string $old_type
	 *
	 * @return Toolset_Result
	 */
	public function update_type_on_type_sets( $new_type, $old_type ) {
		$rows_updated = $this->wpdb->update(
			$this->table_name->type_set_table(),
			array( 'type' => $new_type ),
			array( 'type' => $old_type ),
			'%s',
			'%s'
		);

		$is_success = ( false !== $rows_updated );

		$message = $is_success
			? sprintf(
				__( 'The type_sets table has been updated with the new type "%s". %d rows have been updated.', 'wpv-views' ),
				$new_type,
				$rows_updated
			)
			: sprintf(
				__( 'There has been an error when updating the type_sets table with the new type "%s": %s', 'wpv-views' ),
				$new_type,
				$this->wpdb->last_error
			);

		return new Toolset_Result( $is_success, $message );
	}

}

