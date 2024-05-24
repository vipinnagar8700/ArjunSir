<?php

class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report {
	/**
	 * Slug of the admin page for orders
	 *
	 * @var string
	 */
	public static $orders_slug = 'tickets-orders';

	/**
	 * Slug of the orders tab.
	 *
	 * @var string
	 */
	public static $tab_slug = 'tribe-tickets-plus-woocommerce-orders-report';

	/**
	 * @var string The orders page menu hook suffix.
	 *
	 * @see add_submenu_page()
	 */
	public $orders_page;

	/**
	 * The table that will display the ticket orders.
	 *
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table
	 */
	protected $orders_table;

	/**
	 * Constructor!
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'orders_page_register' ) );
		add_filter( 'post_row_actions', array( $this, 'orders_row_action' ) );
		add_filter( 'tribe_filter_attendee_order_link', array( $this, 'filter_editor_orders_link' ), 10, 2 );

		// register the WooCommerce orders report tab
		$wc_tabbed_view = new Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Report_Tabbed_View();
		$wc_tabbed_view->register( );
	}

	/**
	 * Registers the Orders admin page
	 */
	public function orders_page_register() {
		// the orders table only works with WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->orders_page = add_submenu_page(
			null, 'Order list', 'Order list', 'edit_posts', self::$orders_slug, array(
				$this,
				'orders_page_inside',
			)
		);

		add_filter( 'tribe_filter_attendee_page_slug', array( $this, 'add_attendee_resources_page_slug' ) );
		add_action( 'admin_enqueue_scripts', tribe_callback( 'tickets.attendees', 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', tribe_callback( 'tickets.attendees', 'load_pointers' ) );
		add_action( "load-$this->orders_page", array( $this, 'orders_page_screen_setup' ) );

	}

	/**
	 * Filter the Order Link to EDD in the Ticket Editor Settings
	 *
	 * @since 4.10
	 *
	 * @param string $url     a url for the order page for an event
	 * @param int    $post_id the post id for the current event
	 *
	 * @return string
	 */
	public function filter_editor_orders_link( $url, $post_id ) {
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $provider ) {
			$url = remove_query_arg( 'page', $url );
			$url = add_query_arg( [ 'page' => 'tickets-orders' ], $url );
		}

		return $url;
	}

	/**
	 * Filter the page slugs that the attendee resources will load to add the order page
	 *
	 * @param array $slugs List of page slugs.
	 *
	 * @return array
	 */
	public function add_attendee_resources_page_slug( $slugs ) {
		$slugs[] = $this->orders_page;
		return $slugs;
	}

	/**
	 * Adds the "orders" link in the admin list row actions for each event.
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public function orders_row_action( $actions ) {
		global $post;

		// the orders table only works with WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $actions;
		}

		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types(), true ) ) {
			return $actions;
		}

		$has_tickets = count( (array) Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance()->get_tickets( $post->ID ) );

		if ( ! $has_tickets ) {
			return $actions;
		}

		$url = self::get_tickets_report_link( $post );

		$actions['tickets_orders'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			esc_html__( 'See purchases for this event', 'event-tickets-plus' ),
			esc_url( $url ),
			esc_html__( 'Orders', 'event-tickets-plus' )
		);

		return $actions;
	}

	/**
	 * Setups the Orders screen data.
	 */
	public function orders_page_screen_setup() {
		$this->orders_table = new Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table;
		wp_enqueue_script( 'jquery-ui-dialog' );

		add_filter( 'admin_title', array( $this, 'orders_admin_title' ), 10, 2 );
	}

	/**
	 * Sets the browser title for the Orders admin page.
	 * Uses the event title.
	 *
	 * @param $admin_title
	 * @param $title
	 *
	 * @return string
	 */
	public function orders_admin_title( $admin_title, $title ) {
		if ( ! empty( $_GET['event_id'] ) ) {
			$event       = get_post( absint( $_GET['event_id'] ) );
			$admin_title = sprintf( esc_html_x( '%s - Order list', 'Browser title', 'event-tickets-plus' ), $event->post_title );
		}

		return $admin_title;
	}

	/**
	 * Renders the Orders page
	 */
	public function orders_page_inside() {
		$this->orders_table->prepare_items();

		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$event = get_post( $event_id );
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $event_id );

		/**
		 * Filters whether or not fees are being passed to the end user (purchaser)
		 *
		 * @var boolean $pass_fees Whether or not to pass fees to user
		 * @var int $event_id Event post ID
		 */
		Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::$pass_fees_to_user = apply_filters( 'tribe_tickets_pass_fees_to_user', true, $event_id );

		/**
		 * Filters the fee percentage to apply to a ticket/order
		 *
		 * @var float $fee_percent Fee percentage
		 */
		Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::$fee_percent = apply_filters( 'tribe_tickets_fee_percent', 0, $event_id );

