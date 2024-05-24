<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Cleanup;


/**
 * Interface for a class that handles a cleanup after a single post has been deleted.
 *
 * Needs to be hooked to the before_delete_post and after_delete_post actions (which
 * should be happening in Toolset_Relationship_Controller).
 *
 * Please refer to individual implementations as this is very different for each
 * database layer version.
 *
 * @since 4.0
 */
interface PostCleanupInterface {

	/**
	 * Clean up affected associations before a post is permanently deleted.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function cleanup_before_delete( $post_id );


	/**
	 * Perform necessary clean-up after a post has been permanently deleted.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function cleanup_after_delete( $post_id );
}
