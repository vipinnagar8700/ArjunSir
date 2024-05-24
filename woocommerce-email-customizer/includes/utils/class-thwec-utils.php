<?php
/**
 * The common utility functionalities for the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Utils')):

class THWEC_Utils {
	const OPTION_KEY_TEMPLATE_SETTINGS = 'thwec_template_settings';
	const SETTINGS_KEY_TEMPLATE_LIST = 'templates';
	const SETTINGS_KEY_TEMPLATE_SAMPLES = 'thwec_samples';
	const SETTINGS_KEY_TEMPLATE_MAP = 'template_map';
	const SETTINGS_KEY_SUBJECT_MAP = 'email_subject';
	const SETTINGS_KEY_WPML_MAP = 'thwec_wpml_map';
	const OPTION_KEY_ADVANCED_SETTINGS = 'thwec_advanced_settings';
	const OPTION_KEY_VERSION = 'thwec_version';
	
	public static function get_template_samples_key(){
		return self::SETTINGS_KEY_TEMPLATE_SAMPLES;
	}

	public static function get_template_subject_key(){
		return self::SETTINGS_KEY_SUBJECT_MAP;
	}

	public static function get_templates_key(){
		return self::SETTINGS_KEY_TEMPLATE_LIST;
	}

	public static function get_templates_map_key(){
		return self::SETTINGS_KEY_TEMPLATE_MAP;
	}

	public static function get_version_key(){
		return self::OPTION_KEY_VERSION;
	}

	public static function wpml_map_key(){
		return self::SETTINGS_KEY_WPML_MAP;
	}
	
	public static function is_allowed_menu_cap( $menu_cap ){
		if( in_array( $menu_cap, array('manage_woocommerce', 'manage_options') ) ){
			return $menu_cap;
		}
		return 'manage_options';
	}

	public static function is_a_menu_position( $pos ){
		return absint( intval( $pos ) );
	}

	public static function is_not_empty( $value, $type, $index=false ){
		switch ( $type ) {
			case 'array':
				$empty = is_array( $value ) && !empty( $value );
				break;
			default:
				$empty = isset( $value[$index] ) && !empty( $value[$index] ); 
				break;
		}

		return $empty;
	}

	public static function add_version(){
		$prev_version = get_option( self::OPTION_KEY_VERSION );
		if( THWEC_VERSION > $prev_version ){
			delete_option(self::OPTION_KEY_VERSION);
			add_option(self::OPTION_KEY_VERSION, THWEC_VERSION);
		}
	}

	public static function should_copy_free_settings( $settings ){
		$copy = false;
		if( isset( $settings[self::get_templates_key()] ) && empty( $settings[self::get_templates_key()] ) ){
			$copy = true;
		} 

		return apply_filters( 'thwec_copy_free_version_settings', $copy );
	}

	public static function restore_sample_templates( $settings ){
		$restore = false;
		if( !isset( $settings[self::get_template_samples_key()] ) ){
			$restore = true;
		}else if( isset( $settings[self::get_template_samples_key()] ) && empty( $settings[self::get_template_samples_key()] ) ){
			$restore = true;
		}else if( THWEC_VERSION <= '3.0.0' ){
			$restore = true;
		}

		return apply_filters( 'thwec_reset_sample_templates', $restore );
	}

	public static function restore_email_subjects( $settings ){
		$restore = false;
	
		if( !isset( $settings[self::SETTINGS_KEY_SUBJECT_MAP] ) ){
			$restore = true;

		}else if( isset( $settings[self::SETTINGS_KEY_SUBJECT_MAP] ) && empty( $settings[self::SETTINGS_KEY_SUBJECT_MAP] ) ){
			$restore = true;

		}
		return apply_filters( 'thwec_reset_email_subjects', $restore );
	}
		
	public static function get_template_settings(){
		$settings = get_option(self::OPTION_KEY_TEMPLATE_SETTINGS);
		if(empty($settings)){
			$settings = array(
				self::SETTINGS_KEY_TEMPLATE_LIST => array(), 
				self::SETTINGS_KEY_TEMPLATE_MAP => array(),
				self::SETTINGS_KEY_TEMPLATE_SAMPLES => array(),
				self::SETTINGS_KEY_TEMPLATE_SAMPLES => array(),
				self::SETTINGS_KEY_SUBJECT_MAP => array(),
				self::SETTINGS_KEY_WPML_MAP => array(),
			);
		}
		return $settings;
	}

	public static function get_wpml_map( $settings ){
		return isset( $settings[self::wpml_map_key()] ) ? $settings[self::wpml_map_key()] : array();
	}

	public static function delete_settings(){
		$status1 = $status2 = $save = false;
		$settings = get_option(self::OPTION_KEY_TEMPLATE_SETTINGS);
		if( !empty($settings) ){
			if( isset( $settings[self::SETTINGS_KEY_TEMPLATE_MAP] ) && !empty( $settings[self::SETTINGS_KEY_TEMPLATE_MAP] ) ){
				$settings[self::SETTINGS_KEY_TEMPLATE_MAP] = array();
				$status1 = true;
			}
			if( isset( $settings[self::SETTINGS_KEY_SUBJECT_MAP] ) && !empty( $settings[self::SETTINGS_KEY_SUBJECT_MAP] ) ){
				$settings[self::SETTINGS_KEY_SUBJECT_MAP] = self::email_subjects();
				$status2 = true;
			}
			if( $status1 || $status2 ){
				$save = self::save_template_settings( $settings );
				return $save;
			}		
		}
		return ($status1 || $status2 );
	}

	public static function get_template_list($settings=false, $flag=false){
		$list = [];

		if(!is_array($settings)){
			$settings = self::get_template_settings();
		}
		
		if( $flag ){
			if( is_array( $settings ) ){
				$list['user'] = isset($settings[self::SETTINGS_KEY_TEMPLATE_LIST]) ? $settings[self::SETTINGS_KEY_TEMPLATE_LIST] : array();
				$list['sample'] = isset($settings[self::SETTINGS_KEY_TEMPLATE_SAMPLES]) ? $settings[self::SETTINGS_KEY_TEMPLATE_SAMPLES] : array();
			}		
		}else{
			$list = is_array($settings) && isset($settings[self::SETTINGS_KEY_TEMPLATE_LIST]) ? $settings[self::SETTINGS_KEY_TEMPLATE_LIST] : array();
		}
		return $list;
	}

	public static function get_template_map($settings=false){
		if(!is_array($settings)){
			$settings = self::get_template_settings();
		}
		return is_array($settings) && isset($settings[self::SETTINGS_KEY_TEMPLATE_MAP]) ? $settings[self::SETTINGS_KEY_TEMPLATE_MAP] : array();
	}

	public static function get_template_subject($settings=false){
		if(!is_array($settings)){
			$settings = self::get_template_settings();
		}
		return is_array($settings) && isset($settings[self::SETTINGS_KEY_SUBJECT_MAP]) ? $settings[self::SETTINGS_KEY_SUBJECT_MAP] : array();
	}

	public static function save_template_settings($settings, $new=false){
		$result = false;
		if($new){
			$result = add_option(self::OPTION_KEY_TEMPLATE_SETTINGS, $settings);
		}else{
			$result = update_option(self::OPTION_KEY_TEMPLATE_SETTINGS, $settings);
		}
		return $result;
	}

	public static function get_advanced_settings(){
		$settings = get_option(self::OPTION_KEY_ADVANCED_SETTINGS);
		return empty($settings) ? false : $settings;
	}
	
	public static function get_setting_value($settings, $key){
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return '';
	}
	
	public static function get_settings($key){
		$settings = self::get_advanced_settings();
		if(is_array($settings) && isset($settings[$key])){
			return $settings[$key];
		}
		return '';
	}
	
	public static function get_template_directory(){
	    $upload_dir = wp_upload_dir();
	    $dir = $upload_dir['basedir'].'/thwec_templates';
      	//wp_mkdir_p($templates_folder);
      	$dir = trailingslashit($dir);
      	return $dir;
	}

	public static function get_sample_settings(){
		$path = THWEC_PATH.'includes/settings.txt';
		$content = file_get_contents( $path );
		$settings = unserialize(base64_decode($content));
		$settings = isset( $settings['templates'] ) ? $settings['templates'] : '';
		return $settings;
	}

	public static function save_sample_settings( $content ){
		$content = base64_encode(serialize($content));
		$path = THWEC_PATH.'includes/settings.txt';
		$file = fopen($path, "w") or die("Unable to open file!");
		if(false !== $file){
			fwrite($file, $content);
			fclose($file);
		}
	}

	public function sample_template_reset(){
		$settings = THWEC_Utils::get_template_settings();
		$sample = THWEC_Utils::get_sample_settings();
		$sample['template_name']['template_data'] = $settings['templates']['template_name']['template_data'];
		$new_sample = array();
		$new_sample['templates'] = $sample;
		THWEC_Utils::save_sample_settings( $new_sample );
	}

	public static function email_subjects(){
		$subjects = array(
			'admin-new-order'			=> '[{site_name}]: New order #{order_id}',
			'admin-failed-order'		=> '[{site_name}]: Order #{order_id} has failed',
			'customer-reset-password'	=> 'Password Reset Request for {site_name}',
			'customer-refunded-order'	=> 'Your {site_name} order #{order_id} has been refunded',
			'customer-processing-order'	=> 'Your {site_name} order has been received!',
			'customer-on-hold-order'	=> 'Your {site_name} order has been received!',
			'customer-note'				=> 'Note added to your {site_name} order from {order_created_date}',
			'customer-new-account'		=> 'Your {site_name} account has been created!',
			'customer-invoice'			=> 'Invoice for order #{order_id} on {site_name}',
			'customer-completed-order'	=> 'Your {site_name} order is now complete',
			'admin-cancelled-order'		=> '[{site_name}]: Order #{order_id} has been cancelled',
		);
		return $subjects;
	}

	public static function email_subjects_plain(){
		$subjects = array(
			// 'admin-new-order'			=> '[%s]: New order #%s',
			// 'admin-failed-order'		=> '[%s]: Order #%s has failed',
			'customer-reset-password'	=> 'Password Reset Request for %s',
			'customer-refunded-order'	=> 'Your %s order #%s has been refunded',
			'customer-processing-order'	=> 'Your %s order has been received!',
			'customer-on-hold-order'	=> 'Your %s order has been received!',
			'customer-note'				=> 'Note added to your %s order from %s',
			'customer-new-account'		=> 'Your %s account has been created!',
			'customer-invoice'			=> 'Invoice for order #%s on %s',
			'customer-completed-order'	=> 'Your %s order is now complete',
			// 'admin-cancelled-order'		=> '[%s]: Order #%s has been cancelled',
		);
		return $subjects;
	}

	public static function email_statuses(){
		$email_statuses = array(
			'admin-new-order' 			=> 'Admin New Order Email',
			'admin-cancelled-order'		=> 'Admin Cancelled Order Email',
			'admin-failed-order'		=> 'Admin Failed Order Email',
			'customer-completed-order'	=> 'Customer Completed Order',
			'customer-on-hold-order'	=> 'Customer On Hold Order Email',
			'customer-processing-order'	=> 'Customer Processing Order',
			'customer-refunded-order'	=> 'Customer Refund Order',
			'customer-invoice'			=> 'Customer invoice / Order details',
			'customer-note'				=> 'Customer Note',
			'customer-reset-password'	=> 'Reset Password',
			'customer-new-account'		=> 'New Account',
		);
		return $email_statuses;
	}

	public static function subject_status(){
		$status = array(
			'new_order' => 'admin-new-order',
			'customer_processing_order' => 'customer-processing-order',
			'customer_completed_order' => 'customer-completed-order',
			'customer_invoice' => 'customer-invoice',
			'customer_note' => 'customer-note',
			'customer_new_account' => 'customer-new-account',
			'customer_on_hold_order' => 'customer-on-hold-order',
			'cancelled_order' => 'admin-cancelled-order',
			'customer_refunded_order' => 'customer-refunded-order',
			'failed_order' => 'admin-failed-order',
			'customer_reset_password' => 'customer-reset-password',
		);
		return $status;
	}
	
	public static function format_subjects( $data, $status, $order ){
		$placeholder_arr = array();
		$placeholders = array(
			'{customer_name}'			=> $order->get_billing_first_name(),
			'{customer_full_name}'		=> self::get_customer_full_name( $order ),
			'{site_name}'				=> get_bloginfo(),
			'{order_id}'				=> $order->get_id(),
			'{order_created_date}'		=> self::get_order_created_date( $order ),
			'{order_completed_date}'	=> self::get_order_completed_date( $order ),
			'{order_total}'				=> $order->get_total(),
			'{order_formatted_total}'	=> $order->get_formatted_order_total(),
			'{billing_first_name}'		=> $order->get_billing_first_name(),
			'{billing_last_name}'		=> $order->get_billing_last_name(),
			'{billing_last_name}'		=> $order->get_billing_last_name(),
			'{billing_company}' 		=> $order->get_billing_company(),
			'{billing_country}' 		=> $order->get_billing_country(),
			'{billing_address_1}' 		=> $order->get_billing_address_1(),
			'{billing_address_2}' 		=> $order->get_billing_address_2(),
			'{billing_city}' 			=> $order->get_billing_city(),
			'{billing_state}' 			=> $order->get_billing_state(),
			'{billing_postcode}' 		=> $order->get_billing_postcode(),
			'{billing_phone}' 			=> $order->get_billing_phone(),
			'{billing_email}' 			=> $order->get_billing_email(),
			'{shipping_first_name}' 	=> $order->get_shipping_first_name(),
			'{shipping_last_name}' 		=> $order->get_shipping_last_name(),
			'{shipping_company}' 		=> $order->get_shipping_company(),
			'{shipping_country}' 		=> $order->get_shipping_country(),
			'{shipping_address_1}' 		=> $order->get_shipping_address_1(),
			'{shipping_address_2}' 		=> $order->get_shipping_address_2(),
			'{shipping_city}' 			=> $order->get_shipping_city(),
			'{shipping_state}' 			=> $order->get_shipping_state(),
			'{shipping_postcode}' 		=> $order->get_shipping_postcode(),
			'{payment_method}'			=> $order->get_payment_method(),
		);

		foreach ($placeholders as $key => $value) {
			$count = 0;
			$data = str_replace( $key, '%s', $data, $count );

			if( $count >= 1 ){
				array_push( $placeholder_arr, $value );
			}
		}
		if( !empty( $placeholder_arr ) ){
			$data = vsprintf( __( $data, 'woocommerce-email-customizer-pro' ), $placeholder_arr );

		}else{
			$data = __( $data, 'woocommerce-email-customizer-pro' );
		}
		return $data;
	}

	public static function get_customer_full_name( $order ){
		return $order->get_billing_first_name().' '.$order->get_billing_last_name();
	}

	public static function get_order_completed_date( $order ){
		$order_date = '';
		if( isset($order) && $order->has_status( 'completed' ) ){
			$order_date = wc_format_datetime( $order->get_date_completed() );
		}
		return $order_date;
	}

	public static function get_order_created_date( $order ){
		return wc_format_datetime($order->get_date_created());
	}

	public static function is_wpml_active(){
		global $sitepress;
		return function_exists('icl_object_id') && is_object($sitepress);
	}

	public static function get_wpml_locale( $lang_code, $lowercase=false ){
		global $sitepress;
		$locale = $sitepress->get_locale($lang_code);
		return $lowercase ? strtolower( $locale ) : $locale;
	}

	public static function dump( $str ){
		?>
		<pre>
			<?php echo var_dump($str); ?>
		</pre>
		<?php
	}
}

endif;