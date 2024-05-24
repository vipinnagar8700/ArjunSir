<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;
use Toolset_Element_Domain;

/**
 * Query associations by the domain of selected role.
 */
class HasDomain extends AbstractCondition {


	/** @var TableJoinManager */
	private $join_manager;


	/** @var RelationshipRoleParentChild */
	private $for_role;


	/** @var string */
	private $domain;


	/**
	 * @param string $domain
	 * @param RelationshipRoleParentChild $for_role
	 * @param TableJoinManager $join_manager
	 */
	public function __construct(
		$domain,
		RelationshipRoleParentChild $for_role,
		TableJoinManager $join_manager
	) {
		if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
			throw new InvalidArgumentException( 'Invalid domain provided.' );
		}

		$this->domain = $domain;
		$this->for_role = $for_role;
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
			" %s.%s = '%s' ",
			$this->join_manager->relationships(),
			RelationshipTable::role_to_column( $this->for_role, RelationshipTable::COLUMN_TYPE_DOMAIN  ),
			esc_sql( $this->domain )
		);
	}


	/**
	 * @return string The element domain set on this condition.
	 * @since 2.5.10
	 */
	public function get_domain() {
		return $this->domain;
	}


	public function get_for_role() {
		return $this->for_role;
	}
}
