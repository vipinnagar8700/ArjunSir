<?php

/**
 * Toolset_Condition_Plugin_Views_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Views_Version_Greater_Or_Equal implements Toolset_Condition_Interface {

	private $target_version;

	public function __construct( $target_version ) {
		$this->target_version = $target_version;
	}

	public function is_met() {
		if (
			defined( 'WPV_VERSION' ) &&
			version_compare( WPV_VERSION, $this->target_version, '>=' )
		) {
			return true;
		}


		return false;
	}

}