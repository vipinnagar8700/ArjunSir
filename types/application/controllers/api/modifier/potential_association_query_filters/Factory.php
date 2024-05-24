<?php

namespace OTGS\Toolset\Types\API\Modifier\PotentialAssociationQueryFilters;

use OTGS\Toolset\Types\API\Modifier\PotentialAssociationQueryFilters\Post\AuthorForPostReference;
use OTGS\Toolset\Types\API\Modifier\PotentialAssociationQueryFilters\Post\PostStatusForPostReference;


/**
 * Factory for Types filters for the potential association query.
 *
 * @since 3.2
 */
class Factory {


	/**
	 * @return \Toolset_Potential_Association_Query_Arguments
	 */
	public function argument_builder() {
		return new \Toolset_Potential_Association_Query_Arguments();
	}


	/**
	 * @param \IToolset_Relationship_Definition $relationship_definition
	 * @param \IToolset_Relationship_Role_Parent_Child $target_role
	 *
	 * @return PostStatusForPostReference
	 */
	public function post_status_for_post_reference(
		\IToolset_Relationship_Definition $relationship_definition,
		\IToolset_Relationship_Role_Parent_Child $target_role
	) {
		return new PostStatusForPostReference( $relationship_definition, $target_role );
	}


	/**
	 * @param string $field_slug
	 * @param string $post_type
	 *
	 * @return AuthorForPostReference
	 */
	public function author_for_post_reference(
		$field_slug, $post_type
	) {
		return new AuthorForPostReference( $field_slug, $post_type );
	}

}
