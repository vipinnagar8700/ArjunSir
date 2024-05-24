<?php

namespace OTGS\Toolset\Common\Condition\Plugin\Kadence;

/**
 * Condition for deciding if Kadence Blocks plugin is active.
 */
class IsKadenceActive implements \Toolset_Condition_Interface {
	/** @var Toolset_Constants */
	private $constants;

	/**
	 * IsKadenceActive constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	/**
	 * Checks if the condition of Kadence Blocks plugin is active is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return $this->constants->defined( 'KADENCE_BLOCKS_VERSION' );
	}
}
