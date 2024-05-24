<?php
/**
 * Plugin Name: Simple Custom CSS and JS PRO
 * Plugin URI: https://www.silkypress.com/simple-custom-css-js-pro/
 * Description: Easily add Custom CSS or JS to your website with an awesome editor.
 * Version: 4.34
 * Author: SilkyPress.com
 * Author URI: https://www.silkypress.com
 *
 * Text Domain: custom-css-js-pro
 * Domain Path: /languages/
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 7.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'ccj_plugin_updater_data' ) ) {
	function ccj_plugin_updater_data() {
		return array(
			'plugin_server' => 'https://www.silkypress.com',
			'file'          => __FILE__,
			'data'          => array(
				'version'     => '4.34',
				'plugin_name' => 'Simple Custom CSS and JS PRO',
				'author'      => 'Diana Burduja',
			),
		);
	}
}

if ( ! class_exists( 'CustomCSSandJSpro' ) ) :
	/**
	 * Main CustomCSSandJS Class
	 *
	 * @class CustomCSSandJS
	 */
	final class CustomCSSandJSpro {
		public $search_tree         = false;
		public $allowed_codes       = true;
		protected static $_instance = null;

		/**
		 * Main CustomCSSandJS Instance
		 *
		 * Ensures only one instance of CustomCSSandJS is loaded or can be loaded
		 *
		 * @static
		 * @return CustomCSSandJS - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			 _doing_it_wrong( __FUNCTION__, __( 'An error has occurred. Please reload the page and try again.' ), '1.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'An error has occurred. Please reload the page and try again.' ), '1.0' );
		}

		/**
		 * CustomCSSandJS Constructor
		 *
		 * @access public
		 */
		public function __construct() {

			include_once 'includes/admin-install.php';
			register_activation_hook( __FILE__, array( 'CustomCSSandJS_InstallPro', 'install' ) );
			add_action( 'init', array( 'CustomCSSandJS_InstallPro', 'register_post_type' ) );
			$this->set_constants();

			include_once 'includes/functions.php';

			if ( is_admin() ) {
				add_action( 'init', array( 'CustomCSSandJS_InstallPro', 'capabilites_for_admin' ) );
				add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
				add_action( 'admin_init', array( $this, 'remove_menu_link' ) );
				include_once 'includes/admin-screens.php';
				if ( get_option( 'ccj_license_status' ) == 'valid' ) {
					include_once 'includes/admin-preview.php';
					include_once 'includes/admin-revisions.php';
					include_once 'includes/admin-import.php';
					include_once 'includes/admin-shortcodes.php';
				} else {
					add_action( 'add_meta_boxes', array( $this, 'notice_activate_license' ) );
					add_filter( 'admin_body_class', array( $this, 'admin_body_class_license' ) );
				}
				include_once 'includes/admin-url-rules.php';
				// include_once( 'includes/admin-url-rules2.php' );
				include_once 'includes/admin-choose-theme.php';
				include_once 'includes/admin-config.php';
				include_once 'includes/admin-warnings.php';
			}

			if ( is_admin() ) {
				// In the admin side the `init` hooked is needed to show the codes on all the pages, not only Posts/Pages pages
				add_action( 'init', array( $this, 'initiate_show_codes' ) );
			} else {
				// In the frontend the `wp` hooked is needed. The `init` hooked fires too early for the WP Conditional Tags to take effect
				add_action( 'wp', array( $this, 'initiate_show_codes' ) );
				add_action( 'login_init', array( $this, 'initiate_show_codes' ) );
			}
		}


		/**
		 * Initiate the procedure for showing the codes
		 */
		function initiate_show_codes() {

			include_once 'includes/class-show-codes.php';

			$show_codes = new CustomCSSandJS_ShowCodesPro();
			$show_codes->set_value( 'first_page', home_url() );
			$show_codes->set_value( 'upload_dir', CCJ_UPLOAD_DIR );
			$show_codes->set_value( 'upload_url', CCJ_UPLOAD_URL );

			$settings = get_option( 'ccj_settings', array() );
			if ( isset( $settings['remove_comments'] ) && $settings['remove_comments'] ) {
				$show_codes->set_value( 'remove_comments', true );
			}

			// Get the allowed codes for this specific page
			$uri           = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			$ccj_urls      = get_option( 'custom-css-js-urls', array() );
			$allowed_codes = $show_codes->url_rules( $uri, $ccj_urls );
			if ( ! defined( 'CCJ_WP_CONDITIONALS' ) || CCJ_WP_CONDITIONALS != false ) {
				$allowed_codes = $show_codes->wp_conditional_tags( $allowed_codes, $ccj_urls );
			}

			// Print shortcodes
			$search_tree = get_option( 'custom-css-js-tree', array() );
			$show_codes->add_shortcodes( $search_tree );

			// Get the filtered search tree
			$search_tree = $show_codes->filter_search_tree( $search_tree, $allowed_codes );
			if ( isset( $_GET['ccj-preview-id'] ) ) {
				$preview_id  = $_GET['ccj-preview-id'];
				$search_tree = $show_codes->search_tree_for_preview( $search_tree, $preview_id );
			}

			// Get the network-wide codes
			global $wp_version;
			if ( is_multisite() && version_compare( $wp_version, '4.9.0', '>=' ) && ! is_main_site() ) {

				// Remove custom JS codes from login page, unless the "Allow custom JS codes to the login page in subsites" option is enabled by super admin in the main site. 
				if ( ! get_site_option( 'ccj_multisite_js_loginpage', false ) && count( $search_tree ) > 0 ) {
					foreach ( $search_tree as $_priority => $_codes ) {
						foreach ( $_codes as $_where => $__codes ) {
							if ( strpos ( $_where, 'login-js' ) !== false ) {
								unset( $search_tree[$_priority][$_where] );
							}
						}
					}
				}

				// Get the allowed codes for this specific page
				$ccj_urls_multisite      = get_site_option( 'custom-css-js-urls-multisite' );
				$allowed_codes_multisite = $show_codes->url_rules( $uri, $ccj_urls_multisite );
				$allowed_codes_multisite = $show_codes->wp_conditional_tags( $allowed_codes_multisite, $ccj_urls_multisite );

				$search_tree_multisite = get_site_option( 'custom-css-js-multisite' );
				$show_codes->add_shortcodes( $search_tree_multisite, true );

				// Filter the search tree
				$search_tree_multisite = $show_codes->filter_search_tree( $search_tree_multisite, $allowed_codes_multisite );
				if ( isset( $_GET['ccj-preview-id'] ) ) {
					$preview_id            = $_GET['ccj-preview-id'];
					$search_tree_multisite = $show_codes->search_tree_for_preview( $search_tree_multisite, $preview_id );
				}

				// Add the network-wide codes to the normal $search_tree
				if ( is_array( $search_tree_multisite ) && count( $search_tree_multisite ) > 0 ) {
					foreach ( $search_tree_multisite as $_key => $_value ) {
						if ( isset( $search_tree[ $_key ] ) ) {
							$search_tree[ $_key ] = array_merge( $search_tree[ $_key ], $_value );
						} else {
							$search_tree[ $_key ] = $_value;
						}
					}
				}
			}

			// Print the codes
			$show_codes->set_value( 'search_tree', $search_tree );
			$show_codes->print_code_actions( $search_tree );
			if ( isset( $search_tree[5] ) && isset ( $search_tree[5]['jquery'] ) && true === $search_tree[5]['jquery'] ) {
				add_action( 'wp_enqueue_scripts', 'CustomCSSandJSPro::wp_enqueue_scripts' );
			}

		}


		/**
		 * Remove the "Custom JS & CSS" link in the menu for non-admins
		 */
		function remove_menu_link() {
			global $menu;

			if ( ! is_array( $menu ) || count( $menu ) == 0 ) {
				return false;
			}

			if ( current_user_can( 'activate_plugins' ) || current_user_can( 'publish_custom_csss' ) ) {
				return false;
			}

			remove_menu_page( 'edit.php?post_type=custom-css-js' );
		}


		/**
		 * Tag the edit pages without a valid license key
		 */
		function admin_body_class_license( $classes = '' ) {
			$classes .= 'ccj-license-not-valid';
			return $classes;
		}


		/**
		 * Add a meta box to remind to activate the license
		 */
		function notice_activate_license() {
			add_meta_box( 'activatelicensediv', __( 'Please activate the license', 'custom-css-js-pro' ), array( $this, 'activate_license_meta_box_callback' ), 'custom-css-js', 'normal' );
		}


		/**
		 * The meta box content
		 */
		function activate_license_meta_box_callback( $post ) {
			?>
		<div id="activatelicense-action">
			<?php printf( __( 'Don\'t forget to activate <a href="%s">here</a> the license key you received in the email.', 'customo-css-js-pro' ), 'edit.php?post_type=custom-css-js&page=custom-css-js-config&tab=license' ); ?>
		</div>
			<?php

		}


		/**
		 * Enqueue the jQuery library, if necessary
		 */
		public static function wp_enqueue_scripts() {
			wp_enqueue_script( 'jquery' );
		}


		/**
		 * Set constants for later use
		 */
		public function set_constants() {
			$dir       = wp_upload_dir();
			$constants = array(
				'CCJ_PREVIEW_PREFIX'  => 'ccj_preview-',
				'CCJ_VERSION_PRO'     => '4.34',
				'CCJ_UPLOAD_DIR'      => $dir['basedir'] . '/custom-css-js',
				'CCJ_UPLOAD_URL'      => $dir['baseurl'] . '/custom-css-js',
				'CCJ_PLUGIN_FILE_PRO' => __FILE__,
			);
			foreach ( $constants as $_key => $_value ) {
				if ( ! defined( $_key ) ) {
					define( $_key, $_value );
				}
			}
		}


		/**
		 * Loads a plugin’s translated strings.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'custom-css-js-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}

endif;


if ( ! function_exists( 'CustomCSSandJSPro' ) ) {
	/**
	 * Returns the main instance of CustomCSSandJS
	 *
	 * @return CustomCSSandJS
	 */
	function CustomCSSandJSpro() {
		return CustomCSSandJSpro::instance();
	}

	CustomCSSandJSpro();
}

