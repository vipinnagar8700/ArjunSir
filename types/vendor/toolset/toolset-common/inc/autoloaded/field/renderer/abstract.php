<?php


abstract class Toolset_Field_Renderer_Abstract {

	/** @var null|Toolset_Field_Instance */
	protected $field = null;


	/**
	 * Toolset_Field_Renderer_Abstract constructor.
	 *
	 * @param Toolset_Field_Instance $field
	 */
	public function __construct( $field ) {
		// todo sanitize
		$this->field = $field;
	}


	/**
	 * @param bool $echo
	 *
	 * @return string|array
	 */
	public function render( $echo = false ) {
		$value = $this->get_value();

		if ( $echo ) {
			// ... because the value should be already safe when it comes out of get_value(), or additional escaping needs
			// to be performed on the renderer output.
			//
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $value;
		}

		return $value;
	}


	/**
	 * @return mixed
	 */
	protected function get_value() {
		return ''; // To be overridden.
	}
}
