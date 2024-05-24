<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;

/**
 * Factory for OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\IToolset_Association_Query_Orderby.
 */
class Toolset_Association_Query_Orderby_Factory {


	/**
	 * @return IToolset_Association_Query_Orderby
	 */
	public function nothing() {
		return new Toolset_Association_Query_Orderby_Nothing();
	}


	/**
	 * @param RelationshipRole $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Orderby
	 */
	public function title( RelationshipRole $role, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Orderby_Title( $role, $join_manager );
	}


	/**
	 * @param string $meta_key
	 * @param RelationshipRole $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param string|null $cast_to If the metakey needs to be casted into a different type
	 *
	 * @return IToolset_Association_Query_Orderby
	 */
	public function postmeta( $meta_key, RelationshipRole $role, Toolset_Association_Query_Table_Join_Manager $join_manager, $cast_to = null ) {
		return new Toolset_Association_Query_Orderby_Postmeta( $meta_key, $role, $join_manager, $cast_to );
	}

}
