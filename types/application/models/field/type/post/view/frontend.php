<?php

/**
 * Class Types_Field_Type_Post_View_Frontend
 *
 * Handles view specific tasks for field "Post"
 *
 * @since 2.3
 */
class Types_Field_Type_Post_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * Types_Field_Type_Post_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Post $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Post $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}

	/**
	 * @return string
	 */
	public function get_value() {
		$rendered_value = array();
		$values = (array) $this->entity->get_value_filtered( $this->params );
		if ( $this->empty_values( $values ) ) {
			return '';
		}
		foreach( $values as $value ) {
			$rendered_value[] = $value;
		}

		$value = $this->to_string( $rendered_value );
		$value = $this->get_decorated_value( $value );
		return $this->maybe_show_field_name( $value );
	}
}
