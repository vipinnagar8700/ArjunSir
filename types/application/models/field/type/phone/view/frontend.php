<?php

/**
 * Class Types_Field_Type_Phone_View_Frontend
 *
 * Handles view specific tasks for field "Phone"
 *
 * @since 2.3
 */
class Types_Field_Type_Phone_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {
	/**
	 * Types_Field_Type_Phone_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Phone $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Phone $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}

	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		$rendered_values = $this->get_initial_rendererd_values();
		return $this->get_rendered_value( $rendered_values );
	}
}
