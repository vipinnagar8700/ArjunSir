<?php

namespace OTGS\Toolset\Common\Relationships\GenericQuery\Condition;

use OTGS\Toolset\Common\Relationships\API\QueryCondition;

/**
 * Chains multiple IToolset_Query_Condition with AND.
 *
 * @since m2m
 */
class AndOperator extends Operator {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {

		if( empty( $this->conditions ) ) {
			return '1 = 1';
		}

		$where_clauses = array();

		foreach( $this->conditions as $condition ) {
			$where_clauses[] = $condition->get_where_clause();
		}

		return ' ( ' . implode( ' ) AND ( ', $where_clauses ) . ' ) ';

	}


	/**
	 * @inheritdoc
	 *
	 * @param QueryCondition[] $conditions
	 *
	 * @return QueryCondition
	 */
	protected function instantiate_self( $conditions ) {
		return new self( $conditions );
	}


	public function get_inner_conditions() {
		return $this->conditions;
	}
}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( AndOperator::class, 'Toolset_Query_Condition_And' );
