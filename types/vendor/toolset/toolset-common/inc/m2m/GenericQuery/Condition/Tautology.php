<?php

namespace OTGS\Toolset\Common\Relationships\GenericQuery\Condition;

/**
 * A condition that is always true.
 *
 * It can be useful in situations where we need to return a condition object but don't want to influence
 * the query.
 *
 * Remember, the first rule of the Tautology Club is the first rule of the Tautology Club!
 *
 * @since 2.5.6
 * @since 2.5.8 Adjusted for usage in Toolset_Association_Query_V2 as well.
 */
class Tautology
	implements \OTGS\Toolset\Common\Relationships\API\RelationshipQueryCondition,
	\OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition {


	public function get_join_clause() {
		return '';
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return ' 1 = 1 ';
	}
}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( Tautology::class, 'Toolset_Query_Condition_Tautology' );
