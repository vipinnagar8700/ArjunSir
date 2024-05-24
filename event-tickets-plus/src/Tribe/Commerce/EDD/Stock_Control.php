<?php

if ( class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Stock_Control' ) ) {
	return;
}

/**
 * Helps to manage ticket stock (as EDD itself has no native concept of inventory).
 *
 * Responsibility for stock management involving global stock is mostly delegated to
 * the Tribe__Tickets_Plus__Commerce__EDD__Global_Stock class.
 *
 * @see Tribe__Tickets_Plus__Commerce__EDD__Global_Stock
 */
class Tribe__Tickets_Plus__Commerce__EDD__Stock_Control {
	const PURCHASED_TICKETS  = '_edd_tickets_qty_';
	const COMPUTED_INVENTORY = '_edd_tickets_computed';


	public function __construct() {
		add_action( 'edd_insert_payment', array( $this, 'record_purchased_inventory' ), 10, 2 );
		add_filter( 'edd_edd_update_payment_meta__edd_payment_meta', array( $this, 'recalculate_purchased_inventory' ), 100, 2 );
	}

	/**
	 * Returns the amount of inventory available for the specified ticket.
	 *
	 * @since 4.10.7 Fixed so limit of -1 returns unlimited.
	 *
	 * @param  int $ticket_id
	 *
	 * @return int
	 */
	public function available_units( $ticket_id ) {
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		// Do we have a limit on the number of tickets?
		$limit = get_post_meta( $ticket_id, $handler->key_capacity, true );

		if (
			empty( $limit )
			|| -1 === (int) $limit
		) {
			return Tribe__Tickets_Plus__Commerce__EDD__Main::UNLIMITED;
		}

		// If so, calculate the number still available
		$sold = $this->get_purchased_inventory( $ticket_id );

		return $limit - $sold;
	}

	/**
	 * Increments the inventory of the specified product by 1 (or by the optional
	 * $increment_by value if provided).
	 *
	 * @param int $product_id
	 * @param int $increment_by
	 *
	 * @return bool true|false according to whether the update was successful or not
	 */
	public function increment_units( $product_id, $increment_by = 1 ) {
		$ticket = tribe( 'tickets-plus.commerce.edd' )->get_ticket( null, $product_id );

		if ( ! $ticket || ! $ticket->managing_stock() ) {
			return false;
		}

		$stock = get_post_meta( $product_id, '_stock', true );

		if ( Tribe__Tickets_Plus__Commerce__EDD__Main::UNLIMITED === $stock  ) {
			return false;
		}

		return (bool) update_post_meta( $product_id, '_stock', (int) $stock + $increment_by );
	}

	/**
	 * For each payment, generates a record of the ticket stock purchased for any ticket items.
	 *
	 * @param int   $payment
	 * @param array $payment_data
	 */
	public function record_purchased_inventory( $payment, $payment_data ) {
		$quantity = array();

		$event_key = tribe( 'tickets-plus.commerce.edd' )->event_key;

		// Look through the list of purchased downloads: for any that relate to tickets,
		// determine how much inventory was purchased
		foreach ( $payment_data['downloads'] as $purchase ) {
			if ( ! get_post_meta( $purchase['id'], $event_key ) ) {
				continue;
			}

			$ticket_payments[] = $purchase;
			$existing_quantity = isset( $quantity[ $purchase['id'] ] ) ? $quantity[ $purchase['id'] ] : 0;
			$quantity[ $purchase['id'] ] = $existing_quantity + $purchase['quantity'];
		}

		// For each purchased ticket, record the level of inventory purchased
		foreach ( $quantity as $purchase_id => $amount ) {
			update_post_meta( $payment, self::PURCHASED_TICKETS . $purchase_id, absint( $quantity[ $purchase_id ] ) );
			// and update stock to reflect
			if ( ! empty( $amount ) && is_numeric( $amount ) ) {
				$this->increment_units( $purchase_id, ( 0 - $amount )  );
			}
		}

		if ( ! empty( $quantity ) ) {
			/**
			 * Fires once the EDD provider has recorded inventory levels following an
			 * order that includes ticket products.
			 *
			 * @var array $quantities amount of stock bought for each ticket, indexed by the product ID
			 */
			do_action( 'event_tickets_edd_tickets_purchased_inventory_recorded', $quantity );
		}
	}

