<?php

/**
 * Condition for a function existence.
 *
 * Needs to be configured before using. Intended for dependency injection.
 *
 * @since 3.1
 */
class Toolset_Condition_Function_Exists implements Toolset_Condition_Interface {


	private $function_name;


	public function configure( $function_name ) {
		$this->function_name = $function_name;
	}


	/**
	 * @return bool
	 */
	public function is_met() {
		return function_exists( $this->function_name );
	}

}