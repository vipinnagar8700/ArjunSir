<?php

namespace OTGS\Toolset\Types\Controller\Compatibility;

use function get_current_screen;
use OTGS\Toolset\Common\Utils\Condition\Plugin\Gutenberg\IsUsedForPost;
use OTGS\Toolset\Types\Compatibility\Gutenberg\View\PostEdit as GutenbergPostEdit;
use OTGS\Toolset\Types\Page\Extension\EditPost\PerPostEditorMode;
use Toolset_Element_Factory;
use Toolset_Post_Type_Repository;
use WP_User;

/**
 * Class Gutenberg
 *
 * @package OTGS\Toolset\Types\Controller\Compatibility
 *
 * @since 3.2
 */
class Gutenberg {


	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var IsUsedForPost */
	private $is_used_for_post_condition;


	/**
	 * Gutenberg constructor.
	 *
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 * @param Toolset_Element_Factory $element_factory
	 * @param IsUsedForPost $is_used_for_post_condition
	 */
	public function __construct(
		Toolset_Post_Type_Repository $post_type_repository,
		Toolset_Element_Factory $element_factory,
		IsUsedForPost $is_used_for_post_condition
	) {
		$this->post_type_repository = $post_type_repository;
		$this->element_factory = $element_factory;
		$this->is_used_for_post_condition = $is_used_for_post_condition;
	}


	/**
	 * Initialize the compatibility with the Gutenberg editor.
	 *
	 * @since 3.2.2
	 */
	public function initialize() {

		// PHP 5.3 compatibility.
		$that = $this;
		$post_type_repository = $this->post_type_repository;
		$element_factory = $this->element_factory;
		$is_used_for_post_condition = $this->is_used_for_post_condition;

		// Handle the actions related to the "per post" editor mode.
		add_action( 'save_post', function (
			/** @noinspection PhpUnusedParameterInspection */
			$post_id, $post, $is_update
		) use (
			$that, $post_type_repository, $element_factory, $is_used_for_post_condition
		) {
			if ( ! $that->is_active_for_current_post_type() ) {
				// No Gutenberg for this post type at all - nothing to do here.
				return;
			}

			$per_post_editor_mode = new PerPostEditorMode( $post_type_repository, $element_factory, $is_used_for_post_condition );
			$per_post_editor_mode->on_save_post( $post, $is_update );
		}, 10, 3 );

		// Support both core implementation and the Gutenberg plugin.
		foreach( array( 'use_block_editor_for_post', 'gutenberg_can_edit_post' ) as $filter_name ) {
			add_filter( $filter_name, function ( $use_block_editor, $post ) use ( $post_type_repository, $element_factory, $is_used_for_post_condition ) {
				$per_post_editor_mode = new PerPostEditorMode( $post_type_repository, $element_factory, $is_used_for_post_condition );

				return $per_post_editor_mode->use_block_editor_for_post( $use_block_editor, $post );
			}, 10, 2 );
		}

		/**
		 * This is a workaround for https://github.com/WordPress/gutenberg/issues/15367.
		 *
		 * Please find complete information in the issue description.
		 * Can be removed after the issue is fixed.
		 *
		 * @param WP_User $user
		 *
		 * @since 3.3
		 */
		add_filter( 'user_has_cap', function ( $allcaps, $caps, /** @noinspection PhpUnusedParameterInspection */ $args, $user ) {
			// The condition clauses are ordered so that the most performance-intensive ones
			// are evaluated in the least amount of cases possible.
			if (
				count( $caps ) === 1 && 'edit_others_posts' === reset( $caps )
				&& toolset_getpost( 'post_author' ) === 'undefined'
				&& toolset_getarr( $allcaps, 'edit_others_posts' ) !== true
				&& $user instanceof WP_User
				&& get_current_user_id() === $user->ID
				&& (int) toolset_getpost( 'user_ID' ) === $user->ID
				&& ! post_type_supports( toolset_getpost( 'post_type' ), 'author' )
			) {
				$allcaps['edit_others_posts'] = true;
			}

			return $allcaps;
		}, 10, 4 );

		add_action( 'load-post.php', [ $this, 'on_edit_page' ] );
		add_action( 'load-post-new.php', [ $this, 'on_edit_page' ] );
	}


	/**
	 * On post edit pages, check for Gutenberg and eventually apply
	 * additional compatibility measures.
	 *
	 * @since 3.3.3
	 */
	public function on_edit_page() {
		if ( $this->is_active_for_current_post_type() ) {
			$this->post_edit_screen( new GutenbergPostEdit() );
		}
	}


	/**
	 * @return bool
	 */
	public function is_active_for_current_post_type() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		if ( ! $current_screen = get_current_screen() ) {
			// called to early
			return false;
		}

		// Check Gutenberg
		if ( function_exists( 'use_block_editor_for_post_type' ) ) {
			// >= WP 5.0
			if ( ! use_block_editor_for_post_type( $current_screen->post_type ) ) {
				// no block editor active for this post type
				return false;
			}
		} else {
			// < WP 5.0
			if ( ! function_exists( 'gutenberg_can_edit_post_type' )
				 || ! gutenberg_can_edit_post_type( $current_screen->post_type ) ) {
				// no gutenberg at all or not active for this post type
				return false;
			}
		}

		// gutenberg active
		return true;
	}

	/**
	 * Load Gutenberg compatibility on post edit screen
	 *
	 * @hook load-post.php, load-post-new.php (Post Add/Edit Page), only when the block editor is active
	 * for the current post type.
	 *
	 * @param GutenbergPostEdit $view
	 */
	public function post_edit_screen( GutenbergPostEdit $view ) {
		// hook frontend scripts loading to admin_enqueue_scripts
		add_action( 'admin_enqueue_scripts', function () use ( $view ) {
			// gutenberg active for the current post type
			$view->enqueueScripts();
		}, 11 );

		// Note: needs to happen after the added filters, especially after the use_block_editor_for_post one.
		global $pagenow;
		if ( 'post.php' === $pagenow
			&& 'edit' === toolset_getget( 'action' )
			&& $this->is_active_for_current_post_type()
		) {
			$per_post_editor_mode = new PerPostEditorMode( $this->post_type_repository, $this->element_factory, $this->is_used_for_post_condition );
			$per_post_editor_mode->on_edit_post();
		}
	}
}
