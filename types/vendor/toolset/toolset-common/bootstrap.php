<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

use OTGS\Toolset\Common\Upgrade\UpgradeController;
use OTGS\Toolset\Common\Utils\RequestMode;
use OTGS\Toolset\Common\WpQueryExtension\WpQueryExtensionLoader;

/**
 * Toolset_Common_Bootstrap
 *
 * General class to manage common code loading for all Toolset plugins
 *
 * This class is used to load Toolset Common into all Toolset plugins that have it as a dependency.
 * Note that Assets, Menu, Utils, Settings, Localization, Promotion, Debug, Admin Bar and WPML compatibility are always
 * loaded when first instantiating this class. Toolset_Common_Bootstrap::load_sections must be called
 * on after_setup_theme:10 with an array of sections to load, named as follows:
 * 	toolset_forms						Toolset Forms, the shared component for Types and CRED
 * 	toolset_visual_editor				Visual Editor Addon, to display buttons and dialogs over editors
 * 	toolset_parser						Toolset Parser, to parse conditionals
 *
 * New sections can be added here, following the same structure.
 *
 *
 * Note that you have available the following constants:
 * 	TOOLSET_COMMON_VERSION				The Toolset Common version
 * 	TOOLSET_COMMON_PATH					The path to the active Toolset Common directory
 * 	TOOLSET_COMMON_DIR					The name of the directory of the active Toolset Common
 * 	TOOLSET_COMMON_URL					The URL to the root of Toolset Common, to be used in backend - adjusted as per SSL settings
 * 	TOOLSET_COMMON_FRONTEND_URL			The URL to the root of Toolset Common, to be used in frontend - adjusted as per SSL settings
 *
 * 	TOOLSET_COMMON_PROTOCOL				Deprecated - To be removed - The protocol of TOOLSET_COMMON_URL - http | https
 * 	TOOLSET_COMMON_FRONTEND_PROTOCOL	Deprecated - To be removed - The protocol of TOOLSET_COMMON_FRONTEND_URL - http | https
 */
class Toolset_Common_Bootstrap {

    private static $instance;
	private static $sections_loaded;

	public $assets_manager;
	public $object_relationship;
	public $menu;
	public $export_import_screen;
	public $settings_screen;
	public $localization;
	public $settings;
	public $promotion;
	public $wpml_compatibility;

	/**
	 * @var string|null One of the values from \OTGS\Toolset\Common\Utils\RequestMode
	 *     or null if not determined yet. Use $this->get_request_mode().
	 */
	private $request_mode;

	// Names of various sections/modules of the common library that can be loaded.
	const TOOLSET_AUTOLOADER = 'toolset_autoloader';
	const TOOLSET_API = 'toolset_api';
	const TOOLSET_DEBUG = 'toolset_debug';
	const TOOLSET_FORMS = 'toolset_forms';
	const TOOLSET_BLOCKS = 'toolset_blocks';
	const TOOLSET_PAGE_BUILDER_MODULES = 'toolset_page_builder_modules';
	const TOOLSET_VISUAL_EDITOR = 'toolset_visual_editor';
	const TOOLSET_PARSER = 'toolset_parser';
	const TOOLSET_USER_EDITOR = 'toolset_user_editor';
	const TOOLSET_SHORTCODE_GENERATOR = 'toolset_shortcode_generator';
	const TOOLSET_RESOURCES = 'toolset_res';
	const TOOLSET_LIBRARIES = 'toolset_lib';
	const TOOLSET_INCLUDES = 'toolset_inc';
	const TOOLSET_UTILS = 'toolset_utils';
	const TOOLSET_DIALOGS = 'toolset_dialogs';
	const TOOLSET_HELP_VIDEOS = 'toolset_help_videos';
	const TOOLSET_GUI_BASE = 'toolset_gui_base';
	const TOOLSET_RELATIONSHIPS = 'toolset_relationships';
	const TOOLSET_DIC = 'toolset_dic';


