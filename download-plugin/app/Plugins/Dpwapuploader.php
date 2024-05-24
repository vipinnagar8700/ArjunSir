<?php
namespace DPWAP\Plugins;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use Plugin_Upgrader;
use Plugin_Installer_Skin;

class Dpwapuploader
{
    var $plugin_url;
    var $key;
   
    public function __construct(){
        $this->plugin_url = trailingslashit( get_bloginfo( 'wpurl' ) ) . PLUGINDIR . '/' . dirname( plugin_basename( __FILE__ ) );
        $this->key = 'dpwapuploader';
    }
	  
	// download the plugin handler form the wordpress org
    public function dpwap_plugin_handle_download( $plugin_name, $package, $dpwap_action, $whform, $dpid ) {
        if ( !current_user_can( 'install_plugins' ) ) return;

        global $wp_version;
        if ( !isset( $type ) ) {
            $type = '';
        }
        if ( !isset( $title ) ) {
            $title ='';
        }
        if ( !isset( $url ) ) {
            $url = '';
        }
        if ( !isset( $nonce ) ) {
            $nonce = '';
        }
        if ( version_compare( $wp_version, '3.0', '<' ) ) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';  
            $upgrader = new Plugin_Upgrader();
            $upgrader->install( $package );
        }			
        else{
		    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		    $upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'type', 'title', 'nonce', 'url' ) ) ); 
		    $secPlugin = wp_create_nonce('dpwap_single_plugin_activate');
		    echo "<div class='".esc_attr('dpwap_inner '.$whform)."' id='".esc_attr('dpwap_sec_'.$dpid)."' data-plugin_sec='".esc_attr($secPlugin)."'>";
		    echo "<h4>".esc_html(basename( $package ) )."</h4>";
		    $res = $upgrader->install( $package );
		    if($res){
                echo "<div id='activate_yes'></div>";
            }
		    update_option( "dpwap_plugins", $dpid );
		    echo '<input type="hidden" name="dpid" value="'.esc_attr($dpid).'">';
		    echo '<input type="hidden" id="dpwap_plglist" name="'.esc_attr('dpwap_plglist_'.$dpid).'" value="'.esc_attr($upgrader->plugin_info()).'">';
			//remove temp files
			if ( $whform == "upload_locFiles" ) {
				@unlink( $package );
			}
			
			if ( !$upgrader->plugin_info() ) {
				echo esc_html($res);
			} 
            elseif ( $dpwap_action == "package_activate" ) {
			 	$waplugins = get_option( 'active_plugins' );
			 	if ( $waplugins ) {
			 		$puginsToActiv = array( $upgrader->plugin_info() );
			 		foreach ( $puginsToActiv as $waplugin ) {
			 			if ( !in_array( $waplugin, $waplugins ) ) {
			 			    array_push( $waplugins,$waplugin );
			 				update_option( 'active_plugins', $waplugins );
			 			}
			 		}
			 	}
                echo '<b class="mpi_act">'.esc_html__( 'Plugin activated successfully.', 'download-plugin' ).'</b><br/>';
			}
		    echo "</div>";
        }
    }
	
	public function dpwap_plugin_all_activate(){ 
	    $dpwapNum = get_option( "dpwap_plugins" );
	    for ( $i = 1; $i <= $dpwapNum; $i++ ) {
	 	    $waplugin = sanitize_text_field($_POST["dpwap_plglist_$i"]);
	 	    $waplugins = get_option( 'active_plugins' );
            if ( $waplugins ) {
		        if ( !in_array( $waplugin, $waplugins ) ) {
			        array_push( $waplugins,$waplugin );
			        update_option( 'active_plugins', $waplugins );
		        }
		        if(!empty($waplugin)){
		            $plgname = explode("/",$waplugin);
                    echo "<b>".esc_html($plgname[0]).".zip</b><br>";
                    echo '<p>'.esc_html__( 'Unpacking the package...', 'download-plugin' ).'</p>';
                    echo '<p>'.esc_html__( 'Installing the plugin...', 'download-plugin' ).'</p>';
                    echo '<p>'.esc_html__( 'Plugin installed sucessfully.', 'download-plugin' ).'</p>';
                    echo "<hr>";
		        }
            }
	    } 
    }
	// get plugin information	
    public function dpwap_get_plugin($plugin_name){
        $name = $plugin_name;
        $plugin = $plugin_name;
        $description = $author = '';
        $version = '0.1';
        $plugin_file = "$name.php";
        
        return array(
        	'Name' => $name, 
        	'Title' => $plugin, 
        	'Description' => $description, 
        	'Author' => $author, 
        	'Version' => $version
        );
    }
	
	public function dpwap_create_file($plugins_arr, $dpwap_cfilenm){
		if( $plugins_arr ){
			$dpwap_filetxt = "";
			foreach( $plugins_arr as $dpwap_plugin ) {
				$dpwap_filetxt .= $dpwap_plugin.",";
			}
			$dpwap_filetxt = substr( $dpwap_filetxt, 0, -1 );
			if( $dpwap_cfilenm ) {
				$dpwap_flnm = $dpwap_cfilenm.'_'.time().".mpi";
				$dpwap_file = DPWAPUPLOADDIR_PATH.'/dpwap_logs/files/'.$dpwap_flnm;
			}
			else{
				$dpwap_flnm = "dpwap_".time().".mpi";
				$dpwap_file = DPWAPUPLOADDIR_PATH.'/dpwap_logs/files/'.$dpwap_flnm;
			}
			$dpwap_handle = fopen( $dpwap_file, 'w+' ) or die( esc_html__('Cannot open file: ', 'download-plugin') . esc_html($dpwap_file) );
			fwrite( $dpwap_handle, $dpwap_filetxt );
			fclose( $dpwap_handle );
		}
	}
    
    public function dpwap_get_packages( $plugins_arr, $dpwap_action, $dpwap_cfilenm, $whform ) {
        global $wp_version;
        if ( !function_exists( 'fsockopen' ) ) return false;
        $dpid = 1;
        foreach ( $plugins_arr as $val ) {
            $val = trim( $val );
          	$tmp = explode( '.', $val );
			$file_extension = end( $tmp );
            if ( $file_extension == 'zip' ){
               $this->dpwap_plugin_handle_download( "temp", $val, $dpwap_action, $whform, $dpid );
            }
            else {
                $plugins[plugin_basename( $val . ".php" )] = $this->dpwap_get_plugin( $val );
                $send = 1;
            }
            $dpid++;
        }
        //$plugins = mpi_get_plugins();
        if ( isset( $send ) ) {
            $to_send = new stdClass();
            $to_send->plugins = $plugins;
            $send = serialize( $to_send );
            $request = 'plugins=' . urlencode( $send );
            $http_request = "POST /plugins/update-check/1.0/ HTTP/1.0\r\n";
            $http_request .= "Host: api.wordpress.org\r\n";
            $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option( 'blog_charset' ) . "\r\n";
            $http_request .= "Content-Length: " . strlen( $request ) . "\r\n";
            $http_request .= 'User-Agent: WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) . "\r\n";
            $http_request .= "\r\n";
            $http_request .= $request;            
            //echo $http_request."<br><br>";
            $response = '';
            if ( false !== ( $fs = @fsockopen( 'api.wordpress.org', 80, $errno, $errstr, 3 ) ) && is_resource( $fs ) ) {
                fwrite( $fs, $http_request );
                while ( !feof( $fs ) ) {
                    // One TCP-IP packet
                    $response .= fgets( $fs, 1160 );
                }
                fclose( $fs );
                //echo $response;
                $response = explode( "\r\n\r\n", $response, 2 );
            }            
            $response = unserialize( $response[1] );
            $i = 0;
            foreach ( $plugins_arr as $val ) {
                ++$i;
                if ( $plugins[plugin_basename( "$val.php" )] ) {
                    if ( $response ){
                        $r = $response[plugin_basename( "$val.php" )];
                        if ( !$r ){
                            echo '<p class="not-found">' . $i . '. <strong>' . esc_html($val) . '</strong> '.esc_html__( 'not found. Try', 'download-plugin' ).' <a href="http://google.com/search?q=' . esc_html($val) . ' +wordpress">'.esc_html__( 'manual', 'download-plugin').'</a> '.esc_html__( 'install', 'download-plugin').'.</p>';
                        } 
                        elseif ($r->package) {
                            $this->_dpwapflush("<p class=\"found\">$i. ".esc_html__('Found', 'download-plugin')." <strong>" .esc_html($val). "</strong> ($r->slug, version $r->new_version). ".esc_html__( 'Processing installation...', 'download-plugin')."</strong></p>");
                            $this->dpwap_plugin_handle_download($r->slug,$r->package,$dpwap_action,$whform);
							$dpwap_fileArr[] = $r->slug;
                        } 
                        else{	
                           echo '<p class="not-found">' . $i . esc_html__( 'Package for', 'download-plugin').' <strong><em>' . esc_html($val) . '</em></strong> '.esc_html__( 'not found. Try', 'download-plugin').' <a href="' . esc_url($r->url) . '">'._( 'manual', 'download-plugin').'</a> '.esc_html__( 'install', 'download-plugin').'.</p>';
                        }
                    }
                    else{
                        echo '<p class="not-found">' . $i . '. <strong>' . esc_html($val) . '</strong> '.esc_html__( 'not found. Try', 'download-plugin').' <a href="http://google.com/search?q=' . esc_html($val) . ' +wordpress">'.esc_html__( 'manual', 'download-plugin').'</a> '.esc_html__( 'install', 'download-plugin') .'.</p>';
                    }
                }
            }
			if ( $dpwap_cfilenm != "nocreate" && $dpwap_fileArr > 0 ) {
				$this->dpwap_create_file( $dpwap_fileArr, $dpwap_cfilenm );
			}
        }
    }
	
	public function dpwap_copy_directory( $source, $destination ) {
		if ( is_dir( $source ) ) {
			@mkdir( $destination );
			$directory = dir( $source );
			while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
				if ( $readdirectory == '.' || $readdirectory == '..' ) {
					continue;
				}
				$PathDir = $source . '/' . $readdirectory; 
				if ( is_dir( $PathDir ) ) {
					$this->dpwap_copy_directory( $PathDir, $destination . '/' . $readdirectory );
					continue;
				}
				@copy( $PathDir, $destination . '/' . $readdirectory );
			}
			$directory->close();
		}else {
			@copy( $source, $destination );
		}
	}
	
	public function dpwap_delete_directory($path){
		if ( is_dir( $path ) === true ) {
			$files = array_diff( scandir( $path ), array( '.', '..' ) );
			foreach ( $files as $file ) {
				$this->dpwap_delete_directory( realpath( $path ) . '/' . $file );
			}
			return @rmdir( $path );
		}
		else if ( is_file( $path ) === true ) {
			return @unlink( $path );
		}
		return false;
	}
	
	public function dpwap_getWP_maxupload_filesize(){
		$upload_size_unit = $max_upload_size = wp_max_upload_size();
		$sizes = array( 'KB', 'MB', 'GB' );
		for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
			$upload_size_unit /= 1024;
		}
		if( $u < 0 ) {
			$upload_size_unit = 0;
			$u = 0;
		} else {
			$upload_size_unit = (int) $upload_size_unit;
		}
		printf( esc_html__( 'Maximum upload file size: %d%s.', 'download-plugin' ), esc_html($upload_size_unit), esc_html($sizes[$u]) );
	}

    public function _dpwapflush($s){
        echo $s;
        flush();
    }
	
	public function dpwap_app_DirTesting(){
		if ( !is_dir( DPWAPUPLOADDIR_PATH.'/dpwap_testing' ) ) { 
			if ( !is_dir( DPWAPUPLOADDIR_PATH.'/dpwap_logs/files/tmp' ) ) {
			    $temp_upload_dir = DPWAPUPLOADDIR_PATH . '/dpwap_logs/files/tmp';
                @wp_mkdir_p( $temp_upload_dir, 0777 );
			}
			if ( @mkdir( DPWAPUPLOADDIR_PATH.'/dpwap_testing', 0777 ) ) {
				@rmdir ( DPWAPUPLOADDIR_PATH.'/dpwap_testing' );
				return true;
			}
			else
			return false;
		}
	}	
		
	public function dpwap_plugin_locInstall(){
        // Increase the resources
        @ini_set( 'memory_limit', '1024M' );
        @ini_set( 'upload_max_filesize', '640M' );
        @ini_set( 'post_max_size', '640M' );

        check_admin_referer( $this->key );
        echo '<div class="dpwap_h3">'.esc_html__( 'Installing Plugins', 'download-plugin' ) .':</div>';
        for ( $i = 0; $i < count( $_FILES['dpwap_locFiles']['name'] ); $i++ ) {
            $dpwap_locFilenm = sanitize_file_name($_FILES['dpwap_locFiles']['name'][$i]);
            if ( strpos( $dpwap_locFilenm, 'mpipluginsbackup' ) === false ) {
                //Get the temp file path
                $tmpFilePath = $_FILES['dpwap_locFiles']['tmp_name'][$i];
                //Make sure we have a filepath
                if ( $tmpFilePath != "" ) {
                    //Setup our new file path
                    $newFilePath = DPWAPUPLOADDIR_PATH.'/dpwap_logs/files/tmp/' . $dpwap_locFilenm;
                    //Upload the file into the temp dir
                    if( @move_uploaded_file( $tmpFilePath, $newFilePath ) ) {
                        $dpwap_tempurls[] = DPWAPUPLOADDIR_PATH.'/dpwap_logs/files/tmp/'.$dpwap_locFilenm;
                    }
                }
            }
            else{
                echo esc_html__('This is', 'download-plugin') .' <b>'.esc_html($dpwap_locFilenm).'</b> '.esc_html__( 'not a valid zip archive.', 'download-plugin' );
            }
        }
        if( $dpwap_tempurls )
        $this->dpwap_get_packages( $dpwap_tempurls, "activate", "nocreate", "upload_locFiles" );
	}
}?>