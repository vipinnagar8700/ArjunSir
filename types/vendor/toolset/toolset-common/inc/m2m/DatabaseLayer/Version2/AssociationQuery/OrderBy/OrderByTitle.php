<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Order associations by title of an element of given role.
 *
 * Note: Currently, only the posts domain is supported.
 *
 * Note: Ordering by intermediary posts will exclude associations that don't have one.
 */
class OrderByTitle extends AbstractOrderBy {


	/** @var RelationshipRole */
	private $for_role;


	/**
	 * @param RelationshipRole $role
	 * @param TableJoinManager $join_manager
	 */
	public function __construct(
		RelationshipRole $role,
		TableJoinManager $join_manager
	) {
		parent::__construct( $join_manager );

		$this->for_role = $role;
	}


	/**
	 * @inheritdoc
	 */
	public function register_joins() {
		$this->join_manager->wp_posts( $this->for_role );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_orderby_clause() {
		$posts_table_alias = $this->join_manager->wp_posts( $this->for_role );

		return " {$posts_table_alias}.post_title {$this->order} ";
	}
}
