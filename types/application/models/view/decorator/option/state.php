<?php

/**
 * Class Types_View_Decorator_Option_State
 *
 * Takes a Types_Field_Part_Interface and allows to change the output values
 * Example of use:
 *      __construct( $option, array( 'state' => 'unchecked', 'content' => 'New Text Output' )
 *      When the option is unchecked the output will be: 'New Text Output'
 *
 * @since 2.3
 */
class Types_View_Decorator_Option_State implements Types_Interface_Value {

	protected $option;

	protected $params;


	/**
	 * Types_View_Decorator_Abstract constructor.
	 *
	 * @param Types_Field_Part_Interface $option
	 * @param array $params
	 */
	public function __construct( Types_Field_Part_Interface $option, $params ) {
		$this->option = $option;
		$this->params = $params;
	}


	/**
	 * @return string
	 */
	public function get_value() {
		if ( ! isset( $this->params['state'] ) || ! isset( $this->params['content'] ) ) {
			// state or content missing
			return $this->option->get_value_filtered( $this->params );
		}

		if ( $this->params['state'] === 'unchecked' && ! $this->option->is_active() ) {
			// show unchecked value
			return ! empty( $this->params['content'] )
				? $this->params['content']
				: $this->option->get_value_filtered( $this->params );
		}

		if ( $this->params['state'] === 'checked' && $this->option->is_active() ) {
			// show checked value
			return ! empty( $this->params['content'] )
				? $this->params['content']
				: $this->option->get_value_filtered( $this->params );
		}

		return '';
	}
}
