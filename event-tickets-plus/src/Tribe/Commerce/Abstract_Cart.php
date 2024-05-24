<?php

abstract class Tribe__Tickets_Plus__Commerce__Abstract_Cart {
	/**
	 * Get all tickets currently in the cart.
	 *
	 * @since 4.9
	 *
	 * @param array $tickets Array indexed by ticket id with quantity as the value
	 *
	 * @return array
	 */
	abstract public function get_tickets_in_cart( $tickets = array() );

	/**
	 * Hook relevant actions and filters
	 *
	 * @since 4.9
	 */
	public function hook() {
		add_filter( 'tribe_tickets_attendee_registration_is_meta_up_to_date', array( $this, 'filter_is_meta_up_to_date' ) );
	}

	/**
	 * Filters whether or not tickets in the cart have up-to-date meta data
	 *
	 * @since 4.9
	 *
	 * @param bool $is_up_to_date
	 *
	 * @return bool
	 */
	public function filter_is_meta_up_to_date( $is_up_to_date ) {
		$tickets = $this->get_tickets_in_cart();

		if ( empty( $tickets ) ) {
			return $is_up_to_date;
		}

		/** @var Tribe__Tickets_Plus__Meta__Contents $contents */
		$contents = tribe( 'tickets-plus.meta.contents' );

		return $contents->is_stored_meta_up_to_date( $tickets );
	}
}