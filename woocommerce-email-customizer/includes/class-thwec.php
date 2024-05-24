<?php
/**
 * The file that defines the core plugin class.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    plugin-name
 * @subpackage plugin-name/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC')):

class THWEC {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @access   protected
	 * @var      $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {
		if ( defined( 'THWEC_VERSION' ) ) {
			$this->version = THWEC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-email-customizer-pro';
		
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		
		$this->loader->add_action( 'init', $this, 'init' );
	}
	
	public function init(){
		$this->define_constants();
		//$this->init_auto_updater();
	}
	
	private function define_constants(){
		!defined('THWEC_ASSETS_URL_ADMIN') && define('THWEC_ASSETS_URL_ADMIN', THWEC_URL . 'admin/assets/');
		!defined('THWEC_ASSETS_URL_PUBLIC') && define('THWEC_ASSETS_URL_PUBLIC', THWEC_URL . 'public/assets/');
		!defined('THWEC_WOO_ASSETS_URL') && define('THWEC_WOO_ASSETS_URL', WC()->plugin_url() . '/assets/');
		!defined('THWEC_CUSTOM_TEMPLATE_PATH') && define('THWEC_CUSTOM_TEMPLATE_PATH', THWEC_Utils::get_template_directory());
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - THDEMO_Loader. Orchestrates the hooks of the plugin.
	 * - THDEMO_i18n. Defines internationalization functionality.
	 * - THDEMO_Admin. Defines all hooks for the admin area.
	 * - THDEMO_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @access   private
	 */
	private function load_dependencies() {
		if(!function_exists('is_plugin_active')){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwec-autoloader.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwec-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwec-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-thwec-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-thwec-public.php';

		$this->loader = new THWEC_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the THDEMO_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new THWEC_i18n($this->get_plugin_name());
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}
	
	private function init_auto_updater(){
		if(!class_exists('THWEC_Auto_Update_License') ) {
			$api_url = 'https://themehigh.com/';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class-thwec-auto-update-license.php';
			THWEC_Auto_Update_License::instance(__FILE__, THWEC_SOFTWARE_TITLE, THWEC_VERSION, 'plugin', $api_url, THWEC_i18n::TEXT_DOMAIN);
		}
	}
	
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new THWEC_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles_and_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
		$this->loader->add_filter( 'woocommerce_screen_ids', $plugin_admin, 'add_screen_id' );
		$this->loader->add_filter( 'plugin_action_links_'.THWEC_BASE_NAME, $plugin_admin, 'plugin_action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_row_meta', 10, 2 );
		$this->loader->add_filter( 'admin_print_scripts', $plugin_admin, 'disable_admin_notices', 10, 2 );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_thwec_admin_notices' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'collapse_admin_sidebar' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new THWEC_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles_and_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Loader Object    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

endif;