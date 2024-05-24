<?php

/**
 * Mapper for "Email" field
 *
 * @since 2.3
 */
class Types_Field_Type_Email_Mapper_Legacy extends Types_Field_Mapper_Abstract {

	/**
	 * @var Types_Field_Type_Email_Factory
	 */
	protected $field_factory;


	/**
	 * @param int $id
	 * @param int $id_post
	 *
	 * @return null|Types_Field_Type_Email
	 * @throws Exception
	 */
	public function find_by_id( $id, $id_post ) {
		if ( ! $field = $this->database_get_field_by_id( $id ) ) {
			return null;
		};

		if ( $field['type'] !== 'email' ) {
			throw new Exception( 'Types_Field_Type_Email_Mapper_Legacy can not map type: ' . $field['type'] );
		}

		$field = $this->map_common_field_properties( $field );

		$controlled = $this->is_controlled_by_types( $field );
		if ( $value = $this->get_user_value( $id_post, $field['slug'], $field['repeatable'], $controlled ) ) {
			$field['value'] = $value;
		}

		return $this->field_factory->get_field( $field );
	}
}
