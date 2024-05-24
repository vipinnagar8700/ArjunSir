<?php

/**
 * Toolset_Condition_Plugin_Elementor_Pro_Active
 *
 * @since 3.0.7
 */
class Toolset_Condition_Plugin_Elementor_Pro_Active implements Toolset_Condition_Interface {

	public function is_met() {
		return did_action( 'elementor_pro/init' );
	}

	/**
	 * Return the plugin version number, or false if not available.
	 *
	 * @return bool|string
	 */
	public function get_version() {
		if ( ! $this->is_met() ) {
			return false;
		}

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return false;
		}

		return ELEMENTOR_PRO_VERSION;
	}
}
