<?php

namespace OTGS\Toolset\Common\Condition\Plugin\FusionBuilder;

/**
 * Condition to check whether the Fusion Builder plugin (from Avada) is available.
 */
class IsFusionBuilderActive implements \Toolset_Condition_Interface {

	/**
	 * Check whether the condition is met.
	 *
	 * Note that the fb_library_loaded action runs on after_setup_theme:10.
	 *
	 * @return bool
	 */
	public function is_met() {
		return did_action( 'fb_library_loaded' );
	}
}
