<?php

namespace OTGS\Toolset\Common\Condition\Plugin\UltimateAddonsGutenberg;

/**
 * Condition for deciding if Ultimate Addons Gutenberg plugin is active.
 *
 * @since 3.5.8
 */
class IsUltimateAddonsGutenbergActive implements \Toolset_Condition_Interface {
	/** @var Toolset_Constants */
	private $constants;

	/**
	 * IsUltimateAddonsGutenbergActive constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	/**
	 * Checks if the condition of Ultimate Addons Gutenberg plugin is active is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		if ( $this->constants->defined( 'UAGB_PLUGIN_NAME' ) )
			return true;

		return false;
	}
}