	/** @deprecated Use \OTGS\Toolset\Common\RequestMode::UNDEFINED */
	const MODE_UNDEFINED = '';
	/** @deprecated Use \OTGS\Toolset\Common\RequestMode::AJAX */
	const MODE_AJAX = 'ajax';
	/** @deprecated Use \OTGS\Toolset\Common\RequestMode::ADMIN */
	const MODE_ADMIN = 'admin';
	/** @deprecated Use \OTGS\Toolset\Common\RequestMode::FRONTEND */
	const MODE_FRONTEND = 'frontend';


	private function __construct() {
		self::$sections_loaded = array();

	    // Register assets, utils, settings, localization, promotion, debug, admin bar and WPML compatibility
		$this->register_utils();
		$this->register_res();
		$this->register_libs();
		$this->register_inc();

		add_filter( 'toolset_is_toolset_common_available', '__return_true' );

		add_action( 'switch_blog', array( $this, 'clear_settings_instance' ) );

        /**
         * Action when the Toolset Common Library is completely loaded.
		 *
		 * @param Toolset_Common_Bootstrap instance
         *
         * @since 2.3.0
         */
        do_action( 'toolset_common_loaded', $this );
    }


	/**
	 * @return Toolset_Common_Bootstrap
	 * @deprecated Use get_instance() instead.
	 */
	public static function getInstance() {
        return self::get_instance();
    }


	/**
	 * @return Toolset_Common_Bootstrap
	 * @since 2.1
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Toolset_Common_Bootstrap();
		}
		return self::$instance;
	}


	/**
	 * Determine if a given section is already loaded.
	 *
	 * @param string $section_name
	 * @return bool
	 * @since 2.1
	 */
	private function is_section_loaded( $section_name ) {
		return in_array( $section_name, self::$sections_loaded, true );
	}


	/**
	 * Add a section name to the list of the loaded ones.
	 *
	 * @param string $section_name
	 * @since 2.1
	 */
	private function add_section_loaded( $section_name ) {
		self::$sections_loaded[] = $section_name;
	}


	/**
	 * Decide whether a particular section needs to be loaded.
	 *
	 * @param string[] $sections_to_load Array of sections that should be loaded, or empty array to load all of them.
	 * @param string $section_name Name of a section.
	 * @return bool
	 * @since 2.1
	 */
	private function should_load_section( $sections_to_load, $section_name ) {
		return ( empty( $sections_to_load ) || in_array( $section_name, $sections_to_load ) );
	}


	/**
	 * Apply a filter on the array of names loaded sections.
	 *
	 * @param string $filter_name Name of the filter.
	 * @since 2.1
	 */
	private function apply_filters_on_sections_loaded( $filter_name ) {
		self::$sections_loaded = apply_filters( $filter_name, self::$sections_loaded );
	}


	/**
	 * Load sections on demand
	 *
	 * This needs to be called after after_setup_theme:10 because this file is not loaded before that
	 *
	 * @since 1.9
	 *
	 * @param string[] $load Names of sections to load or an empty array to load everything.
	 */
	public function load_sections( $load = array() ) {

		// Load toolset_debug on demand
		if ( $this->should_load_section( $load, self::TOOLSET_DEBUG ) ) {
			$this->register_debug();
		}

		// Maybe register forms
		if ( $this->should_load_section( $load, self::TOOLSET_FORMS ) ) {
			$this->register_toolset_forms();
		}

		// Maybe register Toolset blocks
		if ( $this->should_load_section( $load, self::TOOLSET_BLOCKS ) ) {
			$this->register_toolset_blocks();
		}

		// Maybe register the editor addon
		if ( $this->should_load_section( $load, self::TOOLSET_VISUAL_EDITOR ) ) {
			$this->register_visual_editor();
		}

		if ( $this->should_load_section( $load, self::TOOLSET_PARSER ) ) {
			$this->register_parser();
		}

		// Maybe register the editor addon
		if ( $this->should_load_section( $load, self::TOOLSET_USER_EDITOR ) ) {
			$this->register_user_editor();
		}

		// Maybe register the editor addon
		if ( $this->should_load_section( $load, self::TOOLSET_SHORTCODE_GENERATOR ) ) {
			$this->register_shortcode_generator();
		}

	}

