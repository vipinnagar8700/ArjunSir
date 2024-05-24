<?php
// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( class_exists( 'Tribe__Tickets_Plus__PUE' ) ) {
	return;
}

/**
 * Registers Event Tickets Plus with the Plugin Update Engine.
 */
class Tribe__Tickets_Plus__PUE {
	/**
	 * This string must match the plugin slug as set in the PUE plugin library.
	 *
	 * @var string
	 */
	private $pue_slug = 'event-tickets-plus';

	/**
	 * @var string
	 */
	private $update_url = 'http://theeventscalendar.com/';

	/**
	 * @var Tribe__PUE__Checker
	 */
	private $pue_instance;

	/**
	 * Setup plugin update checks.
	 */
	public function __construct() {
		$this->load_plugin_update_engine();
		register_activation_hook( EVENT_TICKETS_PLUS_FILE, array( $this, 'register_uninstall_hook' ) );
	}

	/**
	 * If the PUE Checker class exists, go ahead and create a new instance to handle
	 * update checks for this plugin.
	 */
	public function load_plugin_update_engine() {
		/**
		 * Whether PUE checks should run.
		 *
		 * @var bool   $enable_pue
		 * @var string $pue_slug
		 */
		if ( ! class_exists( 'Tribe__PUE__Checker' ) || ! apply_filters( 'tribe_enable_pue', true, $this->pue_slug ) ) {
			return;
		}

		$this->pue_instance = new Tribe__PUE__Checker(
			$this->update_url,
			$this->pue_slug,
			array(),
			plugin_basename( EVENT_TICKETS_PLUS_FILE )
		);
	}

	/**
	 * Register the uninstall hook on activation.
	 */
	public function register_uninstall_hook() {
		register_uninstall_hook( EVENT_TICKETS_PLUS_FILE, array( $this, 'uninstall' ) );
	}

	/**
	 * Plugin has been uninstalled: clean up by purging various options from the database.
	 */
	public function uninstall() {
		$slug = str_replace( '-', '_', $this->pue_slug );
		delete_option( 'pue_install_key_' . $slug );
		delete_option( 'pu_dismissed_upgrade_' . $slug );
	}
}