		/**
		 * Filters the flat fee to apply to a ticket/order
		 *
		 * @var float $fee_flat Flat fee
		 */
		Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::$fee_flat = apply_filters( 'tribe_tickets_fee_flat', 0, $event_id );

		ob_start();
		$this->orders_table->display();
		$table = ob_get_clean();

		$organizer   = get_user_by( 'id', $event->post_author );
		$event_sales = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::event_sales( $event_id );
		$discounts   = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::event_discounts( $event_id );

		$tickets_sold  = [];
		$total_sold    = 0;
		$total_pending = 0;

		/**
		 * Setup the ticket breakdown
		 *
		 * @var Tribe__Tickets__Status__Manager $status_manager
		 */
		$status_manager = tribe( 'tickets.status' );

		$order_overview      = $status_manager->get_providers_status_classes( 'woo' );
		$complete_statuses   = (array) $status_manager->get_statuses_by_action( 'count_completed', 'woo' );
		$incomplete_statuses = (array) $status_manager->get_statuses_by_action( 'count_incomplete', 'woo' );

		/**
		 * Update ticket item counts by order status
		 *
		 * @var Tribe__Tickets__Ticket_Object $ticket
		 */
		foreach ( $tickets as $ticket ) {
			// Only Display if a WooCommerce Ticket otherwise kick out
			if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' != $ticket->provider_class ) {
				continue;
			}

			if ( empty( $tickets_sold[ $ticket->name ] ) ) {
				$tickets_sold[ $ticket->name ] = [
					'ticket'     => $ticket,
					'has_stock'  => ! $ticket->stock(),
					'sku'        => get_post_meta( $ticket->ID, '_sku', true ),
					'sold'       => 0,
					'pending'    => 0,
					'completed'  => 0,
					'refunded'   => 0,
					'incomplete' => 0,
				];
			}

			// update ticket item counts by order status
			$tickets_sold[ $ticket->name ]['product_sales'] = self::get_total_sales_per_productby_status( $ticket->ID );
			foreach ( $tickets_sold[ $ticket->name ]['product_sales'] as $status => $product ) {
				if (
					$status
					&& isset( $product[0] )
					&& is_object( $product[0] )
				) {
					if ( in_array( $status, $complete_statuses, true ) ) {
						$tickets_sold[ $ticket->name ]['completed'] += $product[0]->_qty;
					}

					if ( in_array( $status, $incomplete_statuses, true ) ) {
						$tickets_sold[ $ticket->name ]['incomplete'] += $product[0]->_qty;
					}

					/** @var Tribe__Tickets__Status__Abstract $status_class */
					$status_class = $order_overview->statuses[ $status ];
					$status_class->add_qty( $product[0]->_qty );
					$status_class->add_line_total( $product[0]->_line_total );
					$order_overview->add_qty( $product[0]->_qty );
					$order_overview->add_line_total( $product[0]->_line_total );
				}
			}
		}

		// Build and render the tabbed view from Event Tickets and set this as the active tab
		$tabbed_view = new Tribe__Tickets__Commerce__Orders_Tabbed_View();
		$tabbed_view->set_active( self::$tab_slug );
		$tabbed_view->render();

		include Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/woocommerce-orders.php';
	}

	/**
	 * Returns the link to the "Orders" report for this post.
	 *
	 * @param WP_Post $post
	 *
	 * @return string The absolute URL.
	 */
	public static function get_tickets_report_link( $post ) {
		$url = add_query_arg( array(
			'post_type' => $post->post_type,
			'page'      => self::$orders_slug,
			'event_id'  => $post->ID,
		), admin_url( 'edit.php' ) );

		return $url;
	}

	public static function get_total_sales_per_productby_status( $product_id ) {
		global $wpdb;

		if ( ! $product_id ) {
			return false;
		}

		$order_items = [];

		/** @var Tribe__Tickets__Status__Manager $status_manager */
		$status_manager = tribe( 'tickets.status' );

		$order_statuses = (array) $status_manager->get_statuses_by_action( 'all', 'woo' );

		foreach ( $order_statuses as $order_status ) {

			$sql = $wpdb->prepare( "
 						SELECT SUM( order_item_meta.meta_value ) as _qty,
 						SUM( order_item_meta_3.meta_value ) as _line_total
 						FROM {$wpdb->prefix}woocommerce_order_items as order_items

						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_3 ON order_items.order_item_id = order_item_meta_3.order_item_id
						LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID

						WHERE posts.post_type = 'shop_order'
						AND posts.post_status IN ( '$order_status' )
						AND order_items.order_item_type = 'line_item'
						AND order_item_meta.meta_key = '_qty'
						AND order_item_meta_2.meta_key = '_product_id'
						AND order_item_meta_2.meta_value = %s
						AND order_item_meta_3.meta_key = '_line_total'

						GROUP BY order_item_meta_2.meta_value
					",
					$product_id
				);

			$order_items[ $order_status ] = $wpdb->get_results( $sql );
		}

		return $order_items;
	}
}