	/**
	 * Fires whenever the _edd_payment_meta record is updated: recalculates the amount of
	 * ticket inventory that has been purchased.
	 *
	 * @param  array $payment_data
	 * @param  int   $payment
	 * @return array
	 */
	public function recalculate_purchased_inventory( $payment_data, $payment ) {
		$this->record_purchased_inventory( $payment, $payment_data );
		return $payment_data;
	}

	/**
	 * Returns the amount of inventory purchased for the specified ticket.
	 *
	 * By default this is calculated only for orders with "valid" order statuses (pending and completed),
	 * but the optional param $order_statuses can be used to pass in an alternative list if the calculation
	 * should be restricted to pending orders only (for example).
	 *
	 * @see    $this->get_valid_payment_statuses()
	 * @param  int   $ticket_id
	 * @param  array $order_statuses
	 * @return int
	 */
	public function get_purchased_inventory( $ticket_id, array $order_statuses = [] ) {
		global $wpdb;

		if ( empty( $order_statuses ) ) {
			$order_statuses = $this->get_valid_payment_statuses();
		}

		$order_statuses = $this->escape_fields( $order_statuses );

		$sql = "
			SELECT
				COUNT( * )
				FROM $wpdb->postmeta
					JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				WHERE
					$wpdb->postmeta.meta_key = '_tribe_eddticket_product'
					AND $wpdb->postmeta.meta_value = %s
		";
		$sql .= empty( $order_statuses ) ? ';' : "AND post_status IN ( $order_statuses );";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $ticket_id ) );
	}

	/**
	 * @param  int $ticket_id
	 * @return int
	 */
	public function count_incomplete_order_items( $ticket_id ) {
		return $this->get_purchased_inventory( $ticket_id, $this->get_pending_payment_statuses() );
	}

	/**
	 * @param  int $ticket_id
	 * @return int
	 */
	public function count_refunded_order_items( $ticket_id ) {
		return $this->get_purchased_inventory( $ticket_id, $this->get_refunded_payment_statuses() );
	}

	/**
	 * Returns a comma separated, escaped list of fields.
	 *
	 * @return string
	 */
	protected function escape_fields( array $fields ) {
		global $wpdb;
		$list = array();

		foreach ( $fields as $field ) {
			$list[] = $wpdb->prepare( '%s', $field );
		}

		return join( ',', $list );
	}

	/**
	 * Returns a filterable list of post statuses considered valid (ie, pending or complete but not
	 * cancelled/refunded, etc) in relation to EDD payments.
	 *
	 * @return array
	 */
	protected function get_valid_payment_statuses() {

		$valid_statuses = tribe( 'tickets.status' )->get_statuses_by_action( 'count_sales', 'edd' );
		/**
		 *  Filter EDD Valid Payment Statuses
		 *
		 * @since 4.0
		 *
		 * @param array an array of payment statuses
		 */
		return (array) apply_filters( 'eddtickets_valid_payment_statuses', $valid_statuses );
	}

	/**
	 * Returns a filterable list of post statuses considered "pending" in relation to
	 * EDD payments.
	 *
	 * @return array
	 */
	protected function get_pending_payment_statuses() {
		$pending_statuses = tribe( 'tickets.status' )->get_statuses_by_action( [ 'incomplete', 'count_sales' ], 'edd' );

		/**
		 *  Filter EDD Pending Payment Statuses
		 *
		 * @since 4.0
		 *
		 * @param array an array of payment statuses
		 */
		return (array) apply_filters( 'eddtickets_pending_payment_statuses', $pending_statuses );
	}

	/**
	 * Returns a filterable list of post statuses considered "refunded" in relation to
	 * EDD payments.
	 *
	 * @return array
	 */
	protected function get_refunded_payment_statuses() {
		$refunded_statuses = tribe( 'tickets.status' )->get_statuses_by_action( 'count_refunded', 'edd' );

		/**
		 * Filter EDD Refunded Payment Statuses
		 *
		 * @since 4.10.7
		 *
		 * @param array an array of payment statuses
		 */
		return (array) apply_filters( 'event_tickets_plus_commerce_edd_refunded_payment_statuses', $refunded_statuses );
	}
}
