<?php

use OTGS\Toolset\Common\Exception\NotImplementedException;

/**
 * @since 2.3
 */
class Types_Field_Type_Wysiwyg_Factory implements Types_Field_Factory_Interface {

	/**
	 * @param array $data
	 *
	 * @return Types_Field_Type_Wysiwyg
	 */
	public function get_field( $data = array() ) {
		// field is repeatable
		$repeatable = new Types_Field_Setting_Repeatable( $data );
		$data['repeatable'] = $repeatable;

		return new Types_Field_Type_Wysiwyg( $data );
	}


	/**
	 * @param Types_Field_Gateway_Interface $gateway
	 *
	 * @return Types_Field_Type_Wysiwyg_Mapper_Legacy
	 */
	public function get_mapper( Types_Field_Gateway_Interface $gateway ) {
		return new Types_Field_Type_Wysiwyg_Mapper_Legacy( $this, $gateway );
	}


	/**
	 * @param Types_Field_Interface $field
	 * @param array $user_params
	 *
	 * @return Types_Field_Type_Wysiwyg_View_Frontend|false
	 */
	public function get_view_frontend( Types_Field_Interface $field, $user_params ) {
		if ( ! $field instanceof Types_Field_Type_Wysiwyg ) {
			return false;
		}

		return new Types_Field_Type_Wysiwyg_View_Frontend( $field, $user_params );
	}


	/**
	 * @param Types_Field_Interface $field
	 */
	public function get_view_backend_display( Types_Field_Interface $field ) {
		throw new NotImplementedException();
	}


	/**
	 * @param Types_Field_Interface $field
	 */
	public function get_view_backend_creation( Types_Field_Interface $field ) {
		throw new NotImplementedException();
	}
}
