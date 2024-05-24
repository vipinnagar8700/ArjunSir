<?php
_deprecated_file( __FILE__, '4.10', 'Tribe__Tickets_Plus__Commerce__EDD__Orders__Report' );

/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Orders_Report
 *
 * Handles the Orders report for EDD tickets.
 *
 * @deprecated 4.10
 */
class Tribe__Tickets_Plus__Commerce__EDD__Orders_Report {

	/**
	 * Filters the Orders link to return the correct one.
	 *
	 * Currently `false` as Orders report for Easy Digital Downloads is not supported.
	 *
	 * @deprecated 4.10
	 *
	 * @param string $url     The Orders link URL
	 * @param int    $post_id The current post ID
	 *
	 * @return bool
	 */
	public function filter_attendee_order_link( $url, $post_id ) {
		$default_provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' !== $default_provider ) {
			return $url;
		}

		// currently not supported
		return false;
	}
}
