<?php

/**
 * @since 2.3
 */
interface Types_Field_Gateway_Interface {

	/**
	 * @param string|int $id
	 *
	 * @return null|array
	 */
	public function get_field_by_id( $id );


	/**
	 * @return array
	 */
	public function get_fields();


	/**
	 * @param int $id
	 * @param string $field_slug
	 * @param bool $repeatable
	 * @param bool $third_party_field
	 *
	 * @return array
	 */
	public function get_field_user_value( $id, $field_slug, $repeatable = false, $third_party_field = false );
}
