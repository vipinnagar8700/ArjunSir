<?php
/**
 * The admin general settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Admin_Settings_General')):

class THWEC_Admin_Settings_General extends THWEC_Admin_Settings {
	protected static $_instance = null;
	private $tbuilder = null;
	private $woo_method_variables = array();
	private $wcfe_pattern = '';
	private $wecm_custom_hook = '';
	private $wecm_order_table_head = '';
	private $temp_wrapper_styles = '';
	private $link_pkaceholders = array();
	private $display_name = '';
	private $file_name = '';
	private $template_lang = null;
	private $default_lang = null;
	private $update_template = false;


	public function __construct() {
		parent::__construct('general_settings', '');
		$this->tbuilder = THWEC_Admin_Settings_Builder::instance();

		add_action('wp_ajax_thwec_save_email_template', array($this,'save_email_template'));
		add_action('wp_ajax_nopriv_thwec_save_email_template', array($this,'save_email_template'));

		$this->init_constants();
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function init_constants(){
		$this->woo_method_variables = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_phone',
			'billing_email',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_country',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode'
		);
		$this->wcfe_pattern = '\[(\[?)(WCFE)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)'; 

		$this->wecm_custom_hook = '\[(\[?)(WECM_CUSTOM_HOOK)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)'; 
	
		$this->wecm_order_table_head = '\[(\[?)(WECM_ORDER_T_HEAD)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';

		$this->wecm_order_table_td = '\[(\[?)(WECM_ORDER_TD_CSS)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';

		$this->temp_wrapper_styles = array('bg' => '#f7f7f7', 'padding' => '70px 0');

		$this->link_pkaceholders = array( '{account_area_url}', '{account_order_url}');
		
	}

	public function render_page(){
		$this->render_sections();
		$this->render_content();
	}
	
	private function render_content(){
		$this->tbuilder->render_template_customizer($_POST);
    }

    public function get_th_shortcode_atts_regex() {
		return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
	}

	public function generate_template_file($content, $path, $css=false){
		if( ! is_dir(THWEC_Utils::get_template_directory()) ){
			wp_mkdir_p(THWEC_Utils::get_template_directory());
		}
		$saved = false;
		$myfile_template = fopen($path, "w") or die("Unable to open file!");
		if(false !== $myfile_template){
			fwrite($myfile_template, $content);
			fclose($myfile_template);
			$saved = true; 
		}
		return $saved;
	}

	public function style_inline( $content, $css ) {
		$emogrifier_support = class_exists( 'DOMDocument' ) && version_compare( PHP_VERSION, '5.5', '>=' );
		if ( $content && $css && $emogrifier_support) {
			$emogrifier_class = '\\Pelago\\Emogrifier';
			$emogrifier_class = THWEC_Admin_Utils::woo_emogrifier_version_check() ? '\\Pelago\\Emogrifier' : 'Emogrifier';
			if ( ! class_exists( $emogrifier_class ) ) {
				require_once(WP_PLUGIN_DIR.'/woocommerce/includes/libraries/class-emogrifier.php');
			}
			try {
				$emogrifier = new $emogrifier_class( $content, $css );
				$content    = $emogrifier->emogrify();
				$content    = htmlspecialchars_decode($content);
			} catch ( Exception $e ) {
			}
		}
		return $content;
	}

	public function get_template_meta( $key, $return_name=false ){
		$meta = array();
		$lang = false;
		if( $key == 'wpml-default' ){
			$file = $this->def_lang_template_name();

		}else if( $key == 'wpml-lang' ){
			$file = $this->get_wpml_template_name();

		}else if( $key == 'default' ){
			$file = $this->file_name;
		}
		$meta['name'] = $file;
		$meta['path'] = THWEC_CUSTOM_TEMPLATE_PATH.$file.'.php';
		return $meta;
	}

	public function is_default_lang_template(){
		if( $this->template_lang == $this->default_lang ){
			return true;
		}
		return false;
	}

	public function get_wpml_template_name(){
		return $this->file_name.'-'.$this->template_lang;
	}

	public function def_lang_template_name(){
		return $this->file_name.'-'.$this->default_lang;
	}

	public function has_template_in_def_lang( $settings ){
		$def_lang_template = $this->def_lang_template_name();
		return array_key_exists( $def_lang_template, $settings['templates'] );
	}

	public function prepare_email_content_wrapper($content){
		$wrap_css_arr = apply_filters('thwec_template_wrapper_style_override', $this->temp_wrapper_styles);
		$wrap_css = 'background-color:'.$wrap_css_arr['bg'].';'.'padding:'.$wrap_css_arr['padding'].';';
		$wrapper = '<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="thwec_template_wrapper">';
		$wrapper .= '<tr>';
		$wrapper .= '<td align="center" class="thwec-template-wrapper-column" valign="top" style="'.$wrap_css.'">';
		$wrapper .= '<div id="thwec_template_container">';
		$wrapper .= $content;
		$wrapper .= '</div>';
		$wrapper .= '</td>';
		$wrapper .= '</tr>';
		$wrapper .= '</table>';									
		return $wrapper;
	}

	public function prepare_template_test_mail($posted){
		$test_mail_content = isset($posted['test_mail_data']) ? stripslashes($posted['test_mail_data']) : '';
		$test_mail_content = $this->replace_woocommerce_hooks_contents($test_mail_content,'test-mail');
		$test_mail_content = $this->thwec_shortcode_callbacks($test_mail_content,false);
		$test_mail_css = isset($posted['test_mail_css']) ? stripslashes($posted['test_mail_css']) : '';
		$test_mail_add_css = isset($posted['test_mail_add_css']) ? sanitize_textarea_field($posted['test_mail_add_css']) : '';

		$test_mail_css = $test_mail_css.$test_mail_add_css;
		if( apply_filters( 'thwec_mobile_compatibility_wrapper_padding', true ) ){
			$test_mail_css .= '@media only screen and (max-width:480px) {
	  			#thwec_template_wrapper .thwec-template-wrapper-column{ padding: 0px !important;} 
	  		}';
		}
		$test_mail_id = $posted['test_mail_id'];
		$test_mail_content = $this->prepare_email_content_wrapper($test_mail_content);
		$email_content = $this->style_inline($test_mail_content, $test_mail_css);
		$email_subject = "[".get_bloginfo('name')."] Test Email";
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$send_mail = wp_mail($test_mail_id, $email_subject, $email_content, $headers);
		$response = $send_mail ? 'success' : 'failure';
		wp_send_json($response);
	}

	/*-------------------------------------------------------------------------------------------
	**------------------------------ TEMPLATE FILES AND SETTINGS --------------------------------
	**------------------------------------------------------------------------------------------*/

	public function save_template_file_name($template_name,$posted){
		$template_name = THWEC_Admin_Utils::prepare_template_name($template_name);
		$data = $this->prepare_settings($template_name, $posted, $settings);
		$result = THWEC_Utils::save_template_settings($data);
		return $result;
	}

	public function save_email_template(){
		if(isset($_POST['thwec_action_index']) && $_POST['thwec_action_index'] == 'name'){
			$this->save_template_file_name($_POST['template_name'],$_POST);

		}else if(isset($_POST['thwec_action_index']) && $_POST['thwec_action_index'] == 'settings'){
			if(isset($_POST['template_edit_data'])){			
				$this->initiate_save_template( $_POST );
			}else if(isset($_POST['test_mail_data'])){
				$this->prepare_template_test_mail($_POST);
			}
		}
		
	}

	public function setup_template_variables( $posted ){
		$this->template_lang = isset( $posted['template_lang'] ) ? sanitize_text_field( $posted['template_lang'] ): '';
		if( THWEC_Utils::is_wpml_active() ){
			$this->default_lang = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_default_language', NULL ), true );
		}
		$this->display_name = isset( $posted['template_name'] ) ? sanitize_text_field( $posted['template_name'] ) : '';
		$this->file_name = THWEC_Admin_Utils::prepare_template_file_name( $this->display_name );
	}

	public function initiate_save_template( $posted ){
		$save_meta = $save_files = true;
		$this->setup_template_variables( $posted );
		$tag = THWEC_Utils::is_wpml_active() ? 'wpml-lang' : 'default';
		$template_meta = $this->get_template_meta( $tag );
		$template_name = isset( $template_meta['name'] ) ? $template_meta['name'] : false;
		$template_path = isset( $template_meta['path'] ) ? $template_meta['path'] : false;
		
		if( $template_name && $template_path ){
			//Save template files
			$save_files = $this->save_template_files( $posted, $template_path );

			if($save_files ){
				//Save template meta data in DB
				$save_meta = $this->save_settings( $template_name, $posted );
			}

			if( $this->update_template ){
				$this->remove_non_wpml_template();
			}
		}
		
		return $save_files && $save_meta;
	}

	public function remove_non_wpml_template(){
		$delete_file = $delete_data = false;
		$file = THWEC_CUSTOM_TEMPLATE_PATH.$this->file_name.'.php';
		if( file_exists( $file ) ){
			$delete_file = unlink( $file );
		}
		$settings = THWEC_Utils::get_template_settings();
		if( isset( $settings['templates'][$this->file_name] ) ){
			unset($settings['templates'][$this->file_name]);
		}
		$saved = THWEC_Utils::save_template_settings( $settings );
		return $delete_file && $saved;

	}



	public function save_template_files( $posted, $path ){

		$content = isset($posted['template_render_data']) ? stripslashes($posted['template_render_data']) : '';
		$css = isset($posted['template_render_css']) ? stripslashes($posted['template_render_css']) : '';
		$additional_css = isset($posted['template_add_css']) ? sanitize_textarea_field($posted['template_add_css']) : '';
		$css = $css.$additional_css;
		
		$content = $this->prepare_email_content_wrapper($content);
		$content = $this->style_inline( $content, $css );
		$content = $this->insert_dynamic_data($content);
		$saved = $this->generate_template_file($content, $path);
		return $saved;
	}


	public function save_settings( $template_name, $posted ){
		$settings = THWEC_Utils::get_template_settings();
		$settings = $this->prepare_settings( $template_name, $posted, $settings);
		$result = THWEC_Utils::save_template_settings($settings);
		return $result;
	}

	public function prepare_settings( $template_name, $posted, $settings ){
		if( THWEC_Utils::is_wpml_active() ){
			//Create new template for translated template
			if( $this->is_default_lang_template() ){
				$data = $this->prepare_template_meta_data( $template_name, $posted );
				$this->update_template = $template_name;
			}else{
				if( ! $this->has_template_in_def_lang( $settings ) ){
					// If user first creates a template in secondary language, template data is created for default language inorder to prevent further issues.
					$def_lang_data = $this->prepare_template_meta_data( false, $posted, true, $this->default_lang );
					$settings['templates'][$this->def_lang_template_name()] = $def_lang_data;
					$settings = $this->update_wpml_template_map( $this->def_lang_template_name(), $settings);
					$this->update_template = $template_name;
				}
				$data = $this->prepare_template_meta_data( $template_name, $posted );
			}
			$settings = $this->update_wpml_template_map( $template_name, $settings);
		}else{
			$data = $this->prepare_template_meta_data( $template_name, $posted, false );
		}
		$settings['templates'][$template_name] = $data;
		return $settings;
	}

	public function prepare_template_meta_data( $template_name, $posted, $wpml_lang=true, $lang_code=false ){
		$file_name = $template_name ? $template_name.'.php' : $this->def_lang_template_name().'.php';
		$json = isset($posted['template_json_tree']) ? stripslashes($posted['template_json_tree']) : '';
		$css = isset($posted['template_add_css']) ? sanitize_textarea_field($posted['template_add_css']) : '';
		$data = array();
		$data['file_name'] = $file_name;
		$data['display_name'] = $this->display_name;
		$data['template_data'] = $json;
		$data['additional_css'] = $css;
		if( $wpml_lang  ){
			$data['base'] = $this->file_name;
			$data['lang'] = $lang_code ? $lang_code : $this->template_lang;
		}
		return $data;
	}

	public function update_wpml_template_map( $template_name, $settings ){
		$wpml_map = isset( $settings[THWEC_Utils::wpml_map_key()] ) ? $settings[THWEC_Utils::wpml_map_key()] : array();
		$wpml_map[$template_name] = $this->file_name;
		$settings[THWEC_Utils::wpml_map_key()] = $wpml_map;
		return $settings;
	}



	/*-------------------------------------------------------------------------------------------
	**------------------------------ DYNAMIC DATA AND PLACEHOLDERS-------------------------------
	**------------------------------------------------------------------------------------------*/

	public function insert_dynamic_data($modified_data){

		/*-----------------------Placeholder Replacements ------------------------------*/
		$modified_data = $this->replace_thwec_placeholder_data($modified_data);
		/*-----------------------Address Replacements ------------------------------*/
		$modified_data = str_replace('<span>{billing_address}</span>', $this->billing_data(), $modified_data);
		$modified_data = str_replace('<span>{thwec_before_shipping_address}</span>', $this->shipping_data_additional(true), $modified_data);
		$modified_data = str_replace('<span>{thwec_after_shipping_address}</span>', $this->shipping_data_additional(false), $modified_data);
		$modified_data = str_replace('<span>{shipping_address}</span>', $this->shipping_data(), $modified_data);
		$modified_data = str_replace('<span>{customer_address}</span>', $this->customer_data(), $modified_data);
		$modified_data = str_replace('<span class="before_customer_table"></span>', $this->add_order_head(), $modified_data);
		$modified_data = str_replace('<span class="after_customer_table"></span>', $this->add_order_foot(), $modified_data);
		$modified_data = str_replace('<span class="before_shipping_table"></span>', $this->add_order_head(), $modified_data);
		$modified_data = str_replace('<span class="after_shipping_table"></span>', $this->add_order_foot(), $modified_data);
		$modified_data = str_replace('<span class="before_billing_table"></span>', $this->add_order_head(), $modified_data);
		$modified_data = str_replace('<span class="after_billing_table"></span>', $this->add_order_foot(), $modified_data);
		
		/*-----------------------Order Table Replacements ------------------------------*/

		$modified_data = str_replace('<span class="loop_start_before_order_table"></span>', $this->order_table_before_loop(), $modified_data); //woocommerce_email_before_order_table 
		$modified_data = str_replace('<span class="loop_end_after_order_table"></span>', $this->order_table_after_loop(), $modified_data); //woocommerce_email_before_order_table 
		$modified_data = str_replace('<span class="woocommerce_email_before_order_table"></span>', $this->order_table_before_hook(), $modified_data); //woocommerce_email_before_order_table 
		$modified_data = str_replace('{Order_Product}', $this->order_table_header_product(), $modified_data); //first row content
		$modified_data = str_replace('{Order_Quantity}', $this->order_table_header_qty(), $modified_data); //first row content
		$modified_data = str_replace('{Order_Price}', $this->order_table_header_price(), $modified_data);//first row content
		$modified_data = str_replace('<tr class="item-loop-start"></tr>', $this->order_table_item_loop_start(), $modified_data); // product display loop start
		$modified_data = str_replace('woocommerce_order_item_class-filter1', $this->order_table_class_filter(), $modified_data); // woocommerce filter as class for a <td>
		$modified_data = str_replace('{order_items}', $this->order_table_items(), $modified_data); // Code to display  items without image
		$modified_data = str_replace('{order_items_img}', $this->order_table_items(true), $modified_data); // Code to display  items along with image
		$modified_data = str_replace('{order_items_qty}', $this->order_table_items_qty(), $modified_data);// Code to display  item quantity
		$modified_data = str_replace('{order_items_price}', $this->order_table_items_price(), $modified_data); // Code to display  item price
		$modified_data = str_replace('<tr class="item-loop-end"></tr>',$this->order_table_item_loop_end(), $modified_data);  // product display loop end
		$modified_data = str_replace('<tr class="order-total-loop-start"></tr>', $this->order_table_total_loop_start(), $modified_data); //totals display loop start
		$modified_data = str_replace('{total_label}', $this->order_table_total_labels(), $modified_data); // Code to display <tfoot> total labels
		$modified_data = str_replace('{total_value}', $this->order_table_total_values(), $modified_data); // Code to display <tfoot> total values
		// $modified_data = str_replace('<tr class="order-total-loop-end"></tr>', $this->order_table_total_loop_end(), $modified_data); // totals display loop start

		/*----------------------- Woocommerce Email Hooks ------------------------------*/
		$modified_data = $this->replace_woocommerce_hooks_contents($modified_data);
		
		/*---------------- Checkout fields in email at any position -----------------*/ 
		$modified_data = $this->thwec_shortcode_callbacks($modified_data);
		return $modified_data;
	}

	public function replace_thwec_placeholder_data($modified_data){
		$modified_data = str_replace('{customer_name}', $this->get_customer_name(), $modified_data);
		$modified_data = str_replace('{customer_full_name}', $this->get_customer_full_name(), $modified_data);
		$modified_data = str_replace('{user_email}', $this->get_user_email(), $modified_data);
		//Site Related
		$modified_data = str_replace('{site_url}', $this->get_site_url(), $modified_data);
		$modified_data = str_replace('{site_name}', $this->get_site_name(), $modified_data);

		// Order Related
		$modified_data = str_replace('{order_id}', $this->get_order_id(), $modified_data);
		$modified_data = str_replace('{order_number}', $this->get_order_number(), $modified_data); //Filtered order id by 3rd party plugins
		$modified_data = str_replace('{order_url}', $this->get_order_url(), $modified_data);
		$modified_data = str_replace('{order_completed_date}', $this->get_order_completed_date(), $modified_data);
		$modified_data = str_replace('{order_created_date}', $this->get_order_created_date(), $modified_data);
		
		$modified_data = str_replace('{order_total}', $this->get_order_total(), $modified_data);
		$modified_data = str_replace('{order_formatted_total}', $this->get_order_formatted_total(), $modified_data);

		//Billing
		$modified_data = str_replace('{billing_first_name}', $this->get_default_woocommerce_method('billing_first_name', true), $modified_data);
		$modified_data = str_replace('{billing_last_name}', $this->get_default_woocommerce_method('billing_last_name', true), $modified_data);
		$modified_data = str_replace('{billing_company}', $this->get_default_woocommerce_method('billing_company', true), $modified_data);
		$modified_data = str_replace('{billing_country}', $this->get_default_woocommerce_method('billing_country', true), $modified_data);
		$modified_data = str_replace('{billing_address_1}', $this->get_default_woocommerce_method('billing_address_1', true), $modified_data);
		$modified_data = str_replace('{billing_address_2}', $this->get_default_woocommerce_method('billing_address_2', true), $modified_data);
		$modified_data = str_replace('{billing_city}', $this->get_default_woocommerce_method('billing_city', true), $modified_data);
		$modified_data = str_replace('{billing_state}', $this->get_default_woocommerce_method('billing_state', true), $modified_data);
		$modified_data = str_replace('{billing_postcode}', $this->get_default_woocommerce_method('billing_postcode', true), $modified_data);
		$modified_data = str_replace('{billing_phone}', $this->get_default_woocommerce_method('billing_phone', true), $modified_data);
		$modified_data = str_replace('{billing_email}', $this->get_default_woocommerce_method('billing_email', true), $modified_data);
		
		// Shipping
		$modified_data = str_replace('{shipping_method}', $this->get_shipping_method(), $modified_data);
		$modified_data = str_replace('{shipping_first_name}', $this->get_default_woocommerce_method('shipping_first_name', true), $modified_data);
		$modified_data = str_replace('{shipping_last_name}', $this->get_default_woocommerce_method('shipping_last_name', true), $modified_data);
		$modified_data = str_replace('{shipping_company}', $this->get_default_woocommerce_method('shipping_company', true), $modified_data);
		$modified_data = str_replace('{shipping_country}', $this->get_default_woocommerce_method('shipping_country', true), $modified_data);
		$modified_data = str_replace('{shipping_address_1}', $this->get_default_woocommerce_method('shipping_address_1', true), $modified_data);
		$modified_data = str_replace('{shipping_address_2}', $this->get_default_woocommerce_method('shipping_address_2', true), $modified_data);
		$modified_data = str_replace('{shipping_city}', $this->get_default_woocommerce_method('shipping_city', true), $modified_data);
		$modified_data = str_replace('{shipping_state}', $this->get_default_woocommerce_method('shipping_state', true), $modified_data);
		$modified_data = str_replace('{shipping_postcode}', $this->get_default_woocommerce_method('shipping_postcode', true), $modified_data);
		
		//Misc
		$modified_data = str_replace('{checkout_payment_url}', $this->get_order_checkout_payment_url(), $modified_data);
		$modified_data = str_replace('{payment_method}', $this->get_order_payment_method(), $modified_data);
		$modified_data = str_replace('{customer_note}', $this->get_customer_note(), $modified_data);

		//Account Related
		$modified_data = str_replace('{user_login}', $this->get_user_login(), $modified_data);
		$modified_data = str_replace('{user_pass}', $this->get_user_pass(), $modified_data);
		$modified_data = str_replace('{account_area_url}', $this->get_account_area_url(), $modified_data);
		$modified_data = str_replace('{account_order_url}', $this->get_account_order_url(), $modified_data);
		$modified_data = str_replace('{reset_password_url}', $this->get_reset_password_url(), $modified_data);
		
		//Deprecated placholders
		$modified_data = str_replace('{th_customer_name}', $this->get_customer_name(), $modified_data);
		$modified_data = str_replace('{th_billing_phone}', $this->get_default_woocommerce_method('billing_phone', true), $modified_data);
		$modified_data = str_replace('{th_order_id}', $this->get_order_id(), $modified_data);
		$modified_data = str_replace('{th_order_url}', $this->get_order_url(), $modified_data);
		$modified_data = str_replace('{th_billing_email}', $this->get_default_woocommerce_method('billing_email', true), $modified_data);
		$modified_data = str_replace('{th_site_url}', $this->get_site_url(), $modified_data);
		$modified_data = str_replace('{th_site_name}', $this->get_site_name(), $modified_data);
		$modified_data = str_replace('{th_order_completed_date}', $this->get_order_completed_date(), $modified_data);
		$modified_data = str_replace('{th_order_created_date}', $this->get_order_created_date(), $modified_data);
		$modified_data = str_replace('{th_checkout_payment_url}', $this->get_order_checkout_payment_url(), $modified_data);
		$modified_data = str_replace('{th_payment_method}', $this->get_order_payment_method(), $modified_data);
		$modified_data = str_replace('{th_customer_note}', $this->get_customer_note(), $modified_data);
		$modified_data = str_replace('{th_user_login}', $this->get_user_login(), $modified_data);
		$modified_data = str_replace('{th_user_pass}', $this->get_user_pass(), $modified_data);
		$modified_data = str_replace('{th_account_area_url}', $this->get_account_area_url(), $modified_data);
		$modified_data = str_replace('{th_reset_password_url}', $this->get_reset_password_url(), $modified_data);

		return $modified_data;
	}

	public function replace_woocommerce_hooks_contents($modified_data,$mail_type=false){
		$modified_data = str_replace('<p class="hook-code">{email_header_hook}</p>', $this->thwec_email_hooks('{email_header_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{email_order_details_hook}</p>', $this->thwec_email_hooks('{email_order_details_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{before_order_table_hook}</p>', $this->thwec_email_hooks('{before_order_table_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{after_order_table_hook}</p>', $this->thwec_email_hooks('{after_order_table_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{order_meta_hook}</p>', $this->thwec_email_hooks('{order_meta_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{customer_details_hook}</p>', $this->thwec_email_hooks('{customer_details_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{email_footer_hook}</p>', $this->thwec_email_hooks('{email_footer_hook}'), $modified_data);
		$modified_data = str_replace('<p class="hook-code">{downloadable_product_table}</p>',$this->downloadable_product_table($mail_type), $modified_data);
		return $modified_data;
	}

	// SHORTCODE DYNAMIC DATA

	public function thwec_shortcode_callbacks($modified_data,$shortcode_show=true){
		if($shortcode_show){
			$modified_data = preg_replace_callback("/$this->wcfe_pattern/", array($this, "special_wcfe_meta_functions"),$modified_data);
		}
		$modified_data = preg_replace_callback("/$this->wecm_custom_hook/", array($this, "special_wecm_custom_hook_functions"),$modified_data);
		$modified_data = preg_replace_callback("/$this->wecm_order_table_head/", array($this, "special_wecm_order_table_head_functions"),$modified_data);
		$modified_data = preg_replace_callback("/$this->wecm_order_table_td/", array($this, "special_wecm_order_table_td_functions"),$modified_data);

		return $modified_data;
	}

	

	public function special_wecm_order_table_td_functions( $occurances ){
		$atts = array();
		if ( $occurances[1] == '[' && $occurances[6] == ']' ) {
			return substr($occurances[0], 1, -1);
		}
		$sec_pattern = $this->get_th_shortcode_atts_regex();
		$content = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $occurances[3]);
		if ( preg_match_all($sec_pattern, $content, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) && strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]) && strlen($m[8]))
					$atts[] = stripcslashes($m[8]);
				elseif (isset($m[9]))
					$atts[] = stripcslashes($m[9]);
			}
		}
		$replace_html = '';
		
		if($atts){
			$text = isset($atts['styles']) && !empty($atts['styles']) ? $atts['styles'] : false;
			$replace_html = $text ? $this->order_table_additional_td_css($text) : "";
		}
		return $replace_html;
	}

	public function special_wecm_order_table_head_functions($occurances){
		$atts = array();
		if ( $occurances[1] == '[' && $occurances[6] == ']' ) {
			return substr($occurances[0], 1, -1);
		}
		$sec_pattern = $this->get_th_shortcode_atts_regex();
		$content = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $occurances[3]);
		if ( preg_match_all($sec_pattern, $content, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) && strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]) && strlen($m[8]))
					$atts[] = stripcslashes($m[8]);
				elseif (isset($m[9]))
					$atts[] = stripcslashes($m[9]);
			}
		}
		$version = THWEC_Admin_Utils::woo_version_check() ? true : false;
		$replace_html = '';
		if($atts){
			$text = isset($atts['text']) && !empty($atts['text']) ? $atts['text'] : false;
			$replace_html = $text ? $this->order_table_head($text) : "";
		}
		return $replace_html;
	}

	public function special_wcfe_meta_functions($occurances){
		$atts = array();
		if ( $occurances[1] == '[' && $occurances[6] == ']' ) {
			return substr($occurances[0], 1, -1);
		}
		$sec_pattern = $this->get_th_shortcode_atts_regex();
		$content = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $occurances[3]);
		if ( preg_match_all($sec_pattern, $content, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) && strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]) && strlen($m[8]))
					$atts[] = stripcslashes($m[8]);
				elseif (isset($m[9]))
					$atts[] = stripcslashes($m[9]);
			}
		}
		$version = THWEC_Admin_Utils::woo_version_check() ? true : false;
		$replace_html = '';
		if($atts){
			$replace_html .= $this->set_order_checkout_fields($atts,$version);
			if($replace_html !==''){

				$content_bf = '<?php if(isset($order) && !empty($order)){';
				$content_bf.= '$order_id = $order->get_id();'; 
				$content_bf.= 'if(!empty($order_id)){'; 
				$content_af = '} } ?>';
				$replace_html = $content_bf.$replace_html.$content_af;
			}
		}
		return $replace_html;
	}

	public function special_wecm_custom_hook_functions($occurances){
		$atts = array();
		if ( $occurances[1] == '[' && $occurances[6] == ']' ) {
			return substr($occurances[0], 1, -1);
		}
		$sec_pattern = $this->get_th_shortcode_atts_regex();
		$content = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $occurances[3]);
		if ( preg_match_all($sec_pattern, $content, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) && strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]) && strlen($m[8]))
					$atts[] = stripcslashes($m[8]);
				elseif (isset($m[9]))
					$atts[] = stripcslashes($m[9]);
			}
		}
		$version = THWEC_Admin_Utils::woo_version_check() ? true : false;
		$replace_html = '';
		if($atts){
			$replace_html .= $this->set_wecm_custom_hooks($atts,$version);

		}
		return $replace_html;
	}

	public function set_wecm_custom_hooks($hook_data,$version){
		$html = '';
		$fname = isset($hook_data['name']) && !empty($hook_data['name']) ? $hook_data['name'] : false;
		if($fname){
			$html = '<?php $obj = isset( $order ) && is_a( $order, "WC_Order" ) ? $order : null;';
			$html .= 'do_action( \''.$fname.'\', $obj, $email ); ?>'; 
		}
		return $html;
	}

	public function set_order_checkout_fields($wcfe_data,$version){
		$html='';
		$flabel = '';
		$email_visible = '';
		$fvisibility = '';
		$fname = isset($wcfe_data['name']) && !empty($wcfe_data['name']) ? $wcfe_data['name'] : false;
		$flabel = isset($wcfe_data['label']) && !empty($wcfe_data['label']) ? '<b>'.trim($wcfe_data['label'],'"').'</b> : ' : '' ;
		$fvisibility = isset($wcfe_data['visibility']) && !empty($wcfe_data['visibility'])? trim($wcfe_data['visibility'],'"') : '' ;

		if($fname){
			if(in_array($fname, $this->woo_method_variables)){
				$html .= '$field_name = '.$this->get_default_woocommerce_method($fname);
			}else{
				if($version){
					$html .= '$field_name = get_post_meta($order->get_id(),\''.$fname.'\',true);';
				}else{
					$html .= '$field_name = get_post_meta($order->id,\''.$fname.'\',true);';
				}
				$html.= '$json_value = json_decode($field_name,true);';
				$html .= 'if($json_value){';
				$html.= 'if(isset($json_value["name"]) && !empty($json_value["name"]) && isset($json_value["url"])){';
				$html.= '$field_name = "<a href=\'".$json_value[\'url\']."\'>".$json_value[\'name\']."</a>";';
				$html.= '} }';
			}
			if($fvisibility == 'admin'){
				$email_visible = ' && $sent_to_admin';
			}else if($fvisibility == 'customer'){
				$email_visible = ' && !$sent_to_admin';
			}
			$html .= 'if(!empty($field_name)'.$email_visible.'){';
			$html .= '$field_html = "'.$flabel.'".$field_name;';
			$html .= 'echo $field_html;';
			$html .= '}';
		}
		return $html;
	}

	//DYNAMIC DATA CALLBACK FUNCTIONS

	public function get_default_woocommerce_method($f_name, $wrap=false){
		$method = '';
		switch ($f_name) {
			case 'billing_first_name':
				$method = '$order->get_billing_first_name();';
				break;
			case 'billing_last_name':
				$method = '$order->get_billing_last_name();';
				break;
			case 'billing_company':
				$method = '$order->get_billing_company();';
				break;
			case 'billing_country':
				$method = '$order->get_billing_country();';
				break;
			case 'billing_address_1':
				$method = '$order->get_billing_address_1();';
				break;
			case 'billing_address_2':
				$method = '$order->get_billing_address_2();';
				break;
			case 'billing_city':
				$method = '$order->get_billing_city();';
				break;
			case 'billing_state':
				$method = '$order->get_billing_state();';
				break;
			case 'billing_postcode':
				$method = '$order->get_billing_postcode();';
				break;
			case 'billing_phone':
				$method = '$order->get_billing_phone();';
				break;
			case 'billing_email':
				$method = '$order->get_billing_email();';
				break;

			case 'shipping_first_name':
				$method = '$order->get_shipping_first_name();';
				break;
			case 'shipping_last_name':
				$method = '$order->get_shipping_last_name();';
				break;
			case 'shipping_company':
				$method = '$order->get_shipping_company();';
				break;
			case 'shipping_country':
				$method = '$order->get_shipping_country();';
				break;
			case 'shipping_address_1':
				$method = '$order->get_shipping_address_1();';
				break;
			case 'shipping_address_2':
				$method = '$order->get_shipping_address_2();';
				break;
			case 'shipping_city':
				$method = '$order->get_shipping_city();';
				break;
			case 'shipping_state':
				$method = '$order->get_shipping_state();';
				break;
			case 'shipping_postcode':
				$method = '$order->get_shipping_postcode();';
				break;
			default:
				$method='';
				break;
		}
		if( $wrap && !empty( $method ) ){
			$check = str_replace( ';', '', $method);
			$method = '<?php if( isset( $order ) && '.$check.' ){ echo '.$method.'} ?>';
		}
		return $method;
	}

	public function get_order_id(){
		$order_id = '<?php if(isset($order)) : ?>';
		$order_id.= '<?php echo $order->get_id();?>';
		$order_id.= '<?php endif; ?>';
		return $order_id;
	}

	public function get_order_number(){
		$order_id = '<?php if(isset($order)) : ?>';
		$order_id.= '<?php echo $order->get_order_number();?>';
		$order_id.= '<?php endif; ?>';
		return $order_id;
	}

	public function get_order_url(){
		$order_url = '<?php if(isset($order) && $order->get_user()) : ?>';
		$order_url.= '<?php echo $order->get_view_order_url(); ?>';
		$order_url.= '<?php endif; ?>';
		return $order_url;
	}

	public function get_customer_name(){
		$customer_name = '<?php if(isset($order)) : ?>';
		$customer_name.= '<?php echo $order->get_billing_first_name(); ?>';
		$customer_name.= '<?php endif; ?>';
		return $customer_name;
	}

	public function get_customer_full_name(){
		$customer_name = '<?php if(isset($order)) : ?>';
		$customer_name.= '<?php echo $order->get_billing_first_name().\' \'.$order->get_billing_last_name(); ?>';
		$customer_name.= '<?php endif; ?>';
		return $customer_name;
	}

	public function get_user_email(){
		$user_email = '<?php $user = isset( $user_login ) ? get_user_by(\'login\', $user_login ) : false; ?>';
		$user_email.= '<?php echo $user && isset($user->user_email) ? $user->user_email : "";  ?>';
		return $user_email;
	}

	public function get_billing_email(){
		$billing_email = '<?php if ( isset($order) && $order->get_billing_email() ) : ?>';
		$billing_email.= '<?php echo esc_html( $order->get_billing_email() ); ?>';
		$billing_email.= '<?php endif; ?>';
		return $billing_email;
	}

	public function get_site_url(){
		$site_url = '<?php echo get_site_url();?>';
		return $site_url;
	}

	public function get_site_name(){
		$site_name = '<?php echo get_bloginfo();?>';
		return $site_name;
	}

	public function get_order_completed_date(){
		$order_date = '<?php if(isset($order) && $order->has_status( \'completed\' )):?>';
		$order_date.= '<?php echo wc_format_datetime($order->get_date_completed()); ?>';
		$order_date.= '<?php endif; ?>';
		return $order_date;
	}

	public function get_order_created_date(){
		$order_date = '<?php if(isset($order)) : ?>';
		$order_date.= '<?php echo wc_format_datetime($order->get_date_created()); ?>';
		$order_date.= '<?php endif; ?>';
		return $order_date;
	}

	public function get_order_total(){
		$order_total = '<?php if(isset($order)) : ?>';
		$order_total.= '<?php echo $order->get_total(); ?>';
		$order_total.= '<?php endif; ?>';
		return $order_total;
	}

	public function get_order_formatted_total(){
		$order_ftotal = '<?php if(isset($order)) : ?>';
		$order_ftotal.= '<?php echo $order->get_formatted_order_total(); ?>';
		$order_ftotal.= '<?php endif; ?>';
		return $order_ftotal;
	}
	public function get_order_subtotal(){
		$order_stotal = '<?php if(isset($order)) : ?>';
		$order_stotal.= '<?php echo $order->get_formatted_order_total(); ?>';
		$order_stotal.= '<?php endif; ?>';
		return $order_stotal;
	}

	public function get_shipping_method(){
		$shipping = '<?php if(isset($order)) : ?>';
		$shipping.= '<?php echo $order->get_shipping_method(); ?>';
		$shipping.= '<?php endif; ?>';
		return $shipping;
	}

	public function get_order_checkout_payment_url(){
		$checkout_payment_url = '<?php if ( isset($order) && $order->has_status( \'pending\' ) ) : ?>
		<?php
		printf(
			wp_kses(
				/* translators: %1s item is the name of the site, %2s is a html link */
				__( \'An order has been created for you on %s\', \'woocommerce\' ),
				array(
					\'a\' => array(
						\'href\' => array(),
					),
				)
			),
			\'<a href="\' . esc_url( $order->get_checkout_payment_url() ) . \'">\' . esc_html__( \'Pay for this order\', \'woocommerce\' ) . \'</a>\'); ?>	
		<?php endif; ?>';
		return $checkout_payment_url;
	}

	public function get_order_payment_method(){
		$payment_method = '<?php if(isset($order)) : ?>';
		$payment_method.= '<?php echo $order->get_payment_method(); ?>';
		$payment_method.= '<?php endif; ?>';
		return $payment_method;
	}

	public function get_customer_note(){
		$customer_note = '<?php if(isset($customer_note)) : ?>';
		$customer_note.= '<blockquote><?php echo wptexturize( $customer_note ); ?></blockquote>';
		$customer_note.= '<?php endif; ?>';
		return $customer_note;
	}

	public function get_user_login(){
		$user_login = '<?php if(isset($user_login)){ ?>';
		$user_login .= '<?php echo \'<strong>\' . esc_html( $user_login ) . \'</strong>\' ?>';
		$user_login .= '<?php } ?>';
		return $user_login;
	}

	public function get_user_pass(){
		$user_pass = '<?php if ( \'yes\' === get_option( \'woocommerce_registration_generate_password\' ) && isset($password_generated) ) : ?>';
		$user_pass.= '<?php echo \'<strong>\' . esc_html( $user_pass ) . \'</strong>\' ?>';
		$user_pass.= '<?php endif; ?>';
		return $user_pass;
	}

	public function get_account_area_url( $echo=false ){
		if( $echo ){
			return esc_url( wc_get_page_permalink( 'myaccount' ) );
		}else{
			return '<?php echo make_clickable( esc_url( wc_get_page_permalink( \'myaccount\' ) ) ); ?>';
		}
	}

	public function get_account_order_url( $echo=false ){
		if( $echo ){
			return esc_url( wc_get_account_endpoint_url( 'orders' ) );
		}else{
			return '<?php echo make_clickable( esc_url( wc_get_account_endpoint_url( \'orders\' ) ) ); ?>';
		}
	}

	public function get_reset_password_url(){
		$reset_pass = '<?php if(isset($reset_key) && isset($user_id)): ?>';
		$reset_pass .= '<a class="link" href="<?php echo esc_url( add_query_arg( array( \'key\' => $reset_key, \'id\' => $user_id ), wc_get_endpoint_url( \'lost-password\', \'\', wc_get_page_permalink( \'myaccount\' ) ) ) ); ?>">
			<?php _e( \'Click here to reset your password\', \'woocommerce\' ); ?></a>';
		$reset_pass.= '<?php endif; ?>';
		return $reset_pass;
	}

	public function order_table_total_loop_start(){
		$order_data = '<?php
		if(isset($order)){
			$totals = $order->get_order_item_totals();
			if ( $totals ) {
				$i = 0;
				foreach ( $totals as $total ) {
					$i++;
					?>';
		return $order_data;
	}

	public function order_table_total_labels(){
		$order_data = '<?php echo wp_kses_post( apply_filters("thwec_rename_order_total_labels", $total[\'label\']) ); ?>';
		return $order_data;
	}

	public function order_table_total_values(){
		$order_data = '<?php echo wp_kses_post( $total[\'value\'] ); ?>';
		return $order_data;
	}


	public function order_table_total_loop_end(){
		$order_data = '<?php
				}
			}
			if ( isset($order) && $order->get_customer_note() ) {
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:inherit;padding-top:inherit;padding-right:inherit;padding-bottom:inherit;padding-left:inherit;font-size:inherit;color:inherit;font-family:inherit;line-height:inherit;border-color:inherit;"><?php esc_html_e( \'Note:\', \'woocommerce\' ); ?></th>
					<td class="td" style="text-align:inherit;padding-top:inherit;padding-right:inherit;padding-bottom:inherit;padding-left:inherit;font-size:inherit;color:inherit;font-family:inherit;line-height:inherit;border-color:inherit;"><?php echo wp_kses_post( wptexturize( $order->get_customer_note() ) ); ?></td>
				</tr>
				<?php
			}
		}
			?>';
		return $order_data;
	}

	public function order_table_additional_td_css( $styles ){
		$order_data = '<?php
				}
			}
			if ( isset($order) && $order->get_customer_note() ) {
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="'.esc_attr($styles).'"><?php esc_html_e( \'Note:\', \'woocommerce\' ); ?></th>
					<td class="td" style="'.esc_attr($styles).'"><?php echo wp_kses_post( wptexturize( $order->get_customer_note() ) ); ?></td>
				</tr>
				<?php
			}
		}
			?>';
		return $order_data;
	}

	public function order_table_header_product(){
		$order_data = '<?php echo __( apply_filters("thwec_rename_order_total_labels", "Product"), \'woocommerce\' ); ?>';
		return $order_data;
	}
	public function order_table_header_qty(){
		$order_data = '<?php echo __( apply_filters("thwec_rename_order_total_labels", "Quantity"), \'woocommerce\' ); ?>';
		return $order_data;
	}
	public function order_table_header_price(){
		$order_data = '<?php echo __( apply_filters("thwec_rename_order_total_labels", "Price"), \'woocommerce\' ); ?>';
		return $order_data;
	}
	public function order_table_item_loop_start(){
		$order_data = '<?php 
		$items = $order->get_items();
		foreach ( $items as $item_id => $item ) :
	$product = $item->get_product();
	if ( apply_filters( "woocommerce_order_item_visible", true, $item ) ) {
		?>';
		return $order_data;
	}


	public function order_table_item_loop_end(){
		$order_data = '<?php
		}
		$show_purchase_note=true;
		if ( $show_purchase_note && is_object( $product ) && ( $purchase_note = $product->get_purchase_note() ) ) : ?>
			<tr>
				<td colspan="3" style="text-align:<?php echo $text_align; ?>;vertical-align:middle; border: 1px solid #eee; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) );?></td>
			</tr>
		<?php endif; ?>
		<?php endforeach; ?>';
		return $order_data;
	}

	public function order_table_class_filter(){
		$order_data = '<?php echo esc_attr( apply_filters( \'woocommerce_order_item_class\', \'order_item\', $item, $order ) ); ?>';
		return $order_data;
	}

	public function order_table_items_qty(){
		$order_data = '<?php echo apply_filters( \'woocommerce_email_order_item_quantity\', $item->get_quantity(), $item ); ?>';
		return $order_data;
	}

	public function order_table_items_price(){
		$order_data = '<?php echo $order->get_formatted_line_subtotal( $item ); ?>';
		return $order_data;
	}	

	public function order_table_items($img=false){
		$order_data = '<?php '; 
		if($img){
			$order_data .= '$show_image = true;';
			$order_data .= '$image_size=array( 32, 32);';
		}else{
			$order_data .= '$show_image = false;';
		}
		$order_data .= '$show_sku = apply_filters( "thwec_show_order_table_sku", $sent_to_admin, $item_id, $item, $order, $plain_text );';
		$order_data .= '

				// Show title/image etc
				if ( $show_image ) {
					echo apply_filters( \'woocommerce_order_item_thumbnail\', \'<div style="margin-bottom: 5px"><img src="\' . ( $product->get_image_id() ? current( wp_get_attachment_image_src( $product->get_image_id(), \'thumbnail\' ) ) : wc_placeholder_img_src() ) . \'" alt="\' . esc_attr__( \'Product image\', \'woocommerce\' ) . \'" height="\' . esc_attr( $image_size[1] ) .\'" width="\' . esc_attr( $image_size[0] ) . \'" style="vertical-align:middle; margin-\' . ( is_rtl() ? \'left\' : \'right\' ) . \': 10px;" /></div>\', $item );
				}

				// Product name
				echo apply_filters( \'woocommerce_order_item_name\', $item->get_name(), $item, false );

				// SKU
				if ( $show_sku && is_object( $product ) && $product->get_sku() ) {
					echo \' (#\' . $product->get_sku() . \')\';
				}

				// allow other plugins to add additional product information here
				do_action( \'woocommerce_order_item_meta_start\', $item_id, $item, $order, $plain_text );

				wc_display_item_meta( $item );

				// allow other plugins to add additional product information here
				do_action( \'woocommerce_order_item_meta_end\', $item_id, $item, $order, $plain_text );

			?>';
		return $order_data;
	}

	public function order_table_before_loop(){
		$loop = '<?php if(isset($order)){ ?>';
		return $loop;
	}
	public function order_table_after_loop(){
		$loop = '<?php } ?>';
		return $loop;
	}


	public function billing_data(){
		$address = '<?php echo ( $address = $order->get_formatted_billing_address() ) ? $address : __( "N/A", "woocommerce"); ?>
				<?php if ( $order->get_billing_phone() ) : ?>
					<br><?php echo esc_html( $order->get_billing_phone() ); ?>
				<?php endif; ?>
				<?php if ( $order->get_billing_email() ) : ?>
					<br><span style="color:inherit;"><?php echo esc_html( $order->get_billing_email() ); ?></span>
				<?php endif; ?>';
		return $address;
	}

	public function shipping_data_additional($position){
		$additional = '';
		if($position){
			$additional .= '	<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) : ?>';
		}else{
			$additional .= '<?php endif; ?>';
		}
		return $additional;
	}

	public function shipping_data(){
		$address = '<?php echo $order->get_formatted_shipping_address(); ?>';
		return $address;
	}

	public function customer_data(){
		$address = '<?php echo $order->get_formatted_billing_full_name(); ?><br><?php echo $order->get_billing_phone(); ?><br><?php echo $order->get_billing_email(); ?>';
		return $address;
	}	
	
	public function order_table_before_hook(){
		$order_data = '<?php $text_align = is_rtl() ? "right" : "left"; ?>';
		return $order_data;
	}

	public function order_table_head($text){
		$ot_link = '';
		$ot_title_link = apply_filters('thwec_order_table_title_link', '');
		if( !empty( $ot_title_link ) && in_array( $ot_title_link, $this->link_pkaceholders ) ){
			$ot_link = $this->replace_placeholder_links( $ot_title_link );
		}
		$order_table = '<?php
		if ( $sent_to_admin ) {
			$before = \'<a class="link" style="color:inherit;font-weight:inherit;font-size:inherit;font-family:inherit;line-height:inherit;" href="\' . esc_url( $order->get_edit_order_url() ) . \'">\';
			$after  = \'</a>\';
		} else {
			$before = "";
			$after  = "";';
		if( !empty( $ot_link ) ){
			$order_table .= '$before = \'<a class="link" style="color:inherit;font-weight:inherit;font-size:inherit;font-family:inherit;line-height:inherit;" href="'.$ot_link.'">\';';
		}
		$order_table .= '}
		echo wp_kses_post( $before . sprintf( __( \''.$text.'#%s\', \'woocommerce-email-customizer-pro\' ) . $after . \' (<time datetime="%s">%s</time>)\', $order->get_order_number(), $order->get_date_created()->format( \'c\' ), wc_format_datetime( $order->get_date_created() ) ) );
		?>';
		return $order_table;
	}

	public function downloadable_product_table($mail_type){
		$downloadable_product = '';
		if(!$mail_type){
			$downloadable_product .= '<?php $text_align = is_rtl() ? \'right\' : \'left\';?>
			<?php if(isset($order)){
			$downloads = $order->get_downloadable_items();
			$columns   = apply_filters(
						\'woocommerce_email_downloads_columns\', array(
						\'download-product\' => __( \'Product\', \'woocommerce\' ),
						\'download-expires\' => __( \'Expires\', \'woocommerce\' ),
						\'download-file\'    => __( \'Download\', \'woocommerce\' ),
						)
					); ?>';
			$downloadable_product .= '<?php if($downloads) {?>';
			$downloadable_product .=  '<h2 class="woocommerce-order-downloads__title"><?php esc_html_e( \'Downloads\', \'woocommerce\' ); ?></h2>';
			$downloadable_product .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
			<thead>
				<tr>
					<?php foreach ( $columns as $column_id => $column_name ) : ?>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html( $column_name ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<?php foreach ( $downloads as $download ) : ?>
				<tr>
					<?php foreach ( $columns as $column_id => $column_name ) : ?>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
							<?php
							if ( has_action( \'woocommerce_email_downloads_column_\' . $column_id ) ) {
								do_action( \'woocommerce_email_downloads_column_\' . $column_id, $download, $plain_text );
							} else {
								switch ( $column_id ) {
									case \'download-product\':
										?>
										<a href="<?php echo esc_url( get_permalink( $download[\'product_id\'] ) ); ?>"><?php echo wp_kses_post( $download[\'product_name\'] ); ?></a>
										<?php
										break;
									case \'download-file\':
										?>
										<a href="<?php echo esc_url( $download[\'download_url\'] ); ?>" class="woocommerce-MyAccount-downloads-file button alt"><?php echo esc_html( $download[\'download_name\'] ); ?></a>
										<?php
										break;
									case \'download-expires\':
										if ( ! empty( $download[\'access_expires\'] ) ) {
											?>
											<time datetime="<?php echo esc_attr( date( \'Y-m-d\', strtotime( $download[\'access_expires\'] ) ) ); ?>" title="<?php echo esc_attr( strtotime( $download[\'access_expires\'] ) ); ?>"><?php echo esc_html( date_i18n( get_option( \'date_format\' ), strtotime( $download[\'access_expires\'] ) ) ); ?></time>
											<?php
										} else {
											esc_html_e( \'Never\', \'woocommerce\' );
										}
										break;
								}
							}
							?>
						</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?></table>';
			$downloadable_product .= '<?php } } ?>';
		}
		return $downloadable_product;
	}

	public function downloadable_product_tablesss(){
		$downloadable_product = '<?php $text_align = is_rtl() ? \'right\' : \'left\';?>';
		$downloadable_product .= '<?php if($downloads && $columns) {?>';
		$downloadable_product .=  '<h2 class="woocommerce-order-downloads__title"><?php esc_html_e( \'Downloads\', \'woocommerce\' ); ?></h2>';
		$downloadable_product .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
		<thead>
			<tr>
				<?php foreach ( $columns as $column_id => $column_name ) : ?>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html( $column_name ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<?php foreach ( $downloads as $download ) : ?>
			<tr>
				<?php foreach ( $columns as $column_id => $column_name ) : ?>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
						<?php
						if ( has_action( \'woocommerce_email_downloads_column_\' . $column_id ) ) {
							do_action( \'woocommerce_email_downloads_column_\' . $column_id, $download, $plain_text );
						} else {
							switch ( $column_id ) {
								case \'download-product\':
									?>
									<a href="<?php echo esc_url( get_permalink( $download[\'product_id\'] ) ); ?>"><?php echo wp_kses_post( $download[\'product_name\'] ); ?></a>
									<?php
									break;
								case \'download-file\':
									?>
									<a href="<?php echo esc_url( $download[\'download_url\'] ); ?>" class="woocommerce-MyAccount-downloads-file button alt"><?php echo esc_html( $download[\'download_name\'] ); ?></a>
									<?php
									break;
								case \'download-expires\':
									if ( ! empty( $download[\'access_expires\'] ) ) {
										?>
										<time datetime="<?php echo esc_attr( date( \'Y-m-d\', strtotime( $download[\'access_expires\'] ) ) ); ?>" title="<?php echo esc_attr( strtotime( $download[\'access_expires\'] ) ); ?>"><?php echo esc_html( date_i18n( get_option( \'date_format\' ), strtotime( $download[\'access_expires\'] ) ) ); ?></time>
										<?php
									} else {
										esc_html_e( \'Never\', \'woocommerce\' );
									}
									break;
							}
						}
						?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?></table>';
		$downloadable_product .= '<?php } ?>';
		return $downloadable_product;
	}

	public function add_order_head(){
		$order_head = '<?php if(isset($order)){?>';
		return $order_head;
	}
	
	public function add_order_foot(){
		$order_foot = '<?php } ?>';
		return $order_foot;
	}


	public function thwec_email_hooks($hook){
		switch($hook){
			 case '{email_header_hook}':
                $hook ='<?php do_action( \'woocommerce_email_header\', $email_heading, $email ); ?>'; 
                break;
 			case '{email_order_details_hook}': 
 				$hook = '<?php if(isset($order)){ ?>'; 
 				$hook .= '<div class=\'thwec-order-table-ref\' style=\'border:none;padding:0;margin:0;\'>';
 				$hook .= '<?php do_action( \'woocommerce_email_order_details\', $order, $sent_to_admin, $plain_text, $email ); ?>';
 				$hook .= '</div>';
 				$hook .= '<?php } ?>';
 				break;
  			case '{before_order_table_hook}': 
  				$hook = '<?php if(isset($order)){ 
  					do_action(\'woocommerce_email_before_order_table\', $order, $sent_to_admin, $plain_text, $email); 
  				}?>';
 				break;
  			case '{after_order_table_hook}': 
  				$hook = '<?php if(isset($order)){ 
  					do_action(\'woocommerce_email_after_order_table\', $order, $sent_to_admin, $plain_text, $email); 
  				}?>';
 				break;
  			case '{order_meta_hook}': 
  				$hook = '<?php if(isset($order)){ 
  					do_action( \'woocommerce_email_order_meta\', $order, $sent_to_admin, $plain_text, $email ); 
  				}?>';
 				break;
  			case '{customer_details_hook}': 
  				$hook = '<?php if(isset($order)){ 
  					do_action( \'woocommerce_email_customer_details\', $order, $sent_to_admin, $plain_text, $email ); 
  				}?>';
 				break;
 			case '{email_footer_hook}':
                $hook = '<?php do_action( \'woocommerce_email_footer\', $email ); ?>';
                break;
            case '{email_footer_blogname}':
            $hook = '<?php echo wpautop( wp_kses_post( wptexturize( apply_filters( \'woocommerce_email_footer_text\', \'\' ) ) ) ); ?>';
            default:
                $hook = '';
		}
		return $hook;
	}

	public function replace_placeholder_links( $placeholder ){
		$link = '';
		switch ( $placeholder ) {
			case '{account_area_url}':
				$link = $this->get_account_area_url( true );
				break;
			case '{account_order_url}':
				$link = $this->get_account_order_url( true );
				break;
			default:
				$link = '';
				break;
		}
		return $link;
	}
}

endif;