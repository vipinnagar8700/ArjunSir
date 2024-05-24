<?php

namespace OTGS\Toolset\Common\Wordpress\Option;

/**
 * Interface of a WordPress option model.
 *
 * @since Types 3.0 implemented in Types
 * @since 3.3.5 (Types 3.2.5) moved to Toolset Common
 */
interface IOption {


	/**
	 * Returns the option key.
	 *
	 * @return string
	 */
	public function getKey();


	/**
	 * Returns the option value.
	 *
	 * @return mixed
	 */
	public function getOption();


	/**
	 * Updates the option.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function updateOption( $value );


	/**
	 * Deletes the option.
	 *
	 * @return mixed
	 */
	public function deleteOption();
}
