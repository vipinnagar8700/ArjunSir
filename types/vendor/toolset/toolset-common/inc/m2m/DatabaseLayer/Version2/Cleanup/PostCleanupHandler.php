<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Cleanup;

use IToolset_Post;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Cleanup\PostCleanupInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use OTGS\Toolset\Common\Result\SingleResult;
use Toolset_Association_Cleanup_Factory;
use Toolset_Association_Intermediary_Post_Persistence;
use Toolset_Cron;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;
use wpdb;

/**
 * Handles the clean-up when permanently deleting posts.
 *
 * Following scenarios need to be managed here:
 *
 * - A post which is involved in one or more associations is being deleted.
 *     - Delete associations it is involved in, but only if it's the last one in
 *       its connected element group.
 * - When deleting any associations, also delete involved intermediary posts if the
 *   relationship has the appropriate option set, but don't
 *   trigger an infinite recursion by considering the previous scenario.
 * - Only delete a certain number of intermediary posts, schedule the rest to be deleted
 *   via WP CRON in order to prevent a request timeout (we need to be careful since
 *   posts can be deleted in a number of different contexts).
 * - After a post has been deleted, also update its connected element group, if one exists.
 *
 * Please also see the previous implementation:
 * @see Toolset_Association_Cleanup_Post
 */
class PostCleanupHandler implements PostCleanupInterface {


	/** @var Toolset_Element_Factory */
	private $element_factory;

	/** @var Toolset_Cron */
	private $cron;

