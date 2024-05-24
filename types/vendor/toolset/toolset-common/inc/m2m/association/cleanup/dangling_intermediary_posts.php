<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;

/**
 * Delete a batch of dangling intermediary posts (DIP).
 *
 * A DIP is a post belonging to an intermediary post type that is not involved in an association
 * and is not a translation of any such post. DIPs should not exist and this class queries
 * and permanently deletes them.
 *
 * Only a single batch is deleted on each pass because this might be an expensive operation
 * which can be called from various contexts like WP-Cron or an user-triggered batch process.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Dangling_Intermediary_Posts extends Toolset_Wpdb_User {


	const OPTION_POST_TYPES_TO_DELETE = 'toolset_deleted_ipts';


	/** @var bool After a batch is performed, this will be set to false if there are no more DIPs. */
	private $has_remaining_posts = true;


	/** @var Toolset_Post_Type_Query_Factory */
	private $post_type_query_factory;


	private $database_layer_factory;


	private $deleted_posts = 0;


	/**
	 * Toolset_Association_Cleanup_Dangling_Intermediary_Posts constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Post_Type_Query_Factory|null $post_type_query_factory_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory|null $database_layer_factory
	 */
	public function __construct(
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory,
		wpdb $wpdb_di = null,
		Toolset_Post_Type_Query_Factory $post_type_query_factory_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->post_type_query_factory = $post_type_query_factory_di ?: new Toolset_Post_Type_Query_Factory();
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * Perform one batch of DIP deletions.
	 *
	 * @since 2.5.10
	 */
	public function do_batch() {
		$post_ids = array_map( 'intval', $this->get_post_ids() );
		foreach( $post_ids as $post_to_delete ) {
			add_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );
			wp_delete_post( $post_to_delete, true );
			remove_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );
		}

		$this->deleted_posts = count( $post_ids );
		if( ! $this->has_remaining_posts() ) {
			$this->clear_deletion_by_post_types();
		}
	}


	private function get_post_ids() {
		list( $post_ids, $found_rows ) = $this->database_layer_factory
			->association_database_operations()
			->get_dangling_intermediary_posts(
				$this->get_intermediary_post_types(),
				$this->get_post_types_to_delete_by()
			);

		$this->has_remaining_posts = ( $found_rows > Constants::DELETE_POSTS_PER_BATCH );

		return $post_ids;
	}


	/**
	 * After a batch operation was performed, this will return false if there are no
	 * remaining DIPs to be deleted. Otherwise returns true.
	 *
	 * @return bool
	 */
	public function has_remaining_posts() {
		return $this->has_remaining_posts;
	}


	/**
	 * After a batch operation was performed, this will return the number of posts
	 * that have actually been deleted.
	 *
	 * @return int
	 */
	public function get_deleted_posts() {
		return $this->deleted_posts;
	}


	/**
	 * @return string[] IPT slugs.
	 */
	private function get_intermediary_post_types() {
		$query = $this->post_type_query_factory->create(
			array(
				'is_intermediary' => true,
				'return' => 'slug'
			)
		);

		return $query->get_results();
	}


	public function mark_deletion_by_post_type( $post_type_slug ) {
		$post_types_to_delete = $this->get_post_types_to_delete_by();
		$post_types_to_delete[] = $post_type_slug;
		update_option( self::OPTION_POST_TYPES_TO_DELETE, array_unique( $post_types_to_delete ), false );
	}


	private function get_post_types_to_delete_by() {
		return toolset_ensarr( get_option( self::OPTION_POST_TYPES_TO_DELETE ) );
	}


	private function clear_deletion_by_post_types() {
		delete_option( self::OPTION_POST_TYPES_TO_DELETE );
	}

}
