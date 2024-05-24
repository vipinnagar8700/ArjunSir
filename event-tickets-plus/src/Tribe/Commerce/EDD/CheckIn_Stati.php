<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati
 *
 * @since 4.6.2
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati {

	/**
	 * Filters the checkin stati for a EDD ticket order.
	 *
	 * @since 4.6.2
	 *
	 * @param array $checkin_stati
	 */
	public function filter_attendee_ticket_checkin_stati( array $checkin_stati ) {
		$checkin_stati = array( 'Complete' );

		return $checkin_stati;
	}
}