<?php

/**
 * Class Types_Field_Type_Date_View_Frontend
 *
 * Handles view specific tasks for field "Single Line"
 *
 * @since 2.3
 */
class Types_Field_Type_Date_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {
	/**
	 * Types_Field_Type_Date_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Date $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Date $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->maybe_default_params( $this->normalise_user_values( $params ) );
	}

	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		if( ! $this->is_raw_output() && isset( $this->params['style'] ) && $this->params['style'] ) {
			$this->add_decorator( new Types_View_Decorator_Calendar() );
		}

		$rendered_values = $this->get_initial_rendererd_values();
		return $this->get_rendered_value ( $rendered_values );
	}


	/**
	 * Detects if params are empty
	 *
	 * @param array $params
	 * @return array
	 * @since 3.0.7
	 */
	private function maybe_default_params( $params ) {
		if ( ! isset( $params['style'] ) || ! $params['style'] ) {
			$params['style'] = 'text';
			if ( ! isset( $params['format'] ) || ! $params['format'] ) {
				$params['format'] = get_option( 'date_format' );
			}
		}
		if ( ! isset( $params['field'] ) ) {
			$params['field'] = $this->entity->get_type();
		}

		return $params;
	}
}
