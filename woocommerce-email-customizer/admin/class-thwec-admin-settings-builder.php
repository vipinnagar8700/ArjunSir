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

if(!class_exists('THWEC_Admin_Settings_Builder')):

class THWEC_Admin_Settings_Builder extends THWEC_Admin_Settings {
	protected static $_instance = null;
	
	private $cell_props_T = array();
	private $cell_props_3T = array();
	private $cell_props_4T = array();
	private $cell_props_CB = array();
	private $cell_props_CBS = array();
	private $cell_props_CBL = array();
	private $cell_props_CP = array();
	private $cell_props_S  = array();
	private $cell_props_RB = array();
	private $section_props = array();
	private $field_props = array();
	private $field_props_display = array();
	private $json_css_class = array();
	private $css_props = array();
	private $css_elm_props_map = array();
	private $font_family_list = array();
	private $template_display_name = '';
	private $template_formated_name = '';
	private $template_json_css = '';
	private $template_json = '';
	private $default_css = array();
	private $add_css = '';
	private $thwec_order = '';
	private $wpml_langs = '';
	private $wpml_default_lang = null;
	private $wpml_current_lang = null;
	private $template_lang = '';
	private $wpml_active = false;
	
	public function __construct() {
		$this->init_constants();
	}

	public function admin_init(){
		set_user_setting('mfold', 'f');
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function init_constants(){
		$this->cell_props_T = array( 
			'input_width' => '136px',
			'input_height' => '30px',
			'input_b_r' => '4px',
			'input_font_size' => '13px',  
		);

		$this->cell_props_FT = array( 
			'input_width' => '279px',
			'input_height' => '30px',
			'input_b_r' => '4px',
			'input_font_size' => '13px',  
		);

		$this->cell_props_TA = array( 
			'rows' => '12',
			'cols' => '37',
			'input_font_size' => '13px',  
		);

		$this->cell_props_LT = array( 
			'input_width' => '278px',
			'input_height' => '30px',
			'input_b_r' => '4px',
			'input_font_size' => '13px',  
		);

		$this->cell_props_4T = array( 
			'label_cell_props' => 'style="width:13%"', 
			'input_cell_props' => 'style="width:34%"', 
		);
		
		$this->cell_props_R = array( 
			'label_cell_props' => 'style="width:13%;"', 
			'input_cell_props' => 'style="width:34%;"', 
			'input_width' => '200px', 
		);
		
		$this->cell_props_S = array( 
			'input_width' => '136px', 
			'input_b_r' => '4px', 
			'input_font_size' => '13px', 
		);

		$this->cell_props_CB = array( 
			'label_props' => 'style="margin-right: 40px;"', 
		);

		$this->wpml_active = THWEC_Utils::is_wpml_active();

		$this->json_css_class = array(
			'row'		=> 'thwec-row',
			'column'	=> 'column-padding',
		);

		$this->template_json_css = '';

		$this->field_props = $this->get_field_form_props();
		$this->default_css = array( 
        	'color' 			=> 'transparent',
        	'background-color' 	=> 'transparent',
        	'border-color'		=> 'transparent',
        	'background-color' 	=> 'transparent',
        	'padding-top' 		=> '0px',
        	'padding-right'  	=> '0px',
        	'padding-bottom' 	=> '0px',
        	'padding-left' 		=> '0px',
        	'background-image' 	=> 'none',
        );

		$this->font_family_list = array(
			"helvetica" 	=> 	"'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif",
        	"georgia" 		=> 	"Georgia, serif",
        	"times" 		=> 	"'Times New Roman', Times, serif",
        	"arial" 		=> 	"Arial, Helvetica, sans-serif",
        	"arial-black" 	=> 	"'Arial Black', Gadget, sans-serif",
        	"comic-sans" 	=> 	"'Comic Sans MS', cursive, sans-serif",
        	"impact" 		=> 	"Impact, Charcoal, sans-serif",
        	"tahoma"	 	=> 	"Tahoma, Geneva, sans-serif",
        	"trebuchet" 	=> 	"'Trebuchet MS', Helvetica, sans-serif",
        	"verdana" 		=>	"Verdana, Geneva, sans-serif",
		);

		$this->css_props = array(  
	        'p_t'=>'padding-top',
	        'p_r'=>'padding-right',
	        'p_b'=>'padding-bottom',
	        'p_l'=>'padding-left', 
	        'm_t'=>'margin-top',
	        'm_r'=>'margin-right',
	        'm_b'=>'margin-bottom',
	        'm_l' => 'margin-left',
	        'vertical_align'	=>	'vertical-align',

	        'width' => 'width',
	        'height' => 'height',
	        'size_width' => 'width',
	        'size_height' => 'height',
	        'content_size_width' => 'width',
	        'content_size_height' => 'height',

	        'b_t' => 'border-top',
	        'b_r' => 'border-right',
	        'b_b' => 'border-bottom',
	        'b_l' => 'border-left',
	        'border_style' => 'border-style',
	        'border_color' => 'border-color',
	        'border_radius' => 'border-radius',
	        // bg_image => 'background-image',
	        'upload_bg_url' => 'background-image',
	        'upload_img_url' => 'display',
	        'bg_color'     => 'background-color',
	        'bg_position' => 'background-position',
	        'bg_size' => 'background-size',
	        'bg_repeat' => 'background-repeat',


	        'color' => 'color',
	        'font_size' => 'font-size',
	        'font_weight' => 'font-weight',
	        'text_align' => 'text-align',
	        'line_height' => 'line-height',
	        'font_family' => 'font-family',


	        'align' => 'float',
	        'img_width' => 'width',
	        'img_height' => 'height',
	        'img_size_width' => 'width',
	        'img_size_height' => 'height',
	        

	        'img_bg_color' => 'background-color',
	        'img_p_t' => 'padding-top',
	        'img_p_r' => 'padding-right',
	        'img_p_b' => 'padding-bottom',
	        'img_p_l' => 'padding-left',
	        'img_m_t' => 'margin-top',
	        'img_m_r' => 'margin-right',
	        'img_m_b' => 'margin-bottom',
	        'img_m_l' => 'margin-left',  
	        'img_border_width_top' => 'border-top',  
	        'img_border_width_right' => 'border-right',  
	        'img_border_width_bottom' => 'border-bottom',  
	        'img_border_width_left' => 'border-left',  
	        'img_border_style' => 'border-style',  
	        'img_border_color' => 'border-color',  
	        'img_border_radius' => 'border-radius',  

	        'details_color' => 'color',
	        'details_font_size' => 'font-size',
	        'details_font_weight' => 'font-weight',
	        'details_text_align' => 'text-align',
	        'details_line_height' => 'line-height',
	        'details_font_family' => 'font-family',

	        'content_align'	=>	'text-align',
	        'content_border_width_top' => 'border-top',
	        'content_border_width_right' => 'border-right',
	        'content_border_width_bottom' => 'border-bottom',
	        'content_border_width_left' => 'border-left',
	        'content_border_style' => 'border-style',
	        'content_border_color' => 'border-color',
	        'content_border_radius' => 'border-radius',
	        'content_width' => 'width',
	        'content_height' => 'height',
	        'content_bg_color' => 'background-color',

	        'content_p_t' => 'padding-top',
	        'content_p_r' => 'padding-right',
	        'content_p_b' => 'padding-bottom',
	        'content_p_l' => 'padding-left',
	        'content_m_t' => 'margin-top',
	        'content_m_r' => 'margin-right',
	        'content_m_b' => 'margin-bottom',
	        'content_m_l' => 'margin-left',
	        'border_spacing' => 'border-spacing',
	        'divider_height' => 'border-top-width',
	        'divider_color' => 'border-top-color',
	        'divider_style' => 'border-top-style',
	        'product_img' => 'display',
	        'url1' => 'display',
	        'url2' => 'display',
	        'url3' => 'display',
	        'url4' => 'display',
	        'url5' => 'display',
	        'url6' => 'display',
	        'url7' => 'display',
	        'url' => 'display',
	        'icon_p_t'=>'padding-top',
	        'icon_p_r'=>'padding-right',
	        'icon_p_b'=>'padding-bottom',
	        'icon_p_l'=>'padding-left', 
    	);


    	$this->css_elm_props_map = array(
    		'header_details' => array(
    			'.thwec-block-header'	=>	array('size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_repeat', 'bg_position', 'bg_size', 'm_t', 'm_r', 'm_b', 'm_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color'),

    			' .header-logo-tr'	=> array('upload_img_url'),

    			' .header-logo'	=> array('content_align', 'img_p_t', 'img_p_r', 'img_p_b', 'img_p_l'),

    			' .header-logo .header-logo-ph'	=> array('img_m_t', 'img_m_r', 'img_m_b', 'img_m_l', 'img_size_height','img_size_width','img_border_width_top','img_border_width_right', 'img_border_width_bottom', 'img_border_width_left', 'img_border_color', 'img_border_style', 'img_bg_color', 'align'),

    			' .header-text'	=> array('p_t', 'p_r', 'p_b', 'p_l'),
    			' .header-text h1'	=> array('font_size', 'color','font_weight','text_align', 'line_height','font_family'),
    		),

    		'text'	=> array(

    			'.thwec-block-text'	=>	array(
    				'color', 'align', 'font_size', 'line_height', 'font_weight', 'font_family', 'size_width', 'size_height', 'm_t', 'm_r', 'm_b', 'm_l', 'text_align'
    			),
    			'.thwec-block-text .thwec-block-text-holder'	=>	array(
    				'color', 'font_size', 'font_weight', 'line_height', 'text_align','font_family', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_color', 'border_style', 'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-block-text *'		=>	array(
    				'color', 'font_size', 'font_weight', 'line_height','font_family'
    			),
    		),

    		'image'	=>	array(
    			'.thwec-block-image' => array(
    				'img_m_t', 'img_m_r', 'img_m_b', 'img_m_l', 'img_bg_color', 'img_bg_color'		
    			),
    			'.thwec-block-image td.thwec-image-column' => array(
    				'content_align'
    			),
    			'.thwec-block-image td.thwec-image-column p' => array(
    				'img_size_width', 'img_size_height', 'img_p_t', 'img_p_r', 'img_p_b', 'img_p_l', 'img_border_width_top', 'img_border_width_right', 'img_border_width_bottom', 'img_border_width_left', 'img_border_style', 'img_border_color', 
    			),
    		),

    		'social'	=>	array(
    			'.thwec-block-social'	=>	array('content_align', 'm_t', 'm_r', 'm_b', 'm_l','bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color'),
    			'.thwec-block-social .thwec-social-outer-td'	=>	array('p_t', 'p_r', 'p_b', 'p_l'),
    			'.thwec-block-social .thwec-social-td'	=>	array( 'icon_p_t', 'icon_p_r', 'icon_p_b', 'icon_p_l', 'content_align'),
    			'.thwec-block-social .thwec-social-icon'	=>	array( 'img_size_width', 'img_size_height'),
    			'.thwec-block-social td.thwec-td-fb'	=>	array('url1'),
    			'.thwec-block-social td.thwec-td-mail'	=>	array('url2'),
    			'.thwec-block-social td.thwec-td-tw'	=>	array('url3'),
    			'.thwec-block-social td.thwec-td-yb'	=>	array('url4'),
    			'.thwec-block-social td.thwec-td-lin'	=>	array('url5'),
    			'.thwec-block-social td.thwec-td-pin'	=>	array('url6'),
    			'.thwec-block-social td.thwec-td-insta'	=>	array('url7'),
    		),

    		'button'	=>	array(
    			'.thwec-button-wrapper-table'	=>	array(
    				'size_width', 'size_height', 'm_t', 'm_r', 'm_b', 'm_l', 'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-button-wrapper-table .thwec-button-wrapper'	=>	array(
    				'font_weight', 'font_size', 'font_family','color', 'bg_color','upload_bg_url', 'bg_repeat', 'bg_size', 'bg_position', 'b_t', 'b_b', 'b_l', 'b_r',  'border_style', 'border_color','content_p_t', 'content_p_r', 'content_p_b', 'content_p_l', 'text_align'
    			),

    			' .thwec-button-link'	=>	array(
    				'font_weight', 'line_height', 'font_size','font_family','color', 'text_align'
    			),
    		),

    		'customer_address'	=>	array(
    			'.thwec-block-customer .thwec-address-wrapper-table'	=> array(
    				'size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'
    			),
    			'.thwec-block-customer .customer-padding'		=> array(
    				'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-block-customer .thwec-customer-header'	=> array(
    				'font_size', 'color','text_align','font_weight','line_height','font_family'
    			),
    			'.thwec-block-customer .thwec-customer-body'	=> array(
    				'details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'
    			),
    		),

    		'billing_address'	=>	array(
    			'.thwec-block-billing .thwec-address-wrapper-table'	=> array(
    				'size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'
    			),
    			'.thwec-block-billing .billing-padding'		=> array(
    				'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-block-billing .thwec-billing-header'	=> array(
    				'font_size', 'color','text_align','font_weight','line_height','font_family'
    			),
    			'.thwec-block-billing .thwec-billing-body'	=> array(
    				'details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'
    			),
    		),

    		'shipping_address'	=>	array(
    			'.thwec-block-shipping .thwec-address-wrapper-table'	=> array(
    				'size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'
    			),
    			'.thwec-block-shipping .shipping-padding'		=> array(
    				'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-block-shipping .thwec-shipping-header'	=> array(
    				'font_size', 'color','text_align','font_weight','line_height','font_family'
    			),
    			'.thwec-block-shipping .thwec-shipping-body'	=> array(
    				'details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'
    			),
    		),

    		'order_details'	=>	array(
    			'.thwec-block-order'	=>	array(
    				'align', 'size_width', 'size_height', 'bg_color', 'm_t', 'm_r', 'm_b', 'm_l', 'upload_bg_url', 'bg_size', 'bg_repeat', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color'
    			),
    			'.thwec-block-order .order-padding'	=>	array(
    				'p_t', 'p_r', 'p_b', 'p_l'
    			),
    			'.thwec-block-order .thwec-order-heading'	=>	array(
    				'color', 'font_size', 'text_align', 'font_weight', 'line_height','font_family'
    			),
    			'.thwec-block-order .thwec-order-table'	=>	array(
    				'content_size_width', 'content_size_height', 'content_bg_color', 'content_border_color', 'content_m_t', 'content_m_r', 'content_m_b', 'content_m_l','details_font_family'
    			),
    			'.thwec-block-order .thwec-td'	=>	array(
    				'details_font_size', 'details_color', 'details_text_align','details_font_family','content_border_color', 'details_font_weight', 'details_line_height', 'content_p_t', 'content_p_r', 'content_p_b', 'content_p_l'
    			),
    			'.thwec-block-order .thwec-td .thwec-order-item-img'	=> array('product_img'),
    		),
    		
    		'gap'	=>	array(
    			'.thwec-block-gap'	=> array(
    				'height', 'bg_color', 'upload_bg_url', 'bg_repeat', 'bg_size', 'bg_position', 'b_t', 'b_b', 'b_l', 'b_r',  'border_style', 'border_color'
    			),
    		),

    		'divider'	=>	array(
    			'.thwec-block-divider '	=>	array(
    				'm_t', 'm_r', 'm_b', 'm_l'
    			),
    			'.thwec-block-divider td'	=>	array(
    				'p_t', 'p_r', 'p_b', 'p_l', 'content_align'
    			),
    			'.thwec-block-divider td hr'	=>	array(
    				'width', 'divider_height', 'divider_color', 'divider_style'
    			),
    		),
    		
    		'gif'	=>	array(
    			'.thwec-block-gif'	=>	array(
    				'upload_bg_url', 'bg_position', 'bg_size', 'bg_repeat', 'bg_color', 'm_t', 'm_r', 'm_b', 'm_l'
    			),
    			'.thwec-block-gif td.thwec-gif-column'	=>	array(
    				'content_align'
    			),
    			'.thwec-block-gif td.thwec-gif-column p'	=>	array(
    				'img_size_width', 'img_size_height', 'p_t', 'p_r', 'p_b', 'p_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_color', 'border_style'
    			),
    		),
    		
    		'temp_builder'	=>	array(
    			'.main-builder .thwec-builder-column'	=> 	array(
    				'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'bg_size', 'bg_repeat', 'bg_color', 'upload_bg_url', 'bg_position'
    			),
    		),

    	);

		if ( $this->wpml_active) {
    		$this->wpml_langs = icl_get_languages();
    		$this->wpml_default_lang = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_default_language', NULL ) );
			$this->wpml_current_lang = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_current_language', NULL ) );
		}

		
	}

	public function get_field_form_props(){
		$vertical_align = array('top'=>'Top','middle'=>'Middle','bottom'=>'Bottom');
		$text_align = array('left' => 'Left','center' => 'Center','right' => 'Right');
		$float_align = array('left' => 'Left', 'right' => 'Right', 'none' => 'Center');
		$divider_options = array('dotted' => 'Dotted','solid' => 'Line','dashed' => 'Dashed');
		$font_list = array(
			'helvetica' => 'Helvetica',
			'georgia' => 'Georgia',
			'times' => 'Times New Roman',
			'arial' => 'Arial',
			'arial-black' => 'Arial Black',
			'comic-sans'=>'Comic Sans MS',
			'impact'=>'Impact',
			'tahoma'=>'Tahoma',
			'trebuchet'=>'Trebuchet MS',
			'verdana'=>'Verdana'
		);


		$border_style = array(
			'solid'=>'solid', 
			'dotted'=>'dotted', 
			'dashed'=>'dashed', 
			'none'=>'none',
		);

		$bg_repeat = array(
			'repeat'=>'repeat', 
			'repeat-x'=>'repeat-x',
			'repeat-y'=>'repeat-y',
			'no-repeat'=>'no-repeat',
			'space'=>'space', 
			'round'=>'round',
		);

		$rad_options = array(
			'text'=>'Text',
			'html'=>'Html',
		);
		
		return array(
			'width' => array('type'=>'text', 'name'=>'width', 'label'=>'Width', 'value'=>''),
			'height' => array('type'=>'text', 'name'=>'height', 'label'=>'Height', 'value'=>''),
			'size' => array('type'=>'twoside', 'name'=>'size', 'label'=>'Size', 'value'=>''),
			'content_size' => array('type'=>'twoside', 'name'=>'content_size', 'label'=>'Size', 'value'=>''),
			'padding' => array('type'=>'fourside', 'name'=>'padding', 'label'=>'Padding', 'value'=>''),
			'margin' => array('type'=>'fourside', 'name'=>'margin', 'label'=>'Margin', 'value'=>''),
			'row_cellpadding'	=> array('type'=>'checkbox','name'=>'row_cellpadding','label'=>'Cellpadding','checked'=>0),
			'row_cellspacing'	=> array('type'=>'checkbox','name'=>'row_cellspacing','label'=>'Cellspacing','checked'=>0),
			'vertical_align' => array('type'=>'select', 'name'=>'vertical_align', 'label'=>'Vertical Align', 'options'=>$vertical_align),
			
			'img_width' => array('type'=>'text', 'name'=>'img_width', 'label'=>'Image Width', 'value'=>''),
			'img_height' => array('type'=>'text', 'name'=>'img_height', 'label'=>'Image Height', 'value'=>''),
			'img_size' => array('type'=>'twoside', 'name'=>'img_size', 'label'=>'Size', 'value'=>''),
			'img_padding' => array('type'=>'fourside', 'name'=>'img_padding', 'label'=>'Image Padding', 'value'=>''),
			'icon_padding' => array('type'=>'fourside', 'name'=>'icon_padding', 'label'=>'Icon Padding', 'value'=>''),
			'img_margin' => array('type'=>'fourside', 'name'=>'img_margin', 'label'=>'Image Margin', 'value'=>''),

			'content_width' => array('type'=>'text', 'name'=>'content_width', 'label'=>'Width', 'value'=>''),
			'content_height' => array('type'=>'text', 'name'=>'content_height', 'label'=>'Height', 'value'=>''),
			'content_padding' => array('type'=>'fourside', 'name'=>'content_padding', 'label'=>'Content Padding', 'value'=>''),
			'content_margin' => array('type'=>'fourside', 'name'=>'content_margin', 'label'=>'Content Margin', 'value'=>''),
			'text_n_html' => array('type'=>'radio', 'name'=>'text_n_html', 'options'=>$rad_options,'class'=>'text_n_html','value'=>'text'),
			'img_border_width' => array('type'=>'fourside', 'name'=>'img_border_width', 'label'=>'Border Width', 'value'=>''),
			'img_border_style' => array('type'=>'select', 'name'=>'img_border_style', 'label'=>'Border Style', 'options'=>$border_style),
			'img_border_color' => array('type'=>'colorpicker', 'name'=>'img_border_color', 'label'=>'Border Color', 'value'=>'','placeholder'=>'Color'),
			'img_border_radius' => array('type'=>'text', 'name'=>'img_border_radius', 'label'=>'Border Radius', 'value'=>''),

			'border_width' => array('type'=>'fourside', 'name'=>'border_width', 'label'=>'Border Width', 'value'=>''),
			'border_style' => array('type'=>'select', 'name'=>'border_style', 'label'=>'Border Style', 'options'=>$border_style),
			'border_color' => array('type'=>'colorpicker', 'name'=>'border_color', 'label'=>'Border Color', 'value'=>'','placeholder'=>'Color'),
			'border_radius' => array('type'=>'text', 'name'=>'border_radius', 'label'=>'Border Radius', 'value'=>''),

			'content_border_width' => array('type'=>'fourside', 'name'=>'content_border_width', 'label'=>'Border Width', 'value'=>''),
			'content_border_style' => array('type'=>'select', 'name'=>'content_border_style', 'label'=>'Border Style', 'options'=>$border_style),
			'content_border_color' => array('type'=>'colorpicker', 'name'=>'content_border_color', 'label'=>'Border Color', 'value'=>'','placeholder'=>'Color'),
			'content_border_radius' => array('type'=>'text', 'name'=>'content_border_radius', 'label'=>'Border Radius', 'value'=>''),

			'divider_height' => array('type'=>'text', 'name'=>'divider_height', 'label'=>'Divider Height', 'value'=>''),
			'divider_color' => array('type'=>'colorpicker', 'name'=>'divider_color', 'label'=>'Divider Color', 'value'=>'','placeholder'=>'Color'),
			'divider_style' => array('type'=>'select', 'name'=>'divider_style', 'label'=>'Divider Style', 'options'=>$border_style),

			'img_bg_color' => array('type'=>'colorpicker', 'name'=>'img_bg_color', 'label'=>'BG Color', 'value'=>'','placeholder'=>'Color'),
			'content_bg_color' => array('type'=>'colorpicker', 'name'=>'content_bg_color', 'label'=>'BG Color', 'value'=>'','placeholder'=>'Color'),
			'bg_color' => array('type'=>'colorpicker', 'name'=>'bg_color', 'label'=>'BG Color', 'placeholder'=>'Color', 'value'=>''),
			'bg_image' => array('type'=>'text', 'name'=>'bg_image', 'label'=>'BG Image', 'value'=>''),
			'bg_position' => array('type'=>'text', 'name'=>'bg_position', 'label'=>'BG Position', 'placeholder'=>'Position', 'value'=>'','hint_text'=>'left top | x% y% | xpos ypos etc.'),
			'bg_size' => array('type'=>'text', 'name'=>'bg_size', 'label'=>'BG Size', 'placeholder'=>'Size', 'value'=>''),
			'bg_repeat' => array('type'=>'select', 'name'=>'bg_repeat', 'label'=>'BG Repeat', 'options'=>$bg_repeat,'hint_text'=>'image should be repeated or not','class'=>'thwec-bg-repeat'),

			'url' => array('type'=>'text', 'name'=>'url', 'label'=>'URL', 'value'=>''),
			'upload_bg_url' => array('type'=>'hidden', 'name'=>'upload_bg_url', 'label'=>'URL', 'value'=>'','class'=>'thwec-upload-url'),
			'upload_img_url' => array('type'=>'hidden', 'name'=>'upload_img_url', 'label'=>'URL', 'value'=>'','class'=>'thwec-upload-url'),
			'title' => array('type'=>'text', 'name'=>'title', 'label'=>'Title', 'value'=>''),
			'content' => array('type'=>'text', 'name'=>'content', 'label'=>'Content', 'value'=>'' ),
			'textarea_content' => array('type'=>'textarea', 'name'=>'textarea_content', 'label'=>'Content', 'value'=>'', 'hint_text' => 'Avoid using double qoutes inside textarea'),
			'color' => array('type'=>'colorpicker', 'name'=>'color', 'label'=>'Color', 'value'=>'', 'placeholder'=>'Color',),
			'font_size' => array('type'=>'text', 'name'=>'font_size', 'label'=>'Size', 'value'=>'', 'placeholder'=>'Size',),
			'font_weight' => array('type'=>'text', 'name'=>'font_weight', 'label'=>'Weight', 'value'=>'','placeholder'=>'Font Weight'),
			'font_family' => array('type'=>'select', 'name'=>'font_family', 'label'=>'Family', 'options'=>$font_list),
			'line_height' => array('type'=>'text', 'name'=>'line_height', 'label'=>'Line Height', 'value'=>'','placeholder'=>'Line height'),
			'details_color' => array('type'=>'colorpicker', 'name'=>'details_color', 'label'=>'Color', 'value'=>'','placeholder'=>'Color'),
			'details_font_size' => array('type'=>'text', 'name'=>'details_font_size', 'label'=>'Font Size', 'value'=>'','placeholder'=>'Size'),
			'details_font_weight' => array('type'=>'text', 'name'=>'details_font_weight', 'label'=>'Font Weight', 'value'=>'','placeholder'=>'Font weight'),
			'details_line_height' => array('type'=>'text', 'name'=>'details_line_height', 'label'=>'Line Height', 'value'=>'','placeholder'=>'Line height'),
			'details_font_family' => array('type'=>'select', 'name'=>'details_font_family', 'label'=>'Font Family', 'options'=>$font_list),


			'align' => array('type'=>'alignment-icons', 'name'=>'align', 'label'=>'Alignment', 'class'=>'thwec-text-align-input', 'icon_flag'=>false, 'options'=>$float_align),
			'content_align' => array('type'=>'alignment-icons', 'name'=>'content_align', 'label'=>'Alignment', 'class'=>'thwec-text-align-input', 'options'=>$float_align,'icon_flag'=>false),
			'text_align' => array('type'=>'alignment-icons', 'name'=>'text_align', 'label'=>'Text align', 'class'=>'thwec-text-align-input', 'icon_flag'=>true, 'options'=>$text_align),
			'details_text_align' => array('type'=>'alignment-icons', 'name'=>'details_text_align', 'label'=>'Text align', 'class'=>'thwec-text-align-input','icon_flag'=>true, 'options'=>$text_align),
			'border_spacing' => array('type'=>'text', 'name'=>'border_spacing', 'label'=>'Column Spacing'),
			
			'url1'	=> array('type'=>'text', 'name'=>'url1', 'label'=>'Facebook', 'value'=>''),
			'url2'	=> array('type'=>'text', 'name'=>'url2', 'label'=>'Gmail', 'value'=>''),
			'url3'	=> array('type'=>'text', 'name'=>'url3', 'label'=>'Twitter ', 'value'=>''),
			'url4'	=> array('type'=>'text', 'name'=>'url4', 'label'=>'Youtube', 'value'=>''),
			'url5'	=> array('type'=>'text', 'name'=>'url5', 'label'=>'Linkedin', 'value'=>''),
			'url6'	=> array('type'=>'text', 'name'=>'url6', 'label'=>'Pinterest', 'value'=>''),
			'url7'	=> array('type'=>'text', 'name'=>'url7', 'label'=>'Instagram', 'value'=>''),
			'checkbox_option_image'		=> array('type'=>'checkbox','name'=>'checkbox_option_image','label'=>'Product Image','checked'=>0),
			'textareacontent' => array('type'=>'textarea', 'name'=>'textareacontent', 'label'=>'Content', 'value'=>''),
			
			'custom_hook_name' => array('type'=>'text', 'name'=>'custom_hook_name', 'label'=>'Name', 'value'=>'','placeholder'=>'Name of the hook'),
			'additional_css' => array('type'=>'textarea', 'name'=>'additional_css', 'label'=>'Add your custom css here', 'value'=>'', 'class' => 'additional_css', 'sub_label' => 'use the wrapper class <b>thwec-template-block</b> to style contents'),
		);
	}

	public function prepare_template_data( $posted ){
		$template_content = '';
		$template_type = '';
		$template_data = THWEC_Utils::get_template_settings();
		if( is_array( $posted ) ){
			if( isset( $posted['i_template_name'] ) ){// Editing a saved template
				$template_key = sanitize_text_field( $posted['i_template_name'] );
				$template_type = isset( $posted['i_template_type'] ) ? sanitize_text_field( $posted['i_template_type'] ) : 'user';

			}else if( isset( $posted['thwec_wpml_lang'] ) ){
				// When language is swtiched from builder
				$template_key = isset( $posted['template_format_name'] ) ? $posted['template_format_name'] : '';
				$wpml_lang = $this->wpml_default_lang == $posted['thwec_wpml_lang'] ? '' : $posted['thwec_wpml_lang'];
				if( $template_key && !empty( $wpml_lang ) ){
					$template_key = $template_key.'-'.$wpml_lang;
				}
			}else{
				return;
			}

			if( $template_type == 'sample' ){
				$this->template_display_name = '';
				$this->template_json = isset( $template_data['thwec_samples'][$template_key]['template_data'] ) ? $template_data['thwec_samples'][$template_key]['template_data'] : "";
			}else if( isset( $template_data['templates'][$template_key] ) ){
				// Editing a saved template (inlcudes translated templates)
				$this->get_template_data( $template_data, $template_key, $posted );
			}else{
				//If language switcher in builder is changed the first time for a saved template or trying to edit a template not in the tempalte list
				if( isset( $posted['thwec_wpml_lang'] ) ){
					$template_key = isset( $posted['template_format_name'] ) ? $posted['template_format_name'] : '';
					$template_key_lang = $template_key.'-'.strtolower( $this->wpml_default_lang );
					if( isset( $template_data['templates'][$template_key_lang] ) ){
						//Load template from default language
						$this->get_template_data( $template_data, $template_key_lang, $posted, true );
					}else if( isset( $template_data['templates'][$template_key] ) ){
					    //Load non-wpml template if any.
					    $this->get_template_data( $template_data, $template_key, $posted, true );
					}
				}
			}
		}
	}

	public function get_template_data( $data, $key, $posted, $fresh_translation=false ){
		$this->template_display_name = isset( $data['templates'][$key]['display_name'] ) ? $data['templates'][$key]['display_name'] : '';
		
		$this->template_formated_name = isset( $posted['template_format_name'] ) ? sanitize_text_field( $posted['template_format_name'] ) : ( isset( $data['templates'][$key]['lang'] ) ? str_replace( '-'.$data['templates'][$key]['lang'], '', $key ) : '' );
		if( empty( $this->template_formated_name ) ){
		    $this->template_formated_name = strtolower( str_replace(" ", "_", $data['templates'][$key]['display_name']) );
		}

		
		$this->add_css = isset( $data['templates'][$key]['additional_css'] ) ? $data['templates'][$key]['additional_css'] : "";
		if( $fresh_translation && isset( $posted['thwec_wpml_lang'] ) && !empty( $posted['thwec_wpml_lang'] ) ){
			$this->template_lang =  $posted['thwec_wpml_lang'];
		}else{
			$this->template_lang = isset( $data['templates'][$key]['lang'] ) ? $data['templates'][$key]['lang'] : '';
		}
		$this->template_json = isset( $data['templates'][$key]['template_data'] ) ? $data['templates'][$key]['template_data'] : "";
	}

	public function render_template_customizer( $posted ){
		if( is_array( $posted ) && !empty( $posted ) ){
			$this->prepare_template_data( $posted );
		}else{
			$this->prepare_template_data( false ); 
		}
		$this->template_name_save_box();
		$this->template_test_email_box();
			?>
			<div id="thwec-template-builder-wrapper" class="thwec-tbuilder-wrapper">
				<?php
				$this->render_customizer_modal_block();
				$this->render_customizer_header_block();
				$this->render_customizer_body_block();
				$this->preview_tabs();
				?>
			</div>
			<div id="thwec-ajax-load-modal"></div>
			<div id="thwec_builder_save_messages"></div>
						
		<?php 
	    $this->render_customizer_sidebar_layouts();
		$this->render_template_elements();
	}

	private function preview_tabs(){
		?>
		<div id="thwec_tbuilder_editor_preview" class="thwec-tbuilder-editor-preview" style="display: none;"></div>
		<?php
	}

	private function render_customizer_modal_block(){
		?>
		<div id="thwec_builder_header_action_box">
			<div class="content-box"></div>
		</div>
		<?php
	}

	private function render_customizer_header_block(){
		?>
		<div class="thwec-tbuilder-header-panel">
			<div class="thwec-tbuilder-main-actions">
				<?php 
				$this->render_customizer_header_panel(); 
				?>
			</div>
		</div>
		<?php
	}

	private function render_customizer_body_block(){
		?>
		<div id="thwec-sidebar-element-wrapper" class="thwec-tbuilder-elm-wrapper thwec-tbuilder-sub-wrapper">
			<?php
			$this->render_thwec_sidebar();
			?>
		</div>
		<div class="thwec-tbuilder-editor-wrapper thwec-tbuilder-sub-wrapper">
			<?php $this->render_thwec_builder(); ?>
		</div>
		<?php
	}

	private function render_customizer_sidebar_layouts(){
		// Sidebar Layouts - Row/Columns, Basic Elements, WooCommerce Elements, WooCommerce Hooks, Element Settings
		$this->customizer_sidebar_column_listings();
		$this->customizer_sidebar_element_listings();
		$this->customizer_sidebar_layout_settings();
	}

	private function customizer_sidebar_column_listings(){
		$layouts = array(
			'one'	=> '1', 'two'	=> '2', 'three'	=>	'3', 'four'	=> '4',
		);
		?>
		<div id="template_builder_panel_layout" style="display:none;">
			<div class="panel-layout-outer-wrapper">
				<div class="panel-layout-inner-wrapper">
					<table class="thwec-tbuilder-elm-grid">
						<tbody>
							<tr>
								<td class="thwec-layout-note"><p>Pick the number of columns for the row</p></td>
							</tr>
							<?php
							foreach ($layouts as $name => $label) {
								$dash = $name.'-'.'column';
								$u_score = $name.'_'.'column';
								$image = THWEC_ASSETS_URL_ADMIN.'images/'.$u_score.'.png';
								?>
								<tr>
									<td class="elm-col">
										<div id="<?php echo $dash; ?>" class="tbuilder-elm column_layout" data-block-name="<?php echo $u_score; ?>">
											<img src="<?php echo $image;?>" alt="<?php echo ucfirst( $name.' '.$label )?>">
											<p><?php echo $label?> Column</p>
										</div>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php
	}

	private function customizer_sidebar_element_listings(){
	?>
		<div id="template_builder_panel_layout_element" style="display:none;">
			<form name="thwec_tbuilder_layout_elm_form" method ="post" action="">
				<input type="hidden" name="i_block_id" value="">
				<input type="hidden" name="i_block_name" value="">
				<input type="hidden" name="i_col_count" value="">
				<div class="outer-wrapper">
					<div class="inner-wrapper">
					<?php $this->render_elements_list(); ?>
					</div>
				</div>
			</form>
		</div>
	<?php
	}

	private function render_elements_list(){
		?>
		<table class="thwec-tbuilder-elm-grid-layout-element">
			<tbody>
				<!-- Layouts  -->
				<tr>
					<td class="column-layouts">
						<div class="grid-category category-collapse">
							<p class="grid-title" onclick="thwecCategoryCollapse(this)">Layouts<span class="dashicons dashicons-arrow-down-alt2 direction-arrow"></span>
							<div class="grid-content">
								<?php $this->panel_listing_column_elements(); ?>
							</div>
						</div>
					</td>
				</tr>
				<tr class="section-gap"><td></td></tr>
				<tr>
					<td class="column-basic-elements">
						<div class="grid-category">
							<p class="grid-title" onclick="thwecCategoryCollapse(this)">Basic Elements<span class="dashicons dashicons-arrow-down-alt2 direction-arrow"></span></p>
							<div class="grid-content">
								<?php $this->panel_listing_basic_elements(); ?>
							</div>
						</div>
					</td>
				</tr>
				<tr class="section-gap"><td></td></tr>
				<tr>
					<td class="woocommerce-elements">
						<div class="grid-category category-collapse">
							<p class="grid-title" onclick="thwecCategoryCollapse(this)">WooCommerce Elements<span class="dashicons dashicons-arrow-down-alt2 direction-arrow"></span></p>
							<div class="grid-content">
								<?php $this->panel_listing_woo_elements(); ?>
							</div>
						</div>
					</td>
				</tr>
				<tr class="section-gap"><td></td></tr>
				<tr>
					<td class="woocommerce-hooks">
						<div class="grid-category category-collapse">
							<p class="grid-title" onclick="thwecCategoryCollapse(this)">WooCommerce Hooks<span class="dashicons dashicons-arrow-down-alt2 direction-arrow"></span></p>
							<div class="grid-content">
								<?php $this->panel_listing_woo_hooks(); ?>
							</div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function panel_listing_column_elements(){
		$basic_elms = array(
			'one_column' => array(
				'icon'	=>	'one_column.png',
				'label'	=>	'1 Column',
				'alt'	=>	'One column'
			),
			'two_column' => array(
				'icon'	=>	'two_column.png',
				'label'	=>	'2 Column',
				'alt'	=>	'Two column'
			),
			'three_column' => array(
				'icon'	=>	'three_column.png',
				'label'	=>	'3 Column',
				'alt'	=>	'Three column'
			),
			'four_column' => array(
				'icon'	=>	'four_column.png',
				'label'	=>	'4 Column',
				'alt'	=>	'Four column'
			),
		);
		foreach ($basic_elms as $elm_id => $elm_arr) {
			$alt = isset( $elm_arr['alt'] ) ? $elm_arr['alt'] : $elm_arr['label'];
			$label = isset( $elm_arr['skip'] ) && $elm_arr['skip'] ? $elm_arr['label'] : '<br>'.$elm_arr['label'];
			?>
			<div class="elm-col">
				<div class="tbuilder-elm column_layout" data-block-name="<?php echo $elm_id; ?>">
					<div class="thwec-elm-icon">
						<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/'.$elm_arr['icon']; ?>" alt="<?php echo $alt; ?>">
					</div>
					<div class="thwec-elm-icon-text"><?php echo $elm_arr['label']; ?></div>
				</div>
			</div>
			<?php
		}
	}

	private function panel_listing_basic_elements(){
		$basic_elms = array(
			'text' => array(
				'icon'	=>	'text.svg',
				'label'	=>	'Text'
			),
			'image' => array(
				'icon'	=>	'image.svg',
				'label'	=>	'Image',
			),
			'social' => array(
				'icon'	=>	'social.svg',
				'label'	=>	'Social',
				'alt'	=> 	'Social icons'
			),
			'button' => array(
				'icon'	=>	'button.svg',
				'label'	=>	'Button',
			),
			'divider' => array(
				'icon'	=>	'divider.svg',
				'label'	=>	'Divider',
			),
			'gif' => array(
				'icon'		=>	'gif.svg',
				'label'		=>	'Gif',
			),
			'gap' => array(
				'icon'		=>	'gap.svg',
				'label'		=>	'Gap',
			),
		);

		foreach ($basic_elms as $elm_id => $elm_arr) {
			$alt = isset( $elm_arr['alt'] ) ? $elm_arr['alt'] : $elm_arr['label'];
			$label = isset( $elm_arr['skip'] ) && $elm_arr['skip'] ? $elm_arr['label'] : '<br>'.$elm_arr['label'];
			?>
			<div class="elm-col">
				<div id="<?php echo $elm_id; ?>" class="tbuilder-elm block_element" data-block-name="<?php echo $elm_id; ?>">
					<div class="thwec-elm-icon">
						<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/'.$elm_arr['icon']; ?>" alt="<?php echo $alt; ?>">
					</div>
					<div class="thwec-elm-icon-text"><?php echo $label; ?></div>
				</div>
			</div>
			<?php
		}
	}

	private function panel_listing_woo_elements(){
		$woo_elms = array(
			'header_details' => array(
				'icon'	=>	'header.svg',
				'label'	=>	'Header'
			),
			'customer_address' => array(
				'icon'	=>	'customer-details.svg',
				'label'	=>	'Customer',
				'alt'	=> 	'Customer Details'
			),
			'order_details' => array(
				'icon'	=>	'order.svg',
				'label'	=>	'Order',
				'alt'	=> 	'Order Table'
			),
			'billing_address' => array(
				'icon'	=>	'billing-details.svg',
				'label'	=>	'Billing',
				'alt'	=> 	'Billing Details'
			),
			'shipping_address' => array(
				'icon'	=>	'shipping-details.svg',
				'label'	=>	'Shipping',
				'alt'	=> 	'Shipping Details'
			),
			'downloadable_product' => array(
				'icon'		=>	'downloadable-product.svg',
				'label'		=>	'Downloadable Product',
				'skip'		=> true
			),
		);

		foreach ($woo_elms as $elm_id => $elm_arr) {
			$alt = isset( $elm_arr['alt'] ) ? $elm_arr['alt'] : $elm_arr['label'];
			$label = isset( $elm_arr['skip'] ) && $elm_arr['skip'] ? $elm_arr['label'] : '<br>'.$elm_arr['label'];
			?>
			<div class="elm-col">
				<div id="<?php echo $elm_id; ?>" class="tbuilder-elm block_element" data-block-name="<?php echo $elm_id; ?>">
					<div class="thwec-elm-icon">
						<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/'.$elm_arr['icon']; ?>" alt="<?php echo $alt; ?>">
					</div>
					<div class="thwec-elm-icon-text"><?php echo $label; ?></div>
				</div>
			</div>
			<?php
		}
	}

	private function panel_listing_woo_hooks(){
		$woo_hooks = array(
			'email_header' => array(
				'icon'	=>	'email-header-hook.svg',
				'label'	=>	'Email Header',
				'alt'	=> 	'Email header hook'
			),
			'email_order_details' => array(
				'icon'	=>	'email-order-details-hook.svg',
				'label'	=>	'Email Order Details',
				'skip'	=> true
			),
			'before_order_table' => array(
				'icon'	=>	'before-order-table-hook.svg',
				'label'	=>	'Before <br>Order Table',
				'alt'	=> 	'Before Order table hook',
				'skip'	=> true
			),
			'after_order_table' => array(
				'icon'	=>	'after-order-table-hook.svg',
				'label'	=>	'After <br>Order Table',
				'alt'	=> 	'After order table hook',
				'skip'	=> true
			),
			'order_meta' => array(
				'icon'	=>	'order-meta.svg',
				'label'	=>	'Order Meta',
				'alt'	=> 	'Order meta hook',
			),
			'customer_details' => array(
				'icon'	=>	'customer-details-hook.svg',
				'label'	=>	'Customer Details',
			),
			'email_footer' => array(
				'icon'	=>	'email-footer-hook.svg',
				'label'	=>	'Email Footer',
			),
			'custom_hook' => array(
				'icon'	=>	'custom-hook.svg',
				'label'	=>	'Custom Hook',
				'alt'	=> 	'Custom',
			),
		);

		foreach ($woo_hooks as $elm_id => $elm_arr) {
			$alt = isset( $elm_arr['alt'] ) ? $elm_arr['alt'] : $elm_arr['label'].' Hook';
			$label = isset( $elm_arr['skip'] ) && $elm_arr['skip'] ? $elm_arr['label'] : '<br>'.$elm_arr['label'];
			?>
			<div class="elm-col">
				<div class="tbuilder-elm block_element hook_element" data-block-name="<?php echo $elm_id; ?>">
					<div class="thwec-elm-icon">
						<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/'.$elm_arr['icon']?>" alt="<?php echo $alt; ?>">
					</div>
					<div class="thwec-elm-icon-text"><?php echo $label; ?></div>
				</div>
			</div>
			<?php
		}
	}

	public function template_name_save_box(){
		$template_display_name = empty( $this->template_display_name ) ? $this->get_sample_template_name() : $this->template_display_name;
		?>
		<div id="thwec_modal_template_name" style="display: none;">
			<div class="thwec-model-header">
				<span class="dashicons dashicons-no-alt thwec-hover" onclick="thwecCloseModal(this)"></span>
			</div>
			<div class="thwec-modal-body">
				<p><b>Template name</b></p>
				<div class="thwec-template-modal">
					<input class="thwec-template-display-name" type="text" name="template_display_name" value="<?php echo $template_display_name; ?>" autocomplete="off">
					<button class="button btn btn-primary thwec-hover" onclick="thwecInitiateTemplateSave(this)">Save</button>
				</div>
				<p class="thwec-validation-special-char"><i>Use only letters ([a-z],[A-Z]), digits ([0-9]), hyphen ("-") and underscores ("_") for template name</i></p>
				<div class="thwec-action-validation-box">
				</div>
			</div>
		</div>
		<?php
	}

	public function template_test_email_box(){
		$user_email = apply_filters('thwecm_set_testmail_recepient', true) ? THWEC_Admin_Utils::get_logged_in_user_email() : "";
		?>
		<div id="thwec_modal_test_mail" style="display: none;">
			<div class="thwec-model-header">
				<span class="dashicons dashicons-no-alt thwec-hover" onclick="thwecCloseModal(this)"></span>
			</div>
			<div class="thwec-modal-body">
				<p><b>Email id</b></p>
				<div class="thwec-test-mail-modal">
					<input type="text" class="test-mail-input" name="test_mail_input" placeholder="Enter an email id" value="<?php echo $user_email; ?>">
					<button class="button btn btn-primary thwec-hover" onclick="thwecSendTestMail(this)">Send</button>
				</div>
				<p><i>Enter an email id and click Send button, to see how the email template looks in email clients</i></p>
				<div class="thwec-action-validation-box">
				</div>
			</div>
		</div>
		<?php
	}

	private function get_sample_template_name(){
		$name = 'template_'.date('Y_m_d_H_i_s', time()); // GMT timezone
		return $name;
	}

	

	private function render_customizer_header_panel(){
		?>
		<div class="thwec-tbuilder-header-action-left">
			 <div class="thwec-new-template-icon thwec-header-icons">
			 	<div id="thwec_header_sidebar_nav" class="thwec-header-icons-holder thwec-icon-box thwec-header-tab thwec-inactive-nav" onclick="thwecSidebarNavigateBack(this)">
					<span class="thwec-header-icon-inner thwec-header-tab">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<span class="text-content">Back</span>
					</span>
				</div>
				<div class="thwec-header-icons-holder active-tab thwec-icon-box thwec-header-tab" onclick="thwecBuilderPreviewTab(this)" data-tab="builder">
					<span class="thwec-header-icon-inner thwec-header-tab">
						<span class="dashicons dashicons-admin-customizer"></span>
						<span class="text-content">Builder</span>
					</span>
				</div>
				<div class="thwec-header-icons-holder thwec-icon-box thwec-header-tab" onclick="thwecBuilderPreviewTab(this)" data-tab="preview">
					<span class="thwec-header-icon-inner thwec-header-tab">
						<span class="dashicons dashicons-visibility"></span>
						<span class="text-content">Preview</span>
					</span>			
				</div>
				<form id="wpml_lang_selector" method="POST" action="<?php echo $this->get_admin_url(); ?>">
					<div class="thwec-header-icons-holder thwec-icon-box thwec-template-action-button" id="thwec_template_save_button">
						<span class="thwec-header-icon-inner thwec-header-button" onclick="thwecSaveTemplateEvent(this)">
							<span class="dashicons dashicons-index-card"></span>
							<span class="text-content">Save</span>
							<input type="hidden" id="template_save_name" name="template_save_name" value="<?php echo $this->template_display_name; ?>">
							<input type="hidden" id="template_format_name" name="template_format_name" value="<?php echo $this->template_formated_name; ?>">
						</span>
					</div>
					<?php if( $this->wpml_active && $this->wpml_langs ){ ?>
							<div class="thwec-header-icons-holder thwec-icon-box thwec-header-tab thwec-wpml-langs">
								<span class="thwec-header-icon-inner thwec-header-tab">
									<?php $this->render_wpml_langs(); ?>
								</span>			
							</div>
					<?php }?>
					<div class="thwec-header-icons-holder thwec-meta-display-box<?php echo empty( $this->template_display_name) ? ' thwec-empty-name' : ''; ?>">
						<span class="thwec-header-icon-inner">
						<span class="text-content"><?php echo $this->template_display_name; ?></span>
						<span class="dashicons dashicons-edit" onclick="thwecEditTN(this);"></span>
						</span>
					</div>
				</form>
				<div class="thwec-header-icons-holder thwec-icon-box thwec-test-mail-box">
					<span class="thwec-header-icon-inner thwec-header-button" onclick="thwecOpenModal(this, 'test_mail')">
						<span class="dashicons dashicons-email-alt"></span>
						<span class="text-content">Test Email</span>
					</span>
				</div>
			</div>
		</div>
	<?php
	}

	private function render_wpml_langs(){
		$opt_to_select = !empty( $this->template_lang ) ? $this->template_lang : $this->wpml_default_lang;
		?>
		<select name="thwec_wpml_lang" id="thwec_wpml_lang" data-lang-previous="<?php echo $opt_to_select; ?>">
			<?php
			if( is_array( $this->wpml_langs ) ){
				foreach ($this->wpml_langs as $key => $lang) {
					$key = strtolower($lang['default_locale']);
					$selected = $key === $opt_to_select ? "selected" : "";
					$display_name = $lang['display_name'].' ( '.$lang['default_locale'].' ) ';
					echo '<option value="'.$key.'"'.$selected.'>'.$display_name.'</option>';
				}
			}
			?>
		</select>
		<?php
	}

	private function render_thwec_sidebar(){
		$this->render_template_builder_sidebar_body();
	}

	private function render_template_builder_sidebar_body(){
		?>
		<div class="thwec-sidebar-body-wrapper">
			<div id="thwec-sidebar-configure" class="settings-panel-tabs active-tab">
				<?php $this->render_template_builder_panel_configure(); ?>
			</div>
			<div id="thwec-sidebar-settings" class="settings-panel-tabs inactive-tab"></div>
		</div>
		<?php
	}

	private function render_template_builder_panel_configure(){
		$toggle_layers = $this->template_json ? 'thwec-layers-toggle' : '';
		?>
		<div class="thwec-layers-outer-wrapper">
			<div class="thwec-layers-inner-wrapper">
				<table class="thwec-sidebar-config-elm-layers">
					<thead>
						<tr>
							<td class="configure-title"><b>Configure your email template</b></td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<p class="thwec-empty-layer-msg <?php echo $toggle_layers; ?>">Click on <strong>Add Row</strong> button to start building your email template.</p>
								<div class="thwec-builder-elm-layers">
									<?php 
									if($this->template_json){
										$this->create_layers_from_json(); 
									}
									?>
								</div>
							</td>
						</tr>	
						<tr>
							<td>
								<button type="button" onclick="thwecTActionAddRow(this)" class="thwec-sidebar-add-row">Click to add a row</button>						
							</td>
						</tr>												
					</tbody>
				</table>
			</div>
		</div>
		<!-- Remove wecm-disclaimer table once outlook compatibility is fixed -->
		<table class="wecm-disclaimer">
			<tr>
				<td>
					<p class="wecm-disclaimer-text"><span class="dashicons dashicons-info wecm-disclaimer-icon"></span> Email rendering in Outlook may differ due to limited style support.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private function layout_layers_from_json($row_obj){
		?>
		<div id="<?php echo $row_obj[0]->data_id; ?>" class="rows panel-builder-block" data-columns="<?php echo $row_obj[0]->data_count;?>">	
			<div class="layout-lis-item sortable-row-handle">
				<span class="row-name">Row</span>
				<div class="thwec-block-settings">
					<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $row_obj[0]->data_id; ?>, '<?php echo $row_obj[0]->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $row_obj[0]->data_name; ?>">
					<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
					<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
				</div>
			</div>
			<div class="column-set">
				<?php
				if(count($row_obj[0]->child) > 0){
					foreach ($row_obj[0]->child as $col_key) {
						if($col_key->data_type == 'column'){
						?>
							<div id="<?php echo $col_key->data_id; ?>" class="columns panel-builder-block" data-parent="<?php echo $col_key->data_id; ?>">	
								<div class="layout-lis-item sortable-col-handle">
									<span class="column-name" title="Click here to toggle">Column</span>
									<div class="thwec-block-settings">
										<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $col_key->data_id; ?>, '<?php echo $col_key->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $col_key->data_name; ?>">
										<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
										<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
									</div>
								</div>
								<div class="element-set" style="display: none;">
									<div class="thwec-hidden-sortable elements"></div>
								<?php
								if(count($col_key->child) > 0){
									foreach ($col_key->child as $elm_key) {
										if(isset($elm_key->row) && count($elm_key->row[0]->child) > 0){
											$this->layout_layers_from_json($elm_key->row);
										}else if($elm_key->data_type == 'element'){
											// WECM-224 - versions before 2.0.6 don't have child key. Added in 2.0.6
											$elm_key_name = isset($elm_key->child) ? $elm_key->child : ucwords( str_replace("_", " ", $elm_key->data_name) );
										?>
											<div id="<?php echo $elm_key->data_id; ?>" class="elements panel-builder-block">	
												<div class="layout-lis-item sortable-elm-handle">
													<span class="element-name" title="Click here to toggle"><?php echo $elm_key_name; ?></span>
													<div class="thwec-block-settings">
														<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $elm_key->data_id; ?>, '<?php echo $elm_key->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $elm_key->data_name; ?>">
														<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
														<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
													</div>
												</div>
											</div>
											<?php
										}else{
											?>
											<div id="<?php echo $elm_key->data_id; ?>" class="hooks panel-builder-block">
												<div class="layout-lis-item sortable-elm-handle">
													<span class="hook-name"><?php echo $elm_key->child; ?></span>
													<div class="thwec-block-settings">
														<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
													</div>
												</div>
											</div>
											<?php
										}
									}
								}
								?>
								<div class="btn-add-element panel-add-btn panel-add-element"><a href="#">Add Element</a></div>
							</div>
							</div>
						<?php
						}
					}
				}
				?>
				<div class="panel-add-btn btn-add-column" data-parent="<?php echo $row_obj[0]->data_id; ?>"><a href="#">Add Column</a></div>
			</div>
		</div>
		<?php
	}

	private function create_layers_from_json(){
		$json_tree = json_decode( $this->template_json );
		$row_count = $json_tree->row;
		if($json_tree->row){
			foreach ($json_tree->row as $row_child) {
				?>
				<div id="<?php echo $row_child->data_id; ?>" class="rows panel-builder-block" data-columns="<?php echo $row_child->data_count;?>">	
				<div class="layout-lis-item sortable-row-handle">
					<span class="row-name">Row</span>
					<div class="thwec-block-settings">
						<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $row_child->data_id; ?>, '<?php echo $row_child->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $row_child->data_name; ?>">
						<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
						<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
					</div>
				</div>
				<div class="column-set">
				<?php 
				if(count($row_child->child) > 0 && $row_child->child[0]->data_type =='column'){
					foreach ($row_child->child as $child_col) {
						?>
						<div id="<?php echo $child_col->data_id; ?>" class="columns panel-builder-block" data-parent="<?php echo $row_child->data_id; ?>">	
							<div class="layout-lis-item sortable-col-handle">
								<span class="column-name" title="Click here to toggle">Column</span>
								<div class="thwec-block-settings">
									<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $child_col->data_id; ?>, '<?php echo $child_col->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $child_col->data_name; ?>">
									<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
									<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
								</div>
							</div>
							<div class="element-set">
								<div class="thwec-hidden-sortable elements"></div>
							<?php
							if(count($child_col->child) > 0){
								foreach ($child_col->child as $child_elm) {
									if(isset($child_elm->row) && count($child_elm->row[0]->child) > 0){
										$this->layout_layers_from_json($child_elm->row);
									}else if($child_elm->data_type == 'element'){
									?>
										<div id="<?php echo $child_elm->data_id; ?>" class="elements panel-builder-block">	
											<div class="layout-lis-item sortable-elm-handle">
												<span class="element-name" title="Click here to toggle"><?php echo $child_elm->child; ?></span>
												<div class="thwec-block-settings">
													<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, <?php echo $child_elm->data_id; ?>, '<?php echo $child_elm->data_name; ?>')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="<?php echo $child_elm->data_name; ?>">
													<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
													<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
												</div>
											</div>
										</div>
										<?php
									}else{
										?>
										<div id="<?php echo $child_elm->data_id;?>" class="hooks panel-builder-block">
											<div class="layout-lis-item sortable-elm-handle">
												<span class="hook-name"><?php echo $child_elm->child; ?></span>
												<div class="thwec-block-settings">
													<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
												</div>
											</div>
										</div>
										<?php
									}
								}
							}
							?>
							<div class="btn-add-element panel-add-btn panel-add-element"><a href="#">Add Element</a></div>
						</div>
						</div>
						
						<?php
					}
					?>

					<?php
				}
				?>
				<div class="panel-add-btn btn-add-column" data-parent="<?php echo $row_child->data_id; ?>"><a href="#">Add Column</a></div>
				</div>
			</div>
				<?php
			}
		}
	}

	private function customizer_sidebar_layout_settings(){
		?>
		<div id="thwec_builder_block_edit_form" class="thwec-tbuilder-elm-edit" style="display: none;">
			<form name="thwec_builder_block_form" class="thwec-block-settings-form popup_form_class">
				<input type="hidden" name="i_block_id" value="">
				<input type="hidden" name="i_block_name" value="">
				<input type="hidden" name="i_block_props" value="">
				<input type="hidden" name="i_popup_flag" value="">
				<div class="thwec_field_form_outer_wrapper">
					<div class="thwec_field_form_inner_wrapper">
						<table class="thwec_field_form_general" cellspacing="10px">
							<tr>
								<td></td>
							</tr>
						</table>
					</div>
				</div>
			</form>
		</div>
		<?php
		$this->render_builder_elm_pp_rows();
		$this->render_builder_elm_pp_col();
		$this->render_builder_elm_pp_header(); 
		$this->render_builder_elm_pp_image();
		$this->render_builder_elm_pp_social_icons();
		$this->render_builder_elm_pp_customer_address();
		$this->render_builder_elm_pp_billing_address();
		$this->render_builder_elm_pp_shipping_address();
		$this->render_builder_elm_pp_divider();
		$this->render_builder_elm_pp_text();
		$this->render_builder_elm_pp_button();
		$this->render_builder_elm_pp_order();
		$this->render_builder_elm_pp_gif();
		$this->render_builder_elm_pp_gap();
		$this->render_builder_elm_pp_custom_hook();
		$this->render_builder_elm_pp_temp_builder();
	}

	private function render_thwec_builder() {
		?>
		<div class="template-wrapper-settings thwec-icon-wrapper">
			<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/settings-cogwheel.svg';?>" onclick="thwecBuilderBlockEdit(this, 'temp_builder', 'temp_builder')" alt="Template Builder Settings" title="Template Builder Settings">
		</div>
		<table class="thwec-tbuilder-editor-grid">
			<tr>
				<td class="thwec-tbuilder-editor">
					<div id="template_drag_and_drop" class="thwec-dropable-wrapper">
						<?php 
						if($this->template_json){
							$this->render_template_blocks_json();
						}else { ?>
							<table id="tb_temp_builder" width="600" cellspacing="0" cellpadding="0" class="thwec-dropable sortable main-builder thwec-template-block" data-global-id="1000" data-track-save="1000" data-css-change="true" data-sidebar-change="true" data-css-props='{"b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"solid","border_color":"#dedede","bg_color":"#f6f6f6","upload_bg_url":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","additional_css":""}'>
								<tr>
									<td class="thwec-builder-column"></td>
								</tr>
							</table>
						<?php } ?>
					</div>
				</td>
			</tr>
		</table>
		<?php
		$this->render_template_builder_css_section('thwec_template_css');
		?>
		<?php
	}

	private function render_template_blocks_layout_json($row_obj){
		?>
		<table class="thwec-row thwec-block-<?php echo str_replace('_','-',$row_obj[0]->data_name); ?> builder-block" id="tb_<?php echo $row_obj[0]->data_id; ?>" data-css-props='<?php echo $row_obj[0]->data_css?>' data-name="<?php echo $row_obj[0]->data_name;?>" data-column-count="<?php echo $row_obj[0]->data_count; ?>">
				<tbody>
					<tr>
						<?php
						$this->template_json_css .= $this->prepare_css_from_json($row_obj[0]);
						if(count($row_obj[0]->child) > 0){
							foreach ($row_obj[0]->child as $col_key) {
								$this->template_json_css .= $this->prepare_css_from_json($col_key);
								?>
								<td class="column-padding thwec-col thwec-columns" id="tb_<?php echo $col_key->data_id?>" data-css-props='<?php echo $col_key->data_css?>' data-name="<?php echo $col_key->data_name;?>">
									<?php
									if(count($col_key->child) > 0){
										foreach ($col_key->child as $elm_key) {
											if(isset($elm_key->row) && count($elm_key->row[0]->child) > 0){
												$this->render_template_blocks_layout_json($elm_key->row);
											}else if($elm_key->data_type == 'element'){
												$this->template_json_css .= $this->prepare_css_from_json($elm_key);
												$this->render_builder_element_blocks($elm_key, $elm_key->data_name);
											}else{
												$this->render_builder_element_blocks($elm_key, $elm_key->data_name);
											}
										}
									}else{
										echo '<span class="builder-add-btn btn-add-element">+ Add Element</span>';
									}?>	
								</td>
								<?php
							}
						}
						?>
					</tr>
				</tbody>
			</table>
			<?php
	}

	private function render_template_blocks_json(){
		$this->template_json_css = '';
		$builder_data = json_decode( $this->template_json );
		$this->template_json_css .= $this->prepare_css_from_json($builder_data);
		?>
		<table id="tb_temp_builder" cellpadding="0" width="600" cellspacing="0" class="thwec-dropable sortable main-builder thwec-template-block" data-global-id="<?php echo $builder_data->track_save; ?>" data-track-save="<?php echo $builder_data->track_save; ?>" data-css-change="true" data-css-props='<?php echo $builder_data->data_css;?>'>
			<tr>
				<td class="thwec-builder-column">
					<?php 
					$builder_row = $builder_data->row;
					if($builder_data->row){
						foreach ($builder_data->row as $row_child) {
							$this->template_json_css .= $this->prepare_css_from_json($row_child);
							?>
							<table class="thwec-row thwec-block-<?php echo str_replace('_','-',$row_child->data_name); ?> builder-block" id="tb_<?php echo $row_child->data_id; ?>" data-css-props='<?php echo $row_child->data_css?>' data-name="<?php echo $row_child->data_name;?>" data-column-count="<?php echo $row_child->data_count; ?>">
								<tbody>
									<tr>
										<?php
										if(count($row_child->child) > 0 && $row_child->child[0]->data_type =='column'){
											foreach ($row_child->child as $child_col) {
												$this->template_json_css .= $this->prepare_css_from_json($child_col);
											?>
												<td class="column-padding thwec-col thwec-columns" id="tb_<?php echo $child_col->data_id?>" data-css-props='<?php echo $child_col->data_css?>' data-name="<?php echo $child_col->data_name;?>">
													<?php if(count($child_col->child) > 0){
														foreach ($child_col->child as $child_elm) {
															if(isset($child_elm->row) && count($child_elm->row[0]->child) > 0){
																$this->render_template_blocks_layout_json($child_elm->row);
															}else if($child_elm->data_type == 'element'){
																$this->template_json_css .= $this->prepare_css_from_json($child_elm);
																$this->render_builder_element_blocks($child_elm, $child_elm->data_name);
															}else{
																$this->render_builder_element_blocks($child_elm, $child_elm->data_name);
															}	
														}
													}else{
														echo '<span class="builder-add-btn btn-add-element">+ Add Element</span>';
													}?>
													
												</td>
											<?php
											}
										}
										?>
									</tr>
								</tbody>
							</table>
							<?php
						}
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_template_css_json($css){
		?>
		<style id="thwec_template_css_json" type="text/css">
			<?php echo $css; ?>
		</style>
		<script type="text/javascript">
			$('#thwec_template_css_override').append($css);
		</script>
		<?php
	}

	private function clean_textarea_contents_for_html($obj){
		$formatted_text = '';
		if($obj){
			$formatted_text = THWEC_Admin_Utils::is_json_decode($obj);
			if($formatted_text && isset($formatted_text->textarea_content)){
				$f_text = $formatted_text->textarea_content;
				$f_text = str_replace("'","&#39;", $f_text);
				$f_text = str_replace('"',"&quot;", $f_text);
				$formatted_text->textarea_content = $f_text;
			}
			$formatted_text = json_encode($formatted_text);
		}
		return $formatted_text;
	}

	private function render_builder_element_block_details_text($elm, $elm_name){
		if($elm_name == 'text'){
			$data_text = isset($elm->data_text) ? $elm->data_text : false;
			$data_text = $this->clean_textarea_contents_for_html($data_text);
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<table class="thwec-block thwec-block-text builder-block" id="tb_<?php echo $data_id; ?>" data-block-name="<?php echo $elm_name;?>" data-css-props='<?php echo $data_css ;?>' data-text-props='<?php echo htmlentities($data_text_props, ENT_QUOTES);?>' cellspacing="0" cellpadding="0">
			<tr>
				<td class="thwec-block-text-holder">
					<?php 
					if(!empty($data_text_props)){
						$content = THWEC_Admin_Utils::is_json_decode($data_text_props);
						echo isset($content->textarea_content) ? nl2br($content->textarea_content): '';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_builder_element_block_details_header($elm, $elm_name){
		if(isset($elm->data_text)){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($content){
				$header_title = isset($content->content) && !empty($content->content) ? $content->content : '' ;
				$header_logo = isset($content->upload_img_url) && !empty($content->upload_img_url) ? $content->upload_img_url : "" ;
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		$data_css_comp = isset($elm->data_css_compat) ? $elm->data_css_compat : "";
		$css_comp = THWEC_Admin_Utils::is_json_decode($data_css_comp);
		$img_width = isset($css_comp->cmp_img_width) ? $css_comp->cmp_img_width : "";
		$img_height = isset($css_comp->cmp_img_height) ? $css_comp->cmp_img_height : "";
		$img_wrap_width = isset($css_comp->cmp_img_wrap_width) ? $css_comp->cmp_img_wrap_width : "";
		$img_wrap_height = isset($css_comp->cmp_img_wrap_height) ? $css_comp->cmp_img_wrap_height : "";
		?>
		<table class="thwec-block thwec-block-header builder-block" id="tb_<?php echo $data_id; ?>" data-block-name="<?php echo $elm_name;?>" data-css-props='<?php echo $data_css ;?>' data-text-props='<?php echo $data_text_props; ?>' cellpadding="0" cellspacing="0">
				<tr class="header-logo-tr">
					<td class="header-logo">
						<p class="header-logo-ph">
							<img src="<?php echo $header_logo; ?>" alt="Logo" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
						</p>
					</td>
				</tr>
				<tr>
					<td class="header-text">
						<h1><?php echo $header_title; ?></h1>
					</td>
				</tr>
			</table>
			<?php
	}

	private function render_builder_element_block_details_image($elm, $elm_name){
		if(isset($elm->data_text)){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($content){
				$image = isset($content->upload_img_url) && $content->upload_img_url !=='' ? $content->upload_img_url : THWEC_ASSETS_URL_ADMIN.'/images/placeholder.png';
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		$data_css_comp = isset($elm->data_css_compat) ? $elm->data_css_compat : "";
		$css_comp = THWEC_Admin_Utils::is_json_decode($data_css_comp);
		$img_width = isset($css_comp->cmp_img_width) ? $css_comp->cmp_img_width : "";
		$img_height = isset($css_comp->cmp_img_height) ? $css_comp->cmp_img_height : "";
		$img_wrap_width = isset($css_comp->cmp_img_wrap_width) ? $css_comp->cmp_img_wrap_width : "";
		$img_wrap_height = isset($css_comp->cmp_img_wrap_height) ? $css_comp->cmp_img_wrap_height : "";
		?>
		<table class=" thwec-block thwec-block-image builder-block" id="tb_<?php echo $data_id; ?>" data-block-name="<?php echo $elm_name;?>" data-css-props='<?php echo $data_css; ?>' data-text-props='<?php echo $data_text_props; ?>' cellpadding="0" cellspacing="0" align="center">
		    <tr>
		    	<td class="thwec-image-column">
      				<p><img src="<?php echo $image; ?>" alt="Default Image" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>"/></p>
      			</td>
      		</tr>
      	</table>
		<?php
	}

	private function render_builder_element_block_details_social($elm, $elm_name){
		if(isset($elm->data_text)){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			$url1 = isset($content->url1) && !empty($content->url1) ? $content->url1 : "";
			$url2 = isset($content->url2) && !empty($content->url2) ? $content->url2 : "";
			$url3 = isset($content->url3) && !empty($content->url3) ? $content->url3 : "";
			$url4 = isset($content->url4) && !empty($content->url4) ? $content->url4 : "";
			$url5 = isset($content->url5) && !empty($content->url5) ? $content->url5 : "";
			$url6 = isset($content->url6) && !empty($content->url6) ? $content->url6 : "";
			$url7 = isset($content->url7) && !empty($content->url7) ? $content->url7 : "";
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$social_css = THWEC_Admin_Utils::is_json_decode($data_css);
		$align = isset($social_css->content_align) ? $social_css->content_align : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		$data_css_comp = isset($elm->data_css_compat) ? $elm->data_css_compat : "";
		$css_comp = THWEC_Admin_Utils::is_json_decode($data_css_comp);
		$img_width = isset($css_comp->cmp_img_width) ? $css_comp->cmp_img_width : "";
		$img_height = isset($css_comp->cmp_img_height) ? $css_comp->cmp_img_height : "";
		$img_wrap_width = isset($css_comp->cmp_img_wrap_width) ? $css_comp->cmp_img_wrap_width : "";
		$img_wrap_height = isset($css_comp->cmp_img_wrap_height) ? $css_comp->cmp_img_wrap_height : "";
		?>
			<table class="thwec-block thwec-block-social builder-block" id="tb_<?php echo $data_id;?>" data-block-name="<?php echo $elm_name;?>" data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>' cellspacing="0" cellpadding="0">
				<tr>
					<td class="thwec-social-outer-td" align="<?php echo $align; ?>">
						<table class="thwec-social-inner-tb" cellspacing="0" cellpadding="0">
	  						<tr>
	  							<td class="thwec-social-td thwec-td-fb">
  									<p class="thwec-social-icon"><a href="<?php echo $url1;?>" class="facebook">
      									<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/fb_icon_square.png" alt="Facebook Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
      								</a></p>
      							</td>
			      				<td class="thwec-social-td thwec-td-mail">
				  					<p class="thwec-social-icon"><a href="<?php echo $url2;?>" class="mail">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/google_icon_square.png" alt="Google Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
				      			<td class="thwec-social-td thwec-td-tw">
				      				<p class="thwec-social-icon"><a href="<?php echo $url3;?>" class="twitter">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/twitter_icon_square.png" alt="Twitter Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
								
				      			<td class="thwec-social-td thwec-td-yb">
				      				<p class="thwec-social-icon"><a href="<?php echo $url4;?>" class="youtube">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/youtube_icon_square.png" alt="Youtube Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
				      			<td class="thwec-social-td thwec-td-lin">
				      				<p class="thwec-social-icon"><a href="<?php echo $url5;?>" class="linkedin">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/linkedin_icon_square.png" alt="Linkedin Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
				      			<td class="thwec-social-td thwec-td-pin">
				      				<p class="thwec-social-icon"><a href="<?php echo $url6;?>" class="pinterest">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/pinterest_icon_square.png" alt="Pinterest Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
				      			<td class="thwec-social-td thwec-td-insta">
				      				<p class="thwec-social-icon"><a href="<?php echo $url7;?>" class="instagram">
			    	  					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>/images/instagram_icon_square.png" alt="Instagram Icon" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>">
				      				</a></p>
				      			</td>
	  						</tr>
	  					</table>		
					</td>
		  		</tr>
	  		</table>
		<?php
	}

	private function render_builder_element_block_details_button($elm, $elm_name){
		if(isset($elm->data_text)){
			$text_props = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			$content = isset($text_props->content) && !empty($text_props->content) ? $text_props->content : '' ;
			$data_css = isset($elm->data_css) && !empty($elm->data_css) ? $elm->data_css : '' ;
			$data_url = isset($text_props->url) && !empty($text_props->url) ? $text_props->url : '' ;
			$data_title = isset($text_props->title) && !empty($text_props->title) ? $text_props->title : '' ;
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<table cellspacing="0" cellpadding="0" class="thwec-block builder-block thwec-button-wrapper-table" id="tb_<?php echo $data_id;?>" data-block-name="<?php echo $elm_name;?>" data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>' align="center">
              <tr>
                  <td class="thwec-button-wrapper">
                      	<a href="<?php echo $data_url; ?>" title="<?php echo $data_title; ?>" class="thwec-button-link" style="text-decoration: none;"><?php echo $content; ?></a>
                  </td>
              </tr>
          </table>
		<?php
	}

	private function render_builder_element_block_details_divider($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>

		<table class="thwec-block builder-block thwec-block-divider" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>' cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<hr>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_builder_element_block_details_gif($elm, $elm_name){
		if(isset($elm->data_text)){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			$gif = isset($content->upload_img_url) && !empty($content->upload_img_url) ? $content->upload_img_url : THWEC_ASSETS_URL_ADMIN.'/images/placeholder.png';
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<table class="thwec-block thwec-block-gif builder-block" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>' cellspacing="0" cellpadding="0">
        	<tr>
        		<td class="thwec-gif-column">
        			<p><img src="<?php echo $gif; ?>" alt="Default" /></p>
        		</td>
        	</tr>
        </table>
		<?php
	}

	private function render_builder_element_block_details_gap($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<p class="thwec-block thwec-block-gap builder-block" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>'></p>
		<?php
	}

	private function render_builder_element_block_details_customer($elm, $elm_name){
		if(isset($elm->data_text)){
			$details = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($details){
				$details = isset($details->content) && !empty($details->content) ? $details->content : "";
				$data_css = !empty($elm->data_css) ? json_decode($elm->data_css) : '';
				$align = !empty($data_css->align) ? $data_css->align : '';
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<span class="before_customer_table"></span>
		<table class="thwec-block thwec-block-customer builder-block" cellpadding="0" cellspacing="0" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>'>
      		<tr>
      			<td class="thwec-address-alignment" align="<?php echo $align; ?>">
      				<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
      					<tr>
      						<td class="customer-padding">      
      							<h2 class="thwec-customer-header"><?php echo $details; ?></h2>
      							<p class="address thwec-customer-body">
      								John Smith<br>333-6457<br><a href="#">johnsmith@gmail.com</a>
      							</p>	
      						</td>
      					</tr>
      				</table>
      			</td>
      		</tr>
      	</table>
      	<span class="after_customer_table"></span>
		<?php
	}

	private function render_builder_element_block_details_billing($elm, $elm_name){
		if(isset($elm->data_text)){
			$details = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($details){
				$details = isset($details->content) && !empty($details->content) ? $details->content : "";
				$data_css = !empty($elm->data_css) ? json_decode($elm->data_css) : '';
				$align = !empty($data_css->align) ? $data_css->align : '';
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<span class="before_billing_table"></span>
		<table class="thwec-block thwec-block-billing builder-block" cellpadding="0" cellspacing="0"  id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>'>
  			<tr>
      			<td class="thwec-address-alignment" align="<?php echo $align; ?>">
      				<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
      					<tr>
      						<td class="billing-padding">      
  								<h2 class="thwec-billing-header"><?php echo $details; ?></h2>
  								<p class="address thwec-billing-body">
  									John Smith<br>
 									252  Bryan Avenue<br>
 									Minneapolis, MN 55412<br>
 									United States (US)
 									<br>333-6457<br><a href="#">johnsmith@gmail.com</a>
  								</p>
  							</td>
  						</tr>
  					</table>
  				</td>
  			</tr>
  		</table>
      	<span class="after_billing_table"></span>
		<?php
	}
	
	private function render_builder_element_block_details_shipping($elm, $elm_name){
		if(isset($elm->data_text)){
			$details = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($details){
				$details = isset($details->content) && !empty($details->content) ? $details->content : "";
				$data_css = !empty($elm->data_css) ? json_decode($elm->data_css) : '';
				$align = !empty($data_css->align) ? $data_css->align : '';
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<span class="before_shipping_table"></span>
		<table class="thwec-block thwec-block-shipping builder-block" cellpadding="0" cellspacing="0" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $elm_name;?>' data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>'>
  			<tr>
  				<td class="thwec-address-alignment" align="<?php echo $align?>">
  					<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
  						<tr>
  							<td class="shipping-padding">          
			 	 				<h2 class="thwec-shipping-header"><?php echo $details; ?></h2>
			  					<p class="address thwec-shipping-body">
			 						John Smith<br>
			 						252  Bryan Avenue<br>
			 						Minneapolis, MN 55412<br>
			 						United States (US)
			  					</p>
			  				</td>
			  			</tr>
			  		</table>
  				</td>
  			</tr>
  		</table>
      	<span class="after_shipping_table"></span>
		<?php		
	}

	private function render_builder_element_block_details_order($elm, $elm_name){
		if($elm->data_text){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			if($content){
				$content = isset($content->content) && !empty($content->content) ? $content->content : "";
				$data_css = !empty($elm->data_css) ? json_decode($elm->data_css) : '';
				$align = !empty($data_css->align) ? $data_css->align : '';
			}
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_css = isset($elm->data_css) ? $elm->data_css : "";
		$product_css = THWEC_Admin_Utils::is_json_decode($data_css);
		$show_image = $product_css->product_img == 'block' ? 'show-product-img' : '';
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		$ot_td_css = $this->prepare_order_table_column_json( $data_css );
		$thwec_total = array('label1'=>'Subtotal:','label2'=>'Shipping:','label3'=>'Payment method:','label4'=>'Total:','value1'=>'$20','value2'=>'Free shipping','value3'=>'Cash on delivery','value4'=>'$20');

		$thwec_item = array('item1'=>'T-shirt','item2'=>'Jeans','qty1'=>'1','qty2'=>'1','price1'=>'$5','price2'=>'$15');
		?>
		<span class="loop_start_before_order_table"></span>
		<table class="thwec-block thwec-block-order builder-block" id="tb_<?php echo $data_id;?>" data-block-name="<?php echo $elm_name;?>" cellpadding="0" cellspacing="0" data-css-props='<?php echo $data_css;?>' data-text-props='<?php echo $data_text_props;?>'>
			<tr class="before_order_table"></tr>
			<tr>
				<td class="order-padding" align="<?php echo $align; ?>">
					<span class="woocommerce_email_before_order_table"></span>
						<h2 class="thwec-order-heading"><u><span class="order-title"><?php echo $content; ?></span>#248</u> (January 22, 2019)</h2>
					<table class="thwec-order-table thwec-td" style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th class="thwec-td order-head thwec-td-order-product" scope="col" style="">Product</th>
								<th class="thwec-td order-head thwec-td-order-quantity" scope="col" style="">Quantity</th>
								<th class="thwec-td order-head thwec-td-order-price" scope="col" style="">Price</th>
							</tr>
						</thead>
						<tbody>
							<tr class="item-loop-start"></tr>
							<?php for($j=1;$j<=2;$j++) { ?>
							<tr class="woocommerce_order_item_class-filter<?php echo $j; ?>">
								<td class="order-item thwec-td" style="vertical-align:middle;word-wrap:break-word;">
										<div class="thwec-order-item-img <?php echo $show_image?>">
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/product.png'; ?>" alt="Product Image">
										</div>
										<?php echo $thwec_item['item'.$j]; ?>
								</td>
								<td class="order-item-qty thwec-td" style="vertical-align:middle;">
									<?php echo $thwec_item['qty'.$j]; ?>
								</td>
								<td class="order-item-price thwec-td" style="vertical-align:middle;">
									<?php echo $thwec_item['price'.$j];?>
								</td>
							</tr>
							<?php } ?>
							<tr class="item-loop-end"></tr>
						</tbody>
						<tfoot class="order-footer">
							<tr class="order-total-loop-start"></tr>
						<?php 
						for($i=1;$i<=4;$i++){ ?>
							<tr class="order-footer-row">
								<th class="order-total-label thwec-td" scope="row" colspan="2"><?php echo $thwec_total['label'.$i]; ?></th>
								<td class="order-total-value thwec-td"><?php echo $thwec_total['value'.$i]; ?></td>
							</tr>
						<?php } ?>
						<tr class="order-total-loop-end" data-ot-css='<?php echo $ot_td_css; ?>'></tr>
						</tfoot>
					</table>
				</td>
			</tr>
		</table>
		<span class="loop_end_after_order_table"></span>
		<?php
	}

	private function prepare_order_table_column_json( $css ){
		$ot_td_json = [];
		$default_set = THWEC_Admin_Utils::get_ot_td_css();
		$css = json_decode( $css, true );
		if( json_last_error() === 0 ){
			foreach ($default_set as $index => $key) {
				$ot_td_json[$index] = isset( $css[$index] ) ? $css[$index] : '';
			}
		}
		return json_encode( $ot_td_json );
	}


	private function render_builder_element_block_details_email_header_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name; ?>}</p>
		<?php
	}
	private function render_builder_element_block_details_email_order_details_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_before_order_table_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_after_order_table_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>	
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_order_meta_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>	
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_customer_address_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_email_footer_hook($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php		
	}
	private function render_builder_element_block_details_downloadable_product($elm, $elm_name){
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		?>
			<p class="hook-code" id="tb_<?php echo $data_id;?>">{<?php echo $data_name;?>}</p>
		<?php
	}
	private function render_builder_element_block_details_custom_hook($elm, $elm_name){
		if(isset($elm->data_text)){
			$content = THWEC_Admin_Utils::is_json_decode($elm->data_text);
			$name = $content ? $content->custom_hook_name : '';
		}
		$data_id = isset($elm->data_id) ? $elm->data_id : "";
		$data_name = isset($elm->data_name) ? $elm->data_name : "";
		$data_text_props = isset($elm->data_text) ? $elm->data_text: "";
		?>
		<p class="hook-code thwec-block thwec-block-custom-hook builder-block" id="tb_<?php echo $data_id;?>" data-block-name='<?php echo $data_name;?>' data-text-props='<?php echo $data_text_props ;?>'>{<span class="hook-text"><?php echo $name ?></span>}</p>
		<?php
	}

	private function render_builder_element_blocks($elm, $elm_name){
		switch (strtolower($elm_name)) {
			case 'header_details':
				$content = $this->render_builder_element_block_details_header($elm, $elm_name);
				break;

			case 'text':
				$content = $this->render_builder_element_block_details_text($elm, $elm_name);
				break;
			
			case 'image':
				$content = $this->render_builder_element_block_details_image($elm, $elm_name);
				break;

			case 'social':
				$content = $this->render_builder_element_block_details_social($elm, $elm_name);
				break;

			case 'button':
				$content = $this->render_builder_element_block_details_button($elm, $elm_name);
				break;

			case 'divider':
				$content = $this->render_builder_element_block_details_divider($elm, $elm_name);
				break;

			case 'gif':
				$content = $this->render_builder_element_block_details_gif($elm, $elm_name);
				break;

			case 'gap':
				$content = $this->render_builder_element_block_details_gap($elm, $elm_name);
				break;

			case 'customer_address':
				$content = $this->render_builder_element_block_details_customer($elm, $elm_name);
				break;

			case 'billing_address':
				$content = $this->render_builder_element_block_details_billing($elm, $elm_name);
				break;

			case 'shipping_address':
				$content = $this->render_builder_element_block_details_shipping($elm, $elm_name);
				break;

			case 'order_details':
				$content = $this->render_builder_element_block_details_order($elm, $elm_name);
				break;

			case 'email_header_hook':
				$content = $this->render_builder_element_block_details_email_header_hook($elm, $elm_name);
				break;

			case 'email_order_details_hook':
				$content = $this->render_builder_element_block_details_email_order_details_hook($elm, $elm_name);
				break;

			case 'before_order_table_hook':
				$content = $this->render_builder_element_block_details_before_order_table_hook($elm, $elm_name);
				break;

			case 'after_order_table_hook':
				$content = $this->render_builder_element_block_details_after_order_table_hook($elm, $elm_name);
				break;

			case 'order_meta_hook':
				$content = $this->render_builder_element_block_details_order_meta_hook($elm, $elm_name);
				break;

			case 'customer_details_hook':
				$content = $this->render_builder_element_block_details_customer_address_hook($elm, $elm_name);
				break;

			case 'email_footer_hook':
				$content = $this->render_builder_element_block_details_email_footer_hook($elm, $elm_name);
				break;

			case 'downloadable_product_table':
				$content = $this->render_builder_element_block_details_downloadable_product($elm, $elm_name);
				break;

			case 'custom_hook':
				$content = $this->render_builder_element_block_details_custom_hook($elm, $elm_name); 
				break;

			default:
				$content ='';
				break;
		}
		echo $content;
	}

	private function prepare_css_from_json($block_obj){

		$block_css = '';
		$type = isset($block_obj->data_type) ? $block_obj->data_type : false ;
		$json_css = isset($block_obj->data_css) ? json_decode($block_obj->data_css,true) : false;
		$id = '#tb_'.$block_obj->data_id;
		if($json_css && $type){
			if($type == 'builder'){ // Style rendering for builder
				if( $id == '#tb_temp_builder' || $id == '#tb_t_builder' ){
					// For previous version compatibility && Free version compatibility
					$id = '#tb_temp_builder .thwec-builder-column';
				}
				$block_css.= $id.'{';
				foreach($json_css as $key => $value){
					if(isset($this->css_props[$key])){
						$property = $this->css_props[$key];
						if( empty($value) && array_key_exists($property, $this->default_css)){
							$value = $this->default_css[$property]; 
						}
						$block_css.= $property.':'.$value.';';
					}
				}
				$block_css.= '}';
			}else{ 
				$name = isset($block_obj->data_name) ? $block_obj->data_name : '';
				if($json_css){ // Style rendering for rows and columns
					if(array_key_exists($type, $this->json_css_class)){
						$css_name = $this->json_css_class[$type];
						$block_css.= $id.'.'.$css_name.'{';
						foreach($json_css as $key => $value){
							if(isset($this->css_props[$key])){
								$property = $this->css_props[$key];
								if( empty($value) && array_key_exists($property, $this->default_css)){
									$value = $this->default_css[$property]; 
								}
								$block_css.= $property.':'.$value.';';
							}
						}
						$block_css.= '}';
					}else{ // Style rendering for elements
						$json_css = $this->wecm_previous_version_compatibility($name, $json_css);
						if(isset($this->css_elm_props_map[$name])){
							foreach ($this->css_elm_props_map[$name] as $child_class => $index_value) {
								$block_css.= $id.$child_class.'{';
								foreach ($index_value as $css_attr) {
									$property = '';
									$css_value = ''; 
									if( isset($json_css[$css_attr]) && isset($this->css_props[$css_attr]) ){
										$property = $this->css_props[$css_attr];
										if(array_key_exists($json_css[$css_attr], $this->font_family_list)){
											$css_value = $this->font_family_list[$json_css[$css_attr]];
										}else{
											$css_value = $json_css[$css_attr];
										}
										if( empty($css_value) && array_key_exists($property, $this->default_css)){
											$css_value = $this->default_css[$property]; 
										}
										$block_css.= $this->css_props[$css_attr].':'.$css_value.';';
									}
								}
								$block_css.= '}';
							}
						}
					}
				}
			}
		}
		return $block_css;
	}

	public function wecm_previous_version_compatibility($name, $json_css){
		if($name == 'header_details' && $json_css['upload_img_url'] == 'inline-block'){
			$json_css['upload_img_url'] = 'table-row';
		}
		return $json_css;
	}

	private function render_template_builder_css_section($wrapper_id) {
		?>

		<style id="<?php echo $wrapper_id; ?>" type="text/css">
			.main-builder{
				max-width:600px;
				width:600px;
				margin: auto; 
				box-sizing: border-box;
			}

			.main-builder .thwec-builder-column{
				background-color: #f6f6f6;
				vertical-align: top;
				border-radius: 2px;
				background-size: 100%;
				background-position: center;
				background-repeat: no-repeat;
				border-top-width: 1px;
				border-right-width: 1px;
				border-bottom-width: 1px;
				border-left-width: 1px;
				border-style: solid;
				border-color: #dedede;
			}

			.thwec-row{
				border-spacing: 0px;
				padding-top: 0px;
				padding-bottom: 0px;
				padding-right: 0px;
				padding-left: 0px;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

			.thwec-row,
			.thwec-block{
				width:100%;
				table-layout: fixed;
			}

			.thwec-block td{
				padding: 0;
			}

			.thwec-layout-block{
				overflow: hidden;
			}

			.thwec-row td{
				vertical-align: top;
				box-sizing: border-box;
			}

			.thwec-block-one-column,
			.thwec-block-two-column,
			.thwec-block-three-column,
			.thwec-block-four-column{
				max-width: 100%;
				margin: 0 auto;
				margin-top: 0px;
				margin-right: auto;
				margin-bottom: 0px;
				margin-left: auto;
				background-size: 100%;
				background-repeat: no-repeat;
				background-position: center;
			}

			.thwec-row .thwec-columns{
				border-top-width: 1px;
				border-right-width: 1px;
				border-bottom-width: 1px;
				border-left-width: 1px;
				border-style: dotted;
				border-color: #dddddd;
				word-break: break-word;
				padding: 10px 10px;
				text-align: center;
			}

			.thwec-block-one-column >tbody > tr > td{
				width: 100%;				
			}

			.thwec-block-two-column >tbody > tr > td{
				width: 50%;				
			}

			.thwec-block-three-column >tbody > tr > td{
				width: 33%;				
			}

			.thwec-block-four-column >tbody > tr > td{
				width: 25%;				
			}
			
			.thwec-block-gallery-column td{
				width: 30%;
			}
			
			.thwec-block-header{
				overflow: hidden;
				text-align: center;
				box-sizing: border-box;
				position: relative;
				width:100%;
				margin:0 auto;
				max-width: 100%;
				background-size: 100%;
				background-repeat: no-repeat;
				background-position: center;
				background-color:#0099ff;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}
			
			.thwec-block-header .header-logo{
				text-align: center;
				font-size: 0;
				line-height: 1;
				padding: 15px 5px 15px 5px;
			}

			.thwec-block-header .header-logo-tr{
				display: none;
			}

			.thwec-block-header .header-logo-ph{
				width:155px;
				height: 103px;
				margin:0 auto;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
				display: inline-block;
			}

			.thwec-block-header .header-logo-ph img{
				width:100%;
				height:100%;
				display: block;
			}

			.thwec-block-header .header-text{
				padding: 30px 0px 30px 0px;	
				font-size: 0;
			}

			.thwec-block-header .header-text h1{
				margin:0 auto;
				width: 100%;
				max-width: 100%;
				color:#ffffff;
				font-size:40px;
				font-weight:300;
				mso-line-height-rule: exactly;
				line-height:100%;
				vertical-align: middle;
				text-align:center;
			    font-family: Georgia, serif;
			    border:1px solid transparent;
			    box-sizing: border-box;	
			}

			.thwec-block-header .header-text h3{
				padding:0px;
				margin:0;
				color:#ffffff;
				font-size:22px;
				font-weight:300;
				text-align:center;
			    font-family: times;
			    line-height:150%;		
			}

			.thwec-block-header .header-text p{
				margin:0 auto;
				width: 100%;
				max-width: 100%;
				color:#ffffff;
				font-size:40px;
				font-weight:300;
				mso-line-height-rule: exactly;
				line-height:150%;
				text-align:center;
			    font-family: Georgia, serif;
			    border:1px solid transparent;
			    box-sizing: border-box;	
			}

			.thwec-block-divider{
				margin: 0;
			}

			.thwec-block-divider td{
				padding: 20px 0px;
				text-align: center;
			}

			.thwec-block-divider hr{
				display: inline-block;
				border:none;
				border-top: 2px solid transparent;
				border-color: gray;
				width:70%;
				height: 2px;
				margin: 0;
			}

			.thwec-block-text{
				width: 100%;
				color: #636363;
				font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif;
				font-size: 13px;
				line-height: 22px;
				text-align:center;
				margin: 0 auto;
				box-sizing: border-box;
			}

			.thwec-block-text .thwec-block-text-holder{
				color: #636363;
				font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif;
				font-size: 13px;
				line-height: 22px;
				text-align: center;
				padding: 15px 15px;
				background-color: transparent;
				background-size: 100%;
				background-repeat: no-repeat;
				background-position: center;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-color: transparent;
				border-style: none;
			}

			.thwec-block-text .thwec-block-text-holder p.thwec-text-line{
				margin: 0 0 16px;
			}
			
			.thwec-block-text .thwec-block-text-holder a{
				color: #1155cc !important;
			}

			.thwec-block-image{
				width: auto;
				height: auto;
				max-width: 600px;
				box-sizing: border-box;
				width: 100%;
			}

			.thwec-block-image td.thwec-image-column{
				text-align: center;
			}

			.thwec-block-image p{
				padding: 0;
				margin: 0;
				width: 50%;
				padding: 5px 5px;
				display: inline-block;
				max-width: 100%;
				vertical-align: top;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

			.thwec-block-image img {
				width:100%;
				height:100%;
				display:block;
			}

			.thwec-block-shipping .shipping-padding,
			.thwec-block-billing .billing-padding,
			.thwec-block-customer .customer-padding{
				padding: 5px 0px 2px 0px;
			}

			.thwec-block-billing,
			.thwec-block-shipping,
			.thwec-block-customer,
			.thwec-block-shipping .thwec-address-alignment,
			.thwec-block-billing .thwec-address-alignment,
			.thwec-block-customer .thwec-address-alignment{
				margin: 0;
				padding:0;
				border: 0px none transparent;
				border-collapse: collapse;
				box-sizing: border-box;
			}

			.thwec-block-billing .thwec-address-wrapper-table,
			.thwec-block-shipping .thwec-address-wrapper-table,
			.thwec-block-customer .thwec-address-wrapper-table{
				width:100%;
				height: 115px;
				background-repeat: no-repeat;
				background-size: 100%;
				background-color: transparent;
				background-position: center;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

			.thwec-block-customer .thwec-customer-header,
			.thwec-block-billing .thwec-billing-header,
			.thwec-block-shipping .thwec-shipping-header {
				color:#0099ff;
				display:block;
				font-family:"Helvetica Neue",Helvetica,Roboto,Arial,sans-serif;
				font-size:18px;
				font-weight:bold;
				line-height:100%;
				text-align:center;
				margin: 0px;
			}

			.thwec-block-customer .thwec-customer-body,
			.thwec-block-billing .thwec-billing-body,
			.thwec-block-shipping .thwec-shipping-body {
				margin: 0;
				text-align:center;
				line-height:150%;
				border:0px !important;
				font-family: 'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;
				font-size: 13px;
				padding: 0px 0px 0px 0px;
				color: #444444;
				margin: 13px 0px;
			}

			.thwec-block-social{
				text-align: center;
				width:100%;
				box-sizing: border-box;
				background-size: 100%;
				background-repeat: no-repeat;
				background-position: center;
				background-color: transparent;
				margin: 0 auto;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

			.thwec-block-social .thwec-social-outer-td{
				padding-top: 0px;
				padding-right: 0px;
				padding-bottom: 0px;
				padding-left: 0px;
			}

			.thwec-block-social .thwec-social-td{
				padding: 15px 3px 15px 3px;
				font-size: 0;
				line-height: 1px;
			}

			.thwec-block-social .thwec-social-icon{
				width: 40px;
    			height: 40px;
    			margin: 0px;
    			text-decoration:none;
				box-shadow:none;
			}
	
			.thwec-block-social .thwec-social-icon img {
				width: 100%;
				height: 100%;
				display:block;
			}

			.thwec-button-wrapper-table{
				width: 80px;
				margin: 0 auto;
				padding-top: 10px;
				padding-right: 0px;
				padding-bottom: 10px;
				padding-left: 0px;
			}

			.thwec-button-wrapper-table td{
				border-radius: 2px;
				background-color: #4169e1;
				text-align: center;
				padding: 10px 0px;
				border-top-width: 1px;
				border-right-width: 1px;
				border-bottom-width: 1px;
				border-left-width: 1px;
				border-style: solid;
				border-color: #4169e1;
				text-decoration: none;
				color: #fff;
				font-size: 13px;
			}

			.thwec-button-wrapper-table td a.thwec-button-link{
				color: #fff;
				line-height: 150%;
				font-size: 13px;
				text-decoration: none;
    			font-family: 'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;
			}

			.thwec-block-gif{
				margin: 0;
				width: 100%;
				height: auto;
				max-width: 600px;
				box-sizing: border-box;
			}

			.thwec-block-gif td.thwec-gif-column{
				text-align: center;
			}

			.thwec-block-gif td.thwec-gif-column p{
				margin: 0;
				width: 50%;
				padding: 10px 10px;
				display: inline-block;
				vertical-align: top;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

			.thwec-block-gif td.thwec-gif-column img {
				width:100%;
				height:100%;
				display:block;
			}

			.thwec-block-custom-hook{
				/*margin: 0;*/
				/*line-height: 0;*/
			}

			.thwec-block-order{
				background-color: white;
				margin: 0 auto;
				background-size: 100%;
				background-repeat: no-repeat;
				background-position: center;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-color: transparent;
				border-style: none;
			}

			.thwec-block-order td{
				word-break: unset;
			}

			.thwec-block-order .order-padding {
				padding:20px 48px;
			}

			.thwec-block-order .thwec-order-heading {
				font-size:18px;
				text-align:left;
				line-height:100%;
				color: #4286f4;
				font-family: 'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;
			}

			.thwec-block-order .thwec-order-table {
				table-layout: fixed;
				background-color: #ffffff;
				/*margin:auto;*/
				width:100%;
   	 			font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif;
    			color: #636363;
    			border: 1px solid #e5e5e5;
				border-collapse:collapse;
			}
			.thwec-block-order .thwec-td {
				color: #636363;
				border: 1px solid #e5e5e5;
				padding:12px;
				text-align: left;
				font-size: 14px;
    			line-height: 150%;
			}
			.thwec-block-order .thwec-order-item-img{
				margin-bottom: 5px;
				display: none;
			}
			.thwec-block-order .thwec-order-item-img img{
				width: 32px;
				height: 32px;
				display: inline;
				height: auto;
				outline: none;
				line-height: 100%;
				vertical-align: middle;
    			margin-right: 10px;
    			text-decoration: none;
    			text-transform: capitalize;
			}

			.thwec-block-gap{
				height:48px;
				margin: 0;
				box-sizing: border-box;
				background-size: 100%;
				background-color: transparent;
				background-repeat: no-repeat;
				background-position: center;
				border-top-width: 0px;
				border-right-width: 0px;
				border-bottom-width: 0px;
				border-left-width: 0px;
				border-style: none;
				border-color: transparent;
			}

		</style>
		<style id="<?php echo $wrapper_id; ?>_override" type="text/css">
			<?php if($this->template_json_css !== ''){
				echo $this->template_json_css;
			}?>

		</style>
		<style id="<?php echo $wrapper_id; ?>_preview_override" type="text/css">
			<?php if($this->template_json_css){
				$json_css_override = str_replace('tb_', 'tp_', $this->template_json_css);
				echo $json_css_override;
			}?>
		</style>
		<style type="text/css" id="<?php echo $wrapper_id; ?>_additional_css">
			<?php if(trim($this->add_css) != ''){
				echo trim($this->add_css);
			}
			?>
		</style>
		<?php
	}

	private function render_builder_elm_pp_fragment_border($content=false,$prefix='',$toggle=false){
		$atts = array('content' => 'Border Properties', 'padding-top' => '10px');
		if($content){
			$atts['content'] = $content;
		}
		?>
		<table class="thwec-edit-form">
			<thead class="thwec-toggle-section">
			<?php
			$this->render_form_fragment_h_separator($atts,false);
			?>
			</thead>
			<tbody>
				<?php
				$this->render_form_field_element($this->field_props[$prefix.'border_width'], $this->cell_props_T);  

				$this->render_form_field_element($this->field_props[$prefix.'border_style'], $this->cell_props_S); 
				$this->render_form_field_element($this->field_props[$prefix.'border_color'], $this->cell_props_T);
				?>
			</tbody>
		</table>
		<?php
	}

	private function render_builder_elm_pp_fragment_bg($content=false,$prefix=''){
		$atts = array('content' => 'Background Properties', 'padding-top' => '10px');
		if($content){
			$atts['content'] = $content;
		}

		$cell_props = array('input_width' => '100px');
		$cell_props_combo = array('input_width' => '89px','input_margin' => '0px 6px 0px 0px', 'input_height' => '30px', 'input_b_r' => '4px','input_font_size' => '13px');
		$cell_props_combo_S = array('input_width' => '89px','input_margin' => '-4px 0px 0px 0px', 'input_height' => '30px', 'input_b_r' => '4px', 'input_font_size' => '13px');

		?>
		<table class="thwec-edit-form">
			<thead class="thwec-toggle-section">
				<?php
				$this->render_form_fragment_h_separator($atts,false);
				?>
			</thead>
			<tbody>
				<tr class="thwec-input-spacer"><td></td></tr>
				<?php
				$this->render_builder_elm_pp_fragment_img_upload('bg_image','upload_bg_url');
				$this->render_form_field_element($this->field_props[$prefix.'bg_color']);
				?>
				<tr>
					<td>Background</td>  
				</tr>
				<tr>
					<td>
						<?php
						$this->render_form_field_element($this->field_props[$prefix.'bg_size'], $cell_props_combo,false);
						$this->render_form_field_element($this->field_props[$prefix.'bg_position'], $cell_props_combo,false);
						$this->render_form_field_element($this->field_props[$prefix.'bg_repeat'], $cell_props_combo_S,false);
						?>
					</td>
				</tr>
				<tr class="thwec-input-spacer"><td></td></tr>
			</tbody>
		</table>
		<?php
	}

	private function render_builder_elm_pp_fragment_text($text_flag=true,$prefix=false,$weight=true){
		$cell_props = array('input_width' => '100px');
		$cell_props_combo = array('input_width' => '89px','input_margin' => '0px 6px 0px 0px', 'input_height' => '30px', 'input_b_r' => '4px', 'input_font_size' => '13px');
		$cell_props_combo_L = array('input_width' => '88px', 'input_height' => '30px', 'input_b_r' => '4px', 'input_font_size' => '13px');
		?>   
		<?php 
		if($text_flag){
			$this->render_form_field_element($this->field_props[$prefix.'content'], $this->cell_props_FT);
		}
		?> 
		<?php       
		$this->render_form_field_element($this->field_props[$prefix.'color'], $this->cell_props_T);
		
		$this->render_form_field_element($this->field_props[$prefix.'text_align'], $this->cell_props_S);
		?>
		<tr class="thwec-input-spacer"><td></td></tr>
		<tr>
			<td>
			<?php
			$this->render_form_field_element($this->field_props[$prefix.'font_size'], $cell_props_combo,false);
			$this->render_form_field_element($this->field_props[$prefix.'line_height'], $cell_props_combo,false);
			if($weight){
				$this->render_form_field_element($this->field_props[$prefix.'font_weight'], $cell_props_combo_L,false);
			}
			?>
			</td>
		</tr>
		<?php 
		$this->render_form_field_element($this->field_props[$prefix.'font_family'], $this->cell_props_S);
		?>
		<tr class="thwec-input-spacer"><td></td></tr>
		<?php
	}

	private function render_builder_elm_pp_fragment_img_upload($props,$url_type){
		?>
		<tr>
			<td>
				Upload <?php echo str_replace("_", " ", $props);?>
			</td>
		</tr>
		<tr>
			<td>
				<div class="upload-action-settings img-preview-<?php echo $props;?>">
					<div class="thwec-upload-preview" data-default-url ="<?php echo THWEC_ASSETS_URL_ADMIN.'images/placeholder.png'; ?>">
						<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/placeholder.png'; ?>" alt="Upload Preview">
					</div>
					<input type="button" name="image_upload" value="Upload" onclick="thwecImageUploader(this,'<?php echo $props;?>')" class="thwec-upload-button button">
					<input type="button" name="image_upload" value="Remove" class="remove-upload-btn button remove-upload-inactive" onclick="thwecRemoveUploadedImg(this)" data-status="false">
					<?php
					$this->render_form_field_element($this->field_props[$url_type],false,false);
					?>
				</div>
				<div class="thwec-upload-notices"></div>
			</td>
		</tr>
		<?php
	}

	private function render_builder_elm_pp_fragment_img($content=false, $prefix='img_',$type='image'){
		$atts = array('content' => 'Image Properties', 'padding-top' => '10px');
		if($content){
			$atts['content']=$content;
		$this->render_form_fragment_h_separator($atts);
		}
		?>  
		<?php
		$this->render_builder_elm_pp_fragment_img_upload($type,'upload_img_url');       
		$this->render_form_field_element($this->field_props['img_size'], $this->cell_props_T);
		?>
		<?php
		$this->render_form_field_element($this->field_props['content_align'], $this->cell_props_S);
	}

	private function render_builder_elm_pp_rows(){
		?>
		<div id="thwec_field_form_id_row" class=" thpl-admin-form-table thec-admin-form-table" style="display:none;">
			<table class="thwec-edit-form">  
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Row Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<?php       
				$this->render_form_field_element($this->field_props['height'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['border_spacing'],$this->cell_props_T);	
				$this->render_form_field_element($this->field_props['padding'], $this->cell_props_4T);
				$this->render_form_field_element($this->field_props['margin'], $this->cell_props_4T);
				?>
			</table>
			<?php
			$this->render_builder_elm_pp_fragment_border(); 
			$this->render_builder_elm_pp_fragment_bg(); 
			?>
		</div>
        <?php   
	}

	private function render_builder_elm_pp_col(){
		?>
		<div id="thwec_field_form_id_col" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
			<table class="thwec-edit-form">  
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Column Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<?php
				$this->render_form_field_element($this->field_props['width'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['text_align'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['vertical_align'], $this->cell_props_T);
				?>
			</table>
			<?php       
				$this->render_builder_elm_pp_fragment_border(); 
				$this->render_builder_elm_pp_fragment_bg(); 
			?>
		</div>
        <?php   
	}

	private function render_builder_elm_pp_divider(){
		?>
        <div id="thwec_field_form_id_divider" class=" thpl-admin-form-table thec-admin-form-table" style="display:none;">
			<table class="thwec-edit-form">  
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Divider Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts, false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['width'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['divider_height'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['divider_color'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['divider_style'], $this->cell_props_S);       
					?>
				</tbody>
			</table>
			<table class="thwec-edit-form">	
  				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts, false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['content_align'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					?>
				</tbody>
			</table>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_header(){
		?>
        <div id="thwec_field_form_id_header_details" class="thpl-admin-form-table thec-admin-form-table" style="display: none;">
    
		<table class="thwec-edit-form">
			<thead class="thwec-toggle-section">
				<?php
				$atts = array('content' => 'Header Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>
			</thead>
			<tbody>
				<?php        
				$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);       
				$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T); 
				?>	
			</tbody>
		</table>
		<table class="thwec-edit-form">	
  			<thead class="thwec-toggle-section">
  				<?php
				$atts = array('content' => 'Text Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>
  			</thead>
  			<tbody>
  			<?php
  			$this->render_builder_elm_pp_fragment_text(true,false);
  			?>
  			</tbody>
		</table>
		<table class="thwec-edit-form">
			<thead class="thwec-toggle-section">
  				<?php
				$atts = array('content' => 'Logo Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>
  			</thead>
  			<tbody>
  				<?php
				$this->render_builder_elm_pp_fragment_img(false);
				$logo_padding = $this->field_props['img_padding'];
				$logo_padding['label'] = 'Logo Padding';
				$this->render_form_field_element($logo_padding, $this->cell_props_T);
				?>
  			</tbody>
		</table>
		<?php
       	$this->render_builder_elm_pp_fragment_border('Logo Border Properties','img_'); 
		$this->render_builder_elm_pp_fragment_border();
		$this->render_builder_elm_pp_fragment_bg();
		?>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_text(){
		?>
		<div id="thwec_field_form_id_text" class="thpl-admin-form-table thec-admin-form-table" style="display: none;">
			<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Content', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<tr>
						<td style="text-align:center;">
							<p class="no-render-cell-tooltip"><span class="dashicons dashicons-info"></span> Avoid using double quotes inside textarea</p>
							<?php
							echo '<textarea name="i_textarea_content" rows="12" cols="37" style="border-radius:4px;"></textarea>';
							?>
						</td>
					</tr>
				</tbody>
			</table>
				<table class="thwec-edit-form">	
					<thead class="thwec-toggle-section">
				<?php
				$atts = array('content' => 'Font Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>
				</thead>
				<tbody>
				<?php
				$this->render_builder_elm_pp_fragment_text(false,false);
				?>
				</tbody>
			</table>
			<?php
			$this->render_builder_elm_pp_fragment_border();
			$this->render_builder_elm_pp_fragment_bg();
			?>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px', 'class' => 'thwec-seperator-heading');
				$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>   
				<?php       
				$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
				?>
			</table>
		</div>
        <?php   
	}

	private function render_builder_elm_pp_image(){
		?>
		<div id="thwec_field_form_id_image" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
  			<table class="thwec-edit-form">	
  				<thead class="thwec-toggle-section">
  					<?php
  					$atts = array('content' => 'Image Properties', 'padding-top' => '10px', 'class' => 'thwec-seperator-heading');
					$this->render_form_fragment_h_separator($atts,false);
  					?>
  				</thead>   
			<?php
			$this->render_builder_elm_pp_fragment_img(false); 
			?>
       		</table>
       		<?php
       		$this->render_builder_elm_pp_fragment_border('Image Border Properties','img_'); 
       		?>
       		<table class="thwec-edit-form">
       			<thead class="thwec-toggle-section">
					<?php
  					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['img_padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['img_margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['img_bg_color'], $this->cell_props_T);
					?>
				</tbody>
  			</table>
		</div>
        <?php   
	}

	private function render_builder_elm_pp_social_icons(){
		?>
        <div id="thwec_field_form_id_social" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
  			<table class="thwec-edit-form">	
  				<thead class="thwec-toggle-section">
  					<?php
					$atts = array('content' => 'Icon Url', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['url1'], $this->cell_props_LT);
					$this->render_form_field_element($this->field_props['url2'], $this->cell_props_LT);
	 				$this->render_form_field_element($this->field_props['url3'], $this->cell_props_LT);
					$this->render_form_field_element($this->field_props['url4'], $this->cell_props_LT); 
					$this->render_form_field_element($this->field_props['url5'], $this->cell_props_LT); 
					$this->render_form_field_element($this->field_props['url6'], $this->cell_props_LT);        
					$this->render_form_field_element($this->field_props['url7'], $this->cell_props_LT); 
					?>
				</tbody>
			</table>
  			<table class="thwec-edit-form">	
  				<thead class="thwec-toggle-section">
  					<?php
					$atts = array('content' => 'Icon settings', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
  				</thead>
  				<tbody>
  					<?php
  					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
            		$this->render_form_field_element($this->field_props['img_size'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['content_align'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['icon_padding'], $this->cell_props_T);
  					?>
  				</tbody>
			</table>
				<?php
				$this->render_builder_elm_pp_fragment_border();
				$this->render_builder_elm_pp_fragment_bg(); 
				?>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_customer_address(){
		?>
      	<div id="thwec_field_form_id_customer_address" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
    		<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
    			<?php
    			$atts = array('content' => 'Heading Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>	
    			</thead>
    			<tbody>
    				<?php
    				$this->render_builder_elm_pp_fragment_text(true,false);
    				?>
    			</tbody>
			</table>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Details Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_builder_elm_pp_fragment_text(false,'details_');
					?>
				</tbody>
    		</table>
			<?php       
			$this->render_builder_elm_pp_fragment_border(); 
			$this->render_builder_elm_pp_fragment_bg(); 
			?>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
				<?php
				$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
				$this->render_form_fragment_h_separator($atts,false);
				?>
				</thead>
				<tbody>
					<?php       
				$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);   
				$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
				$this->render_form_field_element($this->field_props['align'], $this->cell_props_S);
				?>  
				</tbody>
			</table>			
        </div>
        <?php   
	}

	private function render_builder_elm_pp_billing_address(){
		?>
        <div id="thwec_field_form_id_billing_address" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
        	<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
  					$atts = array('content' => 'Heading Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_builder_elm_pp_fragment_text(true,false); 
					?>
				</tbody>
			</table>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Details Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
  					?>
				</thead>
				<tbody>
					<?php
					$this->render_builder_elm_pp_fragment_text(false,'details_'); 
  					?>
				</tbody>
			</table>
			<?php       
			$this->render_builder_elm_pp_fragment_border(); 
			$this->render_builder_elm_pp_fragment_bg(); 
			?>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php       
					$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);   
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['align'], $this->cell_props_S);
					?>   
				</tbody>
			</table>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_shipping_address(){
		?>
        <div id="thwec_field_form_id_shipping_address" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
        	<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Heading Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php       
					$this->render_builder_elm_pp_fragment_text(true,false); 
					?>   
				</tbody>
			</table>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Details Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php       
					$this->render_builder_elm_pp_fragment_text(false,'details_'); 
					?>   
				</tbody>
			</table>
  			<?php       
			$this->render_builder_elm_pp_fragment_border(); 
			$this->render_builder_elm_pp_fragment_bg(); 
			?>
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php       
					$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['align'], $this->cell_props_S);
					?>   
				</tbody>
			</table>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_button(){
		?>
        <div id="thwec_field_form_id_button" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
  			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
  					<?php
					$atts = array('content' => 'Button Content', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
  				</thead>
  				<tbody>
  					<?php
					$this->render_form_field_element( $this->field_props['url'], $this->cell_props_T);
					$this->render_form_field_element( $this->field_props['content'], $this->cell_props_T);
					$this->render_form_field_element( $this->field_props['title'], $this->cell_props_T);
					?>
  				</tbody>
  			</table>
  			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
  					<?php
  					$atts = array('content' => 'Content Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
  					?>
  				</thead>
  				<tbody>
  					<?php
  					$this->render_builder_elm_pp_fragment_text(false,false,false);
  					$this->render_form_field_element($this->field_props['content_padding'], $this->cell_props_T);
  					?>
  				</tbody>
  			</table>	
			<?php
			$this->render_builder_elm_pp_fragment_border();	
			$this->render_builder_elm_pp_fragment_bg();
			?>	
			 <table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
  					 <?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
  				</thead>
  				<tbody>
  					<?php
					$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
					?>
  				</tbody>
  			</table>
        </div>
        <?php   
	}


	private function render_builder_elm_pp_order(){
		?>
        <div id="thwec_field_form_id_order_details" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
  			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
  					<?php
  					$atts = array('content' => 'Order Title Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
  					?>
  				</thead>
  				<tbody>
  					<?php
  					$this->render_builder_elm_pp_fragment_text(true,false);
  					?>
  				</tbody>
  			</table>
  	
			<table class="thwec-edit-form">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Content Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_builder_elm_pp_fragment_text(false,'details_',false); 
					?>
					<tr>
						<td>
							<?php
							$this->render_form_field_element($this->field_props['checkbox_option_image'],false,false);
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Table Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['content_padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['content_margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['content_size'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['content_bg_color'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['content_border_color'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['align'], $this->cell_props_S);
					?>
				</tbody>
			</table>
  			<?php
  			$this->render_builder_elm_pp_fragment_border(); 
			$this->render_builder_elm_pp_fragment_bg(); 
			?>
			<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts);
					?>
				</thead>
				<tbody>
					<?php
					$this->render_form_field_element($this->field_props['size'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);  
					?>
				</tbody>
			</table>
        </div>
        <?php   
	}	

	private function render_builder_elm_pp_gif(){
		?>
        <div id="thwec_field_form_id_gif" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
  			<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
  					<?php
  					$atts = array('content' => 'Gif Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php
  					$this->render_builder_elm_pp_fragment_img(false,false,'gif');
  					?>
  				</tbody>
  			</table>
  			<?php
  			$this->render_builder_elm_pp_fragment_border();
        	?>
  			<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
					<?php
  					$atts = array('content' => 'Additional Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>
				<tbody>
					<?php       
					$this->render_form_field_element($this->field_props['padding'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['margin'], $this->cell_props_T);
					$this->render_form_field_element($this->field_props['bg_color'], $this->cell_props_T);
					?>
				</tbody>
  			</table>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_gap(){
		?>
        <div id="thwec_field_form_id_gap" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
        	<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
        			<?php
  					$atts = array('content' => 'Gap Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
        		</thead>
        		<tbody>
        			<?php
        			$this->render_form_field_element($this->field_props['height'], $this->cell_props_T);
					?>
        		</tbody>      
			</table>
			<?php       
        	$this->render_builder_elm_pp_fragment_border();
        	$this->render_builder_elm_pp_fragment_bg();
        	?>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_custom_hook(){
		?>
        <div id="thwec_field_form_id_custom_hook" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">
        	<table class="thwec-edit-form">
				<thead class="thwec-toggle-section">
        			<?php
  					$atts = array('content' => 'Hook Properties', 'padding-top' => '10px');
					$this->render_form_fragment_h_separator($atts,false);
					?>
        		</thead>
        		<tbody>
        			<?php
        			$this->render_form_field_element($this->field_props['custom_hook_name'], $this->cell_props_FT); 
					?>
					<tr>
						<td style="font-style:italic;font-size: 12px;text-align:justify;">
							<br>
							Enter a unique name for the action hook. Using an existing hook name can cause unexpected errors.

							<br><br> Parameters available for the hook are <b>$order</b> and <b>$email</b>
						</td>
					</tr>
        		</tbody>
        		<tfoot>
        			<tr>
        				<td>
        					<div id="sb_validation_msg"></div>
        				</td>
        			</tr>
        		</tfoot>      
			</table>
        </div>
        <?php   
	}

	private function render_builder_elm_pp_temp_builder(){
		?>
		<div id="thwec_field_form_id_temp_builder" class=" thpl-admin-form-table thec-admin-form-table" style="display:none">

			<?php
			$this->render_builder_elm_pp_fragment_border(false,'',true);
        	$this->render_builder_elm_pp_fragment_bg();
        	?>
        	<table class="thwec-edit-form toggle-builder-settings">	
				<thead class="thwec-toggle-section">
					<?php
					$atts = array('content' => 'Additional CSS', 'padding-top' => '10px', 'class' => 'thwec-seperator-heading thwec-seperator-button-box', 'additional' => 'button');
					$this->render_form_fragment_h_separator($atts,false);
					?>
				</thead>   
				<?php       
				$this->render_form_field_element($this->field_props['additional_css'], $this->cell_props_TA);
				?>
			</table>
        </div>
        <?php
	}

	public function render_template_elements(){
		$this->render_template_layout_1_col_row();
		$this->render_template_layout_2_col_row();
		$this->render_template_layout_3_col_row();
		$this->render_template_layout_4_col_row();

		$this->render_template_element_header();
		$this->render_template_element_customer_address();
		$this->render_template_element_order_details();
		$this->render_template_element_billing_address();
		$this->render_template_element_shipping_address();
		$this->render_template_element_text();
		$this->render_template_element_image();
		$this->render_template_element_social();
		$this->render_template_element_button();
		$this->render_template_element_divider();
		$this->render_template_element_gap();
		$this->render_template_element_gif();

		$this->render_template_hook_email_header();
		$this->render_template_hook_email_order_details();
		$this->render_template_hook_before_order_table();
		$this->render_template_hook_after_order_table();
		$this->render_template_hook_order_meta();
		$this->render_template_hook_customer_address();
		$this->render_template_hook_email_footer();
		$this->render_template_hook_downloadable_product();
		$this->render_template_custom_hook();

		$this->render_template_tracking_add_row_html();
		$this->render_template_tracking_add_col_html();
		$this->render_template_tracking_add_elm_html();
		$this->render_template_tracking_add_hook_html();
		// $this->add_column_confirm_dialog();
		// $this->save_changes_confirm_dialog();
		$this->render_template_confirmation_alerts();
		$this->render_template_confirmation_msg_line();

	}

	private function render_template_layout_1_col_row(){
		?>
		<div id="thwec_template_layout_1_col" style="display:none;">
			<table class="thwec-row thwec-block-one-column builder-block" id="one_column" data-elm="row-1-col" data-css-props='{"height":"","border_spacing":"0px","p_t":"0px","p_r":"0px","p_b":"0px","p_l":"0px","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat"}' data-column-count="1">
				<tbody>
					<tr>
						<td class="column-padding thwec-col thwec-columns" id="one_column_1" data-css-props='{"width":"100%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","text_align":"center","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	
	private function render_template_layout_2_col_row(){
		?>
		<div id="thwec_template_layout_2_col" style="display:none;">
			<table class="thwec-row thwec-block-two-column builder-block" id="two_column" data-elm="row-2-col" cellpadding="0" cellspacing="0" data-css-props='{"height":"","border_spacing":"","p_t":"","p_r":"","p_b":"","p_l":"","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat"}' data-column-count="2">
				<tbody>
					<tr>
						<td class="column-padding thwec-col thwec-columns" id="two_column_1"  data-css-props='{"width":"50%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-col thwec-columns" id="two_column_2" data-css-props='{"width":"50%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"","bg_size":"","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	
	private function render_template_layout_3_col_row(){
		?>
		<div id="thwec_template_layout_3_col" style="display:none;">
			<table class="thwec-row thwec-block-three-column builder-block" id="three_column" cellpadding="0" cellspacing="0" data-css-props='{"height":"","border_spacing":"","p_t":"","p_r":"","p_b":"","p_l":"","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat"}' data-column-count="3">
				<tbody>
					<tr>
						<td class="column-padding thwec-columns" id="three_column_1" data-css-props='{"width":"33.333333333333336%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-columns" id="three_column_2" data-css-props='{"width":"33.333333333333336%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-columns" id="three_column_3" data-css-props='{"width":"33.333333333333336%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	
	private function render_template_layout_4_col_row(){
		?>
		<div id="thwec_template_layout_4_col" style="display:none;">
			<table class="thwec-row thwec-block-four-column builder-block" id="four_column" cellpadding="0" cellspacing="0" data-css-props='{"height":"","border_spacing":"","p_t":"","p_r":"","p_b":"","p_l":"","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat"}' data-column-count="4">
				<tbody>
					<tr>           
						<td class="column-padding thwec-columns" id="four_column_1" data-css-props='{"width":"25%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-columns" id="four_column_2" data-css-props='{"width":"25%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-columns" id="four_column_3" data-css-props='{"width":"25%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
						<td class="column-padding thwec-columns" id="four_column_4" data-css-props='{"width":"25%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","text_align":"center","vertical_align":"top"}'>
							<span class="builder-add-btn btn-add-element">+ Add Element</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_template_element_header(){
		?>
		<div id="thwec_template_elm_header" style="display: none;">
			<table class="thwec-block thwec-block-header builder-block" id="{header_details}" data-block-name="header_details" cellpadding="0" cellspacing="0" data-css-props='{"size_width":"100%","size_height":"", "p_t":"30px","p_r":"0px","p_b":"30px","p_l":"0px","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","color":"#ffffff","font_size":"40px","line_height":"100%","text_align":"center","font_family":"georgia","font_weight":"normal","img_p_t":"15px","img_p_r":"5px","img_p_b":"15px","img_p_l":"5px","img_border_style":"none","img_border_width_top":"0px","img_border_width_right":"0px","img_border_width_bottom":"0px","img_border_width_left":"0px","img_border_color":"","bg_color":"#0099ff","bg_size":"100%","bg_position":"center","bg_repeat":"no-repeat","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","content":"","upload_bg_url":"","upload_img_url":"","content_align":"center","img_size_width":"155px","img_size_height":"103px"}' data-text-props='{"content":"Email Template Header","upload_img_url":""}'>
				<tr class="header-logo-tr">
					<td class="header-logo">
						<p class="header-logo-ph">
							<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/placeholder.png" alt="Logo">
						</p>
					</td>
				</tr>
				<tr>
					<td class="header-text">
						<h1>Email Template Header</h1>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

		private function render_template_element_customer_address(){
		?>
		<div id="thwec_template_elm_customer_address" style="display:none;">
			<span class="before_customer_table"></span>
			<table class="thwec-block thwec-block-customer builder-block" id="{customer_address}" data-block-name="customer_address" cellpadding="0" cellspacing="0" data-css-props='{"align":"center","color":"#0099ff","text_align":"center","font_size":"18px","line_height":"100%","font_weight":"bold","font_family":"helvetica","details_color":"#444444","details_text_align":"center","details_font_size":"13px","details_line_height":"150%","details_font_weight":"normal","details_font_family":"helvetica","size_width":"100%","size_height":"115px","p_t":"5px","p_r":"0px","p_b":"2px","p_l":"0px","m_t":"0px","m_r":"0px","m_b":"0px","m_l":"0px","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_position":"center","bg_color":"","bg_size":"100%","bg_repeat":"no-repeat","content":""}' data-text-props='{"content":"Customer Details"}'>
      			<tr>
      				<td class="thwec-address-alignment" align="center">
      					<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
      						<tr>
      							<td class="customer-padding">
 			     					<h2 class="thwec-customer-header">Customer Details</h2>
      								<p class="address thwec-customer-body">
      								John Smith<br>333-6457<br><a href="#">johnsmith@gmail.com</a>
      								</p>
      							</td>
      						</tr>
      					</table>	
      				</td>
      			</tr>
      		</table>
      		<span class="after_customer_table"></span>
		</div>
		<?php
	}

	private function render_template_element_order_details(){
		?>
		<div id="thwec_template_elm_order_details" style="display:none;">
			<?php
			$thwec_total = array('label1'=>'Subtotal:','label2'=>'Shipping:','label3'=>'Payment method:','label4'=>'Total:','value1'=>'$20','value2'=>'Free shipping','value3'=>'Cash on delivery','value4'=>'$20');
			$thwec_item = array('item1'=>'T-shirt','item2'=>'Jeans','qty1'=>'1','qty2'=>'1','price1'=>'$5','price2'=>'$15');
			?>
			<span class="loop_start_before_order_table"></span>
			<table class="thwec-block thwec-block-order builder-block" id="{order_details}" data-block-name="order_details" cellpadding="0" cellspacing="0" data-css-props='{"content":"","align":"center","color":"#4286f4","text_align":"left","font_size":"18px","line_height":"100%","font_weight":"bold","font_family":"helvetica","details_color":"#636363","details_text_align":"left","details_font_size":"14px","details_line_height":"150%","details_font_weight":"","details_font_family":"helvetica","content_p_t":"12px","content_p_r":"12px","content_p_b":"12px","content_p_l":"12px","content_m_t":"0px","content_m_r":"0px","content_m_b":"0px","content_m_l":"0px","content_size_width":"100%","content_size_height":"","content_bg_color":"#ffffff","content_border_color":"#e5e5e5","size_width":"100%","size_height":"","p_t":"20px","p_r":"48px","p_b":"20px","p_l":"48px","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_size":"100%","bg_position":"center","bg_repeat":"no-repeat","bg_color":"#ffffff","product_img":"none"}' data-text-props='{"content":"Order"}' align="center">
				<tr class="before_order_table"></tr>
				<tr>
					<td class="order-padding" align="center">
						<span class="woocommerce_email_before_order_table"></span>
      					<h2 class="thwec-order-heading"><u><span class="order-title">Order</span>#248</u> (January 22, 2019)</h2>
						<table class="thwec-order-table thwec-td" style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th class="thwec-td order-head thwec-td-order-product" scope="col" style="">Product</th>
									<th class="thwec-td order-head thwec-td-order-quantity" scope="col" style="">Quantity</th>
									<th class="thwec-td order-head thwec-td-order-price" scope="col" style="">Price</th>
								</tr>
							</thead>
							<tbody>
								<tr class="item-loop-start"></tr>
								<?php for($j=1;$j<=2;$j++) { ?>
								<tr class="woocommerce_order_item_class-filter<?php echo $j; ?>">
									<td class="order-item thwec-td" style="vertical-align:middle;word-wrap:break-word;">
											<div class="thwec-order-item-img">
												<img src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/product.png'; ?>" alt="Product Image">
											</div>
											<?php echo $thwec_item['item'.$j]; ?>
									</td>
									<td class="order-item-qty thwec-td" style="vertical-align:middle;">
										<?php echo $thwec_item['qty'.$j]; ?>
									</td>
									<td class="order-item-price thwec-td" style="vertical-align:middle;">
										<?php echo $thwec_item['price'.$j];?>
									</td>
								</tr>
								<?php } ?>
								<tr class="item-loop-end"></tr>
							</tbody>
							<tfoot class="order-footer">
								<tr class="order-total-loop-start"></tr>
							<?php 
							for($i=1;$i<=4;$i++){ ?>
								<tr class="order-footer-row">
									<th class="order-total-label thwec-td" scope="row" colspan="2"><?php echo $thwec_total['label'.$i]; ?></th>
									<td class="order-total-value thwec-td"><?php echo $thwec_total['value'.$i]; ?></td>
								</tr>
							<?php } ?>
							<tr class="order-total-loop-end" data-ot-css='<?php echo THWEC_Admin_Utils::get_ot_td_css( true ); ?>'></tr>
							</tfoot>
						</table>
      					</td>
      				</tr>
      			</table>
      			<span class="loop_end_after_order_table"></span>
			</div>

		<?php
	}

	private function render_template_element_billing_address(){
		?>
		<div id="thwec_template_elm_billing_address" style="display:none;">
			<span class="before_billing_table"></span>
			<table class="thwec-block thwec-block-billing builder-block" id="{billing_address}" data-block-name="billing_address" cellpadding="0" cellspacing="0" data-css-props='{"align":"center","color":"#0099ff","text_align":"center","font_size":"18px","line_height":"100%","font_weight":"bold","font_family":"helvetica","details_color":"#444444","details_text_align":"center","details_font_size":"13px","details_line_height":"150%","details_font_weight":"normal","details_font_family":"helvetica","size_width":"100%","size_height":"115px","p_t":"5px","p_r":"0px","p_b":"2px","p_l":"0px","m_t":"0px","m_r":"0px","m_b":"0px","m_l":"0px","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","bg_color":"","content":""}' data-text-props='{"content":"Billing Details"}'>
      			<tr>
      				<td class="thwec-address-alignment" align="center">  	
      					<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
      						<tr>
      							<td  class="billing-padding">
      								<h2 class="thwec-billing-header">Billing Details</h2>
			      					<p class="address thwec-billing-body">
			      						John Smith<br>
			     						252  Bryan Avenue<br>
			     						Minneapolis, MN 55412<br>
			     						United States (US)
			     						<br>333-6457<br><a href="#">johnsmith@gmail.com</a>
			      					</p>
      							</td>
      						</tr>
      					</table>
      				</td>
      			</tr>
      		</table>
      		<span class="after_billing_table"></span>
		</div>
		<?php
	}

	private function render_template_element_shipping_address(){
		?>
		<div id="thwec_template_elm_shipping_address" style="display:none;">
			<span class="before_shipping_table"></span>
			<table class="thwec-block thwec-block-shipping builder-block" id="{shipping_address}" data-block-name="shipping_address" cellpadding="0" cellspacing="0" data-css-props='{"align":"center","color":"#0099ff","text_align":"center","font_size":"18px","line_height":"100%","font_weight":"bold","font_family":"helvetica","details_color":"#444444","details_text_align":"center","details_font_size":"13px","details_line_height":"150%","details_font_weight":"normal","details_font_family":"helvetica","size_width":"100%","size_height":"115px","p_t":"5px","p_r":"0px","p_b":"2px","p_l":"0px","m_t":"0px","m_r":"0px","m_b":"0px","m_l":"0px","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_position":"center","bg_size":"100%","bg_color":"","bg_repeat":"no-repeat","content":""}' data-text-props='{"content":"Shipping Details"}'>
      			<tr>
      				<td class="thwec-address-alignment" align="center">
      					<table class="thwec-address-wrapper-table" cellpadding="0" cellspacing="0">
      						<tr>
      							<td class="shipping-padding">      
     	 							<h2 class="thwec-shipping-header">Shipping Details</h2>
      								<p class="address thwec-shipping-body">
     								John Smith<br>
     								252  Bryan Avenue<br>
     								Minneapolis, MN 55412<br>
     								United States (US)
      								</p>
      							</td>
      						</tr>
      					</table>
      				</td>
      			</tr>
      		</table>
      		<span class="after_shipping_table"></span>
		</div>
		<?php
	}

	private function render_template_element_text(){
		?>
		<div id="thwec_template_elm_text" style="display:none;">
			<table class="thwec-block thwec-block-text builder-block" id="{text}" data-block-name="text" data-css-props='{"color":"#636363", "align":"center", "font_size":"13px", "line_height":"22px", "font_weight":"normal", "font_family":"helvetica", "bg_color":"", "upload_bg_url":"", "bg_size":"100%", "bg_position":"center", "bg_repeat":"no-repeat", "b_t":"0px", "b_r":"0px", "b_b":"0px", "b_l":"0px", "border_color":"", "border_style":"none", "size_width":"100%", "size_height":"", "m_t":"0px", "m_r":"auto", "m_b":"0px", "m_l":"auto", "p_t":"15px", "p_r":"15px", "p_b":"15px", "p_l":"15px", "text_align":"center","textarea_content":""}' data-text-props='{"textarea_content":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#39;s standard dummy text ever since the 1500."}' cellspacing="0" cellpadding="0">
				<tr>
					<td class="thwec-block-text-holder">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500.</td>
				</tr>
			</table>
		</div>
		<?php
	}


	private function render_template_element_image(){
		?>
		<div id="thwec_template_elm_image" style="display:none;"> 
		    <table class=" thwec-block thwec-block-image builder-block" id="{image}" cellpadding="0" cellspacing="0" data-block-name="image" align="center" data-css-props='{"img_size_width":"50%","img_size_height":"","img_m_t":"0px","img_m_r":"auto","img_m_b":"0px","img_m_l":"auto","img_p_t":"5px","img_p_r":"5px","img_p_b":"5px","img_p_l":"5px","img_border_width_top":"0px","img_border_width_right":"0px","img_border_width_bottom":"0px","img_border_width_left":"0px","img_border_style":"none","img_border_color":"","align":"","upload_img_url":"","img_bg_color":"","content_align":"center"}' data-text-props='{"upload_img_url":""}'>
		    	<tr>
		    		<td class="thwec-image-column">
      					<p><img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/placeholder.png" alt="Default Image" width="288" height="186"/></p>
      				</td>
      			</tr>
      		</table>
		</div>
		<?php
	}

	private function render_template_element_social(){
		?>
		<div id="thwec_template_elm_social" style="display:none;">
  			<table class="thwec-block thwec-block-social builder-block" id="{social}" data-block-name="social" data-css-props='{"p_t":"0px","p_r":"0px","p_b":"0px","p_l":"0px","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","img_size_width":"40px","img_size_height":"40px","icon_p_t":"15px","icon_p_r":"3px","icon_p_b":"15px","icon_p_l":"3px","content_align":"center","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","bg_color":"","bg_size":"100%","bg_position":"center","bg_repeat":"no-repeat","upload_bg_url":"","url1":"table-cell","url2":"table-cell","url3":"table-cell","url4":"table-cell","url5":"table-cell","url6":"table-cell","url7":"table-cell"}' data-text-props='{"url1":"http://www.facebook.com/","url2":"https://mail.google.com/mail/?view=cm&to=yourmail@example.com&bcc=somemail@example.com","url3":"http://www.twitter.com/","url4":"http://www.youtube.com/","url5":"https://www.linkedin.com/","url6":"http://www.pinterest.com/","url7":"http://www.instagram.com/"}' cellspacing="0" cellpadding="0">
  				<tbody>
  					<tr>
	  					<td class="thwec-social-outer-td" align="center">
	  						<table class="thwec-social-inner-tb" cellspacing="0" cellpadding="0">
	  							<tr>
	  								<td class="thwec-social-td thwec-td-fb">
				  						<p class="thwec-social-icon"><a href="http://www.facebook.com" class="facebook">
				      						<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/fb_icon_square.png" alt="Facebook Icon" width="40" height="40">
				      					</a></p>
				      				</td>
						      		<td class="thwec-social-td thwec-td-mail">
										<p class="thwec-social-icon"><a href="https://mail.google.com/mail/?view=cm&to=yourmail@example.com&bcc=somemail@example.com" class="mail" >
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/google_icon_square.png" alt="Google Icon" width="40" height="40">
										</a></p>
									</td>
									<td class="thwec-social-td thwec-td-tw">	
										<p class="thwec-social-icon"><a href="http://www.twitter.com" class="twitter">
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/twitter_icon_square.png" alt="Twitter Icon" width="40" height="40">
										</a></p>
									</td>
									<td class="thwec-social-td thwec-td-yb">
										<p class="thwec-social-icon"><a href="http://www.youtube.com" class="youtube">
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/youtube_icon_square.png" alt="Youtube Icon" width="40" height="40">
										</a></p>
									</td>
									<td class="thwec-social-td thwec-td-lin">
										<p class="thwec-social-icon"><a href="https://www.linkedin.com" class="linkedin">
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/linkedin_icon_square.png" alt="Linkedin Icon" width="40" height="40">
										</a></p>
									</td>
									<td class="thwec-social-td thwec-td-pin">
										<p class="thwec-social-icon"><a href="http://www.pinterest.com" class="pinterest">
											<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/pinterest_icon_square.png" alt="Pinterest Icon" width="40" height="40">
										</a></p>
									</td>
									<td class="thwec-social-td thwec-td-insta">
					  					<p class="thwec-social-icon"><a href="http://www.instagram.com" class="instagram">
					    					<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>/images/instagram_icon_square.png" alt="Instagram Icon" width="40" height="40">
					  					</a></p>
					  				</td>
	  							</tr>
	  						</table>
	  					</td>
	  				</tr>
	  			</tbody>
  			</table>
		</div>
		<?php
	}

	private function render_template_element_button(){
		?>
		<div id="thwec_template_elm_button" style="display:none;">
       		<table cellspacing="0" cellpadding="0" class="thwec-block builder-block thwec-button-wrapper-table" id="{button}" data-block-name="button" align="center" data-css-props='{"size_width":"80px","size_height":"20px","text_align":"center","color":"#fff","font_size":"13px","line_height":"150%","font_family":"helvetica","font_weight":"","p_t":"10px","p_r":"0px","p_b":"10px","p_l":"0px","m_t":"0px","m_r":"auto","m_b":"0px","m_l":"auto","content_p_t":"10px","content_p_r":"0px","content_p_b":"10px","content_p_l":"0px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"solid","border_color":"#4169e1","upload_bg_url":"","bg_color":"#4169e1","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","content":"","url":"","title":""}' data-text-props='{"content":"Click Here","url":"#","title":"Title text"}'>
              <tr>
                  <td class="thwec-button-wrapper">
                      	<a href="#" title="Title text" class="thwec-button-link" style="text-decoration: none;">Click Here</a>
                  </td>
              </tr>
          </table>
		</div>
		<?php
	}

	private function render_template_element_divider(){
		?>
		<div id="thwec_template_elm_divider" style="display:none;">
      		<table cellspacing="0" cellpadding="0" class="thwec-block builder-block thwec-block-divider" id="{divider}" data-block-name="divider" data-css-props='{"width":"70%","divider_height":"2px","divider_color":"#808080","divider_style":"solid","m_t":"0px","m_r":"0px","m_b":"0px","m_l":"0px","p_t":"20px","p_r":"0px","p_b":"20px","p_l":"0px","content_align":"center"}'>
      			<tr><td><hr></td></tr>
      		</table>
		</div>
		<?php
	}

	private function render_template_element_gap(){
		?>
		<div id="thwec_template_elm_gap" style="display:none;">
      		<p class="thwec-block thwec-block-gap builder-block" id="{gap}" data-block-name="gap" data-css-props='{"height":"48px","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat"}'></p>
		</div>
		<?php
	}

	private function render_template_element_gif(){
		?>
		<div id="thwec_template_elm_gif" style="display:none;">
        	<table class="thwec-block thwec-block-gif builder-block" id="{gif}" data-block-name="gif" data-css-props='{"p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","m_t":"0px","m_r":"0px","m_b":"0px","m_l":"0px","border_style":"none","border_color":"","upload_bg_url":"","bg_color":"","bg_position":"center","bg_size":"100%","bg_repeat":"no-repeat","img_size_width":"50%","img_size_height":"","content_align":"center","upload_img_url":""}' data-text-props='{"upload_img_url":""}' cellpadding="0" cellspacing="0">
		    	<tr>
		    		<td class="thwec-gif-column">
      					<p>
      						<img src="<?php echo THWEC_ASSETS_URL_ADMIN ?>images/placeholder.png" alt="Default" />
      					</p>
      				</td>
      			</tr>
      		</table>

		</div>
		<?php
	}

	private function render_template_custom_hook(){
		?>
		<div id="thwec_template_custom_hook" style="display: none;">
			<p class="hook-code thwec-block thwec-block-custom-hook builder-block" id="{custom_hook}" data-block-name="custom_hook" data-text-props='{"custom_hook_name":"custom_hook_name"}'>{<span class="hook-text">custom_hook_name</span>}</p>
		</div>
		<?php
	}

		private function render_template_hook_email_header(){
		?>
		<div id="thwec_template_hook_email_header" style="display:none;">
			<p class="hook-code" id="{email_header}">{email_header_hook}</p>
		</div>
		<?php
	}

	private function render_template_hook_email_order_details(){
		?>
		<div id="thwec_template_hook_order_details" style="display:none;">
			<p class="hook-code" id="{email_order_details}">{email_order_details_hook}</p>
		</div>
		<?php		
	}

	private function render_template_hook_before_order_table(){
		?>
		<div id="thwec_template_hook_before_order_table" style="display:none;">
			<p class="hook-code" id="{before_order_table}">{before_order_table_hook}</p>
		</div>
		<?php		
	}

	private function render_template_hook_after_order_table(){
		?>
		<div id="thwec_template_hook_after_order_table" style="display:none;">
			<p class="hook-code" id="{after_order_table}">{after_order_table_hook}</p>
		</div>
		<?php		
	}

	private function render_template_hook_order_meta(){
		?>
		<div id="thwec_template_hook_order_meta" style="display:none;">
			<p class="hook-code" id="{order_meta}">{order_meta_hook}</p>
		</div>
		<?php		
	}

	private function render_template_hook_customer_address(){
		?>
		<div id="thwec_template_hook_customer_address" style="display:none;">
			<p class="hook-code" id="{customer_details}">{customer_details_hook}</p>
		</div>
		<?php		
	}

	private function render_template_hook_email_footer(){
		?>
		<div id="thwec_template_hook_email_footer" style="display:none;">
			<p class="hook-code" id="{email_footer}">{email_footer_hook}</p>
		</div>
		<?php		
	}


	private function render_template_hook_downloadable_product(){
		?>
		<div id="thwec_template_downloadable_product" style="display: none;">
			<p class="hook-code" id="{downloadable_product}">{downloadable_product_table}</p>
		</div>
		<?php
	}

	private function render_template_tracking_add_row_html(){
		?>
		<div id="thwec_tracking_panel_row_html" style="display:none;">	
			<div class="layout-lis-item sortable-row-handle">
				<span class="row-name">Row</span>
				<div class="thwec-block-settings">
					<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, {bl_id}, '{bl_name}')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="{bl_name}">
					<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
					<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
				</div>
			</div>
		</div>
		<?php
	}

	private function render_template_tracking_add_col_html(){
		?>
		<div id="thwec_tracking_panel_col_html" style="display:none;">	
			<div class="layout-lis-item sortable-col-handle">
				<span class="column-name" title="Click here to toggle">Column</span>
				<div class="thwec-block-settings">
					<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, {bl_id}, '{bl_name}')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="{bl_name}">
					<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
					<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
				</div>
			</div>
		</div>
		<?php
	}

	private function render_template_tracking_add_elm_html(){
		?>
		<div id="thwec_tracking_panel_elm_html" style="display:none;">	
			<div class="layout-lis-item sortable-elm-handle">
				<span class="element-name" title="Click here to toggle">{name}</span>
				<div class="thwec-block-settings">
					<img class="template-action-edit" onclick="thwecBuilderBlockEdit(this, {bl_id}, '{bl_name}')" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/pencil.png';?>" style="margin-right: 1px;" alt="Edit" data-icon-attr="{bl_attr_name}">
					<img class="template-action-clone" onclick="thwecBuilderBlockClone(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/copy-files.png';?>" style="margin-right: 1px;" alt="Clone">
					<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
				</div>
			</div>
		</div>
		<?php
	}
	private function render_template_tracking_add_hook_html(){
		?>
		<div id="thwec_tracking_panel_hook_html" style="display:none;">	
			<div class="layout-lis-item sortable-elm-handle">
				<span class="hook-name">{name}</span>
				<div class="thwec-block-settings">
					<img class="template-action-delete" onclick="thwecBuilderBlockDelete(this)" src="<?php echo THWEC_ASSETS_URL_ADMIN.'images/delete-button.png';?>" alt="Delete">
				</div>
			</div>
		</div>
		<?php
	}

	private function render_template_confirmation_alerts(){
		?>
		<div id="thwec_confirmation_alerts" style="display: none;">
			<form name="thwec_confirmation_alert_form" id="thwec_confirmation_alert_form">
				<input type="hidden" name="i_thwec_column_reference" class="thwec-column-reference" value="">
				<input type="hidden" name="i_thwec_flag_reference" class="thwec-flag-reference" value="">
				<input type="hidden" name="i_thwec_column_id" class="thwec-column-id-reference" value="">
				<div class="thwec-confirmation-message-wrapper">
				<div class="thwec-messages"></div>
			</div>
			</form>
		</div>
		<?php
	}

	private function render_template_confirmation_msg_line(){
		?>
		<div id="thwec_clear_builder_confirm" style="display: none;">
			All the unsaved changes will be lost. <br>Are you sure ?
		</div>
		<div id="thwec_column_confirm" style="display:none;">
			Adding new column resize the existing columns in the row
		</div>
		<?php
	}

}

endif;