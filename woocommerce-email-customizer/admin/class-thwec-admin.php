<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Admin')):
 
class THWEC_Admin {
	private $plugin_name;
	private $version;
	public $admin_instance = null;
	private $plugin_pages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_pages = array(
			'toplevel_page_th_email_customizer_templates',
			'email-customizer_page_th_email_customizer_pro',
			// 'email-customizer_page_th_email_customizer_license_settings',
		);
		$this->init();
	}

	public function init() {		
		if(is_admin() || (defined( 'DOING_AJAX' ) && DOING_AJAX)){
			$this->admin_instance = THWEC_Admin_Settings_General::instance();
		}
	}
	
	public function enqueue_styles_and_scripts($hook) {
		if(!in_array($hook, $this->plugin_pages)){
			return;
		}
		
		$debug_mode = apply_filters('thwec_debug_mode', false);
		$suffix = $debug_mode ? '' : '.min';
		
		$this->enqueue_styles($suffix);
		$this->enqueue_scripts($suffix);
		wp_enqueue_media();
	}

	private function enqueue_styles($suffix) {
		wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css?ver=1.11.4');
		wp_enqueue_style('woocommerce_admin_styles', THWEC_WOO_ASSETS_URL.'css/admin.css');
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('thwec-admin-style', THWEC_ASSETS_URL_ADMIN . 'css/thwec-admin'. $suffix .'.css', $this->version);
		wp_enqueue_style('raleway-style','https://fonts.googleapis.com/css?family=Raleway:400,600,800');
		wp_enqueue_style('wp-codemirror');	
	}

	private function enqueue_scripts($suffix) {	
		$deps = array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-dialog','jquery-ui-resizable', 'jquery-ui-widget', 'jquery-ui-tabs','jquery-tiptip', 'woocommerce_admin', 'wc-enhanced-select', 'select2', 'wp-color-picker','select2');
		
		wp_enqueue_script( 'thwec-admin-script', THWEC_ASSETS_URL_ADMIN . 'js/thwec-admin'. $suffix .'.js', $deps, $this->version, false );
		$template_filter = apply_filters('thwec_wpml_template_list_filter', true );
		$cm_settings['TestEditor'] = wp_enqueue_code_editor(array('type' => 'text/css'));
		$script_var = array(
            // 'admin_url' => admin_url(),
            // 'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'template_filter'	=> (int) $template_filter,
            'wpml_active'		=> THWEC_Utils::is_wpml_active(),
            'add_css_feature'	=> true,
            'cm_settings'		=> $cm_settings,
            'save_temp_on_settings'	=> apply_filters('thwec_enable_template_save_on_settings_change', false),
        );
		wp_localize_script('thwec-admin-script', 'thwec_var', $script_var);
	}

	public function collapse_admin_sidebar(){
		$page = isset( $_GET['page'] ) ? $_GET['page'] : false;
		if( $page && $page == 'th_email_customizer_pro' ){
			if( get_user_setting('mfold') != 'f' ){
				set_user_setting('mfold', 'f');
			}
		}else{
			set_user_setting('mfold', 'o');
		}
	}

	public function admin_menu() {
		// $manage_cap = THWEC_Utils::is_allowed_menu_cap( apply_filters('thwec_manage_plugin_menu_capability', 'manage_options') );
		$manage_cap = THWEC_Utils::is_allowed_menu_cap( apply_filters('thwec_manage_plugin_menu_capability', 'manage_woocommerce') );
		$menu_pos = THWEC_Utils::is_a_menu_position( apply_filters( 'thwec_admin_menu_position', 56 ) );
		$this->screen_id = add_menu_page(  THWEC_i18n::t('Email Customizer'), THWEC_i18n::t('Email Customizer'), $manage_cap, 'th_email_customizer_templates', array($this, 'output_settings'), 'dashicons-admin-customizer', $menu_pos );
		$this->screen_id .= add_submenu_page('th_email_customizer_templates', THWEC_i18n::t('Templates'), THWEC_i18n::t('Templates'), $manage_cap, 'th_email_customizer_templates', array($this, 'output_settings'));
		$this->screen_id .= add_submenu_page('th_email_customizer_templates', THWEC_i18n::t('Add New'), THWEC_i18n::t('Add New'), $manage_cap, 'th_email_customizer_pro', array($this, 'output_settings'));
		$this->screen_id .= add_submenu_page('th_email_customizer_templates', THWEC_i18n::t('Plugin License'), THWEC_i18n::t('Plugin License'), $manage_cap, 'th_email_customizer_license_settings', array($this, 'output_settings'));
	}
	
	public function add_screen_id($ids){
		$ids[] = 'woocommerce_page_th_email_customizer_pro';
		$ids[] = strtolower( THWEC_i18n::t('WooCommerce') ) .'_page_th_email_customizer_pro';

		return $ids;
	}
	
	public function plugin_action_links($links) {
		$settings_link = '<a href="'.admin_url('admin.php?page=th_email_customizer_pro').'">'. __('Settings') .'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	public function plugin_row_meta( $links, $file ) {
		if(THWEC_BASE_NAME == $file) {
			$doc_link = esc_url('https://www.themehigh.com/help-guides/woocommerce-email-customizer/');
			$support_link = esc_url('https://www.themehigh.com/help-guides/');
				
			$row_meta = array(
				'docs' => '<a href="'.$doc_link.'" target="_blank" aria-label="'.THWEC_i18n::esc_attr__t('View plugin documentation').'">'.THWEC_i18n::esc_html__t('Docs').'</a>',
				'support' => '<a href="'.$support_link.'" target="_blank" aria-label="'. THWEC_i18n::esc_attr__t('Visit premium customer support' ) .'">'. THWEC_i18n::esc_html__t('Premium support') .'</a>',
			);

			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}

	public function disable_admin_notices(){
		$page  = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : '';
		if($page === 'th_email_customizer_pro'){
			global $wp_filter;
      		if (is_user_admin() ) {
        		if (isset($wp_filter['user_admin_notices'])){
            		unset($wp_filter['user_admin_notices']);
        		}
      		} elseif(isset($wp_filter['admin_notices'])){
            	unset($wp_filter['admin_notices']);
      		}
      		if(isset($wp_filter['all_admin_notices'])){
        		unset($wp_filter['all_admin_notices']);
      		}
		}
	}

	public function display_thwec_admin_notices() {
		if( $this->database_update_required() && apply_filters('thwec_database_upgrade', false ) ){
	 		$premium_settings = THWEC_Utils::get_template_settings();
			if( $premium_settings && is_array( $premium_settings ) ){
				if( !isset( $premium_settings[THWEC_Utils::get_template_samples_key()] ) || apply_filters('thwec_reset_sample_templates', false ) ){
					$premium_settings['thwec_samples'] = THWEC_Utils::get_sample_settings();
				}else if( ( isset( $premium_settings[THWEC_Utils::get_template_samples_key()] ) && empty( $premium_settings[THWEC_Utils::get_template_samples_key()] ) ) || apply_filters('thwec_reset_sample_templates', false ) ){
					$premium_settings['thwec_samples'] = THWEC_Utils::get_sample_settings();
				}
				$save = THWEC_Utils::save_template_settings( $premium_settings );
				if( $save ){
					update_option('thwec_version', THWEC_VERSION);
					?>
    				<div class="notice notice-success is-dismissible">
        		    	<p><?php _e( 'Email Customizer database upgrade successful!', 'thwec' ); ?></p>
        			</div>
    				<?php
				}
			}
    	}
	}

	public function database_update_required(){
		// Version before 2.0.6 doesn't have a version option.
		// So older version updated to 2.0.6 or higher requires db upgrade
		$pre_version = get_option('thwec_version');
		if( !$pre_version || $pre_version < '3.0.0' ){ // 
			return true;
		}
		return apply_filters('thwec_force_update_db', false);
	}
	
	public function output_settings(){
		$page  = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : 'th_email_customizer_pro';

		if($page === 'th_email_customizer_templates'){			
			$template_settings = THWEC_Admin_Settings_Templates::instance();	
			$template_settings->render_page();	
		}else if($page === 'th_email_customizer_advanced_settings'){
			$advanced_settings = THWEC_Admin_Settings_Advanced::instance();	
			$advanced_settings->render_page();		
		}else if($page === 'th_email_customizer_license_settings'){
			$license_settings = THWEC_Admin_Settings_License::instance();	
			$license_settings->render_page();	
		}else{
			$general_settings = THWEC_Admin_Settings_General::instance();	
			$general_settings->render_page();
		}
	}
}

endif;