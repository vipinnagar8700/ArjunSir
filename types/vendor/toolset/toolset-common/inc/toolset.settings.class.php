<?php

// phpcs:ignoreFile PSR2.Methods.MethodDeclaration.Underscore
/**
 * Toolset Settings class
 *
 * It implements both ArrayAccess and dynamic properties. ArrayAccess is deprecated.
 *
 * @since 2.0
 *
 * @property string $show_admin_bar_shortcut
 * @property string $shortcodes_generator
 * @property string $toolset_bootstrap_version
 * @property-read int $bootstrap_version_numeric
 * @property string $toolset_font_awesome_version
 */
class Toolset_Settings implements ArrayAccess {


	/**
	 * WP Option Name for Views settings.
	 */
	const OPTION_NAME = 'toolset_options';


	/* ************************************************************************* *\
		SETTING NAMES
	\* ************************************************************************* */


	/**
	 * Determine whether frontend admin bar menu should be displayed.
	 *
	 * String value, 'on' or 'off'.
	 *
	 * Defaults to 'on'.
	 *
	 * @since 2.0
	 */
	const ADMIN_BAR_CREATE_EDIT = 'show_admin_bar_shortcut';


	/**
	 * Determine whether the backend shortcode generator admin bar menu should be displayed.
	 *
	 * String value, 'unset', 'disable', 'editor' or 'always'.
	 *
	 * Defaults to 'unset'.
	 *
	 * @since 2.0
	 */
	const ADMIN_BAR_SHORTCODES_GENERATOR = 'shortcodes_generator';

	/**
	 * List of Types postmeta fields that we want to index in Relevanssi.
	 *
	 * Array.
	 *
	 * Defaults to an empty array.
	 *
	 * @since 2.2
	 */
	const RELEVANSSI_FIELDS_TO_INDEX = 'relevanssi_fields_to_index';

	/**
	 * Bootstrap version that is expected to be used in a theme.
	 *
	 * Allowed values are:
	 * - '2': Bootstrap 2.0
	 * - '3': Bootstrap 3.0
	 * - '3.toolset': Bootstrap 3.0, but load it from toolset common
	 * - '-1': Site is not using Bootstrap (@since 1.9)
	 * - '1' or missing value (or perhaps anything else, too): Bootstrap version not set
	 *
	 * @since unknown
	 */
	const BOOTSTRAP_VERSION = 'toolset_bootstrap_version';


	/**
	 * Determines whether custom fields should be exposed in the REST API for posts, users and terms.
	 *
	 * Boolean, false by default.
	 *
	 * @since Types 3.3
	 */
	const EXPOSE_CUSTOM_FIELDS_IN_REST = 'toolset_expose_custom_fields_in_rest';

	/**
	 * Font Awesome version that is expected to be used by Toolset.
	 *
	 * Allowed values are:
	 * - '4': Font Awesome 4.7.0
	 * - '5': Font Awesome 5.13.0
	 *
	 * @since
	 */
	const FONT_AWESOME_VERSION = 'toolset_font_awesome_version';


	/**
	 * Determines whether the migration notice is shown in all pages or only in Toolset Dashboard.
	 *
	 * String, yes by default. (yes|no)
	 *
	 * @since Types 3.4.3
	 */
	const DATABASE_MIGRATION_NOTICE_SHOW = 'toolset_database_migration_notice_show';


	/* ************************************************************************* *\
        SINGLETON
    \* ************************************************************************* */


	/**
	 * @var Toolset_Settings Instance of Toolset_Settings.
	 */
	private static $instance = null;


	/** @var \OTGS\Toolset\Common\Settings\BootstrapSetting */
	private $bootstrap_setting_model;

	/** @var \OTGS\Toolset\Common\Settings\FontAwesomeSetting */
	private $font_awesome_setting_model;


	/**
	 * @return Toolset_Settings The instance of Toolset_Settings.
	 */
	public static function get_instance() {
		if ( null == Toolset_Settings::$instance ) {
			Toolset_Settings::$instance = new Toolset_Settings();
		}

		return Toolset_Settings::$instance;
	}


	public static function clear_instance() {
		if ( Toolset_Settings::$instance ) {
			Toolset_Settings::$instance = null;
		}
	}


	/* ************************************************************************* *\
        DEFAULTS
    \* ************************************************************************* */


	/**
	 * @var array Default setting values.
	 */
	protected static $defaults = array(
		Toolset_Settings::ADMIN_BAR_CREATE_EDIT => 'on',
		Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR => 'unset',
		Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX => array(),
		Toolset_Settings::BOOTSTRAP_VERSION => 3,
		Toolset_Settings::FONT_AWESOME_VERSION => '5',
		Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST => false,
		Toolset_Settings::DATABASE_MIGRATION_NOTICE_SHOW => 'yes',
	);


	/**
	 * @return array Associative array of default values for settings.
	 */
	public function get_defaults() {
		return Toolset_Settings::$defaults;
	}


	/**
	 * Toolset_Settings constructor.
	 */
	protected function __construct() {
		$this->load_settings();

	}


	/* ************************************************************************* *\
        OPTION LOADING AND SAVING
    \* ************************************************************************* */


	private $settings = null;


	/**
	 * Load settings from the database.
	 */
	private function load_settings() {
		$this->settings = get_option( self::OPTION_NAME );
		if ( ! is_array( $this->settings ) ) {
			$this->settings = array(); // Defaults will be used in this case.
		}

		$this->bootstrap_setting_model = new \OTGS\Toolset\Common\Settings\BootstrapSetting( $this );
		$this->bootstrap_setting_model->initialize();

		$this->font_awesome_setting_model = new \OTGS\Toolset\Common\Settings\FontAwesomeSetting( $this );
	}




	/**
	 * Persists settings in the database.
	 */
	public function save() {
		update_option( self::OPTION_NAME, $this->settings, true );
	}



	/* ************************************************************************* *\
        ArrayAccess IMPLEMENTATION
    \* ************************************************************************* */


	/**
	 * isset() for ArrayAccess interface.
	 *
	 * @param mixed $offset setting name
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->settings[ $offset ] );
	}


	/**
	 * Getter for ArrayAccess interface.
	 *
	 * @param mixed $offset setting name
	 *
	 * @return mixed setting value
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		if ( $offset ) {
			return $this->get( $offset );
		} else {
			return null;
		}
	}


	/**
	 * Setter for ArrayAccess interface.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}


	/**
	 * unset() for ArrayAccess interface.
	 *
	 * @param mixed $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		if ( isset( $this->settings[ $offset ] ) ) {
			unset( $this->settings[ $offset ] );
		}
	}


	/* ************************************************************************* *\
        MAGIC PROPERTIES
    \* ************************************************************************* */


	/**
	 * PHP dynamic setter.
	 *
	 * @param mixed $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}


	/**
	 * PHP dynamic setter.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}


	/**
	 * PHP dynamic fields unset() method support
	 *
	 * @param string $key
	 */
	public function __unset( $key ) {
		if ( $this->offsetExists( $key ) ) {
			$this->offsetUnset( $key );
		}
	}


	/**
	 * PHP dynamic support for isset($this->name)
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function __isset( $key ) {
		return $this->offsetExists( $key );
	}


	/* ************************************************************************* *\
        GENERIC GET/SET METHODS
    \* ************************************************************************* */


	/**
	 * Obtain a value for a setting (or all settings).
	 *
	 * @param string $key name of the setting to retrieve
	 * @param bool $raw_value If true, the value is going to be taken as-is even though there's a dedicated getter.
	 *     This is useful for managing specific settings by dedicated classes.
	 *
	 * @return mixed value of the key or an array with all key-value pairs
	 */
	public function get( $key = null, $raw_value = false ) {
		if ( $key ) {
			// Retrieve one setting
			$method_name = '_get_' . $key;
			if ( method_exists( $this, $method_name ) && ! $raw_value ) {
				// Use custom getter if it exists
				return $this->$method_name();
			} else {
				return $this->get_raw_value( $key );
			}
		} else {
			// Retrieve all settings
			return wp_parse_args( $this->settings, Toolset_Settings::$defaults );
		}
	}


	/**
	 * Get "raw" value from settings or default settings, without taking custom getters into account.
	 *
	 * @param string $key Setting name
	 *
	 * @return null|mixed Setting value or null if it's not defined anywhere.
	 */
	private function get_raw_value( $key ) {

		if ( isset( $this->settings[ $key ] ) ) {
			// Return user-set value, if available
			return $this->settings[ $key ];
		} elseif ( isset( Toolset_Settings::$defaults[ $key ] ) ) {
			// Use default value, if available
			return Toolset_Settings::$defaults[ $key ];
		} else {
			// There isn't any key like that
			return null;
		}
	}


	/**
	 * Set Setting(s).
	 *
	 * Usage:
	 *  One key-value pair
	 *  set('key', 'value');
	 *
	 *  Multiple key-value pairs
	 *  set( array('key1' => 'value1', 'key2' => 'value2' );
	 *
	 * @param mixed $param1 Name of the setting or an array with name-value pairs of the settings (bulk set).
	 * @param mixed $param2 Value of the setting.
	 * @param bool $set_raw_value If true, the value is going to be set as-is even though there's a dedicated setter.
	 *     This is useful for managing specific settings by dedicated classes.
	 */
	public function set( $param1, $param2 = null, $set_raw_value = false ) {
		if ( is_array( $param1 ) ) {
			foreach ( $param1 as $key => $value ) {
				$this->settings[ $key ] = $value;
			}
		} elseif (
			is_object( $param1 )
			&& is_a( $param1, 'Toolset_Settings' )
		) {
			// DO NOTHING.
			// It's assigned already.
		} elseif (
			is_string( $param1 )
			|| is_integer( $param1 )
		) {
			$key = $param1;
			$value = $param2;
			// Use custom setter if it exists.
			$method_name = '_set_' . $key;
			if ( method_exists( $this, $method_name ) && ! $set_raw_value ) {
				$this->$method_name( $value );
			} else {
				// Fall back to array access mode
				$this->settings[ $key ] = $value;
			}

		}
	}


