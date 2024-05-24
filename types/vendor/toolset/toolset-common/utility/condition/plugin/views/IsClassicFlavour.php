<?php

namespace OTGS\Toolset\Common\Condition\Views;

/**
 * Test if Toolset Views is active in the classic flavour (no Toolset Blocks).
 *
 * @since Types 3.3.8
 */
class IsClassicFlavour extends IsClassicFlavourOrInactive {

	/** @var \Toolset_Condition_Plugin_Views_Active */
	private $is_active_condition;


	/**
	 * IsClassicFlavour constructor.
	 *
	 * @param \Toolset_Condition_Plugin_Views_Active|null $is_active_condition
	 */
	public function __construct( \Toolset_Condition_Plugin_Views_Active $is_active_condition = null ) {
		$this->is_active_condition = $is_active_condition ?: new \Toolset_Condition_Plugin_Views_Active();
	}


	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function is_met() {
		return ( $this->is_active_condition->is_met() && parent::is_met() );
	}


}
