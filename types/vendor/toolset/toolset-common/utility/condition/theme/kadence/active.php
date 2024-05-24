<?php

namespace OTGS\Toolset\Common\Condition\Theme\Kadence;

/**
 * IsKadenceThemeActive
 *
 * Condition for deciding if Kadence Theme is active.
 *
 * @since 3.5.6
 */
class IsKadenceThemeActive implements \Toolset_Condition_Interface {
	/** @var Toolset_Constants */
	protected $constants;

	/**
	 * IsKadenceThemeActive constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	/**
	 * Determines if the condition for active Kadence Theme is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return $this->constants->defined( 'KADENCE_VERSION' );
	}

}
