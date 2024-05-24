<?php

namespace OTGS\Toolset\Common\Condition\Plugin\Genesis;

/**
 * Condition for deciding if Genesis Blocks plugin is active.
 */
class IsGenesisActive implements \Toolset_Condition_Interface {
	/**
	 * Checks if the condition of Genesis Blocks plugin is active is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return function_exists( 'genesis_blocks_load' );
	}
}
