<?php
/**
 * WooCommerce Refunded Orders.
 *
 * Implements methods to get the refunded orders for WooCommerce
 * (complete and partial orders) by $ticket_id.
 *
 * @since 4.7.3
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded {

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded[]
	 */
	protected static $instances;

	/**
	 * @var int
	 */
	protected $ticket_id = 0;

	/**
	 * @var int
	 */
	protected $count_cache = false;

	/**
	 * Get refunds count. If there's something in cache, then
	 * the chached number, if not run `real_get_count()`.
	 *
	 * @since 4.7.3
	 *
	 * @param      $ticket_id
	 *
	 * @return int
	 */
	public function get_count( $ticket_id ) {

		if ( ! is_numeric( $ticket_id ) ) {
			return;
		}

		$ticket_post = get_post( $ticket_id );
		if ( empty( $ticket_post ) ) {
			return;
		}

		if ( false === $this->count_cache ) {
			$this->count_cache = $this->real_get_count( $ticket_id );
		}

		return $this->count_cache;
	}

	/**
	 * Get the number of refunds for a ticket
	 * (both complete refunds, plus partial refunds)
	 *.
	 *
	 * @since 4.7.3
	 *
	 * @return int
	 */
	protected function real_get_count( $ticket_id ) {

		// get the orders associated to the ticket
		$order_item_ids = $this->get_order_item_ids( $ticket_id );

		// if there are no orders associated to the ticket, return zero
		if ( empty( $order_item_ids ) ) {
			return 0;
		}

		// get the actual order ids
		$order_item_ids_interval = $order_item_ids;
		$order_ids               = $this->get_order_ids( $order_item_ids_interval );

		if ( empty( $order_ids ) ) {
			return 0;
		}

		$order_post_ids_interval = $order_ids;

		// Get refunded orders
		$refunded_order_post_ids = $this->get_refunded_order_post_ids( $order_post_ids_interval );
		if ( empty( $refunded_order_post_ids ) ) {
			return 0;
		}

		$refunded_qty = 0;

		foreach ( $refunded_order_post_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			// increment the refund quantity
			$refunded_qty += (int) $order->get_item_count_refunded();
		}

		return $refunded_qty;
	}

	/**
	 * Get the order_item_ids where the ticket is involved (mapped)
	 *
	 * @since 4.7.3
	 *
	 * @return array
	 */
	protected function get_order_item_ids( $ticket_id ) {
		global $wpdb;

		$wc_order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';

		$order_item_ids         = $wpdb->get_col(
			"SELECT order_item_id FROM {$wc_order_itemmeta_table} WHERE meta_key = '_product_id' AND meta_value = {$ticket_id}"
		);

		return implode( ',', array_map( 'intval', $order_item_ids ) );
	}

	/**
	 * Get the actual order ids for the ticket, given the mapped values
	 *
	 * @since 4.7.3
	 *
	 * @return array
	 */
	public function get_order_ids( $order_item_ids_interval ) {
		global $wpdb;

		$wc_order_items_table = $wpdb->prefix . 'woocommerce_order_items';

		$order_ids            = $wpdb->get_results(
			"SELECT order_id, order_item_id FROM {$wc_order_items_table} WHERE order_item_id IN ({$order_item_ids_interval})"
		);

		return implode( ',', wp_list_pluck( $order_ids, 'order_id' ) );
	}

	/**
	 * Get the order ids where there was a refund
	 *
	 * @since 4.7.3
	 *
	 * @return array
	 */
	public function get_refunded_order_post_ids( $order_post_ids_interval ) {
		global $wpdb;

		// keep refunded orders
		$refunded_order_post_ids = $wpdb->get_col(
			"SELECT post_parent FROM {$wpdb->posts} WHERE post_parent in ({$order_post_ids_interval}) AND post_type = 'shop_order_refund'"
		);

		// make sure we don't have duplicates
		$refunded_order_post_ids = array_unique( $refunded_order_post_ids );

		return $refunded_order_post_ids;
	}

}
