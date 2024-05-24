<?php

use OTGS\Toolset\Types\Controller\Page\DatabaseMigration\DatabaseUpgradePage;
use OTGS\Toolset\Types\Controller\Page\PageControllerInterface;
use OTGS\Toolset\Types\Post\Import\Association\View\Page;

/**
 * Admin menu controller for Types.
 *
 * All Types pages, menus, submenus and whatnot need to be registered here. One of the main goals is to avoid
 * loading specific page controllers unless their page is actually being loaded. All page slugs in Types *must*
 * be defined here as constants PAGE_NAME_*.
 *
 * For adding a new persistent page (that displays in the menu at all times), go to get_persistent_pages().
 * For pages that display only under certain circumstances, check out maybe_add_ondemand_submenu().
 *
 * @since 2.0
 */
class Types_Admin_Menu {

	/** Temporary slug compatible with the legacy code. */
	const MENU_SLUG = 'wpcf';


	// All (non-legacy) page slugs.
	const PAGE_NAME_FIELD_CONTROL = 'types-field-control';

	const PAGE_NAME_HELPER = 'types-helper'; // hidden page

	const PAGE_NAME_DASHBOARD = Types_Page_Dashboard::PAGE_SLUG;

	const PAGE_NAME_RELATIONSHIPS = 'types-relationships';

	const PAGE_NAME_FIELD_GROUP_EDIT_POST = 'wpcf-edit';

	const PAGE_NAME_FIELD_GROUP_EDIT_USER = 'wpcf-edit-usermeta';

	const PAGE_NAME_FIELD_GROUP_EDIT_TERM = 'wpcf-termmeta-edit';

	const PAGE_NAME_CUSTOM_FIELDS = 'types-custom-fields'; // Custom fields page name

	const PAGE_NAME_DATABASE_UPGRADE = 'types-database-upgrade';

	// Legacy page slugs
	const LEGACY_PAGE_CUSTOM_POST_FIELDS = 'wpcf-cf';

	const LEGACY_PAGE_CUSTOM_USER_FIELDS = 'wpcf-um';

	const LEGACY_PAGE_CUSTOM_TERM_FIELDS = 'wpcf-termmeta-listing';

	const LEGACY_PAGE_EDIT_POST_FIELD_GROUP = 'wpcf-edit';

	const LEGACY_PAGE_EDIT_POST_TYPE = 'wpcf-edit-type';


	/**
	 * Legacy pages.
	 *
	 * Contains a list of new pages and its legacy pages.
	 * Used for Custom Fields.
	 *
	 * array[                        List of actual pages that had different names.
	 *        legacy => [                Legacy page name.
	 *            actual,                Actual page name.
	 *            array[params]        Array of extra params key=>value
	 *        ]
	 * ]
	 *
	 * @since 2.3
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/types-890
	 * @see redirect_legacy_pages
	 * @var array
	 */
	private $legacy_pages = array(
		self::LEGACY_PAGE_CUSTOM_POST_FIELDS => array(
			self::PAGE_NAME_CUSTOM_FIELDS,
			array( 'domain' => 'posts' ),
		),
		self::LEGACY_PAGE_CUSTOM_USER_FIELDS => array(
			self::PAGE_NAME_CUSTOM_FIELDS,
			array( 'domain' => 'users' ),
		),
		self::LEGACY_PAGE_CUSTOM_TERM_FIELDS => array(
			self::PAGE_NAME_CUSTOM_FIELDS,
			array( 'domain' => 'terms' ),
		),
	);

	private static $instance;


	/** @var Types_Page_Router */
	private $page_router;