	public function register_res() {

		if ( ! $this->is_section_loaded( self::TOOLSET_RESOURCES ) ) {
			$this->add_section_loaded( self::TOOLSET_RESOURCES );

			// Use the class provided by Ric
			require_once( TOOLSET_COMMON_PATH . '/inc/toolset.assets.manager.class.php' );
			$this->assets_manager = Toolset_Assets_Manager::get_instance();
			$this->apply_filters_on_sections_loaded( 'toolset_register_assets_section' );
		}
	}

	public function register_libs() {

		if ( ! $this->is_section_loaded( self::TOOLSET_LIBRARIES ) ) {

			$this->add_section_loaded( self::TOOLSET_LIBRARIES );

			if ( ! class_exists( 'ICL_Array2XML', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/array2xml.php' );
			}
			if ( ! class_exists( 'Zip', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/Zip.php' );
			}
			if ( ! function_exists( 'adodb_date' ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/adodb-time.inc.php' );
			}
			if ( ! class_exists( 'Toolset_CakePHP_Validation', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/cakephp.validation.class.php' );
			}
			if ( ! class_exists( 'Toolset_Validate', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/validate.class.php' );
			}
			if ( ! class_exists( 'Toolset_Enlimbo_Forms', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/lib/enlimbo.forms.class.php' );
			}

			$this->apply_filters_on_sections_loaded( 'toolset_register_library_section' );
		}
	}

	public function register_inc() {

		if ( ! $this->is_section_loaded( self::TOOLSET_INCLUDES ) ) {

			$this->add_section_loaded( self::TOOLSET_INCLUDES );

			$this->register_autoloaded_classes();

			// Load the dependency injection container.
			// This must happen directly after the autoloader initialization.
			if( ! $this->is_section_loaded( self::TOOLSET_DIC ) ) {
				require_once TOOLSET_COMMON_PATH . '/utility/dic.php';
				$this->add_section_loaded( self::TOOLSET_DIC );
			}

			$rest_controller = new \OTGS\Toolset\Common\Rest\Controller();
			$rest_controller->initialize();

			// Manually load the more sensitive code.
			if ( ! class_exists( 'Toolset_Settings', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.settings.class.php';
				$this->settings = Toolset_Settings::get_instance();
			}
			if ( ! class_exists( 'Toolset_Localization', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.localization.class.php';
				$this->localization = new Toolset_Localization();
			}
			if ( ! class_exists( 'Toolset_WPLogger', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.wplogger.class.php';
			}
			if ( ! class_exists( 'Toolset_Settings_Screen', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.settings.screen.class.php';
				$this->settings_screen = new Toolset_Settings_Screen();
			}
			if ( ! class_exists( 'Toolset_Export_Import_Screen', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.export.import.screen.class.php';
				$this->export_import_screen = new Toolset_Export_Import_Screen();
			}
			if ( ! class_exists( 'Toolset_Menu', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.menu.class.php';
				$this->menu = new Toolset_Menu();
			}
			if ( ! class_exists( 'Toolset_Promotion', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.promotion.class.php';
				$this->promotion = new Toolset_Promotion();
			}
			if ( ! class_exists( 'Toolset_Admin_Bar_Menu', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/inc/toolset.admin.bar.menu.class.php';
				/**
				 * @var Toolset_Admin_Bar_Menu $toolset_admin_bar_menu
				 * @deprecated Please use Toolset_Admin_Bar_Menu::get_instance() instead of this global variable.
				 * @noinspection PhpRedundantVariableDocTypeInspection
				 */
				global $toolset_admin_bar_menu;
				$toolset_admin_bar_menu = Toolset_Admin_Bar_Menu::get_instance();
			}
			if ( ! class_exists( 'Toolset_Internal_Compatibility', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/inc/toolset.internal.compatibility.class.php' );
				new Toolset_Internal_Compatibility();
			}

			\OTGS\Toolset\Common\WPML\WpmlService::initialize();

			if ( ! class_exists( 'Toolset_Relevanssi_Compatibility', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/inc/toolset.relevanssi.compatibility.class.php' );
				new Toolset_Relevanssi_Compatibility();
			}

			if ( ! class_exists( 'Toolset_CssComponent', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/inc/toolset.css.component.class.php' );
				$bootstrap_grid_button = new Toolset_CssComponent();
				$bootstrap_grid_button->initialize();
			}

			// Load Admin Notices Manager
			if( ! class_exists( 'Toolset_Admin_Notices_Manager', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/utility/admin/notices/manager.php' );
				Toolset_Admin_Notices_Manager::init();
			}

			// Load Admin Notices Controller (user of our Toolset_Admin_Notices_Manager)
            if( ! class_exists( 'Toolset_Controller_Admin_Notices', false ) ) {
				new Toolset_Controller_Admin_Notices();
            }

			if( ! class_exists( 'Toolset_Singleton_Factory', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/utility/singleton_factory.php';
			}

			require_once TOOLSET_COMMON_PATH . '/inc/toolset.compatibility.php';
			require_once TOOLSET_COMMON_PATH . '/inc/toolset.function.helpers.php';
			require_once TOOLSET_COMMON_PATH . '/deprecated.php';

			/** @var RequestMode $request_mode */
			$request_mode = $this->get_request_mode();

			// Register the AJAX controller with the autoloader so that it's always available, even if we're not
			// DOING_AJAX right now.
			$ajax_class_path = TOOLSET_COMMON_PATH . '/inc/toolset.ajax.class.php';
			$autoloader = Toolset_Common_Autoloader::get_instance();
			$autoloader->register_classmap( array( 'Toolset_Ajax' => $ajax_class_path ) );

			$this->register_relationships();

			$dic = toolset_dic();

			// Initialize a controller per request mode.
			switch( $request_mode ) {
				case RequestMode::ADMIN:
					/** @noinspection PhpUnhandledExceptionInspection */
					$admin_controller = $dic->make( \OTGS\Toolset\Common\AdminController::class );
					$admin_controller->initialize();
					break;
				case RequestMode::FRONTEND:
					$frontend_controller = new \OTGS\Toolset\Common\FrontendController();
					$frontend_controller->initialize();
					break;
				case RequestMode::AJAX:
					Toolset_Ajax::initialize();
					break;
			}

			require_once TOOLSET_COMMON_PATH . '/inc/public_api/loader.php';
			$public_api_loader = new Toolset_Public_API_Loader();
			$public_api_loader->initialize();

			$wp_query_extension = new WpQueryExtensionLoader();
			$wp_query_extension->initialize();

			$interop_mediator = new \OTGS\Toolset\Common\Interop\Mediator();
			$interop_mediator->initialize();

			require_once( TOOLSET_COMMON_PATH . '/inc/toolset.shortcode.transformer.class.php' );
			$shortcode_transformer = new Toolset_Shortcode_Transformer();
			$shortcode_transformer->init_hooks();

			// Passing Toolset_Shortcode_Transformer as a dependency.
			$basic_formatting = new \OTGS\Toolset\Common\BasicFormatting( $shortcode_transformer );
			$basic_formatting->initialize();

			// Make sure we check for upgrades after the Toolset Common library is fully loaded.
			$upgrade_controller = $dic->make(
				UpgradeController::class,
				[
					'command_definition_repository' => \OTGS\Toolset\Common\Upgrade\ToolsetCommonCommandDefinitionRepository::class,
					'version' => \OTGS\Toolset\Common\Upgrade\ToolsetCommonVersion::class,
				]
			);
			$upgrade_controller->initialize();

			/**
			 * Avoid the initialization of this class.
			 *
			 * @since m2m
			 */
			if ( ! apply_filters( 'toolset_disable_legacy_relationships_meta_access', false ) ) {
				$postmeta_access = new Toolset_Postmeta_Access_Loader();
				$postmeta_access->initialize();
			}

			$this->apply_filters_on_sections_loaded( 'toolset_register_include_section' );
		}
	}

	public function register_utils() {

		if( ! $this->is_section_loaded( self::TOOLSET_AUTOLOADER ) ) {
			// This needs to happen very very early
			require_once TOOLSET_COMMON_PATH . '/utility/autoloader.php';
			Toolset_Common_Autoloader::initialize();
			$this->add_section_loaded( self::TOOLSET_AUTOLOADER );

			/**
			 * Broadcast the news! The Toolset Common autoloader is available!
			 *
			 * @since 3.3.3
			 */
			do_action( 'toolset_common_autoloader_loaded' );
		}

		if ( ! $this->is_section_loaded( self::TOOLSET_UTILS ) ) {
			$this->add_section_loaded( self::TOOLSET_UTILS );
			require_once TOOLSET_COMMON_PATH . '/utility/utils.php';
		}

		if ( ! $this->is_section_loaded( self::TOOLSET_API ) ) {
			$this->add_section_loaded( self::TOOLSET_API );
			require_once TOOLSET_COMMON_PATH . '/api.php';
		}

		// Although this is full of DDL prefixes, we need to actually port before using it.
		if ( ! $this->is_section_loaded( self::TOOLSET_DIALOGS ) ) {
			$this->add_section_loaded( self::TOOLSET_DIALOGS );
			require_once TOOLSET_COMMON_PATH . '/utility/dialogs/toolset.dialog-boxes.class.php' ;
		}

        if( ! $this->is_section_loaded( self::TOOLSET_HELP_VIDEOS ) ) {
            $this->add_section_loaded( self::TOOLSET_HELP_VIDEOS );
            require_once TOOLSET_COMMON_PATH . '/utility/help-videos/toolset-help-videos.php';
        }

		$this->apply_filters_on_sections_loaded( 'toolset_register_utility_section' );
	}


	public function register_debug() {

		if ( ! $this->is_section_loaded( self::TOOLSET_DEBUG ) ) {
			$this->add_section_loaded( self::TOOLSET_DEBUG );

			require_once TOOLSET_COMMON_PATH . '/debug/troubleshooting-page.php';

			$this->apply_filters_on_sections_loaded( 'toolset_register_debug_section' );
		}
	}


	public function register_toolset_forms() {

		if ( ! $this->is_section_loaded( self::TOOLSET_FORMS ) ) {
			$this->add_section_loaded( self::TOOLSET_FORMS );
			if ( ! class_exists( 'WPToolset_Forms_Bootstrap', false ) ) {
				require_once TOOLSET_COMMON_PATH . '/toolset-forms/bootstrap.php';
			}

			// It is possible to regenerate the classmap with Zend framework.
			//
			// cd toolset-forms
			// /.../ZendFramework/bin/classmap_generator.php --overwrite
			$classmap = include( TOOLSET_COMMON_PATH . '/toolset-forms/autoload_classmap.php' );
			do_action( 'toolset_register_classmap', $classmap );

			$this->apply_filters_on_sections_loaded( 'toolset_register_forms_section' );
		}
	}

	public function register_toolset_blocks() {
		if ( ! $this->is_section_loaded( self::TOOLSET_BLOCKS ) ) {
			$this->add_section_loaded( self::TOOLSET_BLOCKS );
			$toolset_blocks = new Toolset_Blocks();
			$toolset_blocks->load_blocks();
			$this->apply_filters_on_sections_loaded( 'toolset_register_toolset_blocks_section' );
		}
	}

	public function register_visual_editor() {

		if ( ! $this->is_section_loaded( self::TOOLSET_VISUAL_EDITOR ) ) {
			$this->add_section_loaded( self::TOOLSET_VISUAL_EDITOR );
			require_once( TOOLSET_COMMON_PATH . '/visual-editor/editor-addon-generic.class.php' );
			require_once( TOOLSET_COMMON_PATH . '/visual-editor/editor-addon.class.php' );
			require_once( TOOLSET_COMMON_PATH . '/visual-editor/views-editor-addon.class.php' );
			$this->apply_filters_on_sections_loaded( 'toolset_register_visual_editor_section' );
		}
	}

	public function register_parser() {

		if ( ! $this->is_section_loaded( self::TOOLSET_PARSER ) ) {
			$this->add_section_loaded( self::TOOLSET_PARSER );
			if ( ! class_exists( 'Toolset_Regex', false ) ) {
				require_once( TOOLSET_COMMON_PATH . '/expression-parser/parser.php' );
			}
			$this->apply_filters_on_sections_loaded( 'toolset_register_parsers_section' );
		}
	}


	public function register_user_editor() {

		if ( ! $this->is_section_loaded( self::TOOLSET_USER_EDITOR ) ) {
			$this->add_section_loaded( self::TOOLSET_USER_EDITOR );
			require_once( TOOLSET_COMMON_PATH . '/user-editors/beta.php' );
			$this->apply_filters_on_sections_loaded( 'toolset_register_user_editor_section' );
		}
	}

	public function register_shortcode_generator() {

		if ( ! $this->is_section_loaded( self::TOOLSET_SHORTCODE_GENERATOR ) ) {
			$this->add_section_loaded( self::TOOLSET_SHORTCODE_GENERATOR );
			require_once( TOOLSET_COMMON_PATH . '/inc/toolset.shortcode.generator.class.php' );
			require_once( TOOLSET_COMMON_PATH . '/inc/toolset.shortcode.transformer.class.php' );
			$this->apply_filters_on_sections_loaded( 'toolset_register_shortcode_generator_section' );
		}
	}

	/**
	 * Include the "GUI base" module.
	 *
	 * That will give you Toolset_Gui_Base which you can initialize whenever convenient.
	 *
	 * @since 2.1
	 */
	public function register_gui_base() {
		if( $this->is_section_loaded( self::TOOLSET_GUI_BASE ) ) {
			return;
		}

		require_once TOOLSET_COMMON_PATH . '/utility/gui-base/main.php';
		$this->add_section_loaded( self::TOOLSET_GUI_BASE );
	}


	private function register_relationships() {

		if( $this->is_section_loaded( self::TOOLSET_RELATIONSHIPS ) ) {
			return;
		}

		require_once TOOLSET_COMMON_PATH . '/inc/m2m/MainController.php';
		$relationships_controller = \OTGS\Toolset\Common\Relationships\MainController::get_instance();
		$relationships_controller->initialize();

		// This obscure part of legacy relationship codebase should be loaded only when we really need it. Ewww.
		if ( ! $relationships_controller->is_m2m_enabled() && ! class_exists( 'Toolset_Object_Relationship', false ) ) {
			require_once TOOLSET_COMMON_PATH . '/inc/toolset.object.relationship.class.php';
			$this->object_relationship = Toolset_Object_Relationship::get_instance();
		}


		$this->add_section_loaded( self::TOOLSET_RELATIONSHIPS );
	}


	/**
	 * Add classes from the inc/autoloaded directory to the autoloader classmap.
	 *
	 * @since 2.3
	 */
	private function register_autoloaded_classes() {
		$autoload_classmap_file = TOOLSET_COMMON_PATH . '/autoload_classmap.php';

		if( ! is_file( $autoload_classmap_file ) ) {
			// abort if file does not exist
			return;
		}

		/** @noinspection PhpIncludeInspection */
		$autoload_classmap = include( $autoload_classmap_file );

		if( is_array( $autoload_classmap ) ) {
			// Register autoloaded classes.
			$autoloader = Toolset_Common_Autoloader::get_instance();
			$autoloader->register_classmap( $autoload_classmap );
		}
	}


	public function clear_settings_instance() {
		Toolset_Settings::clear_instance();
	}


	/**
	 * Get current request mode.
	 *
	 * Possible values are:
	 * - MODE_UNDEFINED before the main controller initialization is completed
	 * - MODE_AJAX when doing an AJAX request
	 * - MODE_ADMIN when showing a WP admin page
	 * - MODE_FRONTEND when rendering a frontend page
	 *
	 * @return string
	 * @since 2.3
	 * @deprecated Use \OTGS\Toolset\Common\RequestMode::get() instead.
	 */
	public function get_request_mode() {
		if ( null !== $this->request_mode ) {
			return $this->request_mode;
		}
		/** @var RequestMode $request_mode */
		$request_mode = toolset_dic_make( RequestMode::class );
		$this->request_mode = $request_mode->get();
		return $this->request_mode;
	}


	/**
	 * Perform a full initialization of the m2m API.
	 *
	 * @since m2m
	 */
	public function initialize_m2m() {
		OTGS\Toolset\Common\Relationships\MainController::get_instance()->initialize();
	}


}
