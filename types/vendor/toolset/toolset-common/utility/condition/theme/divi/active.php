<?php

namespace OTGS\Toolset\Common\Condition\Theme\Divi;

/**
 * IsDiviThemeActive
 *
 * Condition for deciding if Divi Theme is active.
 *
 * @since 3.5.6
 */
class IsDiviThemeActive implements \Toolset_Condition_Interface {
	/** @var Toolset_Constants */
	protected $constants;

	/**
	 * IsDiviThemeActive constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	/**
	 * Determines if the condition for active Divi Theme is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		// The condition that decides if Divi is active cannot be cached because the Divi Theme is instantiated after the
		// condition classes are initialized, thus we need to check this condition every time we want to do something related
		// with Divi.
		if ( $this->constants->defined( 'ET_CORE' ) || function_exists( 'et_setup_theme' ) ) {
			// Divi is active.
			return true;
		}

		return false;
	}

}
