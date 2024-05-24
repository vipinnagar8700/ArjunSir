<?php

/**
 * Integration layer for WooCommerce and Custom Meta
 *
 * @since 4.1
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Meta {

	public function __construct() {
		add_action( 'woocommerce_order_status_changed', [ $this, 'save_attendee_meta_to_order' ], 5, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_attendee_meta_to_order' ], 5 );
		add_action( 'event_tickets_woocommerce_ticket_created', [ $this, 'save_attendee_meta_to_ticket' ], 10, 4 );

		add_filter( 'tribe_tickets_plus_meta_storage_get_hash_cookie', [ $this, 'get_hash_cookie' ], 10, 2 );
		add_action( 'tribe_tickets_plus_meta_storage_set_hash_cookie', [ $this, 'set_hash_cookie' ], 10, 3 );
		add_action( 'tribe_tickets_plus_meta_storage_delete_hash_cookie', [ $this, 'delete_hash_cookie' ], 10, 3 );
		add_filter( 'tribe_tickets_plus_meta_storage_combine_new_and_saved_meta', [ $this, 'clear_woocommerce_ar_updated' ] );
	}

	/**
	 * Clears meta cookie data for products when order proceeds from pending payment.
	 *
	 * @since 4.9.2
	 *
	 * @param array $product_ids WooCommerce Product IDs
	 */
	public function clear_meta_cookie_data_for_products( $product_ids ) {
		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		// Clear meta cookie data for products.
		foreach ( $product_ids as $product_id ) {
			$meta_object->clear_meta_cookie_data( $product_id );
		}
	}

	/**
	 * Sets attendee data on order posts.
	 *
	 * @since 4.1
	 *
	 * @param int    $order_id    WooCommerce Order ID
	 * @param string $from_status WooCommerce Status (from)
	 */
	public function save_attendee_meta_to_order( $order_id, $from_status = null ) {
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		$product_ids = [];

		// gather product ids
		foreach ( (array) $order_items as $item ) {
			$product_ids[] = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];
		}

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		// build the custom meta data that will be stored in the order meta
		if ( ! $order_meta = $meta_object->build_order_meta( $product_ids ) ) {
			return;
		}

		// store the custom meta on the order
		update_post_meta( $order_id, Tribe__Tickets_Plus__Meta::META_KEY, $order_meta, true );

		if ( 'pending' === $from_status ) {
			$this->clear_meta_cookie_data_for_products( $product_ids );
		}
	}

	/**
	 * Sets attendee data on attendee posts
	 *
	 * @since 4.1
	 *
	 * @param int $attendee_id       Attendee Ticket Post ID
	 * @param int $order_id          WooCommerce Order ID
	 * @param int $product_id        WooCommerce Product ID
	 * @param int $order_attendee_id Attendee number in submitted order
	 */
	public function save_attendee_meta_to_ticket( $attendee_id, $order_id, $product_id, $order_attendee_id ) {
		$meta = get_post_meta( $order_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( ! isset( $meta[ $product_id ] ) ) {
			return;
		}

		if ( ! isset( $meta[ $product_id ][ $order_attendee_id ] ) ) {
			return;
		}

		update_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, $meta[ $product_id ][ $order_attendee_id ] );
	}

	/**
	 * Get hash value from WooCommerce session.
	 *
	 * @since 4.11.0
	 *
	 * @param null|string $hash    The hash value.
	 * @param null|int    $post_id Post ID (or null if using current post).
	 *
	 * @return null|string The hash value.
	 */
	public function get_hash_cookie( $hash, $post_id ) {
		if ( ! empty( $hash ) || ! is_admin() || 'product' !== get_post_type( $post_id ) ) {
			return $hash;
		}

		$wc_session = WC()->session;

		if ( empty( $wc_session ) ) {
			return $hash;
		}

		$hash = $wc_session->get( Tribe__Tickets_Plus__Meta__Storage::HASH_COOKIE_KEY );

		return $hash;
	}

	/**
	 * Set hash value in the WooCommerce session.
	 *
	 * @since 4.11.0
	 *
	 * @param string      $transient_id Transient ID.
	 * @param array       $ticket_meta  List of ticket meta being saved.
	 * @param null|string $provider     Provider name.
	 */
	public function set_hash_cookie( $transient_id, $ticket_meta, $provider ) {
		if ( empty( $_POST['wootickets_process'] ) && ! in_array( $provider, [ 'woo', 'tribe_wooticket' ], true ) ) {
			return;
		}

		$wc_session = WC()->session;

		if ( empty( $wc_session ) ) {
			return;
		}

		$wc_session->set( Tribe__Tickets_Plus__Meta__Storage::HASH_COOKIE_KEY, $transient_id );
	}

	/**
	 * Delete the hash value from the WooCommerce session.
	 *
	 * @since 4.11.0
	 *
	 * @param int $ticket_id The ticket ID.
	 */
	public function delete_hash_cookie( $ticket_id ) {
		if ( 'product' !== get_post_type( $ticket_id ) ) {
			return;
		}

		$wc_session = WC()->session;

		if ( empty( $wc_session ) ) {
			return;
		}

		$wc_session->__unset( Tribe__Tickets_Plus__Meta__Storage::HASH_COOKIE_KEY );
	}

	/**
	 * Clear WooCommerce session value for whether AR ticket was updated.
	 *
	 * @since 4.11.0
	 *
	 * @param array $to_be_saved The combined attendee meta to save.
	 *
	 * @return array The combined attendee meta to save.
	 */
	public function clear_woocommerce_ar_updated( $to_be_saved ) {
		$wc_session = WC()->session;

		if ( empty( $wc_session ) ) {
			return $to_be_saved;
		}

		$has_wc_ticket = false;

		foreach ( $to_be_saved as $ticket_id => $meta ) {
			if ( 'product' === get_post_type( $ticket_id ) ) {
				$has_wc_ticket = true;

				break;
			}
		}

		if ( $has_wc_ticket ) {
			$wc_session->__unset( 'tribe_ar_ticket_updated' );
		}

		return $to_be_saved;
	}
}
