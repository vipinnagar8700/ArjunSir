<?php

namespace OTGS\Toolset\Common\Condition\Theme\PageBuilderFramework;

/**
 * Condition for deciding if the Page Builder Framework theme is active.
 */
class IsPageBuilderFrameworkThemeActive implements \Toolset_Condition_Interface {
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
	 * Determines if the condition for the theme is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return $this->constants->defined( 'WPBF_VERSION' );
	}

}
