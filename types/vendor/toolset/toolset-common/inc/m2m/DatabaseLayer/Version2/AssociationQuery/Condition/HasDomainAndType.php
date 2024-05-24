<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ConditionFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Condition to filter results by element domain and type at the same time.
 *
 * Actually, this doesn't do anything but to tie those two together so that the association query
 * can perform some more advanced optimizations.
 */
class HasDomainAndType extends AbstractCondition {


	/** @var string */
	private $domain;


	/** @var string */
	private $type;


	/** @var AssociationQueryCondition */
	private $inner_condition;


	/** @var RelationshipRole */
	private $for_role;


	/**
	 * @param RelationshipRoleParentChild $for_role
	 * @param string $domain
	 * @param string $type
	 * @param ConditionFactory $condition_factory
	 */
	public function __construct(
		RelationshipRoleParentChild $for_role,
		$domain,
		$type,
		ConditionFactory $condition_factory
	) {
		if (
			empty( $type )
			|| ! is_string( $type )
			|| ! in_array( $domain, \Toolset_Element_Domain::all(), true )
		) {
			throw new InvalidArgumentException( 'Invalid type or domain provided.' );
		}

		$this->domain = $domain;
		$this->type = $type;
		$this->for_role = $for_role;

		$this->inner_condition = $condition_factory->do_and(
			array(
				$condition_factory->has_domain( $domain, $for_role ),
				$condition_factory->has_type( $type, $for_role ),
			)
		);
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return $this->inner_condition->get_where_clause();
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_join_clause() {
		return $this->inner_condition->get_join_clause();
	}


	/**
	 * @return string The element domain set in this condition.
	 */
	public function get_domain() {
		return $this->domain;
	}


	/**
	 * @return string The element type set in this condition.
	 */
	public function get_type() {
		return $this->type;
	}


	/**
	 * @return RelationshipRole
	 */
	public function get_for_role() {
		return $this->for_role;
	}
}
