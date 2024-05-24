<?php

class Tribe__Tickets_Plus__APM__Sales_Filter extends Tribe__Tickets_Plus__APM__Abstract_Filter {

	/**
	 * @var string
	 */
	protected $type = 'custom_ticket_sales';

	/**
	 * @var string
	 */
	public static $key = 'tickets_plus_sales_filter_key';

	/**
	 * Sets up the `$query_search_options` field.
	 */
	protected function set_up_query_search_options() {
		$this->query_search_options = [
			'is'  => __( 'Are', 'event-tickets-plus' ),
			'not' => __( 'Are Not', 'event-tickets-plus' ),
			'gte' => __( 'Are at least', 'event-tickets-plus' ),
			'lte' => __( 'Are at most', 'event-tickets-plus' ),
		];
	}

	/**
	 * Returns the filter identifying key.
	 *
	 * Workaround for missing late static binding.
	 *
	 * @return mixed
	 */
	protected function key() {
		return self::$key;
	}

	/**
	 * Returns the total numeric value of an event meta.
	 *
	 * E.g. the total tickets sales, stock.
	 *
	 * @since 4.10.10 Added $formatted_amount parameter.
	 *
	 * @see tribe_tickets_get_readable_amount()
	 *
	 * @param int|WP_Post $event
	 * @param bool        $formatted_amount Whether or not to output as comma number.
	 *
	 * @return int|string|WP_Error
	 */
	public function get_total_value( $event, $formatted_amount = false ) {
		$event = get_post( $event );

		$supported_post_types = Tribe__Tickets__Main::instance()->post_types();

		if (
			empty( $event )
			|| ! in_array( $event->post_type, $supported_post_types, true )
		) {
			return new WP_Error( 'not-an-event', sprintf( 'The post with ID "%s" is not an event.', $event->ID ) );
		}

		$sum = 0;

		$all_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID );
		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( $all_tickets as $ticket ) {
			$sum += $ticket->qty_sold();
		}

		if ( $formatted_amount ) {
			// Dash instead of `0` number if event has no tickets
			if ( empty( $sum ) ) {
				if ( ! tribe_events_has_tickets( $event ) ) {
					$sum = '&mdash;';
				}
			} else {
				// `Unlimited` text or the non-zero numeric amount
				$sum = tribe_tickets_get_readable_amount( $sum );
			}

			// not "Unlimited" text
			if ( is_numeric( $sum ) ) {
				$sum = number_format_i18n( $sum );
			}
		}

		// return the sum
		return $sum;
	}
}