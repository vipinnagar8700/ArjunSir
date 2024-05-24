<?php

namespace OTGS\Toolset\Common\Settings;

/**
 * Working with a Bootstrap (BS) version setting is complex because of several backward compatibility
 * mechanisms that combine together. However, the whole logic should be contained within this single class:
 *
 * 1. If Layouts is active and there is no BS version setting stored:
 *     - if Layouts is being activated for the first time
 *         => save BS4_TOOLSET to database (used to be DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION)
 *     - if this is an upgrade from an older version
 *         => save DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION to database
 *
 *     Note: This is handled by hooking into the 'ddl-before_init_layouts_plugin' action which actually
 *     runs *later* and overwrites the version after it has been determined by steps below.
 *
 * 2. Load the value from options with a fallback to a legacy Views setting.
 *     - If it's empty or not valid (which btw. means there's no Layouts), try loading a value from Views settings.
 *     - Use the value from Views, if it's valid (it is also possible that "1" will be stored, which we
 *       no longer accept).
 *
 * 3. Apply the 'toolset_set_bootstrap_option' filter on the current value.
 *
 * 4. Process "automatic defaults" set by Layouts.
 *     - If any filters have actually been applied in 'toolset_set_bootstrap_option' and if we have one of the
 *       "automatic defaults" in the database (DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION or
 *       DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION), ignore the filtered value and use the one from database instead.
 *
 * 5. Translate "automatic defaults" to a value that directly represents a Bootstrap version.
 * 6. Make sure that the returned value is always one of the VALID_VALUES
 *     (invalid one will be transformed to NO_BOOTSTRAP).
 *
 * The reason for all this is that, in the past, using Layouts automatically meant that BS3 will be
 * used, and by default it was not being loaded by Toolset. But in such case, we didn't save any
 * value of this option to the database. When upgrading from such a version of Layouts, we need to recognize
 * the situation and save the correct value. Furthermore, this value will be enforced, ignoring any
 * filters, until the user manually updates the BS version in Toolset Settings. Since that moment, the choice
 * is on them again.
 *
 * @since BS4
 */
class BootstrapSetting implements SettingInterface {

	const NO_BOOTSTRAP = '-1';
	const BS2_EXTERNAL = '2';
	const BS3_EXTERNAL = '3';
	const BS3_TOOLSET = '3.toolset';
	const BS4_EXTERNAL = '4';
	const BS4_TOOLSET = '4.toolset';
	const DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION = '98';
	const DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION = '99';

	const VALID_VALUES = [
		self::NO_BOOTSTRAP,
		self::BS2_EXTERNAL,
		self::BS3_EXTERNAL,
		self::BS3_TOOLSET,
		self::BS4_EXTERNAL,
		self::BS4_TOOLSET,
		self::DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION,
		self::DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION,
	];

	const STYLES_TO_ENQUEUE = [
		self::BS3_TOOLSET => [ \Toolset_Assets_Manager::STYLE_BOOTSTRAP_3 ],
		self::BS4_TOOLSET => [ \Toolset_Assets_Manager::STYLE_BOOTSTRAP_4 ],
	];

	const SCRIPTS_TO_ENQUEUE = [
		self::BS3_TOOLSET => [ \Toolset_Assets_Manager::SCRIPT_BOOTSTRAP_3 ],
		self::BS4_TOOLSET => [ \Toolset_Assets_Manager::SCRIPT_BOOTSTRAP_4 ],
	];

	const ENQUEUING_PRIORITY = [
		self::BS3_TOOLSET => 10,
		// Negative number because some popular themes like Astra are enqueuing their styles with priority 1 and we
		// need to have BS4 loaded even sooner.
		self::BS4_TOOLSET => -10,
	];

	const SETTING_VALUE_TO_BS_VERSION_NUMBER = [
		self::BS2_EXTERNAL => self::NUMERIC_BS2,
		self::BS3_TOOLSET => self::NUMERIC_BS3,
		self::BS3_EXTERNAL => self::NUMERIC_BS3,
		self::BS4_EXTERNAL => self::NUMERIC_BS4,
		self::BS4_TOOLSET => self::NUMERIC_BS4,
	];


	// Give those numbers a semantical meaning...
	const NUMERIC_BS2 = 2;
	const NUMERIC_BS3 = 3;
	const NUMERIC_BS4 = 4;

