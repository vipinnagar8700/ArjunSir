<?php

namespace OTGS\Toolset\Common\CodeSnippets;

use OTGS\Toolset\Common\GuiBase\DialogBoxFactory;
use OTGS\Toolset\Common\Interop\Handler\CodeSnippets;
use OTGS\Toolset\Common\Utils\RequestMode;
use Toolset_Ajax;
use Toolset_Assets_Manager;
use Toolset_Constants;
use Toolset_Gui_Base;
use Toolset_Output_Template_Repository;
use Toolset_Renderer;
use function get_current_screen;
use function get_current_user_id;
use function get_user_meta;


/**
 * Controller for the Customizations tab in Toolset Settings, which displays a snippet listing.
 *
 * @since 3.0.8
 */
class SettingsTab {

	// Assets
	const MAIN_ASSET_HANDLE = 'toolset-code-snippet-setting-section';

	const ADD_NEW_DIALOG_HANDLE = 'toolset-code-snippet-setting-section-add-new-dialog';

	const DELETE_DIALOG_HANDLE = 'toolset-code-snippet-setting-section-delete-dialog';

	const DEFAULT_ITEMS_PER_PAGE = 20;

	const ITEMS_PER_PAGE_OPTION = 'toolset_settings_items_per_page';


	/** @var Toolset_Renderer */
	private $renderer;

	/** @var Toolset_Output_Template_Repository */
	private $template_repository;

	/** @var Toolset_Constants */
	private $constants;

	/** @var Repository */
	private $snippet_repository;

	/** @var SnippetViewModelFactory */
	private $snippet_viewmodel_factory;

	/** @var Toolset_Ajax */
	private $ajax_manager;

	/** @var Explorer */
	private $snippet_explorer;

	/** @var DialogBoxFactory */
	private $dialog_box_factory;


	/**
	 * SettingsTab constructor.
	 *
	 * @param Toolset_Renderer $renderer
	 * @param Toolset_Output_Template_Repository $template_repository
	 * @param Toolset_Constants $constants
	 * @param Repository $snippet_repository
	 * @param SnippetViewModelFactory $snippet_view_model_factory
	 * @param Toolset_Ajax $ajax_manager
	 * @param Explorer $snippet_explorer
	 * @param DialogBoxFactory $dialog_box_factory
	 */
	public function __construct(
		Toolset_Renderer $renderer,
		Toolset_Output_Template_Repository $template_repository,
		Toolset_Constants $constants,
		Repository $snippet_repository,
		SnippetViewModelFactory $snippet_view_model_factory,
		Toolset_Ajax $ajax_manager,
		Explorer $snippet_explorer,
		DialogBoxFactory $dialog_box_factory
	) {
		$this->renderer = $renderer;
		$this->template_repository = $template_repository;
		$this->constants = $constants;
		$this->snippet_viewmodel_factory = $snippet_view_model_factory;
		$this->snippet_repository = $snippet_repository;
		$this->ajax_manager = $ajax_manager;
		$this->snippet_explorer = $snippet_explorer;
		$this->dialog_box_factory = $dialog_box_factory;
	}


	/**
	 * Renders the main content of the setting tab (listing & co.) and returns the output.
	 *
	 * @return string
	 */
	public function render_main_content() {
		return $this->renderer->render(
			$this->template_repository->get( Toolset_Output_Template_Repository::SETTINGS_SECTION_CODE_SNIPPETS ),
			$this->build_twig_context(),
			false
		);
	}


	/**
	 * Renders the "Add new" button that should be displayed as a part of the settings section title.
	 *
	 * @return string
	 */
	public function render_add_new_button() {
		return '<a class="add-new-h2" data-bind="click: onAddNew">' . __( 'Add new', 'wpv-views' ) . '</a>';
	}


