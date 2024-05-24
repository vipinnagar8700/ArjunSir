<?php

/**
 * Check whether there's a problem with old WPML version and m2m.
 *
 * @since m2m
 */
class Toolset_Condition_Plugin_Wpml_Doesnt_Support_M2m implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		$m2m_controller = \OTGS\Toolset\Common\Relationships\MainController::get_instance();

		if( ! \OTGS\Toolset\Common\WPML\WpmlService::get_instance()->is_wpml_active_and_configured() ) {
			return false;
		}

		return version_compare(
			\OTGS\Toolset\Common\WPML\WpmlService::get_instance()->get_wpml_version(),
			\OTGS\Toolset\Common\Relationships\MainController::MINIMAL_WPML_VERSION,
			'<'
		);
	}
}
