<?php
/**
 * EDD cart class
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Commerce__EDD__Cart extends Tribe__Tickets_Plus__Commerce__Abstract_Cart {
	/**
	 * Hook relevant actions and filters
	 *
	 * @since 4.9
	 */
	public function hook() {
		parent::hook();

		add_filter( 'tribe_tickets_attendee_registration_checkout_url', [ $this, 'maybe_filter_attendee_registration_checkout_url' ], 9 );
		add_filter( 'tribe_tickets_tickets_in_cart', [ $this, 'get_tickets_in_cart' ], 10, 2 );
		add_filter( 'tribe_providers_in_cart', [ $this, 'providers_in_cart' ], 11 );
		add_action( 'edd_pre_remove_from_cart', [ $this, 'remove_meta_for_ticket' ] );

		// Commerce hooks.
		add_filter( 'tribe_tickets_commerce_cart_get_cart_url_edd', [ $this, 'get_cart_url' ] );
		add_filter( 'tribe_tickets_commerce_cart_get_checkout_url_edd', [ $this, 'get_checkout_url' ] );
		add_filter( 'tribe_tickets_commerce_cart_get_tickets_edd', [ $this, 'commerce_get_tickets_in_cart' ] );
		add_action( 'tribe_tickets_commerce_cart_update_tickets_edd', [ $this, 'commerce_update_tickets_in_cart' ], 10, 3 );
	}

	/**
	 * Hijack URL if on cart and there
	 * are attendee registration fields that need to be filled out
	 *
	 * @since 4.9
	 *
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public function maybe_filter_attendee_registration_checkout_url( $checkout_url ) {
		global $wp_query;

		if ( empty( $wp_query->query_vars ) || is_admin() || empty( edd_get_cart_contents() ) ) {
			return $checkout_url;
		}

		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $commerce_edd */
		$commerce_edd = tribe( 'tickets-plus.commerce.edd' );

		$provider = strtolower( tribe_get_request_var( 'provider' ) );

		$provider_key             = $commerce_edd->orm_provider;
		$provider_attendee_object = $commerce_edd->attendee_object;

		// Skip provider if it's not this one.
		if (
			null !== $provider
			&& $provider_key !== $provider
			&& $provider_attendee_object !== $provider
		) {
			return $checkout_url;
		}

		$attendee_reg = tribe( 'tickets.attendee_registration' );
		$on_registration_page = $attendee_reg->is_on_page() || $attendee_reg->is_using_shortcode();

		// we only want to override if we are on the cart page or the attendee registration page
		if ( ! $on_registration_page ) {
			return $checkout_url;
		}

		return $this->get_checkout_url();
	}

	/**
	 * Remove meta for ticket when removed from the cart.
	 *
	 * @since 4.11.0
	 *
	 * @param string $cart_item_key The cart item ID.
	 */
	public function remove_meta_for_ticket( $cart_item_key ) {
		/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
		$tickets_meta = tribe( 'tickets-plus.main' )->meta();

		$cart = EDD()->cart;

		if ( empty( $cart->contents[ $cart_item_key ]['id'] ) ) {
			return;
		}

		$product_id      = $cart->contents[ $cart_item_key ]['id'];
		$ticket_has_meta = $tickets_meta->ticket_has_meta( $product_id );

		if ( ! $ticket_has_meta ) {
			return;
		}

		// Go to meta storage and remove any meta for that ticket.
		$storage = new Tribe__Tickets_Plus__Meta__Storage;
		$storage->delete_meta_data_for( $product_id );
	}

	/**
	 * Hooked to tribe_providers_in_cart adds EDD as a provider for checks if there are EDD items in the "cart"
	 *
	 * @since 4.10.2
	 *
	 * @param array $providers
	 * @return array providers, with EDD optionally added
	 */
	public function providers_in_cart( $providers ) {
		if ( empty( edd_get_cart_contents() ) ) {
			return $providers;
		}

		$providers[] = 'edd';

		return $providers;
	}

	/**
	 * Get all tickets currently in the cart.
	 *
	 * @since 4.9
	 *
	 * @param array  $tickets  List of tickets.
	 * @param string $provider Provider of tickets to get (if set).
	 *
	 * @return array List of tickets.
	 */
	public function get_tickets_in_cart( $tickets = [], $provider = null ) {
		$providers = [
			'edd',
			'tribe_eddticket',
			'Tribe__Tickets_Plus__Commerce__EDD__Main',
		];

		// Determine if this provider is being requested or not.
		if ( ! empty( $provider ) && ! in_array( $provider, $providers, true ) ) {
			return $tickets;
		}
		$commerce_tickets = $this->commerce_get_tickets_in_cart( $tickets );

		foreach ( $commerce_tickets as $ticket ) {
			$tickets[ $ticket['ticket_id'] ] = $ticket['quantity'];
		}

		return $tickets;
	}

	/**
	 * Get all tickets currently in the cart for Commerce.
	 *
	 * @since 4.11.0
	 *
	 * @param array $tickets List of tickets.
	 *
	 * @return array List of tickets.
	 */
	public function commerce_get_tickets_in_cart( $tickets = [] ) {
		$contents = edd_get_cart_contents();

		if ( empty( $contents ) ) {
			return $tickets;
		}

		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $commerce_edd */
		$commerce_edd = tribe( 'tickets-plus.commerce.edd' );

		$event_key  = $commerce_edd->event_key;
		$optout_key = $commerce_edd->attendee_optout_key;

		foreach ( $contents as $item ) {
			$ticket_id       = $item['id'];
			$ticket_quantity = $item['quantity'];
			$optout          = false;

			if ( isset( $item['options'][ $optout_key ] ) ) {
				$optout = $item['options'][ $optout_key ];
			}

			$post_id = (int) get_post_meta( $ticket_id, $event_key, true );

			if ( empty( $post_id ) ) {
				continue;
			}

			$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
			$optout = $optout ? 'yes' : 'no';

			$tickets[] = [
				'ticket_id' => $ticket_id,
				'quantity'  => $ticket_quantity,
				'post_id'   => $post_id,
				'optout'    => $optout,
				'provider'  => 'edd',
			];
		}

		/**
		 * Allows for filtering the returned tickets for easier third-party plugin compatibility.
		 *
		 * @since 4.10.8
		 *
		 * @param array $tickets  List of tickets currently in the cart.
		 * @param array $contents The EDD cart contents.
		 */
		return apply_filters( 'tribe_tickets_plus_edd_tickets_in_cart', $tickets, $contents );
	}

	/**
	 * Update tickets in EDD cart for Commerce.
	 *
	 * @since 4.11.0
	 *
	 * @param array   $tickets  List of tickets with their ID and quantity.
	 * @param int     $post_id  Post ID for the cart.
	 * @param boolean $additive Whether to add or replace tickets.
	 *
	 * @throws Tribe__REST__Exceptions__Exception When ticket does not exist or capacity is not enough.
	 */
	public function commerce_update_tickets_in_cart( $tickets, $post_id, $additive ) {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $commerce_edd */
		$commerce_edd = tribe( 'tickets-plus.commerce.edd' );

		$optout_key = $commerce_edd->attendee_optout_key;

		/** @var Tribe__Tickets__REST__V1__Messages $messages */
		$messages = tribe( 'tickets.rest-v1.messages' );

		foreach ( $tickets as $ticket ) {
			// Skip if ticket ID not set.
			if ( empty( $ticket['ticket_id'] ) ) {
				continue;
			}

			$ticket_id       = $ticket['ticket_id'];
			$ticket_quantity = $ticket['quantity'];

			// Get the ticket object.
			$ticket_object = $commerce_edd->get_ticket( 0, $ticket_id );

			// Bail if ticket does not exist.
			if ( ! $ticket_object ) {
				$error_code = 'ticket-does-not-exist';

				throw new Tribe__REST__Exceptions__Exception( sprintf( $messages->get_message( $error_code ), $ticket_id ), $error_code, 500 );
			}

			// Get the number of available tickets.
			$available = $ticket_object->available();

			// Bail if ticket does not have enough available capacity.
			if ( ( -1 !== $available && $available < $ticket_quantity ) || ! $ticket_object->date_in_range() ) {
				$error_code = 'ticket-capacity-not-available';

				throw new Tribe__REST__Exceptions__Exception( sprintf( $messages->get_message( $error_code ), $ticket_object->name ), $error_code, 500 );
			}

			$optout = filter_var( $ticket['optout'], FILTER_VALIDATE_BOOLEAN );
			$optout = $optout ? 'yes' : 'no';

			$extra_data = [
				$optout_key => $optout,
			];

			$this->add_ticket_to_cart( $ticket_id, $ticket_quantity, $extra_data, $additive );
		}
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since 4.11.0
	 *
	 * @param int     $ticket_id  Ticket ID.
	 * @param int     $quantity   Ticket quantity.
	 * @param array   $extra_data Extra data to send to the cart item.
	 * @param boolean $additive   Whether to add or replace tickets.
	 */
	public function add_ticket_to_cart( $ticket_id, $quantity, array $extra_data = [], $additive = true ) {
		$item_position = false;

		if ( edd_item_in_cart( $ticket_id ) ) {
			$item_position = edd_get_item_position_in_cart( $ticket_id );
		}

		if ( ! $additive && false !== $item_position ) {
			// Remove from the cart.
			edd_remove_from_cart( $item_position );
		}

		if ( 0 < $quantity ) {
			// Add item to cart.
			$options = [
				'quantity' => $quantity,
			];

			$options = array_merge( $options, $extra_data );

			if ( $additive && false !== $item_position ) {
				$new_quantity = edd_get_cart_item_quantity( $ticket_id ) + $quantity;

				edd_set_cart_item_quantity( $ticket_id, $new_quantity );
			} else {
				edd_add_to_cart( $ticket_id, $options );

				// If quantities are disabled on a site, we need to manually set the cart item quantity to force it.
				if ( edd_get_cart_item_quantity( $ticket_id ) !== $quantity ) {
					edd_set_cart_item_quantity( $ticket_id, $quantity );
				}
			}
		}
	}

	/**
	 * Get EDD Cart URL.
	 *
	 * @since 4.11.0
	 *
	 * @return string EDD Cart URL.
	 */
	public function get_cart_url() {
		$cart_url = add_query_arg( 'eddtickets_process', 1, $this->get_checkout_url() );

		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $ticket_provider */
		$ticket_provider = tribe( 'tickets-plus.commerce.edd' );
		$cart_url = add_query_arg( 'provider', $ticket_provider::ATTENDEE_OBJECT, $cart_url );

		/**
		 * Allow filtering of the EDD Cart URL.
		 *
		 * @since 4.11.0
		 *
		 * @param string $cart_url EDD Cart URL.
		 */
		return apply_filters( 'tribe_tickets_plus_edd_cart_url', $cart_url );
	}

	/**
	 * Get EDD Checkout URL.
	 *
	 * @since 4.11.0
	 *
	 * @return string EDD Checkout URL.
	 */
	public function get_checkout_url() {
		$checkout_url = edd_get_checkout_uri();

		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $ticket_provider */
		$ticket_provider = tribe( 'tickets-plus.commerce.edd' );
		$checkout_url = add_query_arg( 'provider', $ticket_provider::ATTENDEE_OBJECT, $checkout_url );

		/**
		 * Allow filtering of the EDD Checkout URL.
		 *
		 * @since 4.11.0
		 *
		 * @param string $checkout_url EDD Checkout URL.
		 */
		return apply_filters( 'tribe_tickets_plus_edd_checkout_url', $checkout_url );
	}
}
