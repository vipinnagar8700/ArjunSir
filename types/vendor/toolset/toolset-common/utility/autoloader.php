<?php

/**
 * Toolset autoloader class.
 *
 * Based on classmaps, so each plugin or TCL section that uses this needs to register a classmap
 * either directly or via the toolset_register_classmap action hook.
 *
 * @since m2m
 */
final class Toolset_Common_Autoloader {

	private static $instance;

	public static function get_instance() {
		if( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	private function __construct() { }

	private function __clone() { }


	private static $is_initialized = false;


	/**
	 * This needs to be called before any other autoloader features are used.
	 *
	 * @since m2m
	 */
	public static function initialize() {

		if( self::$is_initialized ) {
			return;
		}

		$instance = self::get_instance();

		/**
		 * Action hook for registering a classmap.
		 *
		 * The one who is adding mappings is responsible for existence of the files.
		 *
		 * @param string[string] $classmap class name => absolute path to a file where this class is defined
		 * @throws InvalidArgumentException
		 * @since m2m
		 */
		add_action( 'toolset_register_classmap', array( $instance, 'register_classmap' ) );

		// Actually register the autoloader.
		//
		// If available (PHP >= 5.3.0), we're setting $prepend = true because this implementation is significantly
		// faster than other (legacy) Toolset autoloaders, especially when they don't find the class we're looking for.
		// This will (statistically) save a lot of execution time.
		if ( PHP_VERSION_ID < 50300 ) {
			spl_autoload_register( array( $instance, 'autoload' ), true );
		} else {
			spl_autoload_register( array( $instance, 'autoload' ), true, true );
		}

		self::$is_initialized = true;
	}


	private $classmap = array();


	/**
	 * Register a classmap.
	 *
	 * Merges given classmap with the existing one.
	 *
	 * The one who is adding mappings is responsible for existence of the files.
	 *
	 * @param string[string] $classmap class name => absolute path to a file where this class is defined
	 * @param null|string $base_path
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function register_classmap( $classmap, $base_path = null ) {

		if( ! is_array( $classmap ) ) {
			throw new InvalidArgumentException( 'The classmap must be an array.' );
		}

		if( is_string( $base_path ) ) {
			foreach( $classmap as $class_name => $relative_path ) {
				$classmap[ $class_name ] = "$base_path/$relative_path";
			}
		}

		$this->classmap = array_merge( $this->classmap, $classmap );

	}


	/**
	 * Try to autoload a class if it's in the classmap.
	 *
	 * @param string $class_name
	 * @return bool True if the file specified by the classmap was loaded, false otherwise.
	 * @since m2m
	 */
	public function autoload( $class_name ) {
		if( ! array_key_exists( $class_name, $this->classmap ) ) {
			return false; // Not our class.
		}

		$file_name = $this->classmap[ $class_name ];

		// Replace require_once by include_once, so that we avoid uncatchable errors, and use @ to suppress warnings.
		/** @noinspection UsingInclusionOnceReturnValueInspection */
		$include_result = @include_once $file_name;

		// Make sure that the file *actually* doesn't exist even if include_once returns a falsy value - it might be
		// a value from the file itself.
		if ( ! $include_result && ! file_exists( $file_name ) ) {
			// The file should have been there but it isn't. Perhaps we're dealing with a case-insensitive file system.
			// If we don't succeed even with lowercase, let the warning manifest - no "@".
			/** @noinspection UsingInclusionOnceReturnValueInspection */
			return include_once strtolower( $file_name );
		}

		return true;
	}

}
