<?php

namespace OTGS\Toolset\Common\Settings;

/**
 * Manager for the Font Awesome version setting.
 *
 * @since 3.6.0
 */
class FontAwesomeSetting implements SettingInterface {

	const FA_4 = '4';
	const FA_5 = '5';

	const VALID_VALUES = [
		self::FA_4,
		self::FA_5,
	];

	const DEFAULT_VALUE = '5';

	/** @var \Toolset_Settings */
	private $settings;

	/**
	 * BootstrapSetting constructor.
	 *
	 * @param \Toolset_Settings $settings
	 */
	public function __construct( \Toolset_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Retrieve current setting value, possibly applying filters or default value.
	 *
	 * @return string
	 */
	public function get_current_value() {
		$version = $this->load_from_options();

		if ( false === $version ) {
			$version = self::DEFAULT_VALUE;
		}

		return $version;
	}

	/**
	 * Load the setting value as it is storied in WordPress options.
	 *
	 * Perform basic sanitization and validation.
	 *
	 * @return string|false
	 */
	public function load_from_options() {
		// We're not using $this->settings deliberately, because we need to bypass the default value setting.
		$raw_options = get_option( \Toolset_Settings::OPTION_NAME );
		$value = toolset_getarr( $raw_options, \Toolset_Settings::FONT_AWESOME_VERSION );

		if ( is_int( $value ) ) {
			$value = (string) $value;
		}

		if ( ! $this->is_value_valid( $value ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Set a new value for the setting.
	 *
	 * @param mixed $new_value Casted to string since import methods can try to force pass an integer.
	 * @return void
	 * @throws \InvalidArgumentException If the value isn't one of VALID_VALUES.
	 */
	public function set_value( $new_value ) {
		$new_value = strval( $new_value );

		if ( ! $this->is_value_valid( $new_value ) ) {
			throw new \InvalidArgumentException();
		}

		$this->settings->set( \Toolset_Settings::FONT_AWESOME_VERSION, $new_value, true );
		$this->settings->save();
	}

	/**
	 * Make sure the value is one of VALID_VALUES.
	 *
	 * Strict checking applies.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private function is_value_valid( $value ) {
		return in_array( $value, self::VALID_VALUES, true );
	}

}
