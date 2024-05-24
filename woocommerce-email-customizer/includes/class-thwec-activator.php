<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Activator')):

class THWEC_Activator {

	public static function activate() {
		self::create_upload_directory();
		self::setup_thwec_settings();
	}

	public static function create_upload_directory(){
      	wp_mkdir_p(THWEC_Utils::get_template_directory());
	}

	public static function setup_thwec_settings(){
		$premium_settings = THWEC_Utils::get_template_settings();
		if( $premium_settings && is_array( $premium_settings ) ){
			$premium_settings = self::check_missing_datas( $premium_settings );
			THWEC_Utils::save_template_settings( $premium_settings );
		}
		self::update_thwec_version();

	}

	public static function check_missing_datas( $premium_settings ){
		if( THWEC_Utils::should_copy_free_settings( $premium_settings ) ){
			$premium_settings = self::copy_free_version_settings( $premium_settings );
		}
		
		if( THWEC_Utils::restore_sample_templates( $premium_settings ) ){
			$premium_settings['thwec_samples'] = THWEC_Utils::get_sample_settings();
		}

		if( THWEC_Utils::restore_email_subjects( $premium_settings ) ){
			$email_subjects = THWEC_Utils::email_subjects_plain();
			$premium_settings['email_subject'] = THWEC_Utils::email_subjects();
			foreach ($email_subjects as $key => $value) {
				THWEC_Admin_Utils::wpml_register_string( $key, $value);
			}
		}
		return $premium_settings;
	}

	public static function copy_free_version_settings( $premium ){
		$free_settings = get_option('thwecmf_template_settings');
		if( $free_settings && is_array( $free_settings ) ){
			if( isset( $free_settings['templates'] ) && !empty( $free_settings['templates'] ) ){
				$premium['templates'] = $free_settings['templates'];
			}
			if( isset( $free_settings['template_map'] ) && !empty( $free_settings['template_map'] ) ){
				$premium['template_map'] = $free_settings['template_map'];
			}

		}
		return $premium;
	}

	public static function update_thwec_version(){
		THWEC_Utils::add_version();
	}

}

endif;