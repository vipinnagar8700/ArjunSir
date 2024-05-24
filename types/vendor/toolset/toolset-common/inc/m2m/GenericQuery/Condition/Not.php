<?php

namespace OTGS\Toolset\Common\Relationships\GenericQuery\Condition;

use OTGS\Toolset\Common\Relationships\API\QueryCondition;

/**
 * Negation of a provided condition.
 *
 * @since 2.6.7
 */
class Not
	implements \OTGS\Toolset\Common\Relationships\API\RelationshipQueryCondition,
	\OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition {

	/** @var QueryCondition */
	private $condition;


	/**
	 * Toolset_Query_Condition_Not constructor.
	 *
	 * @param QueryCondition $condition
	 */
	public function __construct( QueryCondition $condition ) {
		$this->condition = $condition;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return ' NOT ( ' . $this->condition->get_where_clause() . ' ) ';
	}


	/**
	 * Get a part of the JOIN clause that is required by the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used as: $table_as_unique_alias_on_condition_1 $table_as_unique_alias_on_condition_2 ...
	 *     (meaning that every clause should start with its own "JOIN"
	 */
	public function get_join_clause() {
		return $this->condition->get_join_clause();
	}

}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( Not::class, 'Toolset_Query_Condition_Not' );