	/** @var null|string Used only to keep original value from the filter. */
	private $toolset_bootstrap_version_from_filter = null;

	/** @var null|string Selected BS version, one of the VALID_VALUES or null if not initialized yet. */
	private $toolset_bootstrap_version;

	/** @var bool Indicates that the BS version has already been determined. */
	private $was_fully_loaded = false;

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
	 * Initialize the setting class.
	 *
	 * Must be called very early, while initializing Toolset Common.
	 */
	public function initialize() {
		// Note that this runs after the BS version has been determined.
		add_action( 'ddl-before_init_layouts_plugin', function () {
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$layouts_settings = apply_filters( 'ddl-get_all_layouts_settings', array() );
			$is_layouts_newly_activated = ( count( $layouts_settings ) === 0 );
			$setting_value_from_database = $this->load_from_options();

			if ( false === $setting_value_from_database ) {
				$new_value = (
				$is_layouts_newly_activated
					? self::BS4_TOOLSET
					: self::DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION
				);
				$this->set_value( $new_value );
				$this->settings->save();

				// We need to override the already stored value, because this action hook runs
				// (most probably) after it has been determined.
				$this->toolset_bootstrap_version = $this->placeholder_version_to_actual_version( $new_value );
			}
		} );

		add_filter(
			'toolset-toolset_bootstrap_version_filter',
			function ( /** @noinspection PhpUnusedParameterInspection */ $ignored ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				if ( isset( $this->toolset_bootstrap_version_from_filter ) ) {
					return $this->toolset_bootstrap_version_from_filter;
				}

				return false;
			}
		);
		add_filter(
			'toolset-toolset_bootstrap_version_manually_selected',
			array( $this, 'load_from_options' )
		);
	}


	/**
	 * Retrieve current setting value, possibly applying filters or default value.
	 *
	 * @return string
	 */
	public function get_current_value() {
		if ( ! $this->was_fully_loaded ) {
			$this->toolset_bootstrap_version = $this->determine_bootstrap_version();

			$this->was_fully_loaded = true;
		}

		return $this->toolset_bootstrap_version;
	}


	/**
	 * Get the currently chosen Bootstrap version (not considering where is it loaded from) as a number.
	 *
	 * @return int Major version number (2, 3, 4, ...) or zero if no Bootstrap is configured.
	 */
	public function get_current_value_numeric() {
		$version_map = self::SETTING_VALUE_TO_BS_VERSION_NUMBER;
		return (int) toolset_getarr( $version_map, $this->get_current_value(), 0 );
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
		$value = toolset_getarr( $raw_options, \Toolset_Settings::BOOTSTRAP_VERSION );
		if ( is_int( $value ) ) {
			$value = (string) $value;
		}

		if ( ! $this->is_value_valid( $value ) ) {
			return false;
		}

		return $value;
	}


	/**
	 * Make sure the value is one of VALID_VALUES.
	 *
	 * Strict checking applies.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private function is_value_valid( $value ) {
		return in_array( $value, self::VALID_VALUES, true );
	}


	/**
	 * Set a new value for the setting.
	 *
	 * @param mixed $new_value Casted to string since import methods can try to force pass an integer.
	 *
	 * @return void
	 * @throws \InvalidArgumentException If the value isn't one of VALID_VALUES.
	 */
	public function set_value( $new_value ) {
		$new_value = strval( $new_value );

		if ( ! $this->is_value_valid( $new_value ) ) {
			throw new \InvalidArgumentException();
		}
		$this->toolset_bootstrap_version = $new_value;
		$this->settings->set( \Toolset_Settings::BOOTSTRAP_VERSION, $new_value, true );
	}


	/**
	 * If a given BS version is one of the "auto defaults", convert it to a corresponding setting.
	 *
	 * This way, these auto defaults will be hidden from the outside world, which really doesn't need to
	 * know about them.
	 *
	 * @param string|bool $bs_version
	 *
	 * @return bool|string
	 */
	private function placeholder_version_to_actual_version( $bs_version ) {
		if ( ! is_string( $bs_version ) ) {
			return false;
		}

		$real_versions = array(
			self::DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION => self::BS3_TOOLSET,
			self::DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION => self::BS3_EXTERNAL,
		);

		if ( array_key_exists( $bs_version, $real_versions ) ) {
			return $real_versions[ $bs_version ];
		}

		return $bs_version;
	}


	/**
	 * Determine the selected BS version.
	 *
	 * See class description for details.
	 *
	 * @return string A valid BS version.
	 */
	private function determine_bootstrap_version() {
		$version_from_options = $this->load_from_options();
		if ( false === $version_from_options ) {
			$version_from_options = $this->get_default_bootstrap_version_from_views();
		}

		$filtered_version = apply_filters( 'toolset_set_boostrap_option', $version_from_options );

		// If a filter has been applied and one of the "auto default" values is stored in the database,
		// ignore the filtered value.
		if ( has_filter( 'toolset_set_boostrap_option' ) ) {
			// set filter value as backup
			$this->toolset_bootstrap_version_from_filter = $filtered_version;

			// get raw selection from database
			$selected_bs_version = $this->load_from_options();

			if ( in_array(
				$selected_bs_version,
				array(
					self::DEFAULT_BS3_EXTERNAL_AFTER_LAYOUTS_ACTIVATION,
					self::DEFAULT_BS3_TOOLSET_AFTER_LAYOUTS_ACTIVATION,
				),
				true
			) ) {
				// If the user already selected something, use that version.
				return $this->toolset_bootstrap_version_from_filter;
			}

			return $this->transform_invalid_value( $selected_bs_version );
		}

		// No filters have been applied - we just use the value from the database directly.
		return $this->transform_invalid_value( $this->placeholder_version_to_actual_version( $filtered_version ) );
	}


	/**
	 * Get the Bootstrap version from Views, if it's set to something we still support.
	 */
	private function get_default_bootstrap_version_from_views() {
		if ( class_exists( '\WPV_Settings' ) ) {
			$views_settings = \WPV_Settings::get_instance();

			$version_from_views = (string) (int) $views_settings->wpv_bootstrap_version;
			if ( '1' !== $version_from_views && $this->is_value_valid( $version_from_views ) ) {
				return $version_from_views;
			}
		}

		return false;
	}


	/**
	 * Make sure an invalid value is transformed to NO_BOOTSTRAP.
	 *
	 * @param string $bs_version
	 *
	 * @return string
	 */
	private function transform_invalid_value( $bs_version ) {
		if ( ! $this->is_value_valid( $bs_version ) ) {
			return self::NO_BOOTSTRAP;
		}

		return $bs_version;
	}


	/**
	 * Indicates whether any Bootstrap assets need to be loaded by Toolset.
	 *
	 * @return bool
	 */
	public function needs_enqueuing() {
		return in_array( $this->get_current_value(), array( self::BS3_TOOLSET, self::BS4_TOOLSET ), true );
	}


	/**
	 * Get a right set of handles to enqueue according to the selected Bootstrap version.
	 *
	 * @param string[][] $handles Expects either self::STYLES_TO_ENQUEUE or self::SCRIPTS_TO_ENQUEUE.
	 *
	 * @return string[] Handles to enqueue.
	 */
	private function get_handles( $handles ) {
		return toolset_ensarr( toolset_getarr( $handles, $this->get_current_value() ) );
	}


	/**
	 * Get styles that need to be enqueued, depending on the selected Bootstrap version.
	 *
	 * @return string[] Handles to enqueue.
	 */
	public function get_styles_to_enqueue() {
		return $this->get_handles( self::STYLES_TO_ENQUEUE );
	}


	/**
	 * Get scripts that need to be enqueued, depending on the selected Bootstrap version.
	 *
	 * @return string[] Handles to enqueue.
	 */
	public function get_scripts_to_enqueue() {
		return $this->get_handles( self::SCRIPTS_TO_ENQUEUE );
	}


	/**
	 * Get the default priority for the wp_enqueue_scripts hook that should enqueue Bootstrap assets.
	 *
	 * The value depends on BS version because since BS4, we want it to be loaded before theme's assets, but for BS3,
	 * we need to keep the previous default value to prevent potential issues with existing sites.
	 *
	 * @return int|null Priority or null if no enqueuing is supposed to happen.
	 */
	public function get_enqueuing_priority() {
		$priorities = self::ENQUEUING_PRIORITY;
		return toolset_getarr( $priorities, $this->get_current_value(), null );
	}
}
