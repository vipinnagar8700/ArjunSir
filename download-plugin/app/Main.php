<?php
namespace DPWAP;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use DPWAP\Plugins\Base as pluginBase;
use DPWAP\Themes\Base as themeBase;

class Main {
    protected static $instance = null;
    public $extensions = array();

    public function __construct(){
        $this->addActions();
        $this->loadTextdomain();

        add_action( 'admin_enqueue_scripts', array( $this, 'dpwap_load_common_admin_scripts' ) );

        add_action( 'admin_notices', array( $this, 'dpwap_general_admin_notice' ) );

        $plugins = new pluginBase();
        $plugins->setup();

        $themes = new themeBase();
        $themes->setup();
    }

    public function addActions() {
        add_action( 'admin_init', array( $this, 'dpwap_plugin_redirect' ) );
        add_action( 'admin_menu', array( $this, 'dpwap_load_menus' ) );
        add_action( 'wp_ajax_dpwap_dismiss_notice_action', array( $this, 'dpwap_dismiss_notice_action' ) );
    }

    public function loadTextdomain(){
        load_textdomain( 'download-plugin', WP_LANG_DIR . '/download-plugin/download_plugin-' . get_locale() . '.mo' );
    }

    /**
     * redirect plugin to menu on activation
     */
    public function dpwap_plugin_redirect() {
        if ( get_option( 'download_plugin_do_activation_redirect', false ) ) {
            delete_option( 'download_plugin_do_activation_redirect' );
            wp_redirect( admin_url( "admin.php?page=dpwap_plugin" ) );
            exit;
        }
    }

    public function dpwap_load_menus() {
        $dpwap = dpwap_plugin_loaded();
        if ( in_array( 'download-users', $dpwap->extensions ) ) {
            add_menu_page( __( 'Download', 'download-plugin' ), __( 'Download', 'download-plugin' ), 'manage_options', "dpwap_plugin", array( $this, 'dpwap_plugin' ), 'dashicons-media-archive', '99' );
            // download plugin menu
            add_submenu_page( "dpwap_plugin", __( 'Download Plugins', 'download-plugin' ), __( 'Download Plugins', 'download-plugin' ), "manage_options", "dpwap_plugin", array( $this, 'dpwap_plugin' ) );
            // download theme menu
            add_submenu_page( "dpwap_plugin", __( 'Download Themes', 'download-plugin' ), __( 'Download Themes', 'download-plugin' ), "manage_options", "dpwap_theme", array( $this, 'dpwap_theme' ) );
            // load all extensions
            // show default download user menu
            if ( !in_array( 'download-users', $dpwap->extensions ) ) {
                add_submenu_page( "dpwap_plugin", __('Download Users', 'download-plugin'), __('Download Users', 'download-plugin'), "manage_options", "dpwap_users", array( $this, 'duwap_users_check' ) );
            }
            // show default download bbPress menu
            /*if ( !in_array( 'download-bbpress-integration', $dpwap->extensions ) ) {
                add_submenu_page( "dpwap_plugin", __('bbPress', 'download-plugin'), __('bbPress', 'download-plugin'), "manage_options", "dpwap_bbpress", array( $this, 'duwap_bbpress_check' ) );
            }*/
        }
        
        do_action( 'dpwap_downlad_plugin_menus' );
    }

    public function dpwap_plugin() {
        $plugin_info_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'dpwap_plugin_info.php';
        include_once $plugin_info_file;
    }

    public function dpwap_theme() {
        $theme_info_file = DPWAP_DIR.DS.'app'.DS.'Themes'.DS.'templates'.DS.'dpwap_theme_info.php';
        include_once $theme_info_file;
    }

    public function duwap_users_check() {
        $users_info_file = DPWAP_DIR.DS.'app'.DS.'Users'.DS.'templates'.DS.'dpwap_users_info.php';
        include_once $users_info_file;
    }

    public function duwap_bbpress_check() {
        $bbpress_info_file = DPWAP_DIR.DS.'app'.DS.'bbPress'.DS.'templates'.DS.'dpwap_bbpress_info.php';
        include_once $bbpress_info_file;
    }

    public function dpwap_load_common_admin_scripts() {
        wp_enqueue_script( 'dpwap_common_js', DPWAP_URL.'assets/js/dpwap-common.js',array(), DPWAP_VERSION );
        wp_localize_script( 'dpwap_common_js', 'admin_vars', array( 'admin_url' => admin_url(), 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        wp_enqueue_style( 'dpwap_common_css', DPWAP_URL.'assets/css/dpwap-common.css',array(), DPWAP_VERSION );
    }
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Admin notice
     */
    public function dpwap_general_admin_notice() {
        $dpwap = dpwap_plugin_loaded();
        $get_dismiss_option = get_option('dpwap_dismiss_offer_notice', false);
        if(empty($dpwap->extensions) && empty($get_dismiss_option)){
            echo '<div class="dpwap-notice-pre notice notice-info is-dismissible">
                <p><b>Download Plugin</b> now has add-on for downloading and uploading your website\'s user accounts. <a href="https://metagauss.com/wordpress-users-import-export-plugin/?utm_source=dp_plugin&utm_medium=admin_notice&utm_campaign=download_users_addon" target="_new">Click here </a>to get it now!</p>
            </div>';
        }
    }

    /**
     * Hide admin notice
     */
    public function dpwap_dismiss_notice_action() {
        add_option('dpwap_dismiss_offer_notice', true);
        wp_send_json_success('Notice Dismissed');
    }
}