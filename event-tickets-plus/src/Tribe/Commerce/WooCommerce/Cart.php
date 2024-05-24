<?php

/**
 * WooCommerce cart class
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Cart extends Tribe__Tickets_Plus__Commerce__Abstract_Cart {
	/**
	 * Hook relevant actions and filters
	 *
	 * @since 4.9
	 */
	public function hook() {
		parent::hook();

		add_filter( 'tribe_tickets_attendee_registration_checkout_url', [ $this, 'maybe_filter_attendee_registration_checkout_url' ], 8 );
		add_action( 'woocommerce_after_cart_item_quantity_update', [ $this, 'detect_cart_quantity_change' ], 10, 4 );
		add_action( 'woocommerce_remove_cart_item', [ $this, 'remove_meta_for_ticket' ], 10, 2 );
		add_filter( 'woocommerce_get_checkout_url', [ $this, 'maybe_filter_checkout_url_to_attendee_registration' ], 50 );
		add_filter( 'tribe_tickets_tickets_in_cart', [ $this, 'get_tickets_in_cart' ], 10, 2 );
		add_filter( 'tribe_providers_in_cart', [ $this, 'providers_in_cart' ], 15 );

		// Commerce hooks.
		add_filter( 'tribe_tickets_commerce_cart_get_cart_url_woo', [ $this, 'get_cart_url' ] );
		add_filter( 'tribe_tickets_commerce_cart_get_checkout_url_woo', [ $this, 'get_checkout_url' ] );
		add_filter( 'tribe_tickets_commerce_cart_get_tickets_woo', [ $this, 'commerce_get_tickets_in_cart' ] );
		add_action( 'tribe_tickets_commerce_cart_update_tickets_woo', [ $this, 'commerce_update_tickets_in_cart' ], 10, 3 );
	}

	/**
	 * Hooked to the tribe_tickets_attendee_registration_checkout_url filter to hijack URL if on cart and there
	 * are attendee registration fields that need to be filled out.
	 *
	 * @since 4.9
	 *
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public function maybe_filter_attendee_registration_checkout_url( $checkout_url ) {
		return $this->maybe_filter_checkout_url_to_attendee_registration( $checkout_url );
	}

	/**
	 * Detect if the cart Quantity change includes a product with AR Fields.
	 *
	 * @since 4.11.0
	 *
	 * @param string $cart_item_key The cart item ID.
	 * @param int    $quantity      The item quantity.
	 * @param int    $old_quantity  The original item quantity.
	 * @param WC_Cart $cart         The WooCommerce cart class.
	 */
	public function detect_cart_quantity_change( $cart_item_key, $quantity, $old_quantity, $cart ) {
		/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
		$tickets_meta    = tribe( 'tickets-plus.main' )->meta();
		$product_id      = $cart->cart_contents[ $cart_item_key ]['product_id'];
		$ticket_has_meta = $tickets_meta->ticket_has_meta( $product_id );

		if ( ! $ticket_has_meta ) {
			return;
		}

		// set session that a ticket with AR fields has a quantity change
		WC()->session->set( 'tribe_ar_ticket_updated', true );
	}

	/**
	 * Remove meta for ticket when removed from the cart.
	 *
	 * @since 4.11.0
	 *
	 * @param string $cart_item_key The cart item ID.
	 * @param WC_Cart $cart         The WooCommerce cart class.
	 */
	public function remove_meta_for_ticket( $cart_item_key, $cart ) {
		/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
		$tickets_meta    = tribe( 'tickets-plus.main' )->meta();
		$product_id      = $cart->cart_contents[ $cart_item_key ]['product_id'];
		$ticket_has_meta = $tickets_meta->ticket_has_meta( $product_id );

		if ( ! $ticket_has_meta ) {
			return;
		}

		// Go to meta storage and remove any meta for that ticket it.
		$storage = new Tribe__Tickets_Plus__Meta__Storage;
		$storage->delete_meta_data_for( $product_id );
	}

	/**
	 * Maybe set the empty checkout URL to our known checkout URL.
	 *
	 * @since 4.11.0
	 * @since 4.11.2 Do not add 'provider' to checkout URL if no tickets in the Cart.
	 * @since 4.12.0    Stop adding the 'provider' parameter to the checkout URL, was causing conflicts with payment gateways.
	 *
	 * @param string $checkout_url Checkout URL.
	 *
	 * @return string Checkout URL.
	 */
	public function maybe_set_empty_checkout_url( $checkout_url ) {
		if ( empty( $checkout_url ) ) {
			remove_filter( 'woocommerce_get_checkout_url', [ $this, 'maybe_filter_checkout_url_to_attendee_registration' ], 50 );

			$checkout_url = $this->get_checkout_url();

			add_filter( 'woocommerce_get_checkout_url', [ $this, 'maybe_filter_checkout_url_to_attendee_registration' ], 50 );
		}

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $woo */
		$woo = tribe( 'tickets-plus.commerce.woo' );

		return $checkout_url;
	}

	/**
	 * Set WooCommerce cart/checkout URL for Attendee Registration Checkout and elsewhere and add 'provider' query arg.
	 *
	 * @since 4.9
	 *
	 * @see   \wc_get_checkout_url()
	 * @see   \Tribe__Tickets__Attendee_Registration__Main::get_checkout_url()
	 *
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public function maybe_filter_checkout_url_to_attendee_registration( $checkout_url ) {
		// If in the admin area, do not filter.
		if ( is_admin() ) {
			return $this->maybe_set_empty_checkout_url( $checkout_url );
		}

		/** @var \Tribe__Tickets__Attendee_Registration__Main $attendee_reg */
		$attendee_reg = tribe( 'tickets.attendee_registration' );

		global $wp_query;

		// If on the AR page, do not filter.
		if (
			empty( $wp_query->query_vars )
			|| $attendee_reg->is_on_page()
			|| $attendee_reg->is_cart_rest()
			|| $attendee_reg->is_using_shortcode()
		) {
			return $this->maybe_set_empty_checkout_url( $checkout_url );
		}

		$ticket_updated = false;
		$wc_session     = WC()->session;

		if ( $wc_session ) {
			$ticket_updated = filter_var( $wc_session->get( 'tribe_ar_ticket_updated' ), FILTER_VALIDATE_BOOLEAN );
		}

		if ( ! $ticket_updated ) {
			$cart_tickets = $this->get_tickets_in_cart();

			/** @var \Tribe__Tickets_Plus__Meta__Contents $meta_contents */
			$meta_contents   = tribe( 'tickets-plus.meta.contents' );
			$meta_up_to_date = $meta_contents->is_stored_meta_up_to_date( $cart_tickets );

			// If meta is up to date, do not filter.
			if ( $meta_up_to_date ) {
				return $this->maybe_set_empty_checkout_url( $checkout_url );
			}

			/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
			$tickets_meta  = tribe( 'tickets-plus.main' )->meta();
			$cart_has_meta = $tickets_meta->cart_has_meta( $cart_tickets );

			// If cart has no meta, do not filter.
			if ( ! $cart_has_meta ) {
				return $this->maybe_set_empty_checkout_url( $checkout_url );
			}
		}

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $woo */
		$woo = tribe( 'tickets-plus.commerce.woo' );

		$ticket_provider  = $woo->attendee_object;
		$attendee_reg_url = $attendee_reg->get_url();
		$attendee_reg_url = add_query_arg( 'provider', $ticket_provider, $attendee_reg_url );

		return $attendee_reg_url;
	}

	/**
	 * Adds a 'provider' query argument set to the ticket type to the passed URL (e.g. cart or checkout), if a ticket
	 * with Attendee Information is in the cart, to assist with keeping tickets from different providers separate.
	 *
	 * @since 4.10.4
	 *
	 * @see   \Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_cart_url()
	 *
	 * @param string $url Cart or Checkout URL.
	 *
	 * @return string The URL after potentially being modified.
	 */
	public function add_provider_to_cart_url( $url = '' ) {
		if ( empty( $url ) ) {
			return $url;
		}

		$cart_tickets = $this->get_tickets_in_cart();

		/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
		$tickets_meta  = tribe( 'tickets-plus.main' )->meta();

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $woo */
		$woo = tribe( 'tickets-plus.commerce.woo' );

		$url = add_query_arg( 'provider', $woo->attendee_object, $url );

		return $url;
	}

	/**
	 * Identify WooCommerce as a provider for checks if there are WooCommerce tickets in the cart.
	 *
	 * @since 4.10.2
	 *
	 * @see   \Tribe__Tickets__Attendee_Registration__Main::providers_in_cart()
	 * @see   \Tribe__Tickets__Attendee_Registration__View::display_attendee_registration_page()
	 *
	 * @param array $providers
	 *
	 * @return array List of providers, with others optionally added.
	 */
	public function providers_in_cart( $providers ) {
		if ( empty( $this->get_tickets_in_cart() ) ) {
			return $providers;
		}

		$providers[] = 'woo';

		return $providers;
	}

	/**
	 * Filter the tickets in the Cart to have the WooCommerce class as a provider so Attendee Registration works.
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
			'woo',
			'tribe_wooticket',
			'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
		];

		// Determine if this provider is being requested or not.
		if ( ! empty( $provider ) && ! in_array( $provider, $providers, true ) ) {
			return $tickets;
		}

		$rest_tickets = $this->commerce_get_tickets_in_cart( $tickets );

		foreach ( $rest_tickets as $ticket ) {
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
		try {
			$wc = WC();

			// If the cart is null, we need to bail to prevent any "Call to a member function on null" errors
			if ( is_null( $wc->cart ) ) {
				wc_load_cart();

				// API requests need extra things available for the cart.
				if ( $wc->is_rest_api_request() ) {
					$wc->frontend_includes();

					$cart_session = new WC_Cart_Session( $wc->cart );
					$cart_session->maybe_set_cart_cookies();
				}
			}

			$contents = $wc->cart->get_cart_contents();
		} catch ( Exception $exception ) {
			return $tickets;
		}

		if ( empty( $contents ) ) {
			return $tickets;
		}

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $commerce_woo */
		$commerce_woo = tribe( 'tickets-plus.commerce.woo' );

		$event_key  = $commerce_woo->event_key;
		$optout_key = $commerce_woo->attendee_optout_key;

		foreach ( $contents as $item ) {
			$ticket_id       = $item['product_id'];
			$ticket_quantity = $item['quantity'];
			$optout          = false;

			if ( isset( $item[ $optout_key ] ) ) {
				$optout = $item[ $optout_key ];
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
				'provider'  => 'woo',
			];
		}

		/**
		 * Allows for filtering the returned tickets for easier third-party plugin compatibility.
		 *
		 * @since 4.10.8
		 *
		 * @param array $tickets  List of tickets currently in the cart.
		 * @param array $contents The WooCommerce cart contents.
		 */
		return apply_filters( 'tribe_tickets_plus_woocommerce_tickets_in_cart', $tickets, $contents );
	}

	/**
	 * Update tickets in WooCommerce cart for Commerce.
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
		$wc = WC();

		// Include files and setup session/cart.
		$wc->frontend_includes();
		$wc->initialize_session();
		$wc->initialize_cart();

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $commerce_woo */
		$commerce_woo = tribe( 'tickets-plus.commerce.woo' );

		$optout_key = $commerce_woo->attendee_optout_key;

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
			$ticket_object = $commerce_woo->get_ticket( 0, $ticket_id );

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
		/** @var WooCommerce $woocommerce */
		global $woocommerce;

		if ( 0 < $quantity ) {
			/**
			 * Allow hooking into WooCommerce Add to Cart validation.
			 *
			 * Note: This is a WooCommerce filter that is not abstracted for API usage so we have to run it manually.
			 *
			 * @param bool $passed_validation Whether the item can be added to the cart.
			 * @param int  $ticket_id         Ticket ID.
			 * @param int  $quantity          Ticket quantity.
			 */
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $ticket_id, $quantity );

			if ( ! $passed_validation ) {
				return;
			}
		}

		try {
			if ( ! $additive ) {
				$cart_item_keys = $this->get_cart_item_keys_for_ticket_in_cart( $ticket_id );

				if ( ! empty( $cart_item_keys ) ) {
					// Remove cart items.
					array_map( [ $woocommerce->cart, 'remove_cart_item' ], $cart_item_keys );
				}
			}

			// Set quantity in cart.
			if ( 0 < $quantity ) {
				$woocommerce->cart->add_to_cart( $ticket_id, $quantity, 0, [], $extra_data );
			}
		} catch ( \Exception $exception ) {
			// Item not added to / removed from cart.
		}
	}

	/**
	 * Get the cart item keys for a ticket if found.
	 *
	 * @since 4.11.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array The cart item keys for a ticket if found.
	 */
	protected function get_cart_item_keys_for_ticket_in_cart( $ticket_id ) {
		/** @var WooCommerce $woocommerce */
		global $woocommerce;

		// Make sure we have correct cart before checking for contents.
		$woocommerce->cart->get_cart();

		$contents = $woocommerce->cart->get_cart_contents();

		$item_keys = [];

		foreach ( $contents as $item ) {
			if ( (int) $ticket_id !== (int) $item['product_id'] ) {
				continue;
			}

			$item_keys[] = $item['key'];
		}

		return $item_keys;
	}

	/**
	 * Get WooCommerce Cart URL.
	 *
	 * @since 4.11.0
	 *
	 * @return string WooCommerce Cart URL.
	 */
	public function get_cart_url() {
		$cart_url = wc_get_cart_url();
		$cart_url = $this->add_provider_to_cart_url( $cart_url );

		/**
		 * Allow filtering of the WooCommerce Cart URL.
		 *
		 * @since 4.10
		 *
		 * @param string $cart_url WooCommerce Cart URL.
		 */
		return apply_filters( 'tribe_tickets_woo_cart_url', $cart_url );
	}

	/**
	 * Get WooCommerce Checkout URL.
	 *
	 * @since 4.11.0
	 * @since 4.12.0    Stop adding the 'provider' parameter to the checkout URL, was causing conflicts with payment gateways.
	 *
	 * @return string WooCommerce Checkout URL.
	 */
	public function get_checkout_url() {
		$checkout_url = wc_get_checkout_url();

		/**
		 * Allow filtering of the WooCommerce Checkout URL.
		 *
		 * @since 4.11.0
		 *
		 * @param string $checkout_url WooCommerce Checkout URL.
		 */
		$checkout_url = apply_filters( 'tribe_tickets_plus_woo_checkout_url', $checkout_url );

		return $checkout_url;
	}
}