	/** @var Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;

	/** @var Toolset_Association_Intermediary_Post_Persistence */
	private $intermediary_post_persistence;

	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * @param Toolset_Association_Cleanup_Factory $cleanup_factory
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory
	 * @param Toolset_Element_Factory $element_factory
	 * @param Toolset_Cron $cron
	 * @param Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence
	 */
	public function __construct(
		Toolset_Association_Cleanup_Factory $cleanup_factory,
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory,
		Toolset_Element_Factory $element_factory,
		Toolset_Cron $cron,
		Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence
	) {
		$this->cleanup_factory = $cleanup_factory;
		$this->element_factory = $element_factory;
		$this->cron = $cron;
		$this->intermediary_post_persistence = $intermediary_post_persistence;
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * Clean up affected associations before a post is permanently deleted.
	 *
	 * @param int $post_id
	 */
	public function cleanup_before_delete( $post_id ) {
		$is_deleting_association = apply_filters( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, false );

		if ( $is_deleting_association ) {
			// Prevent an infinite recursion if a single association is being deleted.
			// If we got here, it means that the association's intermediary post is about to
			// be deleted and everything else is already handled either
			// in Toolset_Association_Cleanup_Association, or within this class.
			//
			// Or there is a different situation where an intermediary post is being deleted
			// but we want to preserve the association.
			return;
		}

		try {
			// We don't really care about post translations at this point.
			$post = $this->element_factory->get_post_untranslated( $post_id );
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// The post is already gone, do nothing.
			return;
		}

		if ( $post->is_revision() ) {
			// No need to handle revisions. They're not supposed to have any
			// associations at all. Just let WordPress proceed with the deletion.
			return;
		}

		if ( ! $this->is_last_element_in_group_involved_in_association( $post ) ) {
			// A post may be one of several in an element group, which corresponds with a
			// translation group from WPML.
			//
			// We're allowing associations with non-default language posts only, so
			// the need to delete an association arises only when the last post from the group
			// is deleted.
			//
			// Finally, we really need a connected element group to exist for this post,
			// otherwise it means it surely isn't a part of any association, and again, there's
			// nothing to do here.
			return;
		}

		// The post was directly involved in an association - either it's non-translatable
		// or in the default language. We delete the associations and we're done.
		$this->delete_associations_involving_post( $post );
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return bool True if there are associations that involve this element and its connected
	 *     element group doesn't contain anything else.
	 */
	private function is_last_element_in_group_involved_in_association( IToolset_Post $post ) {
		if ( ! $this->is_last_element_in_group( $post ) ) {
			return false;
		}

		$query = $this->database_layer_factory->association_query();

		$involved_association_count = $query
			->add( $query->element( $post ) )
			->do_not_add_default_conditions()
			->get_found_rows_directly();

		return $involved_association_count > 0;
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return bool True if the connected element group exist for given post and there
	 *     are no other posts in it.
	 */
	private function is_last_element_in_group( IToolset_Post $post ) {
		$group_id = $post->get_connected_group_id( false );
		if ( ! $group_id ) {
			// There is no group this element is a part of, so it cannot be the last one.
			// This suits us because without a group_id, it can't be a part of any association
			// and that means it's not relevant here.
			return false;
		}

		if ( ! $post->is_translatable() ) {
			// So, it does have a group_id... but non-translatable posts never have more posts in a group.
			return true;
		}

		$connected_element_group = $this->database_layer_factory
			->connected_element_persistence()
			->get_connected_element_group( $group_id );

		return $connected_element_group && count( $connected_element_group->get_element_ids() ) === 1;
	}


	/**
	 * @param IToolset_Post $post
	 */
	private function delete_associations_involving_post( IToolset_Post $post ) {
		$this->delete_involved_intermediary_posts( $post );
		$this->delete_association_rows( $post );
	}


	/**
	 * Delete all the affected association rows from the database.
	 *
	 * @param IToolset_Post $post
	 *
	 * @return SingleResult
	 */
	private function delete_association_rows( IToolset_Post $post ) {
		return $this->database_layer_factory
			->association_database_operations()
			->delete_association_by_element_in_any_role( $post );
	}


	/**
	 * Delete a first batch of intermediary posts that should be removed together
	 * with the association. If some intermediary posts remain, set up a CRON job and an admin notice
	 * for the user.
	 *
	 * @param IToolset_Post $post
	 */
	private function delete_involved_intermediary_posts( IToolset_Post $post ) {
		$query = $this->database_layer_factory->association_query();

		$intermediary_post_ids = $query
			->add( $query->do_or(
			// Not intermediary posts. If the element is an intermediary post,
			// we need to exclude it (it's already being deleted) and we wouldn't get any other
			// associations where it's involved.
				$query->element( $post, new Toolset_Relationship_Role_Parent(), true, false ),
				$query->element( $post, new Toolset_Relationship_Role_Child(), true, false )
			) )
			->add( $query->has_autodeletable_intermediary_post() )
			->do_not_add_default_conditions()
			->limit( Constants::DELETE_POSTS_PER_BATCH )
			->return_element_ids( new Toolset_Relationship_Role_Intermediary() )
			->need_found_rows()
			->get_results();

		foreach ( $intermediary_post_ids as $intermediary_post_id ) {
			// This will also delete post translations.
			$this->intermediary_post_persistence->delete_intermediary_post( $intermediary_post_id );
		}

		if ( $query->get_found_rows() > Constants::DELETE_POSTS_PER_BATCH ) {
			// Some dangling posts are left, there's too much of them to be deleted at once.
			// Schedule a WP-Cron event to delete them by batches until there are none left.
			$this->schedule_dangling_post_removal();
		}
	}


	private function schedule_dangling_post_removal() {
		$cron_event = $this->cleanup_factory->cron_event();
		$this->cron->schedule_event( $cron_event );
	}


	/**
	 * After a post has been permanently deleted, make sure we don't leave behind any obsolete
	 * data in the connected elements table.
	 *
	 * @param int $post_id
	 */
	public function cleanup_after_delete( $post_id ) {
		$connected_element_persistence = $this->database_layer_factory->connected_element_persistence();
		$group_id = $connected_element_persistence->query_element_group_id_directly( $post_id, \Toolset_Element_Domain::POSTS );

		if( ! $group_id ) {
			return;
		}

		$element_group = $connected_element_persistence->get_connected_element_group( $group_id );
		if( ! $element_group ) {
			return;
		}

		$connected_element_persistence->remove_element_from_group( $element_group, $post_id );
	}
}
