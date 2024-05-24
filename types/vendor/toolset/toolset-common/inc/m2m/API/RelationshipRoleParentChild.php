<?php

namespace OTGS\Toolset\Common\Relationships\API;

/**
 * Represents a relationship role that is either parent or a child.
 *
 * Always expect this interface rather than relying on \IToolset_Relationship_Role_Parent_Child.
 *
 * @since 4.0
 */
interface RelationshipRoleParentChild extends RelationshipRole {

	/**
	 * @return RelationshipRoleParentChild The opposite role.
	 */
	public function other();

}
