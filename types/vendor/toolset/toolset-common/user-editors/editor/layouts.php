<?php

/**
 * Editor class for the Layouts plugin.
 *
 * Handles all the functionality needed to allow the Layouts plugin to work with Content Template editing.
 *
 * @since 3.2.1
 */
class Toolset_User_Editors_Editor_Layouts
	extends Toolset_User_Editors_Editor_Abstract {

	const LAYOUTS_SCREEN_ID = 'layouts';
	const LAYOUTS_BUILDER_OPTION_NAME = '_private_layouts_template_in_use';
	const LAYOUTS_BUILDER_OPTION_VALUE = 'yes';
	const LAYOUTS_BUILDER_PRIVATE_LAYOUT_SETTINGS_OPTION_NAME = '_dd_layouts_settings';

	/**
	 * @var WPDD_Layouts
	 */
	private $layouts;

	/**
	 * @var Toolset_Condition_Plugin_Layouts_Active
	 */
	private $layouts_is_active;

	/**
	 * @var string
	 */
	protected $id = self::LAYOUTS_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'Layouts';

	/**
	 * @var string
	 */
	protected $option_name = '_toolset_user_editors_layouts_template';

	/**
	 * @var string
	 */
	protected $logo_class = 'icon-layouts-logo';

	public function set_layouts_is_active( \Toolset_Condition_Plugin_Layouts_Active $is_layouts_active ) {
		$this->layouts_is_active = $is_layouts_active;
	}

	public function set_layouts( \WPDD_Layouts $layouts ) {
		$this->layouts = $layouts;
	}

	public function initialize() {
		$this->layouts_is_active = new Toolset_Condition_Plugin_Layouts_Active();
		$this->layouts = $this->layouts_is_active->is_met() ? WPDD_Layouts::getInstance() : null;

		$this->set_extra_postmeta_support();
	}

	/**
	 * Add support for managing extra postmeta keys when setting this user editor.
	 *
	 * @since Views 3.1
	 */
	public function set_extra_postmeta_support() {
		if (
			isset( $this->medium )
			&& $this->medium->get_id()
		) {
			// Layouts meta updating filter is initialized on "init", so we need to move the "update_layouts_builder_post_meta"
			// call on "init" and not before that. The current call happens on "before_init".
			// This can be safely removed once Layouts will be initialized on "after_theme_setup".
			add_action( 'init', array( $this, 'update_layouts_builder_post_meta_on_init' ) );
		}

		add_action( 'wpv_content_template_duplicated', array( $this, 'update_extra_post_meta_from_clone' ), 10, 3 );

		add_action( 'toolset_update_layouts_builder_post_meta', array( $this, 'update_extra_post_meta_from_request' ), 10, 2 );
	}

	public function update_layouts_builder_post_meta_on_init() {
		$this->update_extra_post_meta_from_request( $this->medium->get_id(), 'ct_editor_choice' );
	}

	/**
	 * Set the extra postmeta for this given user editor.
	 * Note that besides this we set some defaults for the layout assigned to the given CT.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function update_extra_post_meta( $post_id ) {
		update_post_meta( $post_id, self::LAYOUTS_BUILDER_OPTION_NAME, self::LAYOUTS_BUILDER_OPTION_VALUE );

		$layout_type = 'fluid';
		$layouts = $this->layouts;
		$default_private_layout_setting = call_user_func_array( array( $layouts, 'load_layout'), array( $this->constants->constant( 'WPDDL_PRIVATE_EMPTY_PRESET' ), $layout_type ) );

		// Get the current Content Template content and if the content exists and is not empty, create a new visual
		// editor cell and place the existing Content Template content there.
		$post = get_post( $post_id );

		/* translators: Prefix for the Content Template name that is built using Layouts. */
		$default_private_layout_setting['name'] = __( 'Layout for', 'wpv-views' ) . ' ' . $post->post_title;
		$default_private_layout_setting['type'] = $layout_type;
		$default_private_layout_setting['layout_type']  = 'private';
		$default_private_layout_setting['owner_kind']  = 'view_template';

		if (
			property_exists( $post, 'post_content' ) &&
			'' !== $post->post_content &&
			is_callable(
				array(
					'WPDD_Utils',
					'create_cell',
				)
			)
		) {
			$default_private_layout_setting['Rows'][0] = array(
				'kind' => 'Row',
				'Cells' => array(
					WPDD_Utils::create_cell(
						'Post Content Cell',
						1,
						'cell-text', array(
							'content' => array(
								'content' => sanitize_textarea_field( $post->post_content ),
							),
							'width'   => 12,
						)
					),
				),
				'cssClass' => 'row-fluid',
				'name' => 'Post content row',
				'additionalCssClasses' => '',
				'row_divider' => 1,
				'layout_type' => 'fluid',
				'mode' => 'full-width',
				'cssId' => '',
				'tag' => 'div',
				'width' => 1,
				'editorVisualTemplateID' => '',
			);
		}
		/**
		 * Handles the saving of the Content Layout.
		 *
		 * The return value from this filter is not assigned to a variable because it won't be used at all until the end
		 * of the call. This filter is only called to just save the Layout settings.
		 *
		 * @param int   $post_id
		 * @param array $default_private_layout_setting
		 *
		 * @return int
		 *
		 * @since 3.2.3
		 */
		apply_filters( 'ddl-save_layout_settings', $post_id, $default_private_layout_setting );
	}

	/**
	 * Delete the extra postmeta for this given user editor.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function delete_extra_post_meta( $post_id ) {
		delete_post_meta( $post_id, self::LAYOUTS_BUILDER_OPTION_NAME );
		delete_post_meta( $post_id, self::LAYOUTS_BUILDER_PRIVATE_LAYOUT_SETTINGS_OPTION_NAME );
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if ( $this->layouts_is_active->is_met() ) {
			/* translators: The name of the editor that edits Content Templates using Layouts. */
			$this->name = __( 'Layouts', 'wpv-views' );
			return true;
		}

		return false;
	}

	public function run() {}
}
