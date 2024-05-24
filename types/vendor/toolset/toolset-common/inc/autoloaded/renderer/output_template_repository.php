<?php

/**
 * Repository for templates in Toolset Common.
 *
 * See Toolset_Renderer for a detailed usage instructions.
 *
 * @since 2.5.9
 */
class Toolset_Output_Template_Repository extends Toolset_Output_Template_Repository_Abstract {

	// Names of the templates go here and to $templates
	//
	//

	const FAUX_TEMPLATE = 'faux_template.twig';
	const MAINTENANCE_FILE = 'maintenance.twig';
	const SETTINGS_SECTION_CODE_SNIPPETS = 'admin/settings_section/code_snippets/settings_tab.twig';
	const SETTINGS_SECTION_CODE_SNIPPETS_LEFT = 'admin/settings_section/code_snippets/settings_tab_left.twig';
	const SETTINGS_SECTION_CODE_SNIPPETS_ADD_NEW_DIALOG = 'admin/settings_section/code_snippets/add_new_dialog.twig';
	const SETTINGS_SECTION_CODE_SNIPPETS_DELETE_DIALOG = 'admin/settings_section/code_snippets/delete_dialog.twig';

	// Toolset_Shortcode_Generator templates

	const SHORTCODE_GUI_DIALOG = 'shortcodes_gui/dialog.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_GROUP_WRAPPER = 'shortcodes_gui/wrapper_attribute_group.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_WRAPPER = 'shortcodes_gui/wrapper_attribute.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_INFORMATION = 'shortcodes_gui/attribute_information.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_TEXT = 'shortcodes_gui/attribute_text.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_NUMBER = 'shortcodes_gui/attribute_number.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_TEXTAREA = 'shortcodes_gui/attribute_textarea.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_RADIO = 'shortcodes_gui/attribute_radio.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_SELECT = 'shortcodes_gui/attribute_select.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_SELECT2 = 'shortcodes_gui/attribute_select2.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_AJAXSELECT2 = 'shortcodes_gui/attribute_ajaxselect2.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_SKYPE = 'shortcodes_gui/attribute_skype.phtml';
	const SHORTCODE_GUI_ATTRIBUTE_CALLBACK = 'shortcodes_gui/attribute_callback.phtml';
	const SHORTCODE_GUI_CONTENT = 'shortcodes_gui/content.phtml';
	const SHORTCODE_GUI_POST_SELECTOR_LEGACY = 'shortcodes_gui/post_selector/legacy.phtml';
	const SHORTCODE_GUI_POST_SELECTOR_M2M_WIZARD = 'shortcodes_gui/post_selector/m2m_wizard.phtml';
	const SHORTCODE_GUI_POST_SELECTOR_M2M_POST_REFERENCE = 'shortcodes_gui/post_selector/m2m_post_reference.phtml';
	const SHORTCODE_GUI_POST_SELECTOR_M2M_RFG = 'shortcodes_gui/post_selector/m2m_repeating_field_group.phtml';

	// Toolset User Editors
	const USER_EDITORS_INLINE_EDITOR_OVERLAY = '/admin/user-editors/inline-editor-overlay.phtml';
	const USER_EDITORS_INLINE_EDITOR_SAVING_OVERLAY = '/admin/user-editors/inline-editor-saving-overlay.phtml';
	const USER_EDITORS_INLINE_EDITOR_ACTION_BUTTON = '/admin/user-editors/inline-editor-action-button.phtml';
	const USER_EDITORS_CONTENT_TEMPLATE_EDITOR_OVERLAY = '/admin/user-editors/content-template-editor-overlay.phtml';
	const USER_EDITORS_MY_DIALOG = '/admin/user-editors/unsaved-content-template-dialog.phtml';

	//Toolset Page Builder Modules Templates.
	const PAGE_BUILDER_MODULES_OVERLAY = '/admin/page-builder-modules/module-overlay.phtml';
	const PAGE_BUILDER_MODULES_ELEMENTOR_NOTHING_SELECTED = '/admin/page-builder-modules/elementor/widgets/nothing-selected.phtml';
	const PAGE_BUILDER_MODULES_ELEMENTOR_UPGRADE_TO_PRO_FOR_SELECT2 = '/admin/page-builder-modules/elementor/widgets/upgrade-to-pro-for-select2.phtml';
	const PAGE_BUILDER_MODULES_ELEMENTOR_USE_VIEW_ELEMENTOR_WIDGET_INSTEAD = 'admin/page-builder-modules/elementor/widgets/use-view-elementor-widget-instead.phtml';


	/**
	 * @var array Template definitions.
	 */
	private $templates = array();


	/** @var Toolset_Output_Template_Repository */
	private static $instance;


	/**
	 * @return Toolset_Output_Template_Repository
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function __construct(
		Toolset_Output_Template_Factory $template_factory_di = null,
		Toolset_Constants $constants_di = null
	) {
		parent::__construct( $template_factory_di, $constants_di );

		$templates_base_path = $this->get_templates_dir_base_path();

		$this->templates = array(
			self::FAUX_TEMPLATE => array(
				'base_path' => null,
				'namespaces' => array()
			),
			self::MAINTENANCE_FILE => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_DIALOG => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_GROUP_WRAPPER => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_WRAPPER => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_INFORMATION => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_TEXT => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_NUMBER => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_TEXTAREA => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_RADIO => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_SELECT => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_SELECT2 => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_AJAXSELECT2 => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_SKYPE => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_ATTRIBUTE_CALLBACK => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_CONTENT => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_POST_SELECTOR_LEGACY => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_POST_SELECTOR_M2M_WIZARD => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_POST_SELECTOR_M2M_POST_REFERENCE => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			self::SHORTCODE_GUI_POST_SELECTOR_M2M_RFG => array(
				'base_path' => $this->get_templates_dir_base_path()
			),
			// User Editors
			self::USER_EDITORS_INLINE_EDITOR_OVERLAY => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::USER_EDITORS_INLINE_EDITOR_SAVING_OVERLAY => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::USER_EDITORS_INLINE_EDITOR_ACTION_BUTTON => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::USER_EDITORS_CONTENT_TEMPLATE_EDITOR_OVERLAY => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::USER_EDITORS_MY_DIALOG => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			// Toolset Page Builder Modules
			self::PAGE_BUILDER_MODULES_ELEMENTOR_NOTHING_SELECTED => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::PAGE_BUILDER_MODULES_OVERLAY => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),
			self::PAGE_BUILDER_MODULES_ELEMENTOR_UPGRADE_TO_PRO_FOR_SELECT2 => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),

			self::PAGE_BUILDER_MODULES_ELEMENTOR_USE_VIEW_ELEMENTOR_WIDGET_INSTEAD => array(
				'base_path' => $this->get_templates_dir_base_path(),
				'namespaces' => array(),
			),

			self::SETTINGS_SECTION_CODE_SNIPPETS => array( 'base_path' => $templates_base_path ),
			self::SETTINGS_SECTION_CODE_SNIPPETS_LEFT => array( 'base_path' => $templates_base_path ),
			self::SETTINGS_SECTION_CODE_SNIPPETS_ADD_NEW_DIALOG => array( 'base_path' => $templates_base_path ),
			self::SETTINGS_SECTION_CODE_SNIPPETS_DELETE_DIALOG => array( 'base_path' => $templates_base_path ),
		);
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_default_base_path() {
		return $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/utility/gui-base/twig-templates';
	}


	private function get_templates_dir_base_path() {
		return $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/templates';
	}



	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	protected function get_templates() {
		return $this->templates;
	}
}
