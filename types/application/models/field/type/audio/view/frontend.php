<?php

/**
 * Handles view specific tasks for field "Audio"
 *
 * @since 2.3
 */
class Types_Field_Type_Audio_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * @var Types_Interface_Value
	 */
	private $decorator_audio;


	/**
	 * Types_Field_Type_Audio_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Audio $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Audio $entity, $params = array() ) {
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
			$this->add_decorator( $this->get_decorator_audio() );
		}

		$rendered_values = $this->get_initial_rendererd_values();

		return $this->get_rendered_value( $rendered_values );
	}


	/**
	 * @param Types_Interface_Value $decorator
	 */
	public function set_decorator_audio( Types_Interface_Value $decorator ) {
		$this->decorator_audio = $decorator;
	}


	/**
	 * Default class for audio decorator
	 *
	 * @return Types_Interface_Value|Types_View_Decorator_Audio
	 */
	private function get_decorator_audio() {
		if ( $this->decorator_audio !== null ) {
			return $this->decorator_audio;
		}

		return $this->decorator_audio = new Types_View_Decorator_Audio();
	}
}
