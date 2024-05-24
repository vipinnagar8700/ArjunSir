<?php

/**
 * A passthrough page controller factory. It always instantiates the page controller it is given as a first argument.
 *
 * @since 2.3-b4
 */
class Types_Page_Factory_Passthrough implements Types_Page_Factory_Interface {


	/**
	 * @inheritdoc
	 *
	 * @param string $class Classname of the page controller as Types_Page_Router understands it.
	 * @param array $args Arguments for the page controller.
	 *
	 * @return mixed|Types_Page_Persistent
	 */
	public function get_page_controller( $class, $args ) {
		return new $class( $args );
	}

}