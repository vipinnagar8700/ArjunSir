<?php


/**
 * Provides functionality shared by all Event Tickets Plus ticketing providers.
 */
abstract class Tribe__Tickets_Plus__Tickets extends Tribe__Tickets__Tickets {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Processes the front-end tickets form data to handle requests common to all type of tickets.
	 *
	 * Children classes should call this method when overriding.
	 */
	public function process_front_end_tickets_form() {
		$meta_store = new Tribe__Tickets_Plus__Meta__Storage();
		$meta_store->maybe_set_attendee_meta_cookie();
	}

	/**
	 * Returns the amount of global stock set for the event.
	 *
	 * A positive value does not necessarily mean global stock is currently in effect;
	 * always combine a call to this method with a call to $this->uses_global_stock()!
	 *
	 * @since 4.6
	 *
	 * @param int $post_id
	 * @return int
	 */
	protected function global_stock_level( $post_id ) {
		// In some cases (version mismatch with Event Tickets) the Global Stock class may not be available
		if ( ! class_exists( 'Tribe__Tickets__Global_Stock' ) ) {
			return 0;
		}

		$global_stock = new Tribe__Tickets__Global_Stock( $post_id );

		return $global_stock->get_stock_level();
	}

	/**
	 * Hooks into tribe_tickets_ajax_refresh_tables
	 * to add the capacity table and total capacity line to the refreshed tables.
	 *
	 * @deprecated 4.6.2
	 * @since      4.6
	 *
	 * @param array $return  Data to be returned to ajax function.
	 * @param int   $post_id The post id of the event/post the ticket is attached to.
	 *
	 * @return array Data to return to ajax function.
	 */
	public function refresh_tables( $return, $post_id ) {
		_deprecated_function( __METHOD__, '4.6.2', '' );

		// Add the capacity table to the return
		$return['capacity_table'] = tribe( 'tickets.admin.views' )->template( 'editor/capacity-table', null, false );
		$return['total_capacity'] = tribe( 'tickets-plus.admin.views' )->template( 'editor/total-capacity', null, false );

		return $return;
	}
}