	/** @var array Cache for get_persistent_pages() */
	private $persistent_pages;


	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self( new Types_Page_Router() );
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * Types_Admin_Menu constructor.
	 *
	 * @param Types_Page_Router $page_router_di
	 *
	 * @since m2m
	 */
	public function __construct( Types_Page_Router $page_router_di ) {
		$this->page_router = $page_router_di;

		$this->add_hooks();

		// Load Dashboard
		Types_Page_Dashboard::get_instance();

		// Load Associations Import Page (will be placed on Toolset Import/Export)
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			add_action( 'load-toolset_page_toolset-export-import', static function () {
				new Page(
					new Types_Helper_Twig()
				);
			} );
		}
	}


	public function add_hooks() {

		// Priority is hardcoded by filter documentation.
		add_filter( 'toolset_filter_register_menu_pages', array( $this, 'on_admin_menu' ), 10 );

		// Redirects legacy pages.
		add_action( 'admin_page_access_denied', array( $this, 'redirect_legacy_pages' ) );
	}


	/**
	 * Add all Types submenus and jumpstart a specific page controller if needed.
	 *
	 * Toolset shared menu usage is described here:
	 *
	 * @link https://git.onthegosystems.com/toolset/toolset-common/wikis/toolset-shared-menu
	 *
	 * @param array $pages Array of menu item definitions.
	 *
	 * @return array Updated item definition array.
	 * @since 2.0
	 */
	public function on_admin_menu( $pages ) {
		$pages = wpcf_admin_toolset_register_menu_pages( $pages );

		// Unset some legacy pages because they are handled with Types_Admin_Menu.
		$pages = $this->remove_some_legacy_pages( $pages );

		// For non-legacy pages, also add routes
		$persistent_pages = $this->get_persistent_pages();
		$this->add_routes( $persistent_pages );

		$pages = array_merge( $pages, $persistent_pages );

		$page_name = sanitize_text_field( toolset_getget( 'page' ) );
		if ( ! empty( $page_name ) ) {
			$pages = $this->maybe_add_ondemand_submenu( $pages, $page_name );
		}

		return $pages;
	}


	/**
	 * Register routes for all pages with the router.
	 *
	 * @param array[] $pages
	 */
	private function add_routes( $pages ) {

		foreach ( $pages as $page ) {

			$controller_class_to_instantiate = toolset_getarr( $page, 'controller_class_name', null );

			// We can assume this because all page controllers inherit from Types_Page_Persistent
			$attributes = array(
				'title' => toolset_getarr( $page, 'menu_title' ),

				// Some times menu title and page title are different.
				'menu_title' => toolset_getarr( $page, 'menu_title' ),
				'page_title' => toolset_getarr( $page, 'page_title' ),
				'page_name' => toolset_getarr( $page, 'slug' ),
				'required_capability' => toolset_getarr( $page, 'capability', 'manage_options' ),
			);

			// add route for page preparation and for rendering
			$page_controller_factory = toolset_getarr( $page, 'factory', 'Types_Page_Factory_Passthrough' );

			// extract method name from the callable
			$load_callback_name = $this->get_callback_name( toolset_getarr( $page, 'load_hook' ) );
			$this->page_router->add_route( $load_callback_name, $controller_class_to_instantiate, 'prepare', $attributes, $page_controller_factory );

			$render_callback_name = $this->get_callback_name( toolset_getarr( $page, 'callback' ) );
			$this->page_router->add_route( $render_callback_name, $controller_class_to_instantiate, 'render_page', $attributes, $page_controller_factory );
		}
	}


	/**
	 * Get the name of the callback method from a callable.
	 *
	 * @param callable $callable A callable in the form of array( $page_router, 'callback_name' ).
	 *
	 * @return string The callback name.
	 * @throws InvalidArgumentException
	 */
	private function get_callback_name( $callable ) {
		if ( is_array( $callable ) && count( $callable ) === 2 && is_string( $callable[1] ) ) {
			return $callable[1];
		}

		throw new InvalidArgumentException( 'Invalid admin menu route callback' );
	}


	/**
	 * Check if an on-demand submenu should be added, and jumpstart it's controller if needed.
	 *
	 * On-demand submenu means that the submenu isn't displayed normally, it appears only when its page is loaded.
	 *
	 * Note: All page controllers should inherit from Types_Page_Abstract.
	 *
	 * @param array $pages Array of menu item definitions.
	 * @param string $page_name
	 *
	 * @return array Updated item definition array.
	 * @since 2.0
	 * @noinspection PhpDocMissingThrowsInspection
	 */
	private function maybe_add_ondemand_submenu( $pages, $page_name ) {
		$page = null;
		$dic = toolset_dic();
		switch ( $page_name ) {
			case self::PAGE_NAME_FIELD_CONTROL:
				$page = Types_Page_Field_Control::get_instance();
				break;
			case self::PAGE_NAME_HELPER:
				Types_Page_Hidden_Helper::get_instance();
				break;
			case self::PAGE_NAME_FIELD_GROUP_EDIT_POST:
			case self::PAGE_NAME_FIELD_GROUP_EDIT_USER:
			case self::PAGE_NAME_FIELD_GROUP_EDIT_TERM:
				new Types_Page_Field_Group_Edit();
				break;
			case self::PAGE_NAME_DATABASE_UPGRADE:
				/** @noinspection PhpUnhandledExceptionInspection */
				$page = $dic->make( DatabaseUpgradePage::class );
				break;
		}

		if ( $page instanceof PageControllerInterface ) {
			// Jumpstart the page controller.
			try {
				$page->prepare();
			} catch ( Exception $e ) {
				wp_die( $e->getMessage() );
			}

			$pages[ $page_name ] = array(
				'slug' => $page_name,
				'menu_title' => $page->get_title(),
				'page_title' => $page->get_title(),
				'callback' => $page->get_render_callback(),
				'load_hook' => $page->get_load_callback(),
				'required_capability' => $page->get_required_capability(),
				'contextual_help_hook' => array( Types_Asset_Help_Tab_Loader::get_instance(), 'add_help_tab' ),
			);

			// todo we might need to handle adding URL parameters to submenu URLs in some standard way, it's common scenario for ondemand submenus
		}

		return $pages;
	}


	/**
	 * Get arguments for registering persistent admin pages.
	 *
	 * Each element is an array of arguments for a page that will appear in Toolset submenu at all times.
	 *
	 * The arguments are described here:
	 *
	 * @link https://git.onthegosystems.com/toolset/toolset-common/wikis/toolset-shared-menu
	 *
	 * Besides that, the following is assumed:
	 *
	 * - element key is a page identifier that can be used as a part of the function name,
	 * - there's a 'controller_class_name' argument whose value is the name of the page controller,
	 * - all page controllers inherit from Types_Page_Persistent,
	 * - callbacks are generated via get_callback() using the correct page identifier.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_persistent_pages() {

		if ( null === $this->persistent_pages ) {
			$persistent_pages = array(

				self::PAGE_NAME_CUSTOM_FIELDS => array(
					'slug' => self::PAGE_NAME_CUSTOM_FIELDS,
					'menu_title' => __( 'Custom Fields', 'wpcf' ),
					'page_title' => __( 'Custom Fields Group', 'wpcf' ),
					'load_hook' => $this->get_callback( 'custom_fields', 'load' ),
					'callback' => $this->get_callback( 'custom_fields', 'render' ),
					'required_capability' => 'manage_options',
					'controller_class_name' => 'Types_Page_Custom_Fields',
					'contextual_help_hook' => array(
						Types_Asset_Help_Tab_Loader::get_instance(),
						'add_multiple_help_tabs',
					),
				),

				self::PAGE_NAME_RELATIONSHIPS => array(
					'slug' => self::PAGE_NAME_RELATIONSHIPS,
					'menu_title' => __( 'Relationships', 'wpcf' ),
					'page_title' => __( 'Relationships', 'wpcf' ),
					'load_hook' => $this->get_callback( 'relationships', 'load' ),
					'callback' => $this->get_callback( 'relationships', 'render' ),
					'capability' => 'manage_options',
					'controller_class_name' => 'Types_Page_Relationships',
					'factory' => 'Types_Page_Factory_Relationships',
				),

			);

			$this->persistent_pages = $persistent_pages;
		}

		return $this->persistent_pages;
	}


	/**
	 * Generate a callback for a page.
	 *
	 * @param string $page_identifier Page identifier from get_persistent_pages().
	 * @param string $action self::LOAD_CALL|self::RENDER_CALL
	 *
	 * @return callable
	 * @since m2m
	 */
	private function get_callback( $page_identifier, $action ) {
		return array( $this->page_router, $action . '_' . $page_identifier );
	}


	/**
	 * Redirect legacy pages
	 *
	 * Custom Fields groups three legacy pages: custom posts, users, terms.
	 * This action will redirect theses pages to the new one.
	 *
	 * @since 2.3
	 */
	public function redirect_legacy_pages() {
		$page_name = sanitize_text_field( toolset_getget( 'page' ) );
		if ( $page_name ) {
			foreach ( $this->legacy_pages as $legacy => $actual ) {
				// $actual contains [0] => actual page, [1] => extra params.
				if ( $page_name === $legacy ) {
					wp_redirect( esc_url_raw( add_query_arg(
							array( 'page' => $actual[0] ) + $actual[1],
							admin_url( 'admin.php' )
					) ) );
					exit();
				}
			}
		}
	}


	/**
	 * Remove some legacy pages.
	 *
	 * Because some pages are declared in /vendor and Types is being refactoring,
	 * it is necessary to remove this legacy pages from the initial setup.
	 *
	 * @param array $pages Inicial pages declared in /vendor.
	 *
	 * @return array
	 * @since 2.3
	 * @see wpcf_admin_toolset_register_menu_pages
	 */
	private function remove_some_legacy_pages( $pages ) {
		foreach ( array_keys( $this->legacy_pages ) as $legacy ) {
			unset( $pages[ $legacy ] );
		}

		return $pages;
	}


	/**
	 * Central point to safely obtain URLs of Toolset pages.
	 *
	 * Note: Doesn't work for most legacy pages. If you need it, you can adjust the code below,
	 * but in any case, this method should be used.
	 *
	 * @param string $page_slug Page slug (usually a constant from Types_Admin_Menu)
	 *
	 * @return null|string URL of the page or null if the page isn't recognized.
	 */
	public function get_page_url( $page_slug ) {
		$persistent_pages = $this->get_persistent_pages();

		if ( ! array_key_exists( $page_slug, $persistent_pages ) ) {
			// Check a set of legacy pages
			$legacy_pages = array(
				self::LEGACY_PAGE_EDIT_POST_FIELD_GROUP,
				self::LEGACY_PAGE_EDIT_POST_TYPE,
			);
			if ( ! in_array( $page_slug, $legacy_pages ) ) {
				return null;
			}
		}

		return add_query_arg(
			array( 'page' => $page_slug ),
			admin_url( 'admin.php' )
		);
	}

}
