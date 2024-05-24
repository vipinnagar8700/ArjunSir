<?php

/**
 * Handles view specific tasks for field "Multilines"
 *
 * @since 2.3
 */
class Types_Field_Type_Multiple_Lines_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * Types_Field_Type_Multiple_Lines_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Multiple_Lines $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Multiple_Lines $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}


	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		$is_html_output = $this->is_html_output();
		$values = $this->entity->get_value_filtered( $this->params );
		if ( empty( $values ) ) {
			return '';
		}
		$is_filter_used = serialize( $this->entity->get_value() )
			!== serialize( $this->entity->get_value_filtered( $this->params ) );
		// Transform each value to HTML
		$rendered_values = array();
		$decorator_html = new Types_View_Decorator_Output_HTML();
		foreach ( $values as $value ) {
			$value = $this->filter_field_value_after_decorators( $this->get_decorated_value( $value, $is_html_output ), $value );
			$value = $is_filter_used || $this->is_raw_output()
				? $value
				: wpautop( $value );
			if ( $is_html_output && $value ) {
				$value = $decorator_html->get_value( $value, $this->params, $this->entity, true, true );
			}
			$rendered_values[] = $value;
		}

		return $this->get_rendered_value( $rendered_values );
	}
}
