<?php

/**
 * Class Types_Field_Type_Video_View_Frontend
 *
 * Handles view specific tasks for field "Video"
 *
 * @since 2.3
 */
class Types_Field_Type_Video_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {
	/**
	 * Types_Field_Type_Single_Line_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Video $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Video $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}

	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		if ( ! $this->is_raw_output() ) {
			$this->add_decorator( new Types_View_Decorator_Video() );
		}
		$rendered_values = $this->get_initial_rendererd_values();
		return $this->get_rendered_value( $rendered_values );
	}
}
