<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Cleanup;

use IToolset_Post;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use Toolset_Association_Cleanup_Factory;
use Toolset_Association_Intermediary_Post_Persistence;
use Toolset_Cron;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;
use Toolset_Result;
use Toolset_Wpdb_User;
use wpdb;

/**
 * Perform a cleanup after a single post has been deleted.
 *
 * Needs to be hooked to the before_delete_post action.
 *
 * Short version:
 *
 * This situation is much more tricky than when just deleting a single association. One post
 * can be involved in many associations and deleting those might trigger also deleting of
 * intermediary posts and their translations.
 *
 * Long version:
 *
 * Associations themselves can be handled with a single MySQL query,
 * but for deleting intermediary posts, we have to perform consecutive wp_delete_post() calls,
 * which in turn may trigger further deletions if those intermediary posts are translated
 * to more languages.
 *
 * We simply cannot afford to delete all intermediary posts at once, because that might be
 * easily much more than the server can handle, and we can't immediately show a
 * batch deletion dialog because we don't know in which context the initial post is deleted.
 * It may be even during an AJAX call or whatnot.
 *
 * The problem is that we don't want to have lingering intermediary posts because the user
 * might use them in a View, for example, and assume that an intermediary post == an association.
 *
 * Here, a compromise solution is implemented: We immediately delete a certain number of
 * intermediary posts, which will cover 99% of these cases, and for the remaining 1%
 * of big deletions, offer a clean-up routine on the Toolset Troubleshooting page.
 *
 * If we detect that such a cleanup is needed, we'll display a notice until the user goes
 * to the troubleshooting page and clicks the button.
 *
 * On top of that, a CRON job will be created to complete the cleanup if the user doesn't
 * take action soon enough.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Post extends Toolset_Wpdb_User
	implements \OTGS\Toolset\Common\Relationships\DatabaseLayer\Cleanup\PostCleanupInterface {


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var null|Toolset_Cron */
	private $_cron;


	/** @var null|Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;


	/** @var null|Toolset_Association_Intermediary_Post_Persistence */
	private $_ip_persistence;


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Cleanup\Toolset_Association_Cleanup_Post constructor.
	 *
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Cron|null $cron_di
	 * @param Toolset_Association_Cleanup_Factory|null $cleanup_factory_di
	 * @param Toolset_Association_Intermediary_Post_Persistence|null $intermediary_post_persistence_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory
	 */
	public function __construct(
		Toolset_Association_Cleanup_Factory $cleanup_factory_di,
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory,
		Toolset_Element_Factory $element_factory_di = null,
		wpdb $wpdb_di = null,
		Toolset_Cron $cron_di = null,
		Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->element_factory = $element_factory_di ? : new Toolset_Element_Factory();
		$this->_cron = $cron_di;
		$this->cleanup_factory = $cleanup_factory_di;
		$this->_ip_persistence = $intermediary_post_persistence_di;
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * Clean up affected associations before a post is permanently deleted.
	 *
	 * @param int $post_id
	 */
	public function cleanup_before_delete( $post_id ) {
		/**
		 * Filter that can be used to indicate that an intermediary post is deleted
		 * purposefully, and that the association shouldn't be removed.
		 *
		 * @since 2.6.8
		 */
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

		if ( ! $this->is_involved_in_association_directly( $post ) ) {
			// A post may be a translation of another post that is involved in an association
			// as a parent, a child or an intermediary post. But in any of these cases, we don't
			// have to delete anything. Not even the intermediary post translation. We allow even such
			// scenarios as having a translatable intermediary post type but non-translatable
			// parent and child.
			//
			// Intermediary post translations will be deleted only when the whole association is deleted
			// or they can be deleted manually, if the user cares about it.
			return;
		}

		// The post was directly involved in an association - either it's non-translatable
		// or in the default language. We delete the associations and we're done.
		$this->delete_associations_involving_post( $post );
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return bool
	 */
	private function is_involved_in_association_directly( IToolset_Post $post ) {
		$query = $this->database_layer_factory->association_query();

		$found_rows = $query
			->add( $query->element(
				$post, null, true, false
			) )
			->do_not_add_default_conditions()
			->get_found_rows_directly();

		return ( $found_rows > 0 );
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
	 * @return Toolset_Result
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
			$this->get_intermediary_post_persistence()->delete_intermediary_post( $intermediary_post_id );
		}

		if ( $query->get_found_rows() > Constants::DELETE_POSTS_PER_BATCH ) {
			// Some dangling posts are left, there's too much of them to be deleted at once.
			// Schedule a WP-Cron event to delete them by batches until there are none left.
			$this->schedule_dangling_post_removal();
		}
	}


	/**
	 * @return Toolset_Cron
	 */
	private function get_cron() {
		if ( null === $this->_cron ) {
			$this->_cron = Toolset_Cron::get_instance();
		}

		return $this->_cron;
	}


	private function schedule_dangling_post_removal() {
		$cron_event = $this->cleanup_factory->cron_event();
		$this->get_cron()->schedule_event( $cron_event );
	}


	/**
	 * @return Toolset_Association_Intermediary_Post_Persistence
	 */
	private function get_intermediary_post_persistence() {
		if ( null === $this->_ip_persistence ) {
			$this->_ip_persistence = new Toolset_Association_Intermediary_Post_Persistence();
		}

		return $this->_ip_persistence;
	}


	public function cleanup_after_delete( $post_id ) {
		// Nothing to do here, this method is dictated by the interface.
	}
}
