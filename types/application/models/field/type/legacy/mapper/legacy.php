<?php

/**
 * Legacy implemented field
 *
 * @since 2.3
 */
class Types_Field_Type_Legacy_Mapper_Legacy extends Types_Field_Mapper_Abstract {

	/**
	 * @var Types_Field_Type_Legacy_Factory
	 */
	protected $field_factory;


	/**
	 * @param int $id
	 * @param int $id_post
	 *
	 * @return null|Types_Field_Interface
	 */
	public function find_by_id( $id, $id_post ) {
		if ( ! $field = $this->database_get_field_by_id( $id ) ) {
			return null;
		}

		$controlled = $this->is_controlled_by_types( $field );
		if ( $value = $this->get_user_value( $id_post, $field['slug'], $controlled ) ) {
			$field['value'] = $value;
		}

		return $this->field_factory->get_field( $field );
	}
}
