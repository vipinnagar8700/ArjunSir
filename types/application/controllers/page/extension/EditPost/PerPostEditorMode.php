<?php

namespace OTGS\Toolset\Types\Page\Extension\EditPost;

use OTGS\Toolset\Common\PostType\EditorMode;
use OTGS\Toolset\Common\Utility\Admin\Notices\Builder;
use OTGS\Toolset\Common\Utils\Condition\Plugin\Gutenberg\IsUsedForPost;

/**
 * Encapsulates the functionality of the "Per post" editor mode when editing a post.
 *
 * @since 3.2.2
 */
class PerPostEditorMode {

	// URL query arguments to indicate a switch between editor modes.
	const SWITCH_TO_BLOCK_EDITOR_ARG = 'toolset-switch-to-block-editor';
	const SWITCH_TO_CLASSIC_EDITOR_ARG = 'toolset-switch-to-classic-editor';

	// Values for the SWITCH_TO_CLASSIC_EDITOR_ARG argument.
	const CLASSIC_EDITOR_KEEP = 'keep_current_content';
	const CLASSIC_EDITOR_REVERT = 'revert_to_previous_content';

	/** @var \Toolset_Post_Type_Repository */
	private $post_type_repository;

	/** @var \Toolset_Element_Factory */
	private $element_factory;

	/** @var IsUsedForPost */
	private $is_used_for_post_condition;


	/**
	 * PerPostEditorMode constructor.
	 *
	 * @param \Toolset_Post_Type_Repository $post_type_repository
	 * @param \Toolset_Element_Factory $element_factory
	 * @param IsUsedForPost $is_used_for_post_condition
	 */
	public function __construct(
		\Toolset_Post_Type_Repository $post_type_repository,
		\Toolset_Element_Factory $element_factory,
		IsUsedForPost $is_used_for_post_condition
	) {
		$this->post_type_repository = $post_type_repository;
		$this->element_factory = $element_factory;
		$this->is_used_for_post_condition = $is_used_for_post_condition;
	}


	/**
	 * Callback for the use_block_editor_for_post filter hook.
	 *
	 * Handles ONLY the "per post" editor mode, otherwise leaves the choice of the editor unaffected.
	 *
	 * @param bool $use_block_editor
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function use_block_editor_for_post( $use_block_editor, $post ) {

		if( ! $post instanceof \WP_Post ) {
			// Something fishy is going on. Don't try to do anything.
			return $use_block_editor;
		}

		$post_type = $this->post_type_repository->get( $post->post_type );
		if( null === $post_type ) {
			// Again, something weird is going on.
			return $use_block_editor;
		}

		if( EditorMode::PER_POST !== $post_type->get_editor_mode() ) {
			// Not a per-post editor mode for the current post type. We're not going to alter the value.
			return $use_block_editor;
		}

		global $pagenow;
		if( 'post-new.php' === $pagenow ) {
			// Keep default behaviour (which is supposed to be to show Gutenberg) when adding a new post.
			return $use_block_editor;
		}

		try {
			$post = $this->element_factory->get_post_untranslated( $post );
		} catch ( \Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// Again, this should never happen.
			return $use_block_editor;
		}

		// Editor mode of the actual post - it will always be a specific value, not PER_POST.
		$post_editor_mode = $post->get_editor_mode();

		// Switch to another editor mode if it's indicated by the URL argument.
		// Make sure a redundant switch is prevented.
		if( EditorMode::CLASSIC === $post_editor_mode && toolset_getget( self::SWITCH_TO_BLOCK_EDITOR_ARG ) ) {
			$post->switch_to_block_editor();
			$post_editor_mode = EditorMode::BLOCK;
		} elseif( EditorMode::BLOCK === $post_editor_mode && toolset_getget( self::SWITCH_TO_CLASSIC_EDITOR_ARG ) ) {
			$post->switch_to_classic_editor(
				self::CLASSIC_EDITOR_REVERT === toolset_getget( self::SWITCH_TO_CLASSIC_EDITOR_ARG )
			);
			$post_editor_mode = EditorMode::CLASSIC;
		}

		return ( EditorMode::BLOCK === $post_editor_mode );
	}


	/**
	 * In the "per post" editor mode, choose the block editor for a new post and save this choice.
	 *
	 * This is hooked into the save_post filter hook.
	 *
	 * @param \WP_Post $post
	 * @param bool $is_update
	 */
	public function on_save_post( $post, $is_update ) {
		if( ! $post instanceof \WP_Post ) {
			// Something fishy is going on. Don't try to do anything.
			return;
		}

		$post_type = $this->post_type_repository->get( $post->post_type );
		if( null === $post_type ) {
			// Again, something weird is going on.
			return;
		}

		if( EditorMode::PER_POST !== $post_type->get_editor_mode() ) {
			// Not a per-post editor mode for the current post type. We're not going to alter the value.
			return;
		}

		if( $is_update ) {
			return;
		}

		try {
			$post_model = $this->element_factory->get_post_untranslated( $post );
		} catch ( \Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			return;
		}

		$post_model->set_post_editor_mode( EditorMode::BLOCK );
	}


	/**
	 * Render links for switching the editor mode on the Edit Post page, if the post type has the "per post" editor mode.
	 */
	public function on_edit_post() {

		if ( isset( $_GET['meta-box-loader'] ) ) {
			// We're in the meta box loader, this is a separate request from the Gutenberg editor - so don't interfere.
			return;
		}

		if( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if( ! $screen instanceof \WP_Screen ) {
			return;
		}

		$current_post_id = (int) toolset_getget( 'post' );
		$post = get_post( $current_post_id );

		if( ! $post instanceof \WP_Post ) {
			// Something fishy is going on. Don't try to do anything.
			return;
		}

		$post_type = $this->post_type_repository->get( $post->post_type );
		if( null === $post_type ) {
			// Again, something weird is going on.
			return;
		}

		if( EditorMode::PER_POST !== $post_type->get_editor_mode() ) {
			// Not a per-post editor mode for the current post type. We're not going to alter the value.
			return;
		}

		$this->is_used_for_post_condition->set_post( $post );
		$is_block_editor = $this->is_used_for_post_condition->is_met();

		if( ! $is_block_editor ) {
			// Showing a classic editor. Also display a notice that allows users to safely try the block editor.
			$notice_builder = new Builder();
			$notice = $notice_builder->createNotice( 'types-switch-to-block-editor', 'undismissable' );
			$notice->set_template_path( TYPES_TEMPLATES . '/page/extension/per_post_editor_mode/notice_switch_to_gutenberg.phtml' );
			$notice_builder->addNotice( $notice );
		}

		// In case of the block editor, this is handled in JS (see public_src/js/Post/AddOrEdit.js).
	}
}
