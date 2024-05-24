<?php

class Tribe__Tickets_Plus__APM__Stock_Filter extends Tribe__Tickets_Plus__APM__Abstract_Filter {

	/**
	 * @var string
	 */
	protected $type = 'custom_ticket_stock';

	/**
	 * @var string
	 */
	public static $key = 'tickets_plus_stock_filter_key';

	/**
	 * Sets up the query search options for the filter.
	 */
	protected function set_up_query_search_options() {
		$this->query_search_options = [
			'unlimited' => tribe_tickets_get_readable_amount( -1 ),
			'is'        => __( 'Is', 'event-tickets-plus' ),
			'not'       => __( 'Is Not', 'event-tickets-plus' ),
			'gte'       => __( 'Is at least', 'event-tickets-plus' ),
			'lte'       => __( 'Is at most', 'event-tickets-plus' ),
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
	 * @see \Tribe__Tickets_Plus__APM__Abstract_Filter::filter_posts_results() `-1` is for Unlimited.
	 *
	 * @param int|WP_Post $event
	 * @param bool        $formatted_amount Whether or not to output as "Unlimited" text or comma number.
	 *
	 * @return int|string|WP_Error If not formatted, Unlimited will return `-1`.
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

		$sum = tribe_events_count_available_tickets( $event );

		// Displays "Unlimited" instead of "-1" and adds commas and formatting even if not unlimited.
		if ( $formatted_amount ) {
			// Dash instead of "0" number if event has no tickets.
			if ( empty( $sum ) ) {
				if ( ! tribe_events_has_tickets( $event ) ) {
					$sum = '&mdash;';
				}
			} else {
				// "Unlimited" text or the non-zero numeric amount.
				$sum = tribe_tickets_get_readable_amount( $sum );
			}

			// Format numeric text (not "Unlimited").
			if ( is_numeric( $sum ) ) {
				$sum = number_format_i18n( (float) $sum );
			}
		} else {
			if ( Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK === $sum ) {
				return -1;
			}
		}

		// displays "Unlimited" instead of "-1" and adds commas and formatting even if not unlimited
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
				$sum = number_format_i18n( (float) $sum );
			}
		}

		// return the sum
		return $sum;
	}
}