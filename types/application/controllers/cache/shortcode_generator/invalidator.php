<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

use OTGS\Toolset\Types\Model\Wordpress\Transient;

/**
 * Abstract base for the invalidator controllers.
 *
 * @since 3.3.6
 */
abstract class InvalidatorBase {

	/**
	 * @var Transient
	 */
	protected $transient_manager = null;

	/**
	 * Constructor
	 *
	 * @param Transient $transient_manager
	 */
	public function __construct( Transient $transient_manager ) {
		$this->transient_manager = $transient_manager;
	}

	/**
	 * Initialize this controller.
	 *
	 * @since 3.3.6
	 */
	public function initialize() {
		// Register the hooks that will update the existing cache when a new field arrives
		$this->add_update_hooks();
	}

	/**
	 * Add the right update hooks to invalidate the cache.
	 *
	 * @since 3.3.6
	 */
	protected function add_update_hooks() {
		add_action( 'save_post_' . $this->get_meta_post_type(), array( $this, 'delete_transient' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'delete_transient' ), 10 );
	}

	/**
	 * Get the key for the transient cache.
	 *
	 * @since 3.3.6
	 */
	abstract protected function get_transient_key();

	/**
	 * Get the post type for the items acting as field groups.
	 *
	 * @since 3.3.6
	 */
	abstract protected function get_meta_post_type();

	/**
	 * Delete the cache.
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 * @since 3.3.6
	 */
	public function delete_transient( $post_id, $post = null ) {
		if ( is_null( $post ) ) {
			$post = get_post( $post_id );
			if ( is_null( $post ) ) {
				return;
			}
		}

		if ( $this->get_meta_post_type() !== $post->post_type ) {
			return;
		}

		$this->transient_manager->delete_transient( $this->get_transient_key() );
	}

}
