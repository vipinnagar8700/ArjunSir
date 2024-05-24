<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/public
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Public')):
 
class THWEC_Public {
	private $plugin_name;
	private $version;
	public $templates;
    public $current_email;
    public $subjects;
    private $data_pool = null;
	private $current_lang = null;
	private $wpml_active = false;
	private $wpml_default_lang = '';

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action('after_setup_theme', array($this, 'define_public_hooks'));
	}

	public function enqueue_styles_and_scripts() {
		global $wp_scripts;
		
		if(is_product()){
			$debug_mode = apply_filters('thwec_debug_mode', false);
			$suffix = $debug_mode ? '' : '.min';
			$jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
		}
	}
	
	
	public function define_public_hooks(){
		add_filter('woocommerce_locate_template', array($this, 'woo_locate_template'), 999, 3);
		add_filter('woocommerce_email_styles', array($this, 'th_woocommerce_email_styles') );
		$this->render_subject_filters();
		$this->wpml_active = THWEC_Utils::is_wpml_active();
		if( $this->wpml_active ){
			$this->wpml_default_lang = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_default_language', NULL ), true );
		}
	}

	public function get_default_langauge_template( $template_name ){
		$template = $template_name.'-'.$this->wpml_default_lang;
		$path = $this->get_email_template_path($template);
		return $path;

	}

	public function get_thwec_template_file( $template_name, $current_lang ){
		if( $this->wpml_active ){
			if( $this->wpml_default_lang == $current_lang ){
				$custom_path = $this->get_default_langauge_template( $template_name );
				if( $custom_path ){
					return $custom_path;
				}
			}else{
				$lang_template = $template_name.'-'.$current_lang;
				$custom_path = $this->get_email_template_path($lang_template);
				if( $custom_path ){
					return $custom_path;
				}else{
					$custom_path = $this->get_default_langauge_template( $template_name );
					if( $custom_path ){
						return $custom_path;
					}
				}
			}
		}
			
		$lang_template = $template_name.'-'.$current_lang;
		$custom_path = $this->get_email_template_path($lang_template);
		if( $custom_path ){
			return $custom_path;
		}
		$custom_path = $this->get_email_template_path( $template_name );
		return $custom_path;
	}


	public function woo_locate_template($template, $template_name, $template_path){

		$template_map = THWEC_Utils::get_template_map();
		$current_lang = strtolower(get_locale());
		if($template_map){ 
		    $search = array('emails/', '.php');
            $replace = array('', '');
		    $template_key = str_replace($search, $replace, $template_name);
			if(array_key_exists($template_key, $template_map)) {
    			$template_name_new = $template_map[$template_key];
    			if( $template_name_new != '' ){
        			$custom_path = $this->get_thwec_template_file( $template_name_new, $current_lang );
    				if($custom_path){
    					return $custom_path;
    				}
    			}		
    		}
    	}
       	return $template;
	}

	public function th_woocommerce_email_styles($buffer){
		/** Default styles include styles based on an element id #body_content. That element is not used in our template. So use those styles that require #body_element here.
		
		* Styles added here are only for content that woocommerce render directly Or the woocommerce functions that are used in template.*/
		
		$css = '';
		$text_align  = is_rtl() ? "right" : "left";
		$margin_side = is_rtl() ? "left" : "right";

		$styles = $this->wecmf_template_css_compatibility(); 
		$styles .= '#tp_temp_builder #template_container,#tp_temp_builder #template_header,#tp_temp_builder #template_body,#tp_temp_builder #template_footer{width:100% !important;}';
		$styles.= '#tp_temp_builder #template_container{width:100% !important;border:0px none transparent !important;}';
		$styles.= '#tp_temp_builder #wrapper{padding:0;background-color:transparent;}';
		$styles.= '#tp_temp_builder .thwec-block-text-holder a{color: #1155cc !important;}';

		// Order table hook styles
		$styles.= '#tp_temp_builder table.td{font-size: 14px;line-height:150%;}';
		$styles.= '#tp_temp_builder table.td td,#tp_temp_builder table.td th{padding: 12px;font-size:inherit;line-height:inherit;}';
		$styles.= '#tp_temp_builder .thwec-block-text-holder a{color: #1155cc !important;}';

		// For order table item meta
		$styles.= '#tp_temp_builder ul.wc-item-meta{font-size:small;margin:1em 0 0;padding:0;list-style:none;}';
		$styles.= '#tp_temp_builder ul.wc-item-meta li{margin: 0.5em 0 0;padding:0;}';
		$css.= '#tp_temp_builder ul.wc-item-meta li .wc-item-meta-label{float: ' . esc_attr( $text_align ) . ';margin-' . esc_attr( $margin_side ) . ': .25em;clear:both;}';
		$styles.= '#tp_temp_builder ul.wc-item-meta li p{margin: 0 0 16px;}';
		
		// Top and bottom padding in mobile device ( wrapper with 70px gray color for desktop )
		if( apply_filters( 'thwec_mobile_compatibility_wrapper_padding', true ) ){
			$styles .= '@media only screen and (max-width:480px) {
	  			#thwec_template_wrapper .thwec-template-wrapper-column{ padding: 0px !important;} 
	  		}';
		}

		//Pay with cod text
		$styles.= '#tp_temp_builder div > p{color:#636363;font-size:14px;}';
		
		// Add additional styles to content.
		$styles.= apply_filters('thwec_woo_css_override', $css);
		return $buffer.$styles;
	}

	public function wecmf_template_css_compatibility(){
		// Free version templates that are not edited in the premium version (but assigned to email) atleast once require free version styles.
		$styles = '#tpf_t_builder #template_container,#tpf_t_builder #template_header,#tpf_t_builder #template_body,#tpf_t_builder #template_footer{width:100% !important;}';
		$styles.= '#tpf_t_builder #template_container{width:100% !important;border:0px none transparent !important;}';
		$styles .= '#tpf_t_builder #body_content > table:first-child > tbody > tr > td{padding:15px 0px !important;}'; //To remove the padding after header when woocommerce header hook used in template (48px 48px 0px) 
		$styles.= '#tpf_t_builder #wrapper{padding:0;background-color:transparent;}';
		$styles.= '#tpf_t_builder .thwec-block-text-holder a{color: #1155cc !important;}';
		$styles.= '#tpf_t_builder .thwecmf-columns p{color:#636363;font-size:14px;}';
		$styles.= '#tpf_t_builder .thwecmf-columns .td .td{padding:12px;}';
		$styles.= '#tpf_t_builder .thwecmf-columns .address{font-size:14px;}';
		return $styles;
	}


	public function get_email_template_path($t_name){
    	$tpath = false;
    	$email_template_path = THWEC_CUSTOM_TEMPLATE_PATH.$t_name.'.php';
    	if(file_exists($email_template_path)){
    	   	$tpath = $email_template_path;
    	}
    	return $tpath;
    }

    public function render_subject_filters(){

    	$this->subjects = THWEC_Utils::get_template_subject();

    	add_filter('woocommerce_email_subject_new_order', array($this, 'thwec_email_subject_new_order'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_processing_order', array($this, 'thwec_email_subject_processing'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_completed_order', array($this, 'thwec_email_subject_completed'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_invoice', array($this, 'thwec_email_subject_invoice'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_note', array($this, 'thwec_email_subject_customer_note'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_new_account', array($this, 'thwec_email_subject_new_account'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_on_hold_order', array($this, 'thwec_email_subject_on_hold'), 1, 2);
    	add_filter('woocommerce_email_subject_cancelled_order', array($this, 'thwec_email_subject_cancelled'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_refunded_order', array($this, 'thwec_email_subject_refunded'), 1, 2);
    	add_filter('woocommerce_email_subject_failed_order', array($this, 'thwec_email_subject_failed'), 1, 2);
    	add_filter('woocommerce_email_subject_customer_reset_password', array($this, 'thwec_email_subject_reset_password'), 1, 2);
    }

    public function thwec_email_subject_new_order( $subject, $order ){
    	return $this->format_email_subject( $subject, 'admin-new-order', $order );
    }
    
    public function thwec_email_subject_processing( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-processing-order', $order );
    }
	
	public function thwec_email_subject_completed( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-completed-order', $order );
	}
	
	public function thwec_email_subject_invoice( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-invoice', $order );
	}
	
	public function thwec_email_subject_customer_note( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-note', $order );
	}
	
	public function thwec_email_subject_new_account( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-new-account', $order );
	}
	
	public function thwec_email_subject_on_hold( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-on-hold-order', $order );
	}
	
	public function thwec_email_subject_cancelled( $subject, $order ){
		return $this->format_email_subject( $subject, 'admin-cancelled-order', $order );
	}
	
	public function thwec_email_subject_refunded( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-refunded-order', $order );
	}
	
	public function thwec_email_subject_failed( $subject, $order ){
		return $this->format_email_subject( $subject, 'admin-failed-order', $order );
	}
	
	public function thwec_email_subject_reset_password( $subject, $order ){
		return $this->format_email_subject( $subject, 'customer-reset-password', $order );
	}

	public function format_email_subject( $subject, $status, $order ){
		if( isset( $this->subjects[$status] ) && !empty( $this->subjects[$status] ) ){
			$subject = THWEC_Utils::format_subjects( $this->subjects[$status], $status, $order );
		}
		return $subject;
	}
}
endif;