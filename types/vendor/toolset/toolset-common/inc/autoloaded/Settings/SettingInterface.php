<?php

namespace OTGS\Toolset\Common\Settings;

interface SettingInterface {

	/**
	 * Retrieve current setting value, possibly applying filters or default value.
	 *
	 * @return mixed
	 */
	public function get_current_value();


	/**
	 * Load the setting value as it is storied in WordPress options.
	 *
	 * @return mixed
	 */
	public function load_from_options();


	/**
	 * Set a new value for the setting.
	 *
	 * @param mixed $new_value
	 *
	 * @return void
	 * @throws \InvalidArgumentException When the value type doesn\'t match.
	 * @throws \RuntimeException When the value cannot be applied.
	 */
	public function set_value( $new_value );

}
