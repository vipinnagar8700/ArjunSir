<?php

/**
 * Handles view specific tasks for field "Single Line"
 *
 * @since 2.3
 */
class Types_Field_Type_Checkbox_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * Types_Field_Type_Single_Line_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Checkbox $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Checkbox $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}

	/**
	 * @return string
	 */
	public function get_value() {
		if ( ! $this->entity instanceof Types_Field_Type_Checkbox ) {
			return '';
		}

		if ( $this->is_raw_output() ) {
			$value = $this->entity->get_option()->get_value_raw();
		} else if( isset( $this->params['state'] ) ) {
			$decorator = new Types_View_Decorator_Option_State( $this->entity->get_option(), $this->params );
			$value = $decorator->get_value();
		} else {
			$value = $this->entity->get_option()->get_value_filtered( $this->params );
			if ( empty( $value ) ) {
				return '';
			}
			$value = $this->get_decorated_value( $value );
		}

		$value = $this->filter_field_value_after_decorators( $value );
		return $this->maybe_show_field_name( $value );
	}
}
