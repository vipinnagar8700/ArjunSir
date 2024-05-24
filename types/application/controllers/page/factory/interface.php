<?php

/**
 * Interface for page controllers.
 *
 * A customized factory can be used to choose between different controllers on one page (after Types_Page_Router
 * decided about the page that is being displayed).
 *
 * By default, the trivial implementation Types_Page_Factory_Passthrough is used.
 *
 * @since 2.3-b4
 */
interface Types_Page_Factory_Interface {

	/**
	 * @param string $class Classname of the page controller as Types_Page_Router understands it.
	 * @param array $args Arguments for the page controller.
	 *
	 * @return Types_Page_Persistent
	 */
	public function get_page_controller( $class, $args );

}