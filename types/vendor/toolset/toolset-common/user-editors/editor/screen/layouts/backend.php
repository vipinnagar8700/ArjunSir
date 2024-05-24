<?php

/**
 * Backend Editor class for Layouts.
 *
 * Handles all the functionality needed to allow Layouts to work with Content Template editing on the backend.
 *
 * @since 3.2.3
 */
class Toolset_User_Editors_Editor_Screen_Layouts_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	const LAYOUT_TEMPLATE_SCRIPT_HANDLE = 'toolset-user-editors-layouts-layout-template-script';

	public function initialize() {
		parent::initialize();

		add_action( 'init', array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 50 );

		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes', array( $this, 'layout_template_attribute' ), 10, 2 );

		add_action( 'wpv_action_wpv_ct_inline_user_editor_buttons', array( $this, 'register_inline_editor_action_buttons' ) );

		add_action( 'toolset_set_layout_template_user_editor_layouts', array( $this, 'update_layouts_post_meta' ) );
		add_action( 'toolset_set_layout_template_user_editor_basic', array( $this, 'update_layouts_post_meta' ) );

		add_action( 'ddl-disabled_cells_on_content_layout', array( $this, 'enable_post_content_cell_for_content_templates' ), 1000, 1 );

		add_filter( 'wpv_filter_maybe_ct_designed_with_layouts', array( $this, 'maybe_ct_designed_with_layouts' ), 10, 2 );

		add_filter( 'wpv_filter_post_content_for_post_content_cell', array( $this, 'get_wpv_post_body_shortcode_for_post_content_cell' ) );
	}

	public function is_active() {
		if ( ! $this->set_medium_as_post() ) {
			return false;
		}

		$this->action();

		return true;
	}

	private function action() {
		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_assets' ) );
		$this->medium->set_html_editor_backend( array( $this, 'html_output' ) );
		$this->medium->page_reload_after_backend_save();
	}

	public function register_assets() {
		// Content Template own edit screen assets
		$this->assets_manager->register_style(
			Toolset_User_Editors_Editor_Screen_Basic_Backend::USER_EDITORS_COMMON_STYLE_HANDLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . Toolset_User_Editors_Editor_Screen_Basic_Backend::USER_EDITORS_COMMON_STYLE_RELATIVE_PATH,
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		// Content Template as inline object assets
		$this->assets_manager->register_script(
			self::LAYOUT_TEMPLATE_SCRIPT_HANDLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/user-editors/editor/screen/layouts/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' ),
			true
		);

		$view_id = sanitize_text_field( toolset_getget( 'view_id', '' ) );
		$view_id = '' !== $view_id ?
			'&view_id=' . $view_id :
			'';

		$layouts_layout_template_i18n = array(
			'template_editor_url' => admin_url( 'admin.php?page=dd_layouts_edit&action=edit&source=views-editor' . esc_attr( $view_id ) ),
			'template_overlay' => array(
				/* translators: Informative text about the editor used to create the Content Template. */
				'title' => sprintf( __( 'This template is designed with <strong>%1$s</strong>', 'wpv-views' ), $this->editor->get_name() ),
				/* translators: Text for the button that redirects users to Content Template editing using the editor of choice. */
				'button' => sprintf( __( 'Edit with %1$s', 'wpv-views' ), $this->editor->get_name() ),
				/* translators: Text for the link that reverts the editing of a Content Template back to the Toolset Views native editor. */
				'discard' => sprintf( __( 'Stop using %1$s for this template', 'wpv-views' ), $this->editor->get_name() ),
			),
		);

		$this->assets_manager->localize_script(
			self::LAYOUT_TEMPLATE_SCRIPT_HANDLE,
			'toolset_user_editors_layouts_layout_template_i18n',
			$layouts_layout_template_i18n
		);
	}

	public function admin_enqueue_assets() {
		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( self::LAYOUT_TEMPLATE_SCRIPT_HANDLE ) );
		}
	}

	public function action_enqueue_assets() {
		do_action( 'toolset_enqueue_styles', array( Toolset_User_Editors_Editor_Screen_Basic_Backend::USER_EDITORS_COMMON_STYLE_HANDLE ) );
	}

	private function set_medium_as_post() {
		$medium_id = $this->medium->get_id();

		if ( ! $medium_id ) {
			return false;
		}

		$medium_post_object = get_post( $medium_id );

		return ! ( null === $medium_post_object );
	}

	public function register_user_editor( $editors ) {
		$editors[ $this->editor->get_id() ] = $this->editor->get_name();
		return $editors;
	}

	/**
	 * Content Template editor output.
	 *
	 * Displays the Layouts message and button to fire it up.
	 *
	 * @since 2.5.0
	 */
	public function html_output() {
		$ct_id = (int) toolset_getget( 'ct_id', 0 );
		if ( 0 >= $ct_id ) {
			/* translators: Error message when no or invalid Content Template ID is set. */
			return __( 'No valid content template id', 'wpv-views' );
		}

		$context = array(
			'editor-url' => admin_url( 'admin.php?page=dd_layouts_edit&action=edit&layout_id=' . esc_attr( $ct_id ) . '&source=' . esc_attr( $this->constants->constant( 'WPV_CT_EDITOR_PAGE_NAME' ) ) ),
			'editor-name' => $this->editor->get_name(),
			'frontend-templates' => $this->medium->get_frontend_templates(),
			'admin-url' => admin_url( 'admin.php?page=ct-editor&ct_id=' . esc_attr( $ct_id ) ),
		);
		$output = $this->toolset_renderer->render(
			$this->template_repository->get( Toolset_Output_Template_Repository::USER_EDITORS_CONTENT_TEMPLATE_EDITOR_OVERLAY ),
			$context,
			false
		);

		return $output;
	}

	/**
	 * Creates the Layouts button for the inline Content Template editor action.
	 *
	 * @param WP_POST $content_template
	 */
	public function register_inline_editor_action_buttons( $content_template ) {
		$content_template_has_layouts = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) === Toolset_User_Editors_Editor_Layouts::LAYOUTS_SCREEN_ID );

		$context = array(
			'editor-id' => $this->editor->get_id(),
			'editor-name' => $this->editor->get_name(),
			'editor-logo-class' => $this->editor->get_logo_class(),
			'content-template-has-layouts' => $content_template_has_layouts,
		);
		$this->toolset_renderer->render(
			$this->template_repository->get( Toolset_Output_Template_Repository::USER_EDITORS_INLINE_EDITOR_ACTION_BUTTON ),
			$context
		);
	}

	/**
	 * Set the builder used by a Content Template, if any.
	 *
	 * On a Content Template used inside a View or WPA loop output, we set which builder it is using
	 * so we can link to the CT edit page with the right builder instantiated.
	 *
	 * @param array   $attributes
	 * @param WP_POST $content_template
	 *
	 * @return array
	 *
	 * @since 3.2.1
	 */
	public function layout_template_attribute( $attributes, $content_template ) {
		$content_template_has_layouts = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) === Toolset_User_Editors_Editor_Layouts::LAYOUTS_SCREEN_ID );
		if ( $content_template_has_layouts ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}

	public function update_layouts_post_meta() {
		$ct_id = (int) toolset_getpost( 'ct_id', 0 );
		if ( 0 !== $ct_id ) {
			do_action( 'toolset_update_layouts_builder_post_meta', $ct_id, 'editor' );
		}
	}

	/**
	 * Gets the "Edit with <editor>" link under each Content Template in the Content Templates listing page.
	 *
	 * @param int $template_id
	 *
	 * @return string
	 */
	public function get_ct_edit_with_editor_link( $template_id = 0 ) {
		if ( 0 >= (int) $template_id ) {
			return '';
		}

		$link_format = '<a href="%1$s">%2$s</a>';
		$link_url = esc_url(
			add_query_arg(
				array(
					'page' => $this->constants->constant( 'WPDDL_LAYOUTS_EDITOR_PAGE' ),
					'action' => 'edit',
					'layout_id' => $template_id,
					'source' => 'ct-editor'
				),
				admin_url( 'admin.php' )
			)
		);
		/* translators: Text for the link to edit a Content Template with Layouts in the Content Template listing page. */
		$link_text = __( 'Edit with Layouts', 'wpv-views' );

		return sprintf( $link_format, $link_url, $link_text );
	}

	/**
	 * Re-enables the post content cell for Content Templates edited with Layouts.
	 *
	 * Layouts edits Content Templates as Content Layouts. The post content cell for Content Layouts is disabled by default
	 * but we need this for Content Template, so we need to enable it again.
	 *
	 * @param array $cells The cells to be disabled in Layouts.
	 *
	 * @return array
	 */
	public function enable_post_content_cell_for_content_templates( $cells = array() ) {
		global $post;

		if (
			null === $post ||
			! $post instanceof WP_Post
		) {
			return $cells;
		}

		if ( $this->maybe_ct_is_built_with_editor( $post->ID ) ) {
			return array_diff( $cells, array( 'cell-post-content' ) );
		}

		return $cells;
	}

	/**
	 * Returns true if the $ct_id represents a Content Template designed with Layouts or false otherwise.
	 *
	 * @param bool   $is_ct_designed_with_layouts The boolean value that determines if a Content Template is designed
	 *                                            with Layouts.
	 * @param string $ct_id                       The ID that maybe represents a Content Template.
	 *
	 * @return bool
	 */
	public function maybe_ct_designed_with_layouts( $is_ct_designed_with_layouts, $ct_id ) {
		return $this->maybe_ct_is_built_with_editor( $ct_id );
	}

	/**
	 * Returns the "wpv-post-body" shortcode for the case where a "Post Content" cell is included inside a Content Template
	 * designed with Layouts.
	 *
	 * @param string $content The content that will take the place of the "Post Content" cell in a Content Template
	 *                        designed with Layouts.
	 *
	 * @return string
	 */
	public function get_wpv_post_body_shortcode_for_post_content_cell( $content ) {
		return '[wpv-post-body view_template="None"]';
	}
}
