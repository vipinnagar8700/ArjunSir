<?php

/**
 * Class Types_Field_Type_Number_View_Frontend
 *
 * Handles view specific tasks for field "Number"
 *
 * @since 2.3
 */
class Types_Field_Type_Number_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {
	/**
	 * Types_Field_Type_Number_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Number $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Number $entity, $params = array() ) {
		$this->entity = $params['field'] = $entity;

		$this->params = $this->normalise_user_values( $params );

		if( ! isset( $params['format'] ) || empty( $params['format'] ) ) {
			$this->params['format'] = 'FIELD_VALUE';
		}
	}

	/**
	 * @return string
	 */
	public function get_value() {
		$is_html_output = $this->is_html_output();
		$decorator_html = new Types_View_Decorator_Output_HTML();
		$values = $this->entity->get_value_filtered( $this->params );
		if ( empty( $values ) ) {
			return '';
		}

		if ( ! $this->is_raw_output() ) {
			$placeholder_field = new Types_View_Placeholder_Field();
		}

		$rendered_values = array();
		foreach( $values as $value ) {
			$value = isset( $placeholder_field )
				? $placeholder_field->replace( $this->params['format'], $this->entity, $value )
				: $value;

			$value = $this->filter_field_value_after_decorators( $this->get_decorated_value( $value, $is_html_output ), $value );
			if ( $is_html_output ) {
				 $value = $decorator_html->get_value( $value, $this->params, $this->entity, true, true );
			}
			$rendered_values[] = $value;
		}

		return $this->get_rendered_value( $rendered_values );
	}
}
