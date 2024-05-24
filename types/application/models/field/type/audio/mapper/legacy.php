<?php

/**
 * Mapper for "Audio" field
 *
 * @since 2.3
 */
class Types_Field_Type_Audio_Mapper_Legacy extends Types_Field_Mapper_Abstract {

	/**
	 * @param int $id
	 * @param int $id_post
	 *
	 * @return Types_Field_Interface|null
	 * @throws Exception
	 */
	public function find_by_id( $id, $id_post ) {
		if ( ! $this->field_factory instanceof Types_Field_Type_Audio_Factory ) {
			return null;
		}

		if ( ! $field = $this->database_get_field_by_id( $id ) ) {
			return null;
		}

		if ( $field['type'] !== 'audio' ) {
			throw new RuntimeException( 'Types_Field_Type_Audio_Mapper_Legacy can not map type: ' . $field['type'] );
		}

		$field = $this->map_common_field_properties( $field );

		$controlled = $this->is_controlled_by_types( $field );
		if ( $value = $this->get_user_value( $id_post, $field['slug'], $field['repeatable'], $controlled ) ) {
			$field['value'] = $value;
		}

		return $this->field_factory->get_field( $field );
	}
}
