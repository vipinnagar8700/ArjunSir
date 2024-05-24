<?php

/**
 * Save handler for types settings
 * Settings are defined in Controller/Page/Extension/Settings
 *
 * @since 2.1
 */
final class Types_Ajax_Handler_Settings_Action extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @inheritdoc
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {

		$am = $this->get_ajax_manager();

		$am->ajax_begin( array( 'nonce' => $am->get_action_js_name( Types_Ajax::CALLBACK_SETTINGS_ACTION ) ) );

		$setting_name = sanitize_text_field( toolset_getpost( 'setting' ) );
		$setting_value = toolset_getpost( 'setting_value' );

		if ( ! is_array( $setting_value ) ) {
			parse_str( $setting_value, $setting_value );
			$setting_value = array_pop( $setting_value );
			$settings = array( $setting_name => $setting_value );
		} else {
			$settings = $setting_value;
		}

		$toolset_settings = Toolset_Settings::get_instance();

		foreach ( $settings as $setting_key => $value ) {
			$sanitized_key = sanitize_title( $setting_key );
			$toolset_settings[ $sanitized_key ] = sanitize_text_field( $value );
		}

		$toolset_settings->save();
		$am->ajax_finish( array( 'success' ), true );
	}
}
