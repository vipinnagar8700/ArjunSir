<?php

namespace OTGS\Toolset\Common\Relationships\API;

use IToolset_Association;
use IToolset_Relationship_Definition;
use Toolset_Result;

/**
 * Represents a class for performing database operations related to associations between elements.
 *
 * @since 4.0
 */
interface AssociationDatabaseOperations {

	/**
	 * Create new association and persist it.
	 *
	 * @param \IToolset_Relationship_Definition|string $relationship_definition_source Can also contain slug of
	 *     existing relationship definition.
	 * @param int|\Toolset_Element|\WP_Post $parent_source
	 * @param int|\Toolset_Element|\WP_Post $child_source
	 * @param int $intermediary_id
	 * @param bool $instantiate Whether to create an instance of the newly created association
	 *     or only return a result on success
	 *
	 * @return IToolset_Association|Toolset_Result
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function create_association( $relationship_definition_source, $parent_source, $child_source, $intermediary_id, $instantiate = true );


	/**
	 * Delete all associations of a given relationships that have the given element in the given role.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	public function delete_associations_by_element( $relationship, $element_role_name, $element_id );


	public function delete_association_by_element_in_any_role( \IToolset_Element $element );


	/**
	 * Delete all associations from a given relationship.
	 *
	 * @param int $relationship_row_id
	 *
	 * @return \Toolset_Result_Updated
	 */
	public function delete_associations_by_relationship( $relationship_row_id );


	/**
	 * @param IToolset_Association $association
	 *
	 * @return Toolset_Result
	 */
	public function delete_association( IToolset_Association $association );


	/**
	 * Delete intermediary posts from all associations in a given relationship that have
	 * the given element in the given role.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	public function delete_intermediary_posts_by_element( $relationship, $element_role_name, $element_id );


	/**
	 * When a relationship definition slug is renamed, update the association table (where the slug is used as a
	 * foreign key).
	 *
	 * @param IToolset_Relationship_Definition $old_definition
	 * @param IToolset_Relationship_Definition $new_definition
	 *
	 * @return Toolset_Result
	 * @deprecated Always change the slug via Toolset_Relationship_Definition_Repository::change_definition_slug().
	 */
	public function update_associations_on_definition_renaming(
		IToolset_Relationship_Definition $old_definition,
		IToolset_Relationship_Definition $new_definition
	);


	/**
	 * Updates association intermediary post
	 *
	 * @param int $association_id Association trID
	 * @param int $intermediary_id New intermediary ID
	 */
	public function update_association_intermediary_id( $association_id, $intermediary_id );


	/**
	 * Returns the maximun number of associations of a relationship for a parent id and a child id
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param string $role_name Role name.
	 *
	 * @return int
	 * @throws \InvalidArgumentException In case of error.
	 */
	public function count_max_associations( $relationship_id, $role_name );


	/**
	 * @param array $intermediary_post_types
	 * @param array $post_types_to_delete_by
	 *
	 * @return array
	 */
	public function get_dangling_intermediary_posts( array $intermediary_post_types, array $post_types_to_delete_by );


	/**
	 * Determines whether the relationship database layer needs a default language post version to connect
	 * translatable posts.
	 *
	 * @return bool
	 */
	public function requires_default_language_post();

}
