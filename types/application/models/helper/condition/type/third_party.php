<?php

/**
 * Class Types_Helper_Condition_Type_Third_Party
 *
 * @since 3.2
 */
class Types_Helper_Condition_Type_Third_Party extends Types_Helper_Condition {

	public function valid() {
		$cpt = parent::get_post_type();

		if( property_exists( $cpt, '_toolset_edit_last' ) ) {
			// types cpt
			return false;
		}

		if( property_exists( $cpt, '_builtin' ) && $cpt->_builtin ) {
			// builtin post type
			return false;
		}

		// 3rd party
		return true;
	}
}