<?php

/**
 * Toolset_Condition_Plugin_Views_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Views_Active implements Toolset_Condition_Interface {


	/** @var Toolset_Constants */
	private $constants;


	/**
	 * Toolset_Condition_Plugin_Views_Active constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new Toolset_Constants();
	}


	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function is_met() {
		return $this->constants->defined( 'WPV_VERSION' );
	}

}
