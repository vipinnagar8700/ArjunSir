<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Factory for OrderByInterface
 *
 * @codeCoverageIgnore
 */
class OrderByFactory {


	/**
	 * @return OrderByInterface
	 */
	public function nothing() {
		return new OrderByNothing();
	}


	/**
	 * @param RelationshipRole $role
	 * @param TableJoinManager $join_manager
	 *
	 * @return OrderByInterface
	 */
	public function title( RelationshipRole $role, TableJoinManager $join_manager ) {
		return new OrderByTitle( $role, $join_manager );
	}


	/**
	 * @param string $meta_key
	 * @param RelationshipRole $role
	 * @param TableJoinManager $join_manager
	 * @param string|null $cast_to If the metakey needs to be casted into a different type
	 *
	 * @return OrderByInterface
	 */
	public function postmeta( $meta_key, RelationshipRole $role, TableJoinManager $join_manager, $cast_to = null ) {
		return new OrderByPostmeta( $meta_key, $role, $join_manager, $cast_to );
	}

}
