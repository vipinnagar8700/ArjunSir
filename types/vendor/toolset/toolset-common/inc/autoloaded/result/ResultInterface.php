<?php

namespace OTGS\Toolset\Common\Result;


/**
 * Shared interface for Toolset_Result_* classes that simplifies their usage in case a method can return both
 * Toolset_Result or Toolset_Result_Set.
 *
 * @package OTGS\Toolset\Common\Result
 * @since 3.0.6
 */
interface ResultInterface {


	/**
	 * @return bool
	 */
	public function is_error();


	/**
	 * @return bool
	 */
	public function is_success();


	/**
	 * @return bool
	 */
	public function has_warnings();


	/**
	 * @return bool
	 */
	public function has_message();


	/**
	 * @return string
	 */
	public function get_message();


	/**
	 * @return int
	 */
	public function get_code();


	/**
	 * Returns the result as an associative array in a standard form.
	 *
	 * That means, it will allways have the boolean element 'success' and
	 * a string 'message', if a display message is set.
	 *
	 * @return array
	 */
	public function to_array();


	/**
	 * @return int One of the LogLevel constants. INFO is default.
	 */
	public function get_level();

}
