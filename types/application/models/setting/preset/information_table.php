<?php

/**
 * Class Types_Setting_Preset_Information_Table
 *
 * @method get_id()
 * @method get_value()
 */
class Types_Setting_Preset_Information_Table {

	private $setting;


	public function __construct() {
		$this->setting = new Types_Setting_Boolean( 'types-information-table' );

		$option_edit_post = new Types_Setting_Option( 'show-on-post' );
		$option_edit_post->set_description( __( 'Edit Post pages', 'wpcf' ) );
		$option_edit_post->set_default( true );

		$option_edit_post_type = new Types_Setting_Option( 'show-on-post-type' );
		$option_edit_post_type->set_description( __( 'Edit Post Type pages', 'wpcf' ) );
		$option_edit_post_type->set_default( true );

		$this->setting->add_option( $option_edit_post );
		$this->setting->add_option( $option_edit_post_type );
	}


	public function __call( $name, $arguments ) {
		if ( empty( $arguments ) ) {
			return call_user_func( array( $this->setting, $name ) );
		}

		return call_user_func_array( array( $this->setting, $name ), $arguments );
	}
}
