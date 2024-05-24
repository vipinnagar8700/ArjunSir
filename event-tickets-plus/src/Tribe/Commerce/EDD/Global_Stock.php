<?php
/**
 * Integrates with our EDD stock management facilities to support global stock
 * options.
 *
 * @see Tribe__Tickets_Plus__Commerce__EDD__Stock_Control
 */
class Tribe__Tickets_Plus__Commerce__EDD__Global_Stock {
	/**
	 * Listen for global stock sales so we can adjust stock levels accordingly.
	 */
	public function __construct() {
		add_action( 'event_tickets_edd_tickets_purchased_inventory_recorded', array( $this, 'adjust_stock_levels' ) );
		add_action( 'eddtickets_ticket_deleted', array( $this, 'increase_global_stock_on_delete' ), 10, 3 );
	}

	/**
	 * Container used to store the tickets for various events.
	 *
	 * This allows us to cache the results of the get-tickets-for-an-event queries
	 * which can produce savings where multiple tickets for the same event are
	 * added to the cart.
	 *
	 * The array is structured as follows:
	 *
	 *     [
	 *         event_id =>
	 *         [
	 *             ticket_id => ticket_object,
	 *             ...
	 *         ],
	 *         ...
	 *     ]
	 *
	 * Since the inner arrays are indexed by ticket ID (which always matches
	 * the product ID) looks ups are nice and fast.
	 *
	 * @var array
	 */
	protected $event_tickets = array();

	/**
	 * Looks at the item quantities in the cart and ensures that they are not
	 * "out of bounds" in the case of global stock tickets.
	 *
	 * Amongst other things, this should properly support scenarios where the cart contains:
	 *
	 *     A) Tickets which belong to an event with global stock, but do not themselves
	 *        draw on the global stock
	 *
	 *     B) Multiple tickets utilizing global stock, but belonging to different events
	 *        (ie, some tickets from Event A and some tickets from Event B)
	 *
	 * Expects to be called via Tribe__Tickets_Plus__Commerce__EDD__Main::checkout_errors().
	 */
	public function check_stock() {
		$insufficient_stock = array();

		// Look at the requested totals for each globally stocked event we're interested in and ensure
		// the quantities don't exceed
		foreach ( $this->cart_get_global_stock_quantities() as $event_id => $quantity ) {
			$global_stock = new Tribe__Tickets__Global_Stock( $event_id );

			if ( $quantity > $global_stock->get_stock_level() ) {
				$insufficient_stock[] = get_the_title( $event_id );
			}
		}

		// If we detect out-of-stock scenarios re globally stocked tickets, flag a warning
		if ( ! empty( $insufficient_stock ) ) {
			$this->cart_flag_global_stock_error( $insufficient_stock );
		}
	}

	/**
	 * Gets the total number of tickets requested *per event* (of course, we're only
	 * interested in events that maintain global stock where tickets for those events
	 * that utilize global stock are in the cart).
	 *
	 * @return array
	 */
	protected function cart_get_global_stock_quantities() {
		$edd_tickets = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance();
		$quantities  = array();

		foreach ( (array) edd_get_cart_contents() as $cart_item ) {
			$product = get_post( $cart_item['id'] );
			$event   = $edd_tickets->get_event_for_ticket( $product->ID );

			// Skip on no event
			if ( ! $event instanceof WP_Post ) {
				continue;
			}

			$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );

			// Skip if the ticket does not use global stock
			if ( ! $global_stock->is_enabled() ) {
				continue;
			}

			$tickets = $this->get_event_tickets( $event->ID );

			if ( ! isset( $tickets[ $product->ID ] ) ) {
				continue;
			}

			$ticket_stock_mode = $tickets[ $product->ID ]->global_stock_mode();

			// We only need to accumulate the stock quantities of tickets using *global* stock
			if ( in_array( $ticket_stock_mode, [ Tribe__Tickets__Global_Stock::OWN_STOCK_MODE, '' ], true ) ) {
				continue;
			}

			// This is also a great opportunity to test and see if ticket caps have been exceeded
			if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket_stock_mode ) {

				$ticket_capacity = tribe_tickets_get_capacity( $product->ID ) - $tickets[ $product->ID ]->qty_sold();

				if ( $cart_item['quantity'] > $ticket_capacity ) {

					$this->cart_flag_capped_stock_error( $product->ID );
				}
			}

			if ( ! isset( $quantities[ $event->ID ] ) ) {
				$quantities[ $event->ID ] = 0;
			}

