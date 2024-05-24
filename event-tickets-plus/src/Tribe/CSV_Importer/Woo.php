<?php

class Tribe__Tickets_Plus__CSV_Importer__Woo {
	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Main
	 */
	protected $engine;

	/**
	 * The class singleton constructor.
	 *
	 * @return Tribe__Tickets_Plus__CSV_Importer__Woo
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Tribe__Tickets_Plus__CSV_Importer__Woo constructor.
	 */
	public function __construct() {
		$this->engine = $this->get_commerce_engine_instance();

		add_filter( 'tribe_aggregator_csv_column_mapping', array( $this, 'filter_woo_column_mapping' ) );
		add_filter( "tribe_events_import_{$this->engine->ticket_object}_importer", array( 'Tribe__Tickets_Plus__CSV_Importer__Tickets_Importer', 'woo_instance' ), 10, 2 );
		add_action( 'tribe_aggregator_record_activity_wakeup', array( $this, 'register_woo_activity' ) );
	}

	/**
	 * Returns an instance of the commerce provider.
	 *
	 * This method should be overridden in extending classes.
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Main
	 */
	protected function get_commerce_engine_instance() {
		return Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
	}

	/**
	 * Registers the RSVP post type as a trackable activity
	 *
	 * @param Tribe__Events__Aggregator__Record__Activity $activity
	 */
	public function register_woo_activity( $activity ) {
		$activity->register( $this->engine->ticket_object, array( 'woo', 'woo_tickets' ) );
	}

	/**
	 * Adds Woo column mapping data to the csv_column_mapping array that gets output via JSON
	 *
	 * @param array $mapping Mapping data indexed by CSV import type
	 *
	 * @return array
	 */
	public function filter_woo_column_mapping( $mapping ) {
		$mapping[ $this->engine->ticket_object ] = get_option( 'tribe_events_import_column_mapping_' . $this->engine->ticket_object, array() );
		return $mapping;
	}
}
