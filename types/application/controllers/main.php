<?php

use OTGS\Toolset\Types\TypeRegistration\Controller;

/** @noinspection PhpFullyQualifiedNameUsageInspection */

use OTGS\Toolset\Types\Controller\Compatibility\Gutenberg;

/**
 * Main Types controller.
 *
 * Determines if we're in admin or front-end mode or if an AJAX call is being performed. Handles tasks that are common
 * to all three modes, if there are any.
 *
 * @since 2.0
 */
final class Types_Main {

	private static $instance;


	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function initialize() {
		self::get_instance();
	}



	private function __construct() {
		// Indicate that m2m can be activated with this plugin. This needs to happen very early, even before Toolset
		// Common bootstrap is executed.
		add_filter( 'toolset_is_m2m_ready', '__return_true' );

		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 10 );

		// important to be 11, because we register our post types on 10 (we should consider loading them earlier)
		add_action( 'init', array( $this, 'on_init' ), 11 );

		// attachment deletion
		add_action( 'delete_attachment', static function( $attachment_post_id ) {
			if( class_exists( 'Types_Media_Service' ) ) {
				$media_service = new Types_Media_Service();
				$media_service->delete_resized_images_by_attachment_id( $attachment_post_id );
			}
		} );
	}



	/**
	 * Determine in which mode we are and initialize the right dedicated controller.
	 *
	 * @since 2.0
	 */
	public function on_init() {
		if( is_admin() ) {
			if( defined( 'DOING_AJAX' ) ) {
				$this->mode = self::MODE_AJAX;
				Types_Ajax::initialize();
			} else {
				$this->mode = self::MODE_ADMIN;
				$admin_controller = new Types_Admin();
				$admin_controller->initialize();
			}
		} else {
			$this->mode = self::MODE_FRONTEND;
			Types_Frontend::initialize();
		}

		$dic = toolset_dic();

		$m2m = $dic->make( Types_M2M::class );
		$m2m->initialize();

		// Initialize the "types" shortcode in all contexts.
		$factory = new Types_Shortcode_Factory();
		$types_shortcode = $factory->get_shortcode( 'types' );
		if ( $types_shortcode ) {
			add_shortcode( 'types', array( $types_shortcode, 'render' ) );
		};

		if ( in_array( $this->mode, [ self::MODE_ADMIN, self::MODE_AJAX ], true ) ) {
			/** @var \OTGS\Toolset\Types\TypeRegistration\Controller $type_registration_controller */
			/** @noinspection PhpUnhandledExceptionInspection */
			$type_registration_controller = $dic->make( Controller::class );
			$type_registration_controller->initialize();

			/** @var Gutenberg $gutenberg */
			/** @noinspection PhpUnhandledExceptionInspection */
			$gutenberg = $dic->make( Gutenberg::class );
			$gutenberg->initialize();
		}
	}


	/**
	 * @var string One of the MODE_* constants.
	 */
	private $mode = self::MODE_UNDEFINED;

	const MODE_UNDEFINED = '';
	const MODE_AJAX = 'ajax';
	const MODE_ADMIN = 'admin';
	const MODE_FRONTEND = 'frontend';

	/**
	 * Get current plugin mode.
	 *
	 * Possible values are:
	 * - MODE_UNDEFINED before the main controller initialization is completed
	 * - MODE_AJAX when doing an AJAX request
	 * - MODE_ADMIN when showing a WP admin page
	 * - MODE_FRONTEND when rendering a frontend page
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_plugin_mode() {
		return $this->mode;
	}


	/**
	 * Set current plugin mode.
	 *
	 * @param string $new_mode the new plugin mode
	 * @return bool TRUE if set is succesfully done, FALSE otherwise
	 * @since 2.2
	 */
	public function set_plugin_mode( $new_mode = self::MODE_UNDEFINED ) {
		if ( !in_array( $new_mode, array( self::MODE_UNDEFINED, self::MODE_AJAX, self::MODE_ADMIN, self::MODE_FRONTEND ) ) ){
			return false;
		}
		$this->mode = $new_mode;
		return true;
	}


	/**
	 * Determine whether a WP admin page is being loaded.
	 *
	 * Note that the behaviour differs from the native is_admin() which will return true also for AJAX requests.
	 *
	 * @return bool
	 * @since 2.1
	 */
	public function is_admin() {
		return ( $this->get_plugin_mode() === self::MODE_ADMIN );
	}


	/**
	 * Early loading actions.
	 *
	 * @since 2.0
	 * @since m2m Load the shortcodes generator
	 */
	public function after_setup_theme() {
		

		// Indicate that m2m can be activated with this plugin.
		add_filter( 'toolset_is_m2m_ready', '__return_true' );

		// Initialize the Toolset Common library
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();

		$this->setup_autoloader();
		$this->setup_auryn();

		// If an AJAX callback handler needs other assets, it should initialize the asset manager by itself.
		if( $this->get_plugin_mode() !== self::MODE_AJAX ) {
			Types_Assets::get_instance()->initialize_scripts_and_styles();
		}

		// Handle embedded plugin mode
		Types_Embedded::initialize();

		Types_Api::initialize();

		$dic = types_dic();

		$interop_mediator = $dic->make( \OTGS\Toolset\Types\Controller\Interop\InteropMediator::class );
		$interop_mediator->initialize();

		$plugin_cache = $dic->make( '\OTGS\Toolset\Types\Controller\Cache' );
		$plugin_cache->initialize();

		// Load the shortcodes generator and the blocks section.
		$toolset_common_sections = array( 'toolset_shortcode_generator', Toolset_Common_Bootstrap::TOOLSET_BLOCKS );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		$types_shortcode_generator = new Types_Shortcode_Generator();
		$types_shortcode_generator->initialize();
	}


	private function setup_autoloader() {

		// It is possible to regenerate the classmap with Zend framework.
		//
		// See the "recreate_classmap.sh" script in the plugin root directory.
		$classmap = include( TYPES_ABSPATH . '/application/autoload_classmap.php' );

		// Use Toolset_Common_Autoloader
		do_action( 'toolset_register_classmap', $classmap );

	}


	/**
	 * In some cases, it may not be clear what legacy files are includes and what aren't.
	 *
	 * This method should make sure all is covered (add files when needed). Use only when necessary.
	 *
	 * @since 2.0
	 */
	public function require_legacy_functions() {
		require_once WPCF_INC_ABSPATH . '/fields.php';
	}


	/**
	 * Configure Auryn so that it is able to inject our singleton instances properly.
	 *
	 * @since 3.2.2
	 */
	private function setup_auryn() {
		// This will initialize the DIC if it hasn't been done yet
		$dic = types_dic();

		// See utility/dic.php in Toolset Common for details. We're doing the same for Types here.
		$singleton_delegates = array(
			'\Types_Asset_Manager' => function() {
				return Types_Asset_Manager::get_instance();
			}
		);

		foreach( $singleton_delegates as $class_name => $callback ) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$dic->delegate( $class_name, function() use( $callback, $dic ) {
				$instance = $callback();
				$dic->share( $instance );
				return $instance;
			});
		}
	}
}