	/**
	 * Find out whether we have any knowledge about setting of given name.
	 *
	 * Looks for it's value, default value or for custom getter.
	 *
	 * @param string $key Setting name.
	 *
	 * @return bool True if setting seems to exist.
	 */
	public function has_setting( $key ) {
		return (
			isset( $this->settings[ $key ] )
			|| isset( Toolset_Settings::$defaults[ $key ] )
			|| method_exists( $this, '_get_' . $key )
		);
	}

	/* ************************************************************************* *\
        CUSTOM GETTERS AND SETTERS
    \* ************************************************************************* */

	/**
	 * Safe show_admin_bar_shortcut getter, allways returns a valid value.
	 *
	 * @since 2.0
	 */
	protected function _get_show_admin_bar_shortcut() {
		$value = $this->get_raw_value( Toolset_Settings::ADMIN_BAR_CREATE_EDIT );
		if ( ! $this->_is_valid_show_admin_bar_shortcut( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::ADMIN_BAR_CREATE_EDIT ];
		}

		return $value;
	}


	/**
	 * Safe show_admin_bar_shortcut setter.
	 *
	 * @since 2.0
	 */
	protected function _set_show_admin_bar_shortcut( $value ) {
		if ( $this->_is_valid_show_admin_bar_shortcut( $value ) ) {
			$this->settings[ Toolset_Settings::ADMIN_BAR_CREATE_EDIT ] = $value;
		}
	}


	/**
	 * Helper validation for show_admin_bar_shortcut.
	 *
	 * @since 2.0
	 */
	protected function _is_valid_show_admin_bar_shortcut( $value ) {
		return in_array( $value, array( 'on', 'off' ) );
	}


	/**
	 * Safe shortcodes_generator getter, allways returns a valid value.
	 *
	 * @since 2.0
	 */
	protected function _get_shortcodes_generator() {
		$value = $this->get_raw_value( Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR );
		if ( ! $this->_is_valid_shortcodes_generator( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR ];
		}

		return $value;
	}


	/**
	 * Safe shortcodes_generator setter.
	 *
	 * @since 2.0
	 */
	protected function _set_shortcodes_generator( $value ) {
		if ( $this->_is_valid_shortcodes_generator( $value ) ) {
			$this->settings[ Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR ] = $value;
		}
	}


	/**
	 * Helper validation for shortcodes_generator.
	 *
	 * @since 2.0
	 */
	protected function _is_valid_shortcodes_generator( $value ) {
		return in_array( $value, array( 'unset', 'disable', 'editor', 'always' ) );
	}


	/**
	 * Safe shortcodes_generator getter, allways returns a valid value.
	 *
	 * @since 2.0
	 */
	protected function _get_relevanssi_fields_to_index() {
		$value = $this->get_raw_value( Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX );
		if ( ! $this->_is_valid_relevanssi_fields_to_index( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX ];
		}

		return $value;
	}


	/**
	 * Safe shortcodes_generator setter.
	 *
	 * @since 2.0
	 */
	protected function _set_relevanssi_fields_to_index( $value ) {
		if ( $this->_is_valid_relevanssi_fields_to_index( $value ) ) {
			$this->settings[ Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX ] = $value;
		}
	}


	/**
	 * Helper validation for shortcodes_generator.
	 *
	 * @since 2.0
	 */
	protected function _is_valid_relevanssi_fields_to_index( $value ) {
		return is_array( $value );
	}


	/**
	 * @return bool
	 * @since BS4
	 */
	protected function _get_toolset_expose_custom_fields_in_rest() {
		$value = $this->get_raw_value( Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST );

		return (bool) $value;
	}


	/**
	 * @param bool $value
	 *
	 * @since BS4
	 */
	protected function _set_toolset_expose_custom_fields_in_rest( $value ) {
		$this->settings[ Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST ] = (bool) $value;
	}


	/**
	 * @return string
	 * @since BS4
	 */
	protected function _get_toolset_bootstrap_version() {
		return $this->bootstrap_setting_model->get_current_value();
	}


	protected function _get_bootstrap_version_numeric() {
		return $this->bootstrap_setting_model->get_current_value_numeric();
	}


	/**
	 * Same as the bootstrap_version_numeric property, but mockable.
	 *
	 * @return int
	 */
	public function get_bootstrap_version_numeric() {
		return $this->_get_bootstrap_version_numeric();
	}


	/**
	 * @param string $value
	 *
	 * @since BS4
	 */
	protected function _set_toolset_bootstrap_version( $value ) {
		$this->bootstrap_setting_model->set_value( $value );
	}


	/**
	 * @return \OTGS\Toolset\Common\Settings\BootstrapSetting
	 * @since BS4
	 */
	public function get_bootstrap_setting() {
		return $this->bootstrap_setting_model;
	}

	/**
	 * @return string
	 * @since
	 */
	protected function _get_toolset_font_awesome_versionn() {
		return $this->font_awesome_setting_model->get_current_value();
	}

	/**
	 * @param string $value
	 * @since
	 */
	protected function _set_toolset_font_awesome_version( $value ) {
		$this->font_awesome_setting_model->set_value( $value );
	}
}
