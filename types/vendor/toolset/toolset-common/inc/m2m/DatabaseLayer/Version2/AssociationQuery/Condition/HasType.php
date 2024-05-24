<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Condition to query associations by a type (not domain) of elements in the given role.
 *
 * @since 4.0
 */
class HasType extends AbstractCondition {


	/** @var RelationshipRoleParentChild */
	private $for_role;


	/** @var string */
	private $type;


	/** @var TableJoinManager */
	private $join_manager;


	/** @var TableNames */
	private $table_names;


	/** @var UniqueTableAlias */
	private $unique_table_alias;


	/** @var string|null This needs to be set during get_join_clauses(). */
	private $type_set_table_alias;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Has_Type
	 * constructor.
	 *
	 * @param RelationshipRoleParentChild $for_role
	 * @param string $type
	 * @param TableJoinManager $join_manager
	 * @param UniqueTableAlias $unique_table_alias
	 * @param TableNames $table_names
	 */
	public function __construct(
		RelationshipRoleParentChild $for_role,
		$type,
		TableJoinManager $join_manager,
		UniqueTableAlias $unique_table_alias,
		TableNames $table_names
	) {
		if ( ! is_string( $type ) || empty( $type ) ) {
			throw new InvalidArgumentException( 'Missing or invalid... type type.');
		}

		$this->for_role = $for_role;
		$this->type = $type;
		$this->join_manager = $join_manager;
		$this->unique_table_alias = $unique_table_alias;
		$this->table_names = $table_names;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_join_clause() {
		$relationships_table = $this->join_manager->relationships();
		$type_set_column = RelationshipTable::role_to_column(
			$this->for_role,
			RelationshipTable::COLUMN_TYPE_TYPES
		);
		$type_set_table = $this->table_names->get_full_table_name( TableNames::TYPE_SETS );
		$type_set_table_alias = $this->unique_table_alias->generate( $type_set_table, true );

		$this->type_set_table_alias = $type_set_table_alias;

		return " JOIN $type_set_table AS $type_set_table_alias ON ( $type_set_table_alias.set_id = $relationships_table.$type_set_column ) ";
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( " %s.type = '%s' ", $this->type_set_table_alias, esc_sql( $this->type ) );
	}

}