/**
 * Register activation hook
 */
function custom_css_js_pro_activation() {
	$ccj_urls = get_option( 'custom-css-js-urls', false );

	if ( $ccj_urls !== false ) {
		return false;
	}

	// get all codes
	$posts = query_posts( 'post_type=custom-css-js&post_status=publish&nopaging=true' );

	// return if no codes available
	if ( ! is_array( $posts ) || count( $posts ) == 0 ) {
		return false;
	}

	$ids = array();
	foreach ( $posts as $_post ) {
		if ( get_post_meta( $_post->ID, '_active', true ) === 'no' ) {
			// return if the code is not active
			continue;
		}
		$options = ccj_get_options( $_post->ID );
		if ( ! isset( $options['language'] ) ) {
			continue;
		}
		if ( $options['language'] == 'html' ) {
			$file = $_post->ID;
		} else {
			$file = $_post->ID . '.' . $options['language'];
		}
		$ids[] = $file;
	}
	// create the custom-css-js-urls option
	update_option( 'custom-css-js-urls', array( 'all' => $ids ) );

	// rebuild the custom-css-js-tree
	ccj_build_search_tree();
}
register_activation_hook( __FILE__, 'custom_css_js_pro_activation' );


if ( ! function_exists( 'custom_css_js_plugin_action_links' ) ) {
	/**
	 * Plugin action link to Settings page.
	 *
	 * @param array $links The settings links.
	 *
	 * @return array The settings links.
	 */
	function custom_css_js_pro_plugin_action_links( $links ) {

		$settings_link = '<a href="edit.php?post_type=custom-css-js&page=custom-css-js-config">' .
			esc_html( __( 'Settings' ) ) . '</a>';

		return array_merge( array( $settings_link ), $links );

	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'custom_css_js_pro_plugin_action_links' );
}

if ( ! function_exists( 'custom_css_js_pro_quads_pro_compat' ) ) {
	/**
	 * Compatibility with the WP Quads Pro plugin,
	 * otherwise on a Custom Code save there is a
	 * "The link you followed has expired." page shown.
	 *
	 * @param array $post_types The Post types.
	 * @return array The Post types.
	 */
	function custom_css_js_pro_quads_pro_compat( $post_types ) {
		$match = array_search( 'custom-css-js', $post_types, true );
		if ( $match ) {
			unset( $post_types[ $match ] );
		}
		return $post_types;
	}
	add_filter( 'quads_meta_box_post_types', 'custom_css_js_pro_quads_pro_compat', 20 );
}

