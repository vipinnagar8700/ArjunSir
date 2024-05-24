<?php
/**
 * The admin settings page common utility functionalities.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Admin_Utils')):

class THWEC_Admin_Utils {
	const WPML_CONTEXT = 'woocommerce-email-customizer-pro';

	public static function prepare_template_file_name($display_name){
		$name = strtolower($display_name);
		$name = preg_replace('/\s+/', '_', $name);
		return $name;
	}

	public static function woo_version_check( $version = '3.0' ) {
	  	if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
		  		return true;
			}
	  	}
	  	return false;
	}

	public static function woo_emogrifier_version_check( $version = '3.6' ) {
	  	if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">" ) ) {
		  		return true;
			}
	  	}
	  	return false;
	}

	public static function is_json_decode($data){
		$json_data = json_decode($data);
		$json_data = json_last_error() == JSON_ERROR_NONE ?  $json_data : false;
		return $json_data;
	}

	public static function get_logged_in_user_email(){
		$email = '';
	   	$current_user = wp_get_current_user();
		if( $current_user !== 0 ){
			$email =  $current_user->user_email;
		}
		return $email;
	}

	public static function get_ot_td_css( $json=false ){
		$content = array("details_color"=>"#636363","details_text_align"=>"left","details_font_size"=>"14px","details_line_height"=>"100%","details_font_weight"=>"","details_font_family"=>"helvetica","content_p_t"=>"12px","content_p_r"=>"12px","content_p_b"=>"12px","content_p_l"=>"12px","content_border_color"=>"#e5e5e5");
		return $json ? json_encode( $content ) : $content;
	}

	public static function wpml_register_string($id, $subject ){
		$name = 'WEC Subject'." - ".$id;
		
		if(function_exists('icl_register_string')){
			icl_register_string(self::WPML_CONTEXT, $name, $subject);
		}
	}
	
	public static function wpml_unregister_string($name){
		if(function_exists('icl_unregister_string')){
			icl_unregister_string(self::WPML_CONTEXT, $name);
		}
	}

}

endif;