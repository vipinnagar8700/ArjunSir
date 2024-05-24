<?php
/**
 * Abstract Backend Editor class.
 *
 * Handles all the functionality needed to allow editors to work with Content Template editing on the backend.
 *
 * @since 2.5.9
 */
abstract class Toolset_User_Editors_Editor_Screen_Abstract
	implements Toolset_User_Editors_Editor_Screen_Interface {

	/**
	 * @var Toolset_User_Editors_Medium_Interface
	 */
	protected $medium;

	/**
	 * @var Toolset_User_Editors_Editor_Interface
	 */
	protected $editor;

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * @var Toolset_Renderer
	 */
	protected $toolset_renderer;

	/**
	 * @var Toolset_Output_Template_Repository
	 */
	protected $template_repository;

	/**
	 * @var null|Toolset_Assets_Manager
	 */
	protected $assets_manager;

	/**
	 * @var Toolset_Constants
	 */
	protected $toolset_constants;

	/**
	 * @var \WPV_Settings
	 */
	protected $wpv_settings;


	/**
	 * @param \Toolset_User_Editors_Editor_Abstract $editor
	 * @param \Toolset_User_Editors_Medium_Interface $medium
	 * @param \Toolset_Constants $constants
	 * @param \Toolset_Renderer $toolset_renderer
	 * @param \Toolset_Output_Template_Repository $template_repository
	 * @param \Toolset_Assets_Manager $assets_manager
	 * @param \Toolset_Constants|null $toolset_constants
	 * @param \WPV_Settings|null $wpv_settings
	 */
	public function __construct(
		\Toolset_User_Editors_Editor_Abstract $editor,
		\Toolset_User_Editors_Medium_Interface $medium,
		\Toolset_Constants $constants,
		\Toolset_Renderer $toolset_renderer,
		\Toolset_Output_Template_Repository $template_repository,
		\Toolset_Assets_Manager $assets_manager,
		\Toolset_Constants $toolset_constants = null,
		$wpv_settings = null
	) {
		$this->editor = $editor;
		$this->medium = $medium;
		$this->constants = $constants;
		$this->toolset_renderer = $toolset_renderer;
		$this->template_repository = $template_repository;
		$this->assets_manager = $assets_manager;
		$this->toolset_constants = null !== $toolset_constants ? $toolset_constants : new Toolset_Constants();

		if ( class_exists( \WPV_Settings::class ) ) {
			$this->wpv_settings = null !== $wpv_settings ? $wpv_settings : \WPV_Settings::get_instance();
		}
	}

	/**
	 * Initializes the Toolset_User_Editors_Editor_Screen_Abstract class.
	 */
	public function initialize() {
		add_filter( 'wpv_filter_display_ct_used_editor', array( $this, 'get_editor_name_for_ct_states' ), 10, 2 );

		add_filter( 'wpv_filter_get_edit_ct_with_editor_link', array( $this, 'maybe_get_ct_edit_with_editor_link' ), 10, 2 );
	}

	/**
	 * Check whether the current page is a Views edit page or a WPAs edit page.
	 * We need this check to register the needed assets for the inline CT section of those pages.
	 *
	 * @return bool Return true if the current page is the Views or WPAs edit page, false othewrise.
	 */
	public function is_views_or_wpa_edit_page() {
		$screen = get_current_screen();

		/*
		 * Class "WPV_Page_Slug" was introduced in Views 2.5.0, which caused issues when the installed version of Views
		 * was older than 2.5.0.
		 */
		$views_edit_page_screen_id = class_exists( 'WPV_Page_Slug' ) ? WPV_Page_Slug::VIEWS_EDIT_PAGE : 'toolset_page_views-editor';
		$wpa_edit_page_screen_id = class_exists( 'WPV_Page_Slug' ) ? WPV_Page_Slug::WORDPRESS_ARCHIVES_EDIT_PAGE : 'toolset_page_view-archives-editor';

		return in_array(
			$screen->id,
			array(
				$views_edit_page_screen_id,
				$wpa_edit_page_screen_id,
			),
			true
		);
	}


	public function add_medium( Toolset_User_Editors_Medium_Interface $medium ) {
		$this->medium = $medium;
	}

	public function add_editor( Toolset_User_Editors_Editor_Interface $editor ) {
		$this->editor = $editor;
	}

	public function is_active() {
		return false;
	}

	/**
	 * Returns the editor name to display it next to the Content Template on the Content Template listing page, if the
	 * Content Template of the displayed row is built the the current editor.
	 *
	 * @param  string  $used_ct_editor Either the name of the editor used to build the Content Template or an empty string.
	 * @param  WP_Post $ct             The currently investigated Content Template.
	 *
	 * @return string  Either the name of the editor used to build the Content Template or an empty string.
	 */
	public function get_editor_name_for_ct_states( $used_ct_editor, $ct ) {
		if ( $this->maybe_ct_is_built_with_editor( $ct->ID ) ) {
			$used_ct_editor = $this->editor->get_name();
		}

		return $used_ct_editor;
	}

	/**
	 * Determines if the Content Template with ID equals to the given parameter is using the current editor.
	 *
	 * @param  int  $ct_id The ID of the investigated Content Template.
	 *
	 * @return bool True if the investigated Content Template uses the current editor, false otherwise.
	 */
	protected function maybe_ct_is_built_with_editor( $ct_id ) {
		$ct_user_editor_choice_meta = get_post_meta( $ct_id, '_toolset_user_editors_editor_choice', true );
		$editor_screen_id = $this->editor->get_id();

		return $ct_user_editor_choice_meta === $editor_screen_id;
	}

	/**
	 * Gets the "Edit with <editor>" link under each Content Template in the Content Templates listing page.
	 *
	 * @param int $template_id
	 *
	 * @return string
	 */
	public function get_ct_edit_with_editor_link( $template_id = 0 ) {
		return '';
	}

	/**
	 * Callback for the filter to get the "Edit with <editor>" link under each Content Template in the Content Templates listing page.
	 *
	 * @param string $edit_with_editor_output
	 * @param int    $template_id
	 *
	 * @return string
	 */
	public function maybe_get_ct_edit_with_editor_link( $edit_with_editor_output, $template_id ) {
		if ( $this->maybe_ct_is_built_with_editor( $template_id ) ) {
			$edit_with_editor_output = $this->get_ct_edit_with_editor_link( $template_id );
		}

		return $edit_with_editor_output;
	}
}
