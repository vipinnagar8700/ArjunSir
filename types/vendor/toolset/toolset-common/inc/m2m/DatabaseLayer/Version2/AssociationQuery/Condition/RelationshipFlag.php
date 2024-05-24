<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Query associations by a flag of a relationship they belong to.
 *
 * @since 4.0
 */
abstract class RelationshipFlag extends AbstractCondition {


	/** @var TableJoinManager */
	private $join_manager;


	/** @var bool */
	private $expected_value;


	/**
	 * @param bool $expected_value
	 * @param TableJoinManager $join_manager
	 */
	public function __construct( $expected_value, TableJoinManager $join_manager ) {
		$this->expected_value = (bool) $expected_value;
		$this->join_manager = $join_manager;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$relationships_table = $this->join_manager->relationships();

		return sprintf(
			' %s.%s = %d ',
			$relationships_table,
			$this->get_flag_name(),
			$this->expected_value ? 1 : 0
		);
	}


	/**
	 * Get the name of the column in the relationships table to query by.
	 *
	 * @return string
	 */
	abstract protected function get_flag_name();
}
