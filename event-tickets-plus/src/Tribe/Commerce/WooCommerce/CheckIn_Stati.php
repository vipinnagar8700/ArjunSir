<?php


class Tribe__Tickets_Plus__Commerce__WooCommerce__CheckIn_Stati {

	/**
	 * Filters the checkin stati for a WooCommerce ticket order.
	 *
	 * @param array $checkin_stati
	 */
	public function filter_attendee_ticket_checkin_stati( array $checkin_stati ) {
		$checkin_stati = array( 'completed' );

		return $checkin_stati;
	}
}