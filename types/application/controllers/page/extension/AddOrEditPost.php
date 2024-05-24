<?php

namespace OTGS\Toolset\Types\Page\Extension;

use Types_Ajax;
use Types_Helper_TinyMCE;

/**
 * Page extension controller that loads on both Add and Edit Post pages.
 *
 * @since 3.2.2
 */
class AddOrEditPost {

	/** @var \Types_Asset_Manager */
	private $asset_manager;

	/** @var \Toolset_Post_Type_Repository */
	private $post_type_repository;


	/**
	 * AddOrEditPost constructor.
	 *
	 * @param \Types_Asset_Manager $asset_manager
	 * @param \Toolset_Post_Type_Repository $post_type_repository
	 */
	public function __construct( \Types_Asset_Manager $asset_manager, \Toolset_Post_Type_Repository $post_type_repository ) {
		$this->asset_manager = $asset_manager;
		$this->post_type_repository = $post_type_repository;
	}


	/**
	 * Initialize the page extension.
	 *
	 * @codeCoverageIgnore because this is bootstrap code that ought to be tested by acceptance tests.
	 */
	public function initialize() {
		$post = wpcf_admin_get_edited_post();
		$post_type = wpcf_admin_get_edited_post_type( $post );

		$asset_manager = $this->asset_manager;
		$post_type_repository = $this->post_type_repository;
		add_action( 'admin_enqueue_scripts', static function() use( $post_type, $asset_manager, $post_type_repository, $post ) {
			$asset_manager->enqueue_scripts( array(
				// If there are no wp-components available, this will not load, but we don't mind because there's no block editor,
				// for which the script is intended.
				//
				// An edge case: The post type uses a block editor but has no editor -> WordPress will load
				// the classic editing experience while those dependencies being present as well.
				// This needs to be handled in the script below as well.
				\Types_Asset_Manager::SCRIPT_POST_ADD_OR_EDIT,
				// This will load always.
				\Types_Asset_Manager::SCRIPT_POST_ADD_OR_EDIT_NO_COMPONENTS,
			) );
			$asset_manager->enqueue_styles( \Types_Asset_Manager::STYLE_POST_ADD_OR_EDIT );

			// Tell the script about the editor mode (it will decide whether to show the button to switch back to the classic editor).
			$post_type_model = $post_type_repository->get( $post_type );
			$editor_mode = ( null === $post_type_model ) ? '' : $post_type_model->get_editor_mode();

			// Decide whether it is necessary to reload the page after saving the post.
			$needs_reload_after_saving = (bool) apply_filters( 'types_reload_post_after_saving', false, $post );

			wp_localize_script( \Types_Asset_Manager::SCRIPT_POST_ADD_OR_EDIT, 'types_post_add_or_edit_l10n', array(
				'editorMode' => $editor_mode,
				'needsReloadAfterSaving' => $needs_reload_after_saving,
				'reevaluateFieldGroup' => [
					'actionName' => Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_REEVALUATE_DISPLAYED_FIELD_GROUPS ),
					'nonce' => wp_create_nonce( Types_Ajax::CALLBACK_REEVALUATE_DISPLAYED_FIELD_GROUPS ),
				],
				'strings' => array(
					// translators: Admin notice that displays in the (block) post editor after the post is saved.
					'displayConditionsMightHaveChanged' => __( 'Conditions for displaying some custom fields on this page have changed.', 'wpcf' ),
					// translators: Second part (with a link) of an admin notice that displays in the (block) post editor after the post is saved.
					'reloadPage' => __( 'Reload this page to see the changes.', 'wpcf' ),
				)
			) );

			$tinymce_helper = new Types_Helper_TinyMCE();
			$tinymce_helper->localize_dynamic_tinymce_init_script();
		} );
	}
}
