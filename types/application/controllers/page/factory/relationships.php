<?php

/**
 * Relationships page controller.
 *
 * Chooses the proper controller for the Relationships page depending on whether m2m is active or not.
 *
 * @since 2.3-b4
 */
class Types_Page_Factory_Relationships implements Types_Page_Factory_Interface {


	/**
	 * @inheritdoc
	 *
	 * @param string $class Classname of the page controller as Types_Page_Router understands it.
	 * @param array $args Arguments for the page controller.
	 *
	 * @return Types_Page_Relationships|Types_Page_Relationships_Inactive
	 */
	public function get_page_controller( $class, $args ) {
		if ( true === apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return new Types_Page_Relationships( $args );
		} else {
			return new Types_Page_Relationships_Inactive( $args );
		}
	}

}