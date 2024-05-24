<?php
/*
Plugin Name: Toolset Types
Plugin URI: https://toolset.com/?utm_source=typesplugin&utm_campaign=types&utm_medium=plugins-list-full-version&utm_term=Visit plugin site
Description: Toolset Types defines custom content in WordPress. Easily create custom post types, fields and taxonomy and connect everything together.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 3.5.2
License: GPLv2 or later

Types is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Types is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Types. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/



// abort if called directly
if ( ! function_exists( 'add_action' ) ) {
	die( 'Types is a WordPress plugin and can not be called directly.' );
}

// version
if( ! defined( 'TYPES_VERSION' ) ) {
	define( 'TYPES_VERSION', '3.5.2' );
}

// backward compatibility
if ( ! defined( 'WPCF_VERSION' ) ) {
	define( 'WPCF_VERSION', TYPES_VERSION );
}

// release notes
if ( ! defined( 'TYPES_RELEASE_NOTES' ) ) {
	define(
		'TYPES_RELEASE_NOTES',
		'https://toolset.com/version/types-' . str_replace( '.', '-', TYPES_VERSION )
		. '/?utm_source=typesplugin&utm_campaign=types&utm_medium=release-notes-admin-notice&utm_term=Types%20' . TYPES_VERSION . '%20release%20notes'
	);
}


/*
 * Path Constants
 */
if ( ! defined( 'TYPES_ABSPATH' ) ) {
	define( 'TYPES_ABSPATH', dirname( __FILE__ ) );
}


if ( ! defined( 'TYPES_RELPATH' ) ) {
	define( 'TYPES_RELPATH', plugins_url() . '/' . basename( TYPES_ABSPATH ) );
}

if ( ! defined( 'TYPES_DATA' ) ) {
	define( 'TYPES_DATA', dirname( __FILE__ ) . '/application/data' );
}

if ( ! defined( 'TYPES_TEMPLATES' ) ) {
	define( 'TYPES_TEMPLATES', dirname( __FILE__ ) . '/application/views' );
}

/*
 * Bootstrap Types
 */
require_once( dirname( __FILE__ ) . '/application/bootstrap.php' );

/*
 * Compatibilities Switches
 */
add_action( 'plugins_loaded', function() {
	if( ! defined( 'TOOLSET_TYPES_YOAST' ) ) {
		// Yoast Compatibility
		define( 'TOOLSET_TYPES_YOAST', defined( 'WPSEO_VERSION' ) );
	}
} );

//
// Activation and deactivation hooks must be defined in the main file.
//
register_deactivation_hook( __FILE__, 'wpcf_deactivation_hook' );
register_activation_hook( __FILE__, 'wpcf_activation_hook' );

