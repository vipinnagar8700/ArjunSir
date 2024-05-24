<?php
namespace DPWAP\Plugins;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use DPWAP\Plugins\Dpwapuploader;

class Base {

    private $dpwap_all_plugins;

    public function setup() {
        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->dpwap_all_plugins = get_plugins();
        // init
        add_action( 'init', array( $this, 'dpwap_plugin_init_action' ) );
        // plugin menu action
        add_action( 'admin_menu', array( $this, 'dpwap_plugin_register_menupage' ) );
        // enqueue js and css
        add_action( 'admin_enqueue_scripts', array( $this, 'dpwap_load_plugin_admin_scripts' ) );
        if ( !current_user_can( 'install_plugins' ) ) return;

        // add dowload links to all plugins
        $this->addDownloadLinks();
        // add bulk download link
        add_filter( 'bulk_actions-plugins', array( $this, 'add_download_plugin_bulk_actions' ) );
        // loader handler
        add_action( 'admin_head', array( $this, 'dpwap_custom_admin_head_loader' ) );
        // popup in install handler
        //add_action( 'admin_footer', array( $this, 'dpwap_plugin_setting_popup_func' ) );
        // multiple download handler
        add_action( 'admin_footer', array( $this, 'dpwap_plugin_multiple_download_action' ) );
        // ajax multi download handler
        add_action( 'wp_ajax_dpwap_plugin_download_url', array( $this, 'dpwap_plugin_multiple_download_func' ) );
        // upload multiple plugin handler
        add_action( 'admin_notices', array( $this, 'dpwap_multiple_upload_admin_func' ) );
        // plugin activation handler
        add_action( 'wp_ajax_dpwap_plugin_activate', array( $this, 'dpwap_plugin_activate_func' ) );
        // feature select
        //add_action( 'wp_ajax_dpwap_feature_select', array( $this, 'dpwap_plugin_feature_select_func' ) );
    }

    public function dpwap_plugin_init_action(){
        global $pagenow;
        if ( is_user_logged_in() && current_user_can( 'activate_plugins' ) ) {
            if ( isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] == 'dpwap_plugin' ) ) {
                if ( isset( $_GET['dpwap_plugin_download'] ) && !empty( $_GET['dpwap_plugin_download'] ) && isset( $_GET['f'] ) && !empty( $_GET['f'] ) ) {
                    $this->dpwap_plugin_download_action();
                }
            }
        }
    }

    public function dpwap_plugin_register_menupage(){ 
        // download plugin submenu
        add_submenu_page( 'dp_main_menu_hidden', __( 'Multiple Upload', ' download-plugin' ), __(' Multiple Upload', 'download-plugin' ), 'manage_options', 'mul_upload', array( $this, 'dpwap_plugin_multiple_upload_func' ) );
    	add_submenu_page( 'dp_main_menu_hidden_activate', __( 'Dpwap Activate', 'download-plugin' ), __(' Dpwap Activate', 'download-plugin' ), 'manage_options', 'dpwap-activate', array( $this, 'dpwap_plugin_package_activate_func' ) );
    	add_submenu_page( 'dp_main_menu_hidden_status', __( 'Dpwap Status', 'download-plugin' ), __(' Dpwap Status', 'download-plugin' ), 'manage_options', 'activate-status', array( $this, 'dpwap_plugin_all_activate_status_func' ) );
        add_submenu_page( 'dp_main_menu_hidden_download', __( 'Dpwap Plugin Download', 'download-plugin' ), __(' Dpwap Plugin Download', 'download-plugin' ), 'manage_options', 'dpwap_plugin_download', array( $this, 'dpwap_plugin_download_func' ) );
    }
    
    // multiple upload function
    public function dpwap_plugin_multiple_upload_func(){  
        $dpwapObj = new Dpwapuploader();
        $plugin_multiple_upload_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'multiple_upload_plugin.php';
        include_once $plugin_multiple_upload_file;
    }

    // single plugin activate function
    public function dpwap_plugin_package_activate_func(){
        $featureObj = new Dpwapuploader();
        $plugin_feature_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'feature-package.php';
        include_once $plugin_feature_file;
    }

    // all plugins activate function
    public function dpwap_plugin_all_activate_status_func(){
        $dpwapObj = new Dpwapuploader();
        $plugin_active_status_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'activate-status.php';
        include_once $plugin_active_status_file;
    }

    public function dpwap_load_plugin_admin_scripts(){ 
        if ( !current_user_can( 'install_plugins' ) ) return;

        global $pagenow;
        $isDpPage = 0;
        if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'mul_upload' || $_GET['page'] == 'activate-status' || $_GET['page'] == 'dpwap-activate' ) ) {
            $isDpPage = 1;
        }
        if ( $pagenow == 'plugins.php' || $pagenow == 'plugin-install.php' || !empty( $isDpPage ) ) {
            //wp_enqueue_script( 'up_admin_script', DPWAP_URL.'assets/js/bootstrap.min.js', array('jquery'), DPWAP_VERSION );
            wp_enqueue_script( 'up_admin_func', DPWAP_URL.'assets/js/multiple.js',array(), DPWAP_VERSION );
            wp_register_style( 'dpwap-admin-style', DPWAP_URL.'assets/css/dpwap-admin.css', array(), DPWAP_VERSION );
            wp_enqueue_style( 'dpwap-admin-style' );
            $dpwap_string = array(
                "server_max_upload" => esc_html__( 'server maximum upload size limit', 'download-plugin' ),
                "mb" => esc_html__( 'MB', 'download-plugin' ),
                "valid_zip_message" => esc_html__( 'Please upload vaild .zip extension file.', 'download-plugin' ),
                "max_20_upload" => esc_html__( 'Max. 20 uploads are supported at a time.', 'download-plugin' ),
                "activate_message" => esc_html__( 'Plugin activated successfully.', 'download-plugin' ),
                "activated_message" => esc_html__( 'Plugin activated', 'download-plugin' ),
                "wait_text" => esc_html__( 'Please wait...', 'download-plugin' ),
                "feature_message" => esc_html__( 'You are all set! Install and activate our following plugins from wordpress.org to add selected features.', 'download-plugin' ),
                "select_feature" => esc_html__( 'Select at least one Feature', 'download-plugin' ),
                "no_plugin_message" => esc_html__( 'Please select a plugin (or multiple plugins) to begin download.', 'download-plugin' ),
                "max_30_dpwnload" => esc_html__( 'Max. 30 downloads are supported at a time', 'download-plugin' ),
            );
            wp_localize_script( 'up_admin_func', 'dpwap_string', $dpwap_string );

            do_action( 'dpwap_plugin_admin_enqueues' );
        }
    }

    public function addDownloadLinks(){
        if ( !current_user_can( 'install_plugins' ) ) return;

        foreach( $this->dpwap_all_plugins as $key => $value ) {
            add_filter( 'plugin_action_links_' . $key, array($this, 'dpwap_plugin_download_link'), 20, 2 );
        }
    }

    public function dpwap_plugin_download_link( $links, $plugin_file ) {
        if ( !current_user_can( 'install_plugins' ) ) return;

        if( strpos( $plugin_file, '/' ) !== false ) {
            $explode = explode( '/', $plugin_file );
            $path    = $explode[0];
            $folder  = 1;
        } else {
            $path   = $plugin_file;
            $folder = 2;
        }
        $nonce = wp_create_nonce('bulk-plugins');
        $pluginDownloadLink = admin_url( 'admin.php?page=dpwap_plugin&dpwap_plugin_download='.$path.'&f='.$folder.'&_wpnonce='.$nonce );
        $download_link = array(
            '<span class="dpwap_download-wrap">
            <a href="'.esc_url($pluginDownloadLink).'" class="dpwap_download_link">'.esc_html__( 'Download', 'download-plugin' ).'</a></span>',
        );
        return array_merge( $links, $download_link );
    }

    public function add_download_plugin_bulk_actions( $bulk_array ) {
        if ( !current_user_can( 'install_plugins' ) ) return;
        
        $bulk_array_keys = array_keys( $bulk_array );
        if( !empty( $bulk_array_keys) ) {
            $lastKey = end( $bulk_array_keys );    
            if( $lastKey == 'delete-selected' ) { 
                unset( $bulk_array['delete-selected'] );
                $bulk_array['all_download'] = 'Download';
                $bulk_array['delete-selected'] = 'Delete';
            }
            else{
                $bulk_array['all_download'] = 'Download';	
            }
        }
        return $bulk_array;
    }

    public function dpwap_custom_admin_head_loader() {
        global $pagenow;
        $isDpPage = 0;
        if( isset($_GET['page'] ) && ( $_GET['page'] == 'mul_upload' || $_GET['page'] == 'activate-status' || $_GET['page'] == 'dpwap-activate' ) ) {
            $isDpPage = 1;
        }
        if( $pagenow == 'plugins.php' || $pagenow == 'plugin-install.php' || !empty( $isDpPage ) ) {
            $imgUrl = DPWAP_URL.'/assets/images/dpwap-loader.gif';
            echo "<div id='dpwapLoader'>";
            echo  "<img src='".esc_url($imgUrl)."'>";
            echo "<p>".esc_html__( 'This may take few minutes based on the number and size of the plugins', 'download-plugin' )."</p></div>";
        }
    }

    /*public function dpwap_plugin_setting_popup_func() {
        global $pagenow;
        if ( $pagenow == 'plugins.php' ) {
            $plugin_setting_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'dpwap_setting.php';
            if( !get_option( 'dpwap_popup_status' ) ) { ?>
                <script language="javascript">
                    jQuery(window).load(function() {
                            //jQuery('#dpwap_modal').modal();
                        jQuery('#dpwap_modal').toggle();
                        jQuery('#dpwap_modal').toggleClass('in');
                        jQuery( "#dpwap_modal .dpwap-close, #dpwap_modal .dpwap_modal-ovalay" ).click(function() {     
                        jQuery('#dpwap_modal').hide();
                       });
                            //jQuery('#dpwap_modal').modal();
                    });
                </script>
                    <?php
                require_once $plugin_setting_file;
                add_option( 'dpwap_popup_status', 1 );
           }
            require_once $plugin_setting_file;
        }
    }*/

    private function dpwap_plugin_download_action() {
        if ( !current_user_can( 'install_plugins' ) ) return;
        if(!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'],'bulk-plugins')){
            return;
        }
        $all_plugins    = array_keys( $this->dpwap_all_plugins );
        $plugins_arr    = array();
        $dpwap_download = sanitize_text_field( $_GET['dpwap_plugin_download'] );
        foreach( $all_plugins as $key => $value ) {
            $explode = explode( '/', $value );
            array_push( $plugins_arr, $explode[0] );
        }
        if( in_array( $dpwap_download, $plugins_arr ) ) {
            $folder = sanitize_text_field( $_GET['f'] );
            if($folder == 2) {
                $dpwap_download = basename( $dpwap_download, '.php' );
                $folder_path  = WP_PLUGIN_DIR.'/'.$dpwap_download;
                if( !file_exists( $folder_path ) ) {
                    mkdir( $folder_path, 0777, true );
                }
                $source      = $folder_path.'.php';
                $destination = $folder_path.'/'.$dpwap_download.'.php';
                copy( $source, $destination );
            } else {
                $folder_path = WP_PLUGIN_DIR.'/'.$dpwap_download;
            }
            $root_path = realpath( $folder_path );
            $zip = new ZipArchive();
            $zip->open( $folder_path.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE );
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $root_path ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach( $files as $name => $file ) {
                if ( !$file->isDir() ) {
                    $file_path	   = $file->getRealPath();
                    $relative_path = substr( $file_path, strlen( $root_path ) + 1 );
                    $zip->addFile( $file_path, $relative_path );
                }
            }
            $zip->close();
            if( $folder == 2 ){
                $this->dpwap_delete_temp_folder( $folder_path );
            }
            // Download Zip
            $zip_file = $folder_path.'.zip';
            if( file_exists( $zip_file ) ) {
                header( 'Content-Description: File Transfer' );
                header( 'Content-Type: application/octet-stream' );
                header( 'Content-Disposition: attachment; filename="'.basename( $zip_file ).'"' );
                header( 'Expires: 0' );
                header( 'Cache-Control: must-revalidate' );
                header( 'Pragma: public' );
                header( 'Content-Length: ' . filesize($zip_file) );
                header( 'Set-Cookie:fileLoading=true' );
                readfile( $zip_file );
                unlink( $zip_file );
                exit;
            }
        }
    }

    public function dpwap_delete_temp_folder( $path ){
        if( is_dir( $path ) === true ) {
            $files = array_diff( scandir( $path ), array( '.', '..' ) );
            foreach( $files as $file ) {
                $this->dpwap_delete_temp_folder( realpath( $path ) . '/' . $file );
            }
            return rmdir( $path );
        } else if( is_file( $path ) === true ) {
            return unlink( $path );
        }
        return false;
    }

    //all plugins activate get ajax response code
    public function dpwap_plugin_multiple_download_func() {
        if ( !current_user_can( 'install_plugins' ) ) return;
        if(!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'bulk-plugins')){
            return;
        }
        $strPluginCount = ( isset( $_POST['plugin_count'] ) ) ? sanitize_text_field( $_POST['plugin_count'] ) : '0';
        if( $strPluginCount == '1' ){
            if( file_exists( DPWAP_PLUGINS_TEMP ) ) {
                $folder = DPWAP_PLUGINS_TEMP;
                $files = glob( "$folder/*" );
                foreach( $files as $file ) {
                    unlink( $file );
                }
            }
        }        
        $dpwap_download      = sanitize_text_field( $_POST['pluginData'] );
        $dpwap_download_base = basename( $dpwap_download, '.php' );
        $explode             = explode( '/', $dpwap_download );
        $folderpath          = $explode[0];

        if(!file_exists( DPWAP_PLUGINS_TEMP ) ) {
            mkdir( DPWAP_PLUGINS_TEMP, 0777, true );
        }

        $folder_path  = WP_PLUGIN_DIR.'/'.$folderpath;
        $zipPath = $folderpath.'.zip';
        if($geturls = get_option( "dpwap_downloads_url" ) ) {
            $getDwnurls = maybe_unserialize( $geturls );
            array_push( $getDwnurls, $zipPath );
            $plugins_arry = maybe_serialize( $getDwnurls );
            update_option( "dpwap_downloads_url", $plugins_arry );
        }
        else{ 
            $plugins_arry[] = $zipPath;
            update_option( "dpwap_downloads_url", $plugins_arry );
        }

        $rlpath = DPWAP_PLUGINS_TEMP.'/'.$folderpath;
        $root_path = realpath( $folder_path );
        $zip = new ZipArchive();
        $zip->open( $rlpath.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE );

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $root_path ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach( $files as $name => $file ) {
            if( !$file->isDir() ) {
                $file_path	   = $file->getRealPath();
                $relative_path = substr( $file_path, strlen( $root_path ) + 1 );
                $zip->addFile( $file_path, $relative_path );
            }
        }
        $zip->close();
    }

    public function dpwap_plugin_multiple_download_action(){
        if ( !current_user_can( 'install_plugins' ) ) return;

        global $pagenow;
        if( $pagenow == 'plugins.php' && isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'multiple_download' ) {
            $dpwap_plugins = maybe_unserialize( get_option( 'dpwap_downloads_url' ) );
            if( !empty( $dpwap_plugins ) ) {
                foreach( $dpwap_plugins as $pluginUrl ) {
                    $downUrl = site_url()."/wp-content/uploads/dpwap_plugins/".$pluginUrl;?>
                    <script language="javascript" type="text/javascript">
                        jQuery(document).ready(function(){
                            var iframe = document.createElement('iframe');
                            iframe.src = "<?php echo esc_url($downUrl); ?>";
                            iframe.style.display = 'none';
                            document.body.appendChild(iframe);
                        });
                    </script><?php
                }
            }
            delete_option( "dpwap_downloads_url" );
        }
    }

    public function dpwap_multiple_upload_admin_func(){
        if ( !current_user_can( 'install_plugins' ) ) return;
        global $pagenow;
        if( $pagenow == 'plugin-install.php' ) {
            $plugin_setting_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'dpwap_setting.php';
            require_once $plugin_setting_file;
            $redirect = admin_url( 'admin.php?page=mul_upload' );
            echo '<div class="wrap" id="btn_upload">
            <a id="mul_upload" class="page-title-action show" href="'.esc_url($redirect).'">
            <span class="upload">'.esc_html__( 'Upload Multiple Plugins', 'download-plugin') .'</span></a>
            </div>';
        }
    }

    public function dpwap_plugin_activate_func() {
        if( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'dpwap_single_plugin_activate' ) && current_user_can( 'install_plugins' ) ) {
            $waplugin = sanitize_text_field( $_POST['dpwap_url'] );
            $waplugins = get_option( 'active_plugins' );
            if( $waplugins ) {
                if ( !in_array( $waplugin, $waplugins ) ) {
                    array_push( $waplugins,$waplugin );
                    update_option( 'active_plugins',$waplugins );
                }
            }
        }
    }

    // public function dpwap_plugin_feature_select_func() {
    //     $waplugin = sanitize_text_field( $_POST['dpwap_feature'] );
    //     foreach ( $waplugin as $value ) {
    //         if( $value == 1 ){ 
    //             $feature1 = 1;
    //         }
    //         elseif ( ( $value >= 2 ) && ( $value <= 8 ) ) {
    //             $feature2 = 1;
    //         }
    //         elseif ( ( $value >= 9 ) && ( $value <= 15 ) ) {
    //             $feature3 = 1;
    //         }
    //         else{
    //             $feature4 = 1;
    //         }
    //     }
    //     $feature_file = DPWAP_DIR.DS.'app'.DS.'Plugins'.DS.'templates'.DS.'dpwap-add-feature.php';
    //     require_once $feature_file;
    //     wp_die();
    // }
}