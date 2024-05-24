<?php

namespace OTGS\Toolset\Common\Condition\Plugin\UltimateAddonsGutenberg;

/**
 * Condition for deciding if Ultimate Addons Gutenberg plugin is in a version prior to 1.23.0.
 */
class IsUltimateAddonsGutenbergPre123 implements \Toolset_Condition_Interface {
	/** @var \Toolset_Constants */
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
	 * Checks if the condition of Ultimate Addons Gutenberg plugin is in a version prior to 1.23.0 is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return (
			$this->constants->defined( 'UAGB_VER' ) &&
			version_compare( $this->constants->constant( 'UAGB_VER' ), '1.23.0', '<' )
		);
	}
}
