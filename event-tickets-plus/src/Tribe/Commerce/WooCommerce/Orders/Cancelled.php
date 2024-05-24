<?php


class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled {

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled[]
	 */
	protected static $instances;

	/**
	 * @var int
	 */
	protected $ticket_id;

	/**
	 * @var int
	 */
	protected $count_cache = false;

	/**
	 * @param $ticket_id
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled|WP_Error
	 */
	public static function for_ticket( $ticket_id ) {

		if ( empty( self::$instances[ $ticket_id ] ) ) {
			try {
				self::$instances[ $ticket_id ] = new self( $ticket_id );
			} catch ( InvalidArgumentException $e ) {
				return new WP_Error( 'invalid-ticket-id', $e->getMessage() );
			}
		}

		return self::$instances[ $ticket_id ];
	}

	/**
	 * Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled constructor.
	 *
	 * Reccomended way to instance the object is using the `for_ticket` factory method.
	 *
	 * @param      $ticket_id
	 */
	public function __construct( $ticket_id ) {
		if ( ! is_numeric( $ticket_id ) ) {
			throw new InvalidArgumentException(
				esc_html(
					sprintf(
						__(
							'%s post ID must be an int or a numeric string.',
							'event-tickets-plus'
						),
						tribe_get_ticket_label_singular( 'woo_orders_cancelled_exception' )
					)
				)
			);
		}

		$ticket_post = get_post( $ticket_id );
		if ( empty( $ticket_post ) ) {
			throw new InvalidArgumentException(
				esc_html(
					sprintf(
						__(
							'%1$s with ID %2$s does not exist.',
							'event-tickets-plus'
						),
						tribe_get_ticket_label_singular( 'woo_orders_cancelled_exception' ),
						esc_html( $ticket_id )
					)
				)
			);
		}

		$this->ticket_id = $ticket_id;
	}

	public function get_count() {
		if ( false === $this->count_cache ) {
			$this->count_cache = $this->real_get_count();
		}

		return $this->count_cache;
	}

	protected function real_get_count() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$wc_order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$wc_order_items_table    = $wpdb->prefix . 'woocommerce_order_items';

		// get the orders associated to the ticket
		$order_item_ids = $wpdb->get_col(
			"SELECT order_item_id FROM {$wc_order_itemmeta_table} WHERE meta_key = '_product_id' AND meta_value = {$this->ticket_id}"
		);

		if ( empty( $order_item_ids ) ) {
			return 0;
		}

		$order_item_ids_interval = implode( ',', $order_item_ids );
		$order_ids               = $wpdb->get_results(
			"SELECT order_id, order_item_id  FROM {$wc_order_items_table} WHERE order_item_id IN ({$order_item_ids_interval})"
		);

		if ( empty( $order_ids ) ) {
			return 0;
		}

		// keep cancelled orders
		$order_post_ids_interval  = implode( ',', wp_list_pluck( $order_ids, 'order_id' ) );
		$cancelled_order_post_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE ID in ({$order_post_ids_interval}) AND post_status = 'wc-cancelled'"
		);

		if ( empty( $cancelled_order_post_ids ) ) {
			return 0;
		}

		// get each cancelled order qty
		$cancelled_order_item_ids = array();
		foreach ( $order_ids as $order_id ) {
			if ( in_array( $order_id->order_id, $cancelled_order_post_ids ) ) {
				$cancelled_order_item_ids[] = $order_id->order_item_id;
			}
		}

		if ( empty( $cancelled_order_item_ids ) ) {
			return 0;
		}

		$cancelled_order_item_ids_interval = implode( ',', $cancelled_order_item_ids );
		$cancelled_qty                     = $wpdb->get_var(
			"SELECT SUM(meta_value) FROM {$wc_order_itemmeta_table} WHERE order_item_id IN ({$cancelled_order_item_ids_interval}) AND meta_key = '_qty'"
		);

		return empty( $cancelled_qty ) ? 0 : $cancelled_qty;
	}
}
