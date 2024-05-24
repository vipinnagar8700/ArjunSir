<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use InvalidArgumentException;
use IToolset_Relationship_Definition;
use Toolset_Utils;

/**
 * Condition to query associations by a specific relationship (row) ID.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Relationship_Id extends Toolset_Association_Query_Condition {


	/** @var int */
	private $relationship_id;

	/** @var IToolset_Relationship_Definition|null */
	private $relationship_definition;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Relationship_Id
	 * constructor.
	 *
	 * @param int $relationship_id
	 * @param IToolset_Relationship_Definition|null $relationship_definition Optional, pass only when already available
	 *     to allow additional optimizations.
	 *
	 * @throws \InvalidArgumentException When an obviously invalid relationship ID is provided.
	 */
	public function __construct( $relationship_id, IToolset_Relationship_Definition $relationship_definition = null ) {
		if ( ! Toolset_Utils::is_nonnegative_integer( $relationship_id ) ) {
			throw new InvalidArgumentException( 'Invalid relationship ID provided' );
		}

		$this->relationship_id = (int) $relationship_id;
		$this->relationship_definition = $relationship_definition;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( 'associations.relationship_id %s %d', $this->get_operator(), $this->relationship_id );
	}


	/**
	 * Returns condition operator
	 *
	 * @return string
	 * @since m2m
	 */
	protected function get_operator() {
		return '=';
	}


	/**
	 * @return IToolset_Relationship_Definition|null
	 */
	public function get_relationship_definition() {
		return $this->relationship_definition;
	}

}
