<?php

use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * @since 2.3
 * @codeCoverageIgnore
 */
class Types_Field_Type_Post_Factory implements Types_Field_Factory_Interface {

	/**
	 * @param array $data
	 *
	 * @return Types_Field_Type_Post
	 * @throws Exception
	 */
	public function get_field( $data = array() ) {
		return new Types_Field_Type_Post( $data );
	}


	/**
	 * @param Types_Field_Gateway_Interface $gateway
	 *
	 * @return Types_Field_Type_Post_Mapper_Legacy
	 */
	public function get_mapper( Types_Field_Gateway_Interface $gateway ) {
		return new Types_Field_Type_Post_Mapper_Legacy(
			$this,
			$gateway,
			new PostStatus(),
			Toolset_Relationship_Definition_Repository::get_instance(),
			new Factory(),
			WpmlService::get_instance()
		);
	}


	/**
	 * @param Types_Field_Interface $field
	 * @param array $user_params
	 *
	 * @return Types_Interface_Value|false
	 */
	public function get_view_frontend( Types_Field_Interface $field, $user_params ) {
		if ( ! $field instanceof Types_Field_Type_Post ) {
			return false;
		}

		return new Types_Field_Type_Post_View_Frontend( $field, $user_params );
	}


	/**
	 * @param Types_Field_Interface $field
	 *
	 * @return Types_Field_Type_Post_View_Backend_Display|false
	 */
	public function get_view_backend_display( Types_Field_Interface $field ) {
		if ( ! $field instanceof Types_Field_Type_Post ) {
			return false;
		}

		return new Types_Field_Type_Post_View_Backend_Display(
			WpmlService::get_instance(),
			new Factory()
		);
	}


	/**
	 * @param Types_Field_Interface $field
	 *
	 * @return Types_Field_Type_Post_View_Backend_Creation|false
	 */
	public function get_view_backend_creation( Types_Field_Interface $field ) {
		if ( ! $field instanceof Types_Field_Type_Post ) {
			return false;
		}

		return new Types_Field_Type_Post_View_Backend_Creation( $field );
	}
}
