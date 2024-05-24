<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Orders__Table
 *
 * @since 4.10
 *
 * See documentation for WP_List_Table
 */
class Tribe__Tickets_Plus__Commerce__EDD__Orders__Table extends WP_List_Table {

	public $post_id;
	public $total_purchased   = 0;
	public $overall_total     = 0;
	public $valid_order_items = array();

	public static $pass_fees_to_user = true;
	public static $fee_percent       = 0;
	public static $fee_flat          = 0;

	/**
	 * In-memory cache of orders per event, where each key represents the event ID
	 * and the value is an array of orders.
	 *
	 * @var array
	 */
	protected static $orders = array();

	/**
	 * @var string The user option that will be used to store the number of orders per page to show.
	 */
	public $per_page_option;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$args = array(
			'singular' => 'order',
			'plural'   => 'orders',
			'ajax'     => true,
		);

		$this->per_page_option = Tribe__Tickets_Plus__Commerce__EDD__Screen_Options::$per_page_user_option;

		$screen = get_current_screen();

		if ( $screen instanceof WP_Screen ) {
			$screen->add_option( 'per_page', array(
				'label'  => __( 'Number of orders per page:', 'event-tickets-plus' ),
				'option' => $this->per_page_option,
			) );
		}

		parent::__construct( $args );
	}//end __construct

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 * This function is not hookable in core, so it needs to be overridden!
	 *
	 * @since 4.10.6
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = [ 'widefat', 'striped', 'orders', 'edd-orders' ];

		if ( is_admin() ) {
			$classes[] = 'fixed';
		}

		/**
		 * Filters the default classes added to the EDD order report `WP_List_Table`.
		 *
		 * @since 4.10.6
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		$classes = apply_filters( 'tribe_tickets_plus_edd_order_table_classes', $classes );

		return $classes;
	}

	/**
	 * Display the search box.
	 * We don't want Core's search box, because we implemented our own jQuery based filter,
	 * so this function overrides the parent's one and returns empty.
	 *
	 * @since 4.10
	 *
	 * @param string $text     The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text, $input_id ) {
		return;
	}

	/**
	 * Checks the current user's permissions
	 *
	 * @since 4.10
	 *
	 */
	public function ajax_user_can() {
		$post_type = get_post_type_object( $this->screen->post_type );

		return ! empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 4.10
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'order'     => __( 'Order', 'event-tickets-plus' ),
			'purchaser' => __( 'Purchaser', 'event-tickets-plus' ),
			'email'     => __( 'Email', 'event-tickets-plus' ),
			'purchased' => __( 'Purchased', 'event-tickets-plus' ),
			'address'   => __( 'Address', 'event-tickets-plus' ),
			'date'      => __( 'Date', 'event-tickets-plus' ),
			'status'    => __( 'Status', 'event-tickets-plus' ),
		);

		if ( self::event_fees( $this->post_id ) ) {
			$columns['subtotal'] = __( 'Subtotal', 'event-tickets-plus' );
			$columns['site_fee'] = __( 'Site Fee', 'event-tickets-plus' );
		}

		$columns['total'] = __( 'Total', 'event-tickets-plus' );

		return $columns;
	}

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @since 4.10
	 *
	 * @param $item
	 * @param $column
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		$value = empty( $item->$column ) ? '' : $item->$column;

		/**
		 * Filter EDD Order Table columns that don't have a specific column_{name} handler function.
		 *
		 * @since 4.10
		 *
		 * @param object    $item   the current item
		 * @param array     $column an array of columns
		 *
		 * @return string   $value  name of handler function
		 */
		return apply_filters( 'tribe_events_tickets_edd_orders_table_column', $value, $item, $column );
	}

	/**
	 * Handler for the date column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {

		$date = ( 'publish' === $item->status ) ? $item->completed_date : $item->date;

		return Tribe__Date_Utils::reformat( $date, Tribe__Date_Utils::DATEONLYFORMAT );
	}

	/**
	 * Handler for the ship to column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_address( $item ) {

		$shipping = $item->address;
		if ( empty( $shipping['line1'] )
		     || empty( $shipping['city'] )
		) {
			return '';
		}

		$address = trim( "{$item->first_name} {$item->last_name}" );

		$address .= "<br>{$shipping['line1']}<br>";

		if ( ! empty( $shipping['line2'] ) ) {
			$address .= "{$shipping['line2']}<br>";
		}

		$address .= $shipping['city'];

		if ( ! empty( $shipping['state'] ) ) {
			$address .= ", {$shipping['state']}";
		}

		if ( ! empty( $shipping['country'] ) ) {
			$address .= " {$shipping['country']}";
		}

		if ( ! empty( $shipping['zip'] ) ) {
			$address .= " {$shipping['zip']}";
		}

		return $address;
	}

	/**
	 * Handler for the purchased column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_purchased( $item ) {

		$tickets   = array();
		$num_items = 0;
		foreach ( $item->cart_details as $line_item ) {
			$ticket_id = $line_item['id'];

			if ( ! isset( $this->valid_order_items[ $item->ID ][ $ticket_id ] ) ) {
				continue;
			}

			$num_items += $line_item['quantity'];

			if ( empty( $tickets[ $line_item['name'] ] ) ) {
				$tickets[ $line_item['name'] ] = 0;
			}

			$tickets[ $line_item['name'] ] += $line_item['quantity'];
		}

		$this->total_purchased = $num_items;

		ksort( $tickets );

		$output = '';

		foreach ( $tickets as $name => $quantity ) {

			$output .= '<div class="tribe-line-item">' . esc_html( $quantity ) . ' - ' . esc_html( $name ) . '</div>';
		}

		return $output;
	}

	/**
	 * Handler for the order column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		$icon    = '';
		$warning = false;

		$order_number = $item->number;

		$order_url = admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $order_number );

		$order_number_link = '<a href="' . esc_url( $order_url ) . '">#' . absint( $order_number ) . '</a>';

		/**
		 * Allows for control of the order number link in the attendee report
		 *
		 * @since 4.10
		 *
		 * @param string $order_number_link The default "order" link.
		 * @param int    $order_number      The Post ID of the order.
		 */
		$order_number_link = apply_filters( 'tribe_tickets_plus_edd_order_link_url', $order_number_link, $order_number );

		$output = sprintf(
			esc_html__(
				'%1$s', 'event-tickets-plus'
			), $order_number_link
		);

		if ( 'publish' !== $item->status ) {
			$output .= '<div class="order-status order-status-' . esc_attr( $item->status ) . '">' . esc_html(
					$item->status_nicename
				) . '</div>';
		}

		return $output;
	}

	/**
	 * Handler for the subtotal column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_subtotal( $item ) {
		$total = 0;

		foreach ( $this->valid_order_items[ $item->ID ] as $line_item ) {
			$total += $line_item['subtotal'];
		}

		if ( ! self::$pass_fees_to_user ) {
			$total -= self::calc_site_fee( $total );
		}

		return tribe_format_currency( number_format( $total, 2 ) );
	}

	/**
	 * Handler for the total column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_total( $item ) {
		$total = 0;
		foreach ( $this->valid_order_items[ $item->ID ] as $line_item ) {
			$total += (float) $line_item['subtotal'];
			if ( self::item_has_discount( $line_item ) ) {
				$total -= self::item_get_discount( $line_item );
			}
		}

		if ( self::$pass_fees_to_user ) {
			$total += $this->calc_site_fee( $total );
		}

		$post_id = Tribe__Utils__Array::get_in_any( array( $_GET, $_REQUEST ), 'post_id', null );

		return tribe_format_currency( number_format( $total, 2 ), $post_id );
	}

	/**
	 * Handler for the site fees column
	 *
	 * @since 4.10
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_site_fee( $item ) {
		$total = 0;

		foreach ( $this->valid_order_items[ $item->ID ] as $line_item ) {
			$total += $line_item['subtotal'];
		}

		return tribe_format_currency( number_format( $this->calc_site_fee( $total ), 2 ) );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 4.10
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr class="' . esc_attr( $item->status ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Get all orders for a post id
	 *
	 * @since 4.10
	 *
	 * @param $post_id
	 *
	 * @return array|mixed
	 */
	public static function get_orders( $post_id ) {
		if ( ! $post_id ) {
			return array();
		}

		if ( isset( self::$orders[ $post_id ] ) ) {
			return self::$orders[ $post_id ];
		}

		$statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'edd' );
		$args = array(
			'post_type'      => 'tribe_eddticket',
			'posts_per_page' => -1,
			'post_status'    => $statuses,
			'meta_query'     => array(
				array(
					'key'   => tribe( 'tickets-plus.commerce.edd' )->attendee_event_key,
					'value' => $post_id,
				),
			),
		);

		$orders = array();
		$query  = new WP_Query( $args );

		foreach ( $query->posts as &$item ) {
			$order_id = get_post_meta( $item->ID, tribe( 'tickets-plus.commerce.edd' )->attendee_order_key, true );

			if ( isset( $orders[ $order_id ] ) ) {
				continue;
			}

			$order = edd_get_payment( $order_id );

			//prevent fatal error if no orders
			if ( $order && ! is_wp_error( $order ) ) {
				$orders[ $order_id ] = $order;
			}
		}

		self::$orders[ $post_id ] = $orders;

		return $orders;
	}

	/**
	 * Only get orders with at least on ticket attached to post type
	 *
	 * @since 4.10
	 *
	 *
	 * @param $post_id
	 * @param $items
	 *
	 * @return array
	 */
	public static function get_valid_order_items_for_event( $post_id, $items ) {
		$valid_order_items = array();

		$post_id = absint( $post_id );

		foreach ( $items as $order ) {
			if ( ! isset( $valid_order_items[ $order->ID ] ) ) {
				$valid_order_items[ $order->ID ] = array();
			}

			foreach (  $order->cart_details as $line_item ) {
				$ticket_id       = $line_item['id'];
				$ticket_post_id = absint(
					get_post_meta( $ticket_id, tribe( 'tickets-plus.commerce.edd' )->event_key, true )
				);

				// if the ticket isn't for the currently viewed event, skip it
				if ( $ticket_post_id !== $post_id ) {
					continue;
				}

				$valid_order_items[ $order->ID ][ $ticket_id ] = $line_item;
			}
		}

		return $valid_order_items;
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 4.10
	 *
	 */
	public function prepare_items() {

		$this->post_id = Tribe__Utils__Array::get( $_GET, 'event_id', Tribe__Utils__Array::get( $_GET, 'post_id', 0 ) );
		$items       = self::get_orders( $this->post_id );
		$total_items = count( $items );
		$per_page    = $this->get_items_per_page( $this->per_page_option );

		$this->valid_order_items = self::get_valid_order_items_for_event( $this->post_id, $items );

		$current_page = $this->get_pagenum();

		$this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Return sales (sans fees) for the given event
	 *
	 * @since 4.10
	 *
	 * @param int $post_id Event post ID
	 *
	 * @return float
	 */
	public static function event_sales( $post_id ) {
		$orders            = self::get_orders( $post_id );
		$valid_order_items = self::get_valid_order_items_for_event( $post_id, $orders );

		$total = 0;

		foreach ( $valid_order_items as $order_id => $order ) {
			if ( 'cancelled' === $orders[ $order_id ]['status']
			     || 'refunded' === $orders[ $order_id ]['status']
			     || 'failed' === $orders[ $order_id ]['status']
			) {
				continue;
			}

			$order_total = 0;

			foreach ( $order as $line_item ) {
				$order_total += $line_item['subtotal'];
			}

			if ( ! self::$pass_fees_to_user ) {
				$order_total -= self::calc_site_fee( $order_total, self::$pass_fees_to_user );
			}

			$total += $order_total;
		}

		return $total;
	}

	/**
	 * Return fees for the given event
	 *
	 * @since 4.10
	 *
	 * @param int $post_id Event post ID
	 *
	 * @return float
	 */
	public static function event_fees( $post_id ) {
		$orders            = self::get_orders( $post_id );
		$valid_order_items = self::get_valid_order_items_for_event( $post_id, $orders );

		$fees = 0;

		foreach ( $valid_order_items as $order_id => $order ) {

			if ( 'cancelled' === $orders[ $order_id ]->status
			     || 'refunded' === $orders[ $order_id ]->status
			     || 'failed' === $orders[ $order_id ]->status
			) {
				continue;
			}

			$order_total = 0;

			foreach ( $order as $line_item ) {
				$order_total += $line_item['subtotal'];
			}

			$fees += self::calc_site_fee( $order_total, self::$pass_fees_to_user );
		}

		return $fees;
	}

	/**
	 * Return total revenue for the given event
	 *
	 * @since 4.10
	 *
	 * @param int $post_id Event post ID
	 *
	 * @return float
	 */
	public static function event_revenue( $post_id ) {
		return self::event_sales( $post_id, self::$pass_fees_to_user ) + self::event_fees( $post_id, self::$pass_fees_to_user );
	}

	/**
	 * Calculate site fees
	 *
	 * @since 4.10
	 *
	 * @param int $amount Total to calculate site fees on
	 *
	 * @return float
	 */
	public static function calc_site_fee( $amount ) {
		return round( $amount * ( self::$fee_percent / 100 ), 2 ) + self::$fee_flat;
	}

	/**
	 * Echoes the customer name.
	 *
	 * @since 4.10
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_purchaser( $item ) {
		return $item->first_name . ' ' . $item->last_name;
	}

	/**
	 * Echoes the customer email.
	 *
	 * @since 4.10
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_email( $item ) {
		return  $item->email;
	}

	/**
	 * Echoes the order status.
	 *
	 * @since 4.10
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		return  $item->status_nicename;
	}
}