			$quantities[ $event->ID ] += $cart_item['quantity'];
		}

		return $quantities;
	}

	/**
	 * Returns an array of ticket objects for the specified event. The array is
	 * indexed by ticket ID.
	 *
	 * @param int $event_id
	 *
	 * @return array
	 */
	protected function get_event_tickets( $event_id ) {
		if ( ! isset( $this->event_tickets[ $event_id ] ) ) {
			$tickets = array();

			foreach ( Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance()->get_all_event_tickets( $event_id ) as $ticket_object ) {
				/**
				 * @var Tribe__Tickets__Ticket_Object $ticket_object
				 */
				$tickets[ $ticket_object->ID ] = $ticket_object;
			}

			$this->event_tickets[ $event_id ] = $tickets;
		}

		return $this->event_tickets[ $event_id ];
	}

	/**
	 * Trigger an error and add an insufficient stock warning notice in relation to globally
	 * stocked tickets.
	 *
	 * @param array $insufficient_stock_items
	 */
	protected function cart_flag_global_stock_error( array $insufficient_stock_items ) {
		$error = new WP_Error;

		$message = _n(
			'Sorry, there is insufficient stock to fulfill your order with respect to the tickets you selected in relation to this event: %s',
			'Sorry, there is insufficient stock to fulfill your order with respect to the tickets you selected in relation to these events: %s',
			count( $insufficient_stock_items ),
			'event-tickets-plus'
		);

		$ticket_list = '<i>' . join( ', ', $insufficient_stock_items ) . '</i>';

		/**
		 * Error message generated when an insufficiency of global stock is discovered during
		 * validation of cart item quantities.
		 *
		 * @param string $message
		 * @param array  $insufficient_stock_items
		 */
		$message = apply_filters( 'tribe_tickets_plus_edd_global_stock_cart_error', sprintf( $message, $ticket_list ), $insufficient_stock_items );

		$error->add( 'out-of-global-stock', $message );
		edd_set_error( 'insufficient_stock_global', $error->get_error_message() );
	}

	/**
	 * Trigger an error if the quantity for a capped ticket is exceeded.
	 *
	 * @param int $product_id
	 */
	protected function cart_flag_capped_stock_error( $product_id ) {
		$error = new WP_Error;
		$ticket_name = get_the_title( $product_id );

		$message = sprintf(
			__( 'Sorry, there is insufficient stock to fulfill your order with respect to %s', 'event-tickets-plus' ),
			'<i>' . $ticket_name . '</i>'
		);

		/**
		 * Error message generated when an insufficiency of stock for a capped-sales ticket
		 * is discovered during validation of cart item quantities.
		 *
		 * @param string $message
		 * @param int    $product_ud
		 */
		$message = apply_filters( 'tribe_tickets_plus_edd_global_stock_cart_error', $message, $product_id );

		$error->add( 'out-of-capped-stock-' . $product_id, $message );
		edd_set_error( 'insufficient_stock_capped_' . $product_id, $error->get_error_message() );
	}

	/**
	 * Adjusts global stock levels for any of the products that were just purchased.
	 *
	 * Expects to fire during the 'event_tickets_edd_tickets_purchased_inventory_recorded' action.
	 *
	 * @param array $quantities
	 */
	public function adjust_stock_levels( array $quantities ) {
		foreach ( $quantities as $ticket_id => $amount_purchased ) {
			$ticket_id    = absint( $ticket_id );
			$event        = tribe_events_get_ticket_event( $ticket_id );

			if ( ! $event ) {
				continue;
			}

			$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );

			// We're only interested if the ticket utilizes global stock
			if ( ! $global_stock->is_enabled() ) {
				continue;
			}

			// Try to load the actual ticket object
			$tickets = $this->get_event_tickets( $event->ID );

			// Move on if we couldn't obtain the ticket object
			if ( empty( $tickets[ $ticket_id ] ) ) {
				continue;
			}

			switch ( $tickets[ $ticket_id ]->global_stock_mode() ) {
				// Reduce the cap in line with the number of capped tickets that were purchased, if any
				case Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE:
					$original_level = $tickets[ $ticket_id ]->global_stock_cap();
					update_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, $original_level - $amount_purchased );
				// Fall-through is deliberate - capped sales still draw from the global inventory pool
				case Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE:
					$original_level = $global_stock->get_stock_level();
					$global_stock->set_stock_level( $original_level - $amount_purchased );
					break;
			}
		}
	}

	/**
	 * Increase Global Stock on Delete of EDD Attendee
	 *
	 * @since 4.10.4
	 *
	 * @param int $ticket_id the attendee id being deleted
	 * @param int $post_id the post or event id for the attendee
	 * @param int $product_id the ticket-product id in EDD
	 */
	public function increase_global_stock_on_delete( $ticket_id, $post_id, $product_id ) {

		$ticket  = tribe( 'tickets-plus.commerce.edd' )->get_ticket( $post_id, $product_id );

		if ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE !== $ticket->global_stock_mode() ) {
			$global_stock_obj = new Tribe__Tickets__Global_Stock( $post_id );

			$old_stock = $global_stock_obj->get_stock_level();
			$new_stock = $global_stock_obj->get_stock_level() + 1;
			$global_stock_obj->set_stock_level( $new_stock );

			if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket->global_stock_mode() ) {
				$capped_stock     = get_post_meta( $product_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );
				$new_capped_stock = $capped_stock + ( $new_stock - $old_stock );

				update_post_meta( $product_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, $new_capped_stock, $capped_stock );
			}
		}
	}
}
