<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autoloads Twig classes.
 *
 * This is a modified version of Twig_Autoloader that survives without producing a fatal error even if someone else
 * includes Twig_Autoloader recklessly, without checking if !class_exists(). When the register() method is being
 * called, it checks all registered autoloaders. If the native Twig_Autoloader is already there, this class resigns
 * and doesn't complete it's own registration.
 *
 * This will, however, work only if it happens late enough. In Toolset we assume that it is ok to do this during 'init'.
 * The one known issue is with older WPML versions that register Twig right when the plugin is loaded.
 *
 * Note: Twig_Autoloader is marked as deprecated, however we can't easily use the proposed composer autoloader, since
 * that breaks the PHP 5.2 compatibility.
 *
 * The original author of this class:
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @since 2.2
 * @since 2.5.6 Autoloader bails out when it's possible to load the Twig_Environment class.
 */
class Toolset_Twig_Autoloader {


	/** @var string[]|null Autoloader classmap (once loaded). */
	private static $classmap;


	/**
	 * Registers Types_Twig_Autoloader as an SPL autoloader if Twig_Autoloader isn't already registered.
	 *
	 * @param bool $prepend Whether to prepend the autoloader or not.
	 *
	 * @throws Exception Coming from spl_autoload_register().
	 */
	public static function register( $prepend = false ) {
		if ( PHP_VERSION_ID < 50300 ) {
			spl_autoload_register( array( __CLASS__, 'autoload' ) );
		} else {
			spl_autoload_register( array( __CLASS__, 'autoload' ), true, $prepend );
		}

		self::$classmap = include __DIR__ . '/twig_autoload_classmap.php';
	}


	/**
	 * Handles autoloading of classes.
	 *
	 * @param string $class_name A class name.
	 *
	 * @return bool|mixed
	 */
	public static function autoload( $class_name ) {
		if ( ! array_key_exists( $class_name, self::$classmap ) ) {
			return false; // Not our class.
		}

		$file_name = self::$classmap[ $class_name ];

		// Replace require_once by include_once, so that we avoid uncatchable errors, and use @ to suppress warnings.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! @include_once $file_name ) {
			// The file should have been there but it isn't. Perhaps we're dealing with a case-insensitive file system.
			// If we don't succeed even with lowercase, let the warning manifest - no "@".
			return include_once strtolower( $file_name );
		}

		return true;
	}
}
