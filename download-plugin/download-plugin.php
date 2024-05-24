<?php
/*
*  Plugin Name: Download Plugin
*  Plugin URI: http://metagauss.com
*  Description: Download any plugin from your wordpress admin panel's plugins page by just one click!
*  Version: 2.1.0
*  Author: Metagauss
*  Author URI: https://profiles.wordpress.org/metagauss/
*  Text Domain: download-plugin
*  Requires at least: 4.8
*  Tested up to: 6.5
*  Requires PHP: 5.6
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if( !is_admin() ) return;

// plugin version
define('DPWAP_VERSION', '2.1.0');
// directory separator
if ( !defined( 'DS' ) ) define( 'DS', DIRECTORY_SEPARATOR );
// plugin file name
if ( !defined( 'DPWAP_PLUGIN_FILE' ) ) {
    define( 'DPWAP_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'DPWAP_DIR' ) ) {
    define( 'DPWAP_DIR', dirname( __FILE__ ) );	// Plugin dir
}
if ( !defined( 'DPWAP_URL' ) ) {
    define( 'DPWAP_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}
if ( !defined( 'DPWAP_PREFIX' ) ) {
    define( 'DPWAP_PREFIX', 'dpwap_' ); // Plugin Prefix
}

$dpwapUploadDir = wp_upload_dir();
if ( !defined( 'DPWAPUPLOADDIR_PATH' ) ) {
    define( 'DPWAPUPLOADDIR_PATH', $dpwapUploadDir['basedir'] );
}    
if ( !defined( 'DPWAP_PLUGINS_TEMP' ) ) {
    define( 'DPWAP_PLUGINS_TEMP', $dpwapUploadDir['basedir'].'/dpwap_plugins' ); // Plugin Prefix
}

require_once dirname( DPWAP_PLUGIN_FILE ) . '/vendor/autoload.php';

add_action( 'plugins_loaded', 'dpwap_plugin_loaded' );

//register_activation_hook( __FILE__, 'dpwap_func_activate' );

register_uninstall_hook( __FILE__, 'dpwap_func_uninstall' );

function dpwap_plugin_loaded() {
    static $instance;
	if ( is_null( $instance ) ) {
		$instance = new DPWAP\Main();
        /**
         * Download plugin loaded.
         *
         * Fires when Download plugin was fully loaded and instantiated.
         *
         */
        do_action( 'dpwap_download_plugin_loaded' );
	}
	return $instance;
}

if( !function_exists( 'dpwap_func_activate' ) ) {
    function dpwap_func_activate() {
        add_option( 'download_plugin_do_activation_redirect', true );
    }
}

if ( !function_exists( 'dpwap_func_uninstall' ) ){
    function dpwap_func_uninstall() {
        //delete_option( 'dpwap_popup_status' );
        $folder = DPWAP_PLUGINS_TEMP;
        $files = glob( "$folder/*" );
        if ( !empty( $files) ) {
            foreach( $files as $file ) {
                if ( is_file( $file) ){
                    unlink( $file );
                }
            }
        }
    }
}