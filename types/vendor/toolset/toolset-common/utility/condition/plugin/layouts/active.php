<?php

/**
 * Toolset_Condition_Plugin_Layouts_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Layouts_Active implements Toolset_Condition_Interface {
	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();
	}

	public function is_met() {
		return $this->constants->defined( 'WPDDL_DEVELOPMENT' ) || $this->constants->defined( 'WPDDL_PRODUCTION' );
	}

}