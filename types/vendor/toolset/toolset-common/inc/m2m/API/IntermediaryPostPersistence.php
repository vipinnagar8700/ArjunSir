<?php

namespace OTGS\Toolset\Common\Relationships\API;


use IToolset_Association;

/**
 * Handles the persistence of intermediary posts.
 *
 * @since 4.0
 */
interface IntermediaryPostPersistence {

	/**
	 * Create an intermediary post for a new association.
	 *
	 * @param int $parent_id Association parent id.
	 * @param int $child_id Association child id.
	 *
	 * @return int|null ID of the new post or null if the post creation failed.
	 */
	public function create_intermediary_post( $parent_id, $child_id );


	/**
	 * It there are associations belonging to the definition, intermediary post without field values has to be created.
	 *
	 * @param int $limit The number of associations in a loop.
	 */
	public function create_empty_associations_intermediary_posts( $limit = 0 );


	/**
	 * Removes intermediary post from associations.
	 *
	 * @param int $limit The number of associations in a loop.
	 *
	 * @return int Number of associations updated.
	 */
	public function remove_associations_intermediary_posts( $limit = 0 );


	/**
	 * Creates an empty association intermediary post
	 *
	 * @param IToolset_Association $association Association.
	 *
	 * @return int Post ID
	 */
	public function create_empty_association_intermediary_post( $association );


	/**
	 * Delete the intermediary post if it exists and it's not disabled by a filter.
	 *
	 * This also deletes all its translations.
	 *
	 * @param IToolset_Association $association
	 */
	public function maybe_delete_intermediary_post( IToolset_Association $association );


	/**
	 * Delete the intermediary post if it's not disabled by a filter.
	 *
	 * This also deletes all its translations.
	 *
	 * @param int $post_id
	 */
	public function delete_intermediary_post( $post_id );
}
