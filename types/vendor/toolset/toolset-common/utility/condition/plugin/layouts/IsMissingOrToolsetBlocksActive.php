<?php

namespace OTGS\Toolset\Common\Condition\Layouts;

use OTGS\Toolset\Common\Condition\Views\IsBlocksFlavour;

/**
 * Test if Layouts is not active or Views (in the Toolset Blocks flavour) is active.
 *
 * @since Types 3.3.8
 */
class IsMissingOrToolsetBlocksActive extends \Toolset_Condition_Plugin_Layouts_Missing {


	/** @var IsBlocksFlavour */
	private $is_blocks_flavour_condition;


	/**
	 * IsMissingOrToolsetBlocksActive constructor.
	 *
	 * @param IsBlocksFlavour|null $is_blocks_flavour_condition
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct(
		IsBlocksFlavour $is_blocks_flavour_condition = null, \Toolset_Constants $constants = null
	) {
		parent::__construct( $constants );
		$this->is_blocks_flavour_condition = $is_blocks_flavour_condition ?: new IsBlocksFlavour();
	}


	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function is_met() {
		return parent::is_met() || $this->is_blocks_flavour_condition->is_met();
	}

}
