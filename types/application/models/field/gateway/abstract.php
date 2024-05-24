<?php

/**
 * @since 2.3
 */
abstract class Types_Field_Gateway_Abstract implements Types_Field_Gateway_Interface {

	/**
	 * @param string|int $id
	 *
	 * @return null|array
	 */
	public function get_field_by_id( $id ) {
		$id = trim( $id );
		$fields = $this->get_fields();

		if ( ! isset( $fields[ $id ], $fields[ $id ]['type'] ) ) {
			// requested field does not exist
			return null;
		}

		$field = $fields[ $id ];

		if ( ! is_array( $field )
			|| ! array_key_exists( 'slug', $field )
			|| ! array_key_exists( 'type', $field )
		) {
			// invalid field
			return null;
		}

		return $field;
	}
}
