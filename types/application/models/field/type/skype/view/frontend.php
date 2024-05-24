<?php

/**
 * Class Types_Field_Type_Skype_View_Frontend
 *
 * Handles view specific tasks for field "Single Line"
 *
 * @since 2.3
 */
class Types_Field_Type_Skype_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {
	/**
	 * Types_Field_Type_Skype_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Skype $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Skype $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );

		if( ! wp_script_is( 'skype-sdk', 'registered' ) ) {
			wp_register_script( 'skype-sdk', '//swc.cdn.skype.com/sdk/v1/sdk.min.js' );
		}
	}

	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		if ( ! $this->is_raw_output() ) {
			$decorator_skype = isset( $this->params['button'] ) && $this->params['button'] !== null // only used on the 3.1 skype field
				? new Types_View_Decorator_Skype()
				: new Types_View_Decorator_Skype_Legacy();

			$this->add_decorator( $decorator_skype );
		}

		$is_html_output = $this->is_html_output();
		$values = $this->entity->get_value_filtered( $this->params );

		if ( $this->empty_values( $values ) ) {
			return '';
		}
		// Transform each value to HTML
		$rendered_values = array();
		$decorator_html = new Types_View_Decorator_Output_HTML();
		foreach( $values as $value ) {
			$value = is_array( $value ) && array_key_exists( 'skypename', $value )
				? $value['skypename']
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
