<?php

/**
 * Every field has it's own factory which need to take care of delivering
 * the field object, the mapper and the views for frontend, backend and creation.
 *
 * @since 2.3
 */
interface Types_Field_Factory_Interface {

	/**
	 * @return Types_Field_Interface
	 */
	public function get_field();


	/**
	 * @param Types_Field_Gateway_Interface $gateway
	 *
	 * @return Types_Field_Mapper_Interface
	 */
	public function get_mapper( Types_Field_Gateway_Interface $gateway );


	/**
	 * @param Types_Field_Interface $field
	 * @param array $user_params
	 *
	 * @return Types_Interface_Value
	 */
	public function get_view_frontend( Types_Field_Interface $field, $user_params );


	/**
	 * @param Types_Field_Interface $field
	 *
	 * @return Types_Interface_Value
	 */
	public function get_view_backend_display( Types_Field_Interface $field );


	/**
	 * @param Types_Field_Interface $field
	 *
	 * @return Types_Interface_Value
	 */
	public function get_view_backend_creation( Types_Field_Interface $field );
}
