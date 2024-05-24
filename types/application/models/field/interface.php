<?php

/**
 * Interface Types_Field_Interface
 *
 * @since 2.3
 */
interface Types_Field_Interface extends Types_Interface_Value {
	/**
	 * @return string
	 */
	public function get_slug();

	/**
	 * @return array|string
	 */
	public function get_title();

	/**
	 * User stored value with applied display filters
	 *
	 * @param array $user_params
	 * @param Types_Interface_Value|null $source
	 * @return array|string
	 */
	public function get_value_filtered( $user_params = array(), $source = null );

	/**
	 * @return bool
	 */
	public function is_repeatable();

	/**
	 * @return string
	 */
	public function get_type();


	/**
	 * @return string
	 */
	public function get_description();
}
