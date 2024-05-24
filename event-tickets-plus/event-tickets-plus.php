<?php
/*
Plugin Name: Satori Custom Plus
Plugin URI:  https://satorilearning.com
Description: Class Registrations Plus lets you sell registrations to classes, collect custom attendee information, and more! Includes advanced options like shared capacity between registrations, registration QR codes, and integrations with your favorite ecommerce provider.
Version: 4.12.1
Author: Modern Tribe, Inc.
Author URI: https://satorilearning.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: satori-custom
Domain Path: /lang/
 */

/*
 Copyright 2010-2012 by Modern Tribe Inc and the contributors

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without seven the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) die( '-1' );

define( 'EVENT_TICKETS_PLUS_DIR', dirname( __FILE__ ) );
define( 'EVENT_TICKETS_PLUS_FILE', __FILE__ );

// Load the required php min version functions
require_once dirname( EVENT_TICKETS_PLUS_FILE ) . '/src/functions/php-min-version.php';

/**
 * Verifies if we need to warn the user about min PHP version and bail to avoid fatals
 */
if ( tribe_is_not_min_php_version() ) {
	tribe_not_php_version_textdomain( 'event-tickets-plus', EVENT_TICKETS_PLUS_FILE );

	/**
	 * Include the plugin name into the correct place
	 *
	 * @since  4.10
	 *
	 * @param  array $names current list of names
	 *
	 * @return array
	 */
	function tribe_tickets_plus_not_php_version_plugin_name( $names ) {
		$names['event-tickets-plus'] = esc_html__( 'Event Tickets Plus', 'event-tickets-plus' );
		return $names;
	}

	add_filter( 'tribe_not_php_version_names', 'tribe_tickets_plus_not_php_version_plugin_name' );
	if ( ! has_filter( 'admin_notices', 'tribe_not_php_version_notice' ) ) {
		add_action( 'admin_notices', 'tribe_not_php_version_notice' );
	}
	return false;
}

/**
 * Attempt to Register Plugin
 *
 * @since 4.10
 */
function tribe_register_event_tickets_plus() {

	// Remove action if we run this hook through common.
	remove_action( 'plugins_loaded', 'tribe_register_event_tickets_plus', 50 );

	if ( ! class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
		// load to display error message
		add_action( 'admin_notices', 'event_tickets_plus_show_fail_message' );
		add_action( 'network_admin_notices', 'event_tickets_plus_show_fail_message' );

		remove_action( 'tribe_common_loaded', 'event_tickets_plus_init', 10 );

		return;
	}

	tribe_init_tickets_plus_autoloading();

	new Tribe__Tickets_Plus__Plugin_Register();

}
add_action( 'tribe_common_loaded', 'tribe_register_event_tickets_plus', 5 );
// add action if Event Tickets or the Events Calendar is not active
add_action( 'plugins_loaded', 'tribe_register_event_tickets_plus', 50 );
// ensure we load the lang files
add_action( 'tribe_load_text_domains', 'event_tickets_plus_setup_textdomain' );

/**
 * Instantiate class and set up WordPress actions.
 *
 * @since 4.10
 */
function event_tickets_plus_init() {

	if ( class_exists( 'Tribe__Main' ) && ! is_admin() && ! class_exists( 'Tribe__Tickets_Plus__PUE__Helper' ) ) {
		tribe_main_pue_helper();
	}

	new Tribe__Tickets_Plus__PUE( __FILE__ );

	$classes_exist  = class_exists( 'Tribe__Tickets__Main' ) && class_exists( 'Tribe__Tickets_Plus__Main' );
	$plugins_check  = tribe_check_plugin( 'Tribe__Tickets_Plus__Main' );
	$plugin_can_run = $classes_exist && $plugins_check;

	/**
	 * Filter whether the plugin can run.
	 *
	 * @since 4.10
	 *
	 * @param boolean $plugin_can_run Whether the plugin can run.
	 */
	$plugin_can_run = apply_filters( 'tribe_event_tickets_plus_can_run', $plugin_can_run );

	if ( $plugin_can_run ) {
		tribe( 'tickets-plus.main' )->instance();

		return;
	}

	// if we have the plugin register the dependency check will handle the messages
	if ( class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
		new Tribe__Tickets_Plus__PUE( __FILE__ );

		return;
	}

	add_action( 'admin_notices', 'event_tickets_plus_show_fail_message' );
	add_action( 'network_admin_notices', 'event_tickets_plus_show_fail_message' );
}
add_action( 'tribe_common_loaded', 'event_tickets_plus_init' );

/**
 * Sets up the textdomain stuff
 */
function event_tickets_plus_setup_textdomain() {
	if ( defined( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED' ) && EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED ) {
		return;
	}

	$mopath = trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/';
	$domain = 'event-tickets-plus';

	// If we don't have Common classes load the old fashioned way
	if ( ! class_exists( 'Tribe__Main' ) ) {
		load_plugin_textdomain( $domain, false, $mopath );
	} else {
		// This will load `wp-content/languages/plugins` files first
		Tribe__Main::instance()->load_text_domain( $domain, $mopath );
	}

	define( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED', true );
}

/**
 * Requires the autoloader class from the main plugin class and sets up autoloading.
 *
 * @since 4.12.1 Added support for namespaced classes to autoload.
 */
function tribe_init_tickets_plus_autoloading() {
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	$autoloader = Tribe__Autoloader::instance();

	// For class names with `__` string separator.
	$autoloader->register_prefix(
		'Tribe__Tickets_Plus__',
		__DIR__ . '/src/Tribe',
		'event-tickets-plus'
	);

	// For namespaced classes.
	$autoloader->register_prefix(
		'\\Tribe\\Tickets\\Plus\\',
		__DIR__ . '/src/Tribe',
		'event-tickets-plus-ns'
	);

	$autoloader->register_autoloader();

	$plugin_path = trailingslashit( EVENT_TICKETS_PLUS_DIR );

	require_once( $plugin_path . 'src/functions/template-tags.php' );

	tribe_singleton( 'tickets-plus.main', 'Tribe__Tickets_Plus__Main' );
}

/**
 * Shows an admin_notices message explaining why it couldn't be activated.
 */
function event_tickets_plus_show_fail_message() {
	event_tickets_plus_setup_textdomain();

	if ( ! current_user_can( 'activate_plugins' ) )
		return;

	$url = add_query_arg( array(
		'tab'       => 'plugin-information',
		'plugin'    => 'event-tickets',
		'TB_iframe' => 'true',
	), admin_url( 'plugin-install.php' ) );

	$title = esc_html__( 'Event Tickets', 'event-tickets-plus' );

	echo '<div class="error"><p>';

	printf(
		esc_html__( 'To begin using Event Tickets Plus, please install and activate the latest version of %1$s%2$s%3$s and ensure its own requirements have been met.', 'event-tickets-plus' ),
		'<a href="' . esc_url( $url ) . '" class="thickbox" title="' . $title . '">',
		$title,
		'</a>'
	);

	echo '</p></div>';
}


/**
 * Whether the current version is incompatible with the installed and active The Events Calendar
 *
 * @deprecated 4.10
 *
 * @return bool
 */
function event_tickets_plus_is_incompatible_tickets_core_installed() {
	_deprecated_function( __FUNCTION__, '4.10', '' );

	if ( ! class_exists( 'Tribe__Tickets__Main' ) ) {
		return true;
	}

	if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
		return true;
	}

	if ( ! version_compare( Tribe__Tickets__Main::VERSION, Tribe__Tickets_Plus__Main::REQUIRED_TICKETS_VERSION, '>=' ) ) {
		return true;
	}

	return false;
}

/**
 * Hooks up the failure message.
 *
 * @deprecated 4.10
 */
function event_tickets_plus_setup_fail_message() {
	_deprecated_function( __FUNCTION__, '4.10', '' );

	add_action( 'admin_notices', 'event_tickets_plus_show_fail_message' );
}

/**
 * Last ditch effort to display an error message in the event that Event Tickets didn't even load
 * far enough to fire tribe_tickets_plugin_loaded or tribe_tickets_plugin_failed_to_load
 *
 * @deprecated 4.10
 */
function event_tickets_plus_check_for_init_failure() {
	_deprecated_function( __FUNCTION__, '4.10', '' );

	if ( defined( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED' ) && EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED ) {
		return;
	}

	event_tickets_plus_setup_fail_message();
}