	/**
	 * Renders the left side of the settings section which should be displayed below the section title.
	 *
	 * @return string
	 */
	public function render_left_side() {
		return $this->renderer->render(
			$this->template_repository->get( Toolset_Output_Template_Repository::SETTINGS_SECTION_CODE_SNIPPETS_LEFT ),
			array(
				'is_test_mode_enabled' => (
					$this->constants->defined( 'TOOLSET_CODE_SNIPPETS_TEST_MODE' )
					&& $this->constants->constant( 'TOOLSET_CODE_SNIPPETS_TEST_MODE' )
				),
				'test_mode_constant' => 'TOOLSET_CODE_SNIPPETS_TEST_MODE',
				'rescue_mode_parameter' => CodeSnippets::RESCUE_MODE_PARAMETER,
				'site_url' => get_bloginfo( 'url' ),
				'disable_snippets_constant' => 'TOOLSET_DISABLE_CODE_SNIPPETS',
				'disable_gui_constant' => 'TOOLSET_DISABLE_CODE_SNIPPETS_GUI',
				'base_dir' => $this->snippet_explorer->get_base_directory(),
			),
			false
		);
	}


	/**
	 * Initialize the controller. Needs to be called early enough before loading the settings page.
	 */
	public function initialize() {
		add_action( 'load-toolset_page_toolset-settings', [ $this, 'add_screen_option' ] );

		// Keep in mind that we don't know what page we're loading yet at this point.
		add_action( 'load-toolset_page_toolset-settings', function() {
			add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		} );

		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );
	}


	public function add_screen_option() {
		add_screen_option( 'per_page', array(
			'label' => __( 'Items', 'wpv-views' ),
			'default' => self::DEFAULT_ITEMS_PER_PAGE,
			'option' => self::ITEMS_PER_PAGE_OPTION,
		) );
	}


	public function set_screen_option( $status, $option, $value ) {
		if ( self::ITEMS_PER_PAGE_OPTION === $option ) {
			return $value;
		}

		return $status;
	}


	/**
	 * Enqueue assets necessary for the listing.
	 */
	public function on_admin_enqueue_scripts() {
		wp_enqueue_script(
			self::MAIN_ASSET_HANDLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/inc/autoloaded/code_snippets/assets/main.js',
			array(
				Toolset_Assets_Manager::SCRIPT_HEADJS,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_ADVANCED_ITEM_VIEWMODEL,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_CODEMIRROR,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_PHP,
			),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		wp_enqueue_style(
			self::MAIN_ASSET_HANDLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/inc/autoloaded/code_snippets/assets/style.css',
			array(
				Toolset_Gui_Base::STYLE_GUI_BASE,
				Toolset_Assets_Manager::STYLE_TOOLSET_COMMON,
				Toolset_Assets_Manager::STYLE_CODEMIRROR,
				Toolset_Assets_Manager::STYLE_DDL_DIALOGS,
				//\Toolset_Assets_Manager::STYLE_EDITOR_ADDON_MENU,
				Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES,
			),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		$add_new_dialog = $this->dialog_box_factory->createTemplateDialogBox(
			self::ADD_NEW_DIALOG_HANDLE,
			$this->template_repository->get( Toolset_Output_Template_Repository::SETTINGS_SECTION_CODE_SNIPPETS_ADD_NEW_DIALOG )
		);
		$add_new_dialog->initialize();

		$delete_dialog = $this->dialog_box_factory->createTemplateDialogBox(
			self::DELETE_DIALOG_HANDLE,
			$this->template_repository->get( Toolset_Output_Template_Repository::SETTINGS_SECTION_CODE_SNIPPETS_DELETE_DIALOG )
		);
		$delete_dialog->initialize();
	}


	/**
	 * Context array to be passed to Twig when rendering the main content.
	 *
	 * @return array
	 */
	private function build_twig_context() {
		// Basics for the listing page which  we'll merge with specific data later on.
		$base_context = $this->renderer->get_twig_context_base(
			Toolset_Gui_Base::TEMPLATE_LISTING, $this->build_js_data()
		);

		$specific_context = array(
			'id_main_wrapper' => 'toolset_codesnippets_listing',
			'id_model_data' => 'toolset_codesnippets_model_data',
			'strings' => array(
				'site_url' => get_bloginfo( 'url' ),
				'ondemand_run_trigger' => CodeSnippets::ON_DEMAND_RUN_TRIGGER,
			),
		);

		return toolset_array_merge_recursive_distinct( $base_context, $specific_context );
	}


	/**
	 * Context array to be passed to the JavaScript page controller.
	 *
	 * @return array
	 */
	private function build_js_data() {
		return array(
			'jsIncludePath' => TOOLSET_COMMON_URL . '/inc/autoloaded/code_snippets/assets',
			'toolsetCommonVersion' => TOOLSET_COMMON_VERSION,
			'itemsPerPage' => $this->get_items_per_page(),
			'nonce' => wp_create_nonce( $this->ajax_manager->get_action_js_name( Toolset_Ajax::CALLBACK_CODE_SNIPPETS_ACTION ) ),
			'strings' => array(
				'active' => __( 'Active', 'wpv-views' ),
				'inactive' => __( 'Inactive', 'wpv-views' ),
				'bulkAction' => array(
					'select' => __( 'Select', 'wpv-views' ),
					'activate' => __( 'Activate', 'wpv-views' ),
					'deactivate' => __( 'Deactivate', 'wpv-views' ),
				),
				'updateResults' => array(
					'error' => __( 'There was an error when updating snippets.', 'wpv-views' ),
				),
				'dialogs' => array(
					'addNewDialog' => array(
						'handle' => self::ADD_NEW_DIALOG_HANDLE,
						'title' => __( 'Create a new code snippet', 'wpv-views' ),
						'cancel' => __( 'Cancel', 'wpv-views' ),
						'create' => __( 'Create', 'wpv-views' ),
					),
					'deleteDialog' => array(
						'handle' => self::DELETE_DIALOG_HANDLE,
						'title' => __( 'Delete a code snippet', 'wpv-views' ),
						'cancel' => __( 'Cancel', 'wpv-views' ),
						'delete' => __( 'Delete', 'wpv-views' ),
					),
				),
				'runMode' => array(
					SnippetOption::RUN_ONCE => __( 'Run once', 'wpv-views' ),
					SnippetOption::RUN_ON_DEMAND => __( 'On demand', 'wpv-views' ),
					SnippetOption::RUN_ALWAYS => __( 'Always', 'wpv-views' ),
				),
				'runContexts' => array(
					RequestMode::ADMIN => __( 'WordPress admin', 'wpv-views' ),
					RequestMode::AJAX => __( 'AJAX calls', 'wpv-views' ),
					RequestMode::FRONTEND => __( 'Front-end', 'wpv-views' ),
					'everywhere' => __( 'Everywhere', 'wpv-views' ),
					'nowhere' => __( 'Nowhere', 'wpv-views' ),
				),
			),
			'snippets' => $this->build_snippet_data(),
		);
	}


	private function get_items_per_page() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return self::DEFAULT_ITEMS_PER_PAGE;
		}
		$option_name = $screen->get_option( 'per_page', 'option' );
		$per_page = (int) get_user_meta( get_current_user_id(), $option_name, true );

		if ( $per_page < 1 ) {
			// Something went wrong
			$per_page = self::DEFAULT_ITEMS_PER_PAGE;
		}

		return $per_page;
	}


	private function build_snippet_data() {
		$snippet_viewmodel_factory = $this->snippet_viewmodel_factory;

		return array_map( static function ( Snippet $snippet ) use ( $snippet_viewmodel_factory ) {
			return $snippet_viewmodel_factory->create( $snippet )->to_array();
		}, $this->snippet_repository->load_all() );
	}
}
