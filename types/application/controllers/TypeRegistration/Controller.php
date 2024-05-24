<?php

namespace OTGS\Toolset\Types\TypeRegistration;


use OTGS\Toolset\Common\PostType\EditorMode;


/**
 * This is supposed to become the main controller for handling the registration of custom data types registered by
 * Types - post types and taxonomies.
 *
 * Whenever any part of the codebase used by wpcf_init_custom_types_taxonomies() is refactored, it should become a
 * part of the TypeRegistration namespace.
 *
 * @since 3.2.2
 */
class Controller {


	/** @var \Toolset_Post_Type_Repository */
	private $post_type_repository;


	/**
	 * Controller constructor.
	 *
	 * @param \Toolset_Post_Type_Repository $post_type_repository
	 */
	public function __construct( \Toolset_Post_Type_Repository $post_type_repository ) {
		$this->post_type_repository = $post_type_repository;
	}


	/**
	 * Initialize the controller.
	 */
	public function initialize() {
		// Handle the Classic editor mode for all post types (including built-in ones).
		// We need to apply two filters because it's different for the built-in block editor and the Gutenberg plugin.
		foreach( array( 'gutenberg_can_edit_post_type', 'use_block_editor_for_post_type' ) as $hook_name ) {
			add_filter( $hook_name, array( $this, 'apply_classic_editor_mode_for_post_type' ), 10, 2 );
		};
	}


	/**
	 * If the post type has a Classic editor mode, apply it here.
	 *
	 * The "per post" editor mode is handled separately in \OTGS\Toolset\Types\Page\Extension\EditPost\PerPostEditorMode.
	 *
	 * @param bool $use_block_editor
	 * @param string $post_type Post type slug.
	 *
	 * @return bool
	 */
	public function apply_classic_editor_mode_for_post_type( $use_block_editor, $post_type ) {
		$post_type = $this->post_type_repository->get( $post_type );
		if( null === $post_type ) {
			// Again, something weird is going on.
			return $use_block_editor;
		}

		if( EditorMode::CLASSIC !== $post_type->get_editor_mode() ) {
			// Not a classic editor mode for the current post type. We're not going to alter the value.
			return $use_block_editor;
		}

		// For classic editor mode, disable the block editor.
		return false;
	}
}
