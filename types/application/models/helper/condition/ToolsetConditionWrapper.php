<?php

namespace OTGS\Toolset\Types\Helper\Condition;

use Types_Helper_Condition;

/**
 * A subclass of Types_Helper_Condition that encapsulates a Toolset_Condition_Interface,
 * so that it can be used in the code handling Toolset Dashboard.
 *
 * @since 3.3.8
 */
class ToolsetConditionWrapper extends Types_Helper_Condition {


	/** @var \Toolset_Condition_Interface */
	private $inner_condition;


	/**
	 * ToolsetConditionWrapper constructor.
	 *
	 * @param \Toolset_Condition_Interface $inner_condition
	 */
	public function __construct( \Toolset_Condition_Interface $inner_condition ) {
		$this->inner_condition = $inner_condition;
	}


	/**
	 * @inheritDoc
	 * @return bool True if the inner condition is met, false otherwise.
	 */
	public function valid() {
		return $this->inner_condition->is_met();
	}

}
