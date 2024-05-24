<?php

namespace OTGS\Toolset\Common\Relationships\GenericQuery\Condition;

use OTGS\Toolset\Common\Relationships\API\QueryCondition;

/**
 * Chains multiple IToolset_Query_Condition with OR.
 *
 * @since m2m
 */
class OrOperator extends Operator {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {

		if ( empty( $this->conditions ) ) {
			return '1 = 1';
		}

		$clauses = array();
		foreach ( $this->conditions as $condition ) {
			$clauses[] = $condition->get_where_clause();
		}

		return ' ( ' . implode( ' ) OR ( ', $clauses ) . ' ) ';
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
}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( OrOperator::class, 'Toolset_Query_Condition_Or' );
