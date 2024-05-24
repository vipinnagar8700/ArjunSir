<?php
/**
 * Facilitates deeper integration of Event Tickets global stock capabilities and
 * the WooCommerce implementation provided by Event Tickets Plus.
 *
 * @internal
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Global_Stock {
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


	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'cart_check_stock' ) );
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'stock_equalize' ) );

		/*
		 * This currently causes a fatal error because it needs a ticket object but the ticket has already been deleted.
		 *
		 * See [GTRIA-77] for more details. This ticket was created to follow up on the temporary workaround.
		 */
		//add_action( 'wootickets_ticket_deleted', array( $this, 'increase_global_stock_on_delete' ), 10, 3 );
	}

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
	 * Expects to be called when the woocommerce_check_cart_items action fires, which
	 * typically occurs both when the cart is updated and when the cart is submitted via
	 * the checkout page.
	 */
	public function cart_check_stock() {
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
		$cart        = WC()->cart;
		$current     = $cart->get_cart_item_quantities();
		$quantities  = [];

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! $cart_item['data'] instanceof WC_Product_Simple ) {
				continue;
			}

			$product = $cart_item['data'];

			$product_id = tribe( 'tickets-plus.commerce.woo' )->get_product_id( $product );

			$event = tribe( 'tickets-plus.commerce.woo' )->get_event_for_ticket( $product_id );

			// Skip on no event
			if ( ! $event instanceof WP_Post ) {
				continue;
			}

			// Skip if it doesn't use Global Stock
			if ( ! tribe( 'tickets-plus.commerce.woo' )->uses_global_stock( $event->ID ) ) {
				continue;
			}

			// Skip if we are not managing stock
			if ( ! $product->managing_stock() ) {
				continue;
			}

			$tickets = $this->get_event_tickets( $event->ID );

			// If the Ticket doesn't exist also Skip
			if ( ! isset( $tickets[ $product_id ] ) ) {
				continue;
			}

			/** @var Tribe__Tickets__Ticket_Object $ticket */
			$ticket = $tickets[ $product_id ];

			// Skip on Unlimited Capacity
			if ( -1 === $ticket->capacity() ) {
				continue;
			}

			$ticket_stock_mode = $ticket->global_stock_mode();

			// We only need to accumulate the stock quantities of tickets using *global* stock
			if ( in_array( $ticket_stock_mode, [ Tribe__Tickets__Global_Stock::OWN_STOCK_MODE, '' ], true ) ) {
				continue;
			}

			// Make sure ticket caps haven't been exceeded
			if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket_stock_mode ) {
				if ( $current[ $product_id ] > $ticket->capacity() ) {
					$this->cart_flag_capped_stock_error( $product_id );
				}
			}

			if ( ! isset( $quantities[ $event->ID ] ) ) {
				$quantities[ $event->ID ] = 0;
			}

			$quantities[ $event->ID ] += $current[ $product_id ];
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

			foreach ( Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance()->get_all_event_tickets( $event_id ) as $ticket_object ) {
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
		$message = apply_filters( 'tribe_tickets_plus_woo_global_stock_cart_error', sprintf( $message, $ticket_list ), $insufficient_stock_items );

		$error->add( 'out-of-global-stock', $message );

		wc_add_notice( $error->get_error_message(), 'error' );
	}

	/**
	 * Trigger an error if the quantity for a capped ticket is exceeded.
	 *
	 * @param int $product_id
	 */
	protected function cart_flag_capped_stock_error( $product_id ) {
		$error       = new WP_Error;
		$ticket_name = wc_get_product( $product_id )->get_title();

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
		$message = apply_filters( 'tribe_tickets_plus_woo_global_stock_cart_error', $message, $product_id );

		$error->add( 'out-of-capped-stock-' . $product_id, $message );

		wc_add_notice( $error->get_error_message(), 'error' );
	}

	/**
	 * When WooCommerce reduces stock levels during order processing we need to look
	 * for global stock tickets and ensure we "equalize" the total stock levels/sales
	 * caps as appropriate.
	 *
	 * @param WC_Order $order
	 */
	public function stock_equalize( WC_Order $order ) {
		$woo_tickets = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		$ordered     = array();

		// Get the total quantity of global stock ordered per event
		foreach ( $order->get_items() as $item ) {
			$product       = $woo_tickets->get_product_from_item( $order, $item );
			$product_id    = $woo_tickets->get_product_id( $product );
			$event         = $woo_tickets->get_event_for_ticket( $product_id );

			// Skip non-tickets
			if ( ! $event || ! isset( $event->ID ) ) {
				continue;
			}

			$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );

			// Skip tickets that do not utilize global stock
			if ( ! $global_stock->is_enabled() || ! $product->managing_stock() ) {
				continue;
			}

			$ticket = $woo_tickets->get_ticket( $event->ID, $product_id );

			$modes = array(
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
			);

			if ( in_array( $ticket->global_stock_mode(), $modes ) ) {
				// If not defined setup this event
				if ( ! isset( $ordered[ $event->ID ] ) ) {
					$ordered[ $event->ID ] = 0;
				}

				// Add each item qty
				$ordered[ $event->ID ] += (int) $item->get_quantity();
			}
		}

		// For each ticket product that utilizes global stock, adjust the product inventory
		foreach ( $ordered as $event_id => $quantity ) {
			$global_stock = new Tribe__Tickets__Global_Stock( $event_id );
			$level        = $global_stock->get_stock_level();
			$new_level    = (int) $level - (int) $quantity;

			$global_stock->set_stock_level( $new_level );
		}
	}

	/**
	 * Updates the global stock level and individual product inventories for any ticket products
	 * that utilize global stock.
	 *
	 * @param int $event_id
	 * @param int $stock_level
	 */
	public function stock_update_global_tickets( $event_id, $stock_level ) {
		$woo_tickets = tribe( 'tickets-plus.commerce.woo' );

		foreach ( $woo_tickets->get_tickets( $event_id ) as $ticket ) {
			/**
			 * @var Tribe__Tickets__Ticket_Object $ticket
			 */
			if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE !== $ticket->global_stock_mode() ) {
				continue;
			}

			wc_update_product_stock( $ticket->ID, (int) $stock_level );
		}
	}

	/**
	 * Increase Global Stock on Delete of WooCommerce Attendee
	 *
	 * @since 4.10.4
	 *
	 * @param int $ticket_id the attendee id being deleted
	 * @param int $post_id the post or event id for the attendee
	 * @param int $product_id the ticket-product id in WooCommerce
	 */
	public function increase_global_stock_on_delete( $ticket_id, $post_id, $product_id ) {
		$ticket  = tribe( 'tickets-plus.commerce.woo' )->get_ticket( $post_id, $product_id );

		$this->maybe_increase_global_stock( $post_id, $product_id, $ticket );
	}

	/**
	 * Maybe Increase the Global Stock
	 *
	 * @see \Tribe__Tickets__Ticket_Object::global_stock_mode() Definitions for the various ways to account for stock.
	 *
	 * @since 4.10.4
	 *
	 * @param int                           $post_id        The post or event id for the attendee.
	 * @param int                           $product_id     The ticket-product id in WooCommerce.
	 * @param Tribe__Tickets__Ticket_Object $ticket         A ticket object.
	 * @param int                           $stock_increase An integer to increase stock by.
	 */
	public function maybe_increase_global_stock( $post_id, $product_id, Tribe__Tickets__Ticket_Object $ticket, $stock_increase = 1) {

		if ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE !== $ticket->global_stock_mode() ) {
			$global_stock_obj = new Tribe__Tickets__Global_Stock( $post_id );
			$new_stock = $global_stock_obj->get_stock_level() + $stock_increase;

			$global_stock_obj->set_stock_level( $new_stock );

			if ( Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket->global_stock_mode() ) {
				$capped_stock     = get_post_meta( $product_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );
				$new_capped_stock = $capped_stock + $stock_increase;

				update_post_meta( $product_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, $new_capped_stock, $capped_stock );
			}
		}
	}

}
