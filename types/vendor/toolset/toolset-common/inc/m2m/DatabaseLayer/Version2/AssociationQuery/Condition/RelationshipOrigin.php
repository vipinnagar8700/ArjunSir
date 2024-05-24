<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;
use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;

/**
 * Query associations by the origin value of a relationship they belong to.
 */
class RelationshipOrigin extends AbstractCondition {

	/** @var TableJoinManager */
	private $join_manager;


	/** @var bool */
	private $expected_value;


	/**
	 * @param string $expected_value
	 * @param TableJoinManager $join_manager
	 */
	public function __construct( $expected_value, TableJoinManager $join_manager ) {
		if ( ! is_string( $expected_value ) ) {
			throw new InvalidArgumentException( 'Invalid origin value provided.' );
		}
		$this->expected_value = $expected_value;
		$this->join_manager = $join_manager;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {

		return sprintf(
			' %1$s.%2$s = \'%3$s\' ',
			$this->join_manager->relationships(),
			RelationshipTable::ORIGIN,
			esc_sql( $this->expected_value )
		);
	}
}
