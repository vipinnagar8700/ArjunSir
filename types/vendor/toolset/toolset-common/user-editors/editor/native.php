<?php

/**
 * Editor class for the Native post editor.
 *
 * Handles all the functionality needed to allow the Native post editor to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Native
	extends Toolset_User_Editors_Editor_Abstract {

	const NATIVE_SCREEN_ID = 'native';

	/**
	 * @var string
	 */
	protected $id = self::NATIVE_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'Native editor';

	/**
	 * @var string
	 */
	protected $option_name = '_toolset_user_editors_native';

	public function initialize() {
		$this->name = __( 'Classic Editor', 'wpv-views' );

		if ( $this->is_native_editor_for_cts() ) {
			add_action( 'init', array( $this, 'add_support_for_ct_edit_by_native_editor' ), 9 );

			add_action( 'current_screen', array( $this, 'show_notice_to_get_back_to_toolset_ct_editor' ) );

			add_action( 'add_meta_boxes_view-template', array( $this, 'remove_native_editor_meta_boxes' ) );

			add_action( 'init', array( $this, 'register_assets_for_backend_editor' ), 51 );

			add_filter( 'wpv_filter_is_native_editor_for_cts', array( $this, 'is_native_editor_for_cts' ) );

			add_filter( 'toolset_filter_force_shortcode_generator_display', array( $this, 'force_shortcode_generator_display' ) );

			add_filter( 'post_updated_messages', array( $this, 'adjust_post_updated_messages' ) );
		}
	}

	public function required_plugin_active() {
		return $this->is_views_active->is_met();
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-native-script' ) );
	}


	/**
	 * Verify that the current page and URL parameters qualify for the editing of Content Templates using the
	 * native post editor.
	 *
	 * @param  null|int $post_id The current post ("view-template" post) ID.
	 *
	 * @return bool
	 *
	 * @since 2.5.0
	 */
	public function is_native_editor_for_cts() {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return false;
		}

		$action = sanitize_text_field( toolset_getget( 'action', '' ) );
		$action = '' === $action ? sanitize_text_field( toolset_getpost( 'action', '' ) ) : $action;

		$post_id = (int) sanitize_text_field( toolset_getget( 'post', 0 ) );
		$post_id = ( 0 === $post_id ? (int) sanitize_text_field( toolset_getpost( 'post_ID', 0 ) ) : $post_id );

		if (
			in_array( $action, array( 'edit', 'editpost' ), true )
			&& 'view-template' === get_post_type( $post_id )
		) {
			return true;
		}

		return false;
	}

	public function add_support_for_ct_edit_by_native_editor() {
		add_filter( 'register_post_type_args', array( $this, 'make_ct_editable_by_native_editor' ), 10, 2 );

		// This filter is only included in the Gutenberg plugin.
		add_filter( 'gutenberg_can_edit_post_type', array( $this, 'disable_gutenberg_for_content_templates' ), 10, 2 );

		// This filter is only included in the core.
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg_for_content_templates' ), 10, 2 );
	}

	/**
	 * For the "view-template" custom post type to be editable by the native post editor, we need to temporarily set
	 * the "show_ui" argument that is used during the custom post type registration to true.
	 *
	 * @param  array  $args The arguments of the custom post type for its registration.
	 * @param  string $name The name of the custom post type to be registered.
	 *
	 * @return mixed        The filtered arguments.
	 *
	 * @since 2.5.0
	 */
	public function make_ct_editable_by_native_editor( $args, $name ) {
		if ( 'view-template' === $name ) {
			$args['show_ui'] = true;
		}
		return $args;
	}

	/**
	 * Disable the new editor (Gutenberg) for Content Templates, to repair the integrations with page builder that is
	 * achieved through the classic editor.
	 *
	 * @param bool   $is_enabled The status of the new editor (Gutenberg) for the selected post type.
	 * @param string $post_type  The selected post type.
	 *
	 * @return bool
	 */
	public function disable_gutenberg_for_content_templates( $is_enabled, $post_type ) {
		if ( 'view-template' === $post_type ) {
			return false;
		}

		return $is_enabled;
	}

	/**
	 * The "view-template" custom post type supports "author" and "slug" by design. In the native post editor,
	 * when editing a Content Template, we need to hide the meta-boxes.
	 *
	 * @since 2.5.0
	 */
	public function remove_native_editor_meta_boxes() {
		remove_meta_box( 'authordiv', 'view-template', 'normal' );
		remove_meta_box( 'slugdiv', 'view-template', 'normal' );
		remove_meta_box( 'postcustom', 'view-template', 'normal' );
	}

	/**
	 * Show a notice on the top of the native post editor with a link to return back to the Toolset Content Template editor.
	 *
	 * @since 2.5.0
	 */
	public function show_notice_to_get_back_to_toolset_ct_editor() {
		$ct_id = (int) wpv_getget( 'post', 0 );

		$notice = new Toolset_Admin_Notice_Success( 'return-to-toolset-ct-editor-page-notice' );

		$notice_content = sprintf(
			__( 'Done editing here? Return to the %1$sToolset Content Template editor.%2$s', 'wpv-views' ),
			'<a href="' . admin_url( 'admin.php?page=ct-editor&ct_id=' . $ct_id ) . '">',
			'</a>'
		);

		Toolset_Admin_Notices_Manager::add_notice( $notice, $notice_content );
	}

	public function force_shortcode_generator_display( $register_section ) {
		return true;
	}

	public function adjust_post_updated_messages( $messages ) {
		$messages['post'][1] = __( 'Content Template updated.', 'wpv-views' );
		return $messages;
	}
}
