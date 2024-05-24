<?php

namespace OTGS\Toolset\Common\Relationships\GenericQuery\Condition;

use OTGS\Toolset\Common\Relationships\API\QueryCondition;

/**
 * Abstract condition for implementing operators in the MySQL query.
 *
 * @since 2.5.4
 */
abstract class Operator
	implements \OTGS\Toolset\Common\Relationships\API\RelationshipQueryCondition,
	\OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition {


	/** @var QueryCondition[] */
	protected $conditions = array();


	/**
	 * Toolset_Query_Condition_Operator constructor.
	 *
	 * @param QueryCondition[]|array $conditions If a nested array of conditions
	 *     is provided, it will be handled as a nested $op ($op is the operation):
	 *     ( $condition1 ) $op ( ( $condition2_1 ) $op ( $condition2_2 ) ) $op ...etc.
	 */
	public function __construct( $conditions ) {
		$this->add_conditions( $conditions );
	}


	/**
	 * @param QueryCondition[]|array $conditions
	 */
	private function add_conditions( $conditions ) {
		foreach ( $conditions as $condition ) {
			if ( $condition instanceof QueryCondition ) {
				$this->conditions[] = $condition;
			} elseif ( is_array( $condition ) ) {
				if ( count( $condition ) === 1 ) {
					// single condition inside an array - it doesn't have to be nested in another condition
					$this->add_conditions( $condition );
				} else {
					$this->conditions[] = $this->instantiate_self( $condition );
				}
			} else {
				throw new \InvalidArgumentException();
			}
		}
	}


	/**
	 * Just joins the join clauses from nested conditions.
	 *
	 * @return string
	 */
	public function get_join_clause() {
		$join_clauses = '';

		foreach ( $this->conditions as $condition ) {
			$join_clauses .= ' ' . $condition->get_join_clause() . ' ';
		}

		return $join_clauses;
	}


	/**
	 * Return an instance of self with provided conditions.
	 *
	 * Used for nesting when a nested array of conditions is passed to the constructor.
	 *
	 * @param QueryCondition[] $conditions
	 *
	 * @return QueryCondition
	 */
	abstract protected function instantiate_self( $conditions );

}


// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( Operator::class, 'Toolset_Query_Condition_Operator' );
