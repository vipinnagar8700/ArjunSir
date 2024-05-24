<?php

/**
 * Toolset_Condition_Plugin_Views_Show_Page_Builder_Frontend_Content_Settings
 *
 * @since 3.1.1
 */
class Toolset_Condition_Plugin_Views_Show_Page_Builder_Frontend_Content_Settings implements Toolset_Condition_Interface {
	public function is_met() {
		$elementor_active = new Toolset_Condition_Plugin_Elementor_Active();

		if ( $elementor_active->is_met() ) {
			return true;
		}

		return false;
	}
}
