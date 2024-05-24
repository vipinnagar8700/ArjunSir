<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Orders__Report
 *
 * @since 4.10
 */
class Tribe__Tickets_Plus__Commerce__EDD__Orders__Report {

	/**
	 * Slug of the admin page for orders
	 *
	 * @var string
	 */
	public static $orders_slug = 'edd-orders';

	/**
	 * @var string
	 */
	public static $tab_slug = 'tribe-tickets-edd-orders-report';

	/**
	 * @var string The menu slug of the orders page
	 */
	public $orders_page;

	/**
	 * @var Tribe__Tickets_Plus__Commerce__EDD__Orders__Table
	 */
	public $orders_table;

	/**
	 * Returns the link to the "Orders" report for this post.
	 *
	 * @since 4.10
	 *
	 * @param WP_Post $post
	 *
	 * @return string The absolute URL.
	 */
	public static function get_tickets_report_link( $post ) {
		$url = add_query_arg( [
			'post_type' => $post->post_type,
			'page'      => self::$orders_slug,
			'post_id'   => $post->ID,
		], admin_url( 'edit.php' ) );

		return $url;
	}

	/**
	 * Hooks the actions and filter required by the class.
	 *
	 * @since 4.10
	 */
	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_orders_row_action' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'register_orders_page' ) );
		add_filter( 'tribe_filter_attendee_order_link', array( $this, 'filter_editor_orders_link' ), 10, 2 );

		// register the tabbed view
		$edd_tabbed_view = new Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Report_Tabbed_View();
		$edd_tabbed_view->register();
	}

	/**
	 * Adds order related actions to the available row actions for the post.
	 *
	 * @since 4.10
	 *
	 * @param array $actions
	 * @param       $post
	 *
	 * @return array
	 */
	public function add_orders_row_action( array $actions, $post ) {
		$post_id = Tribe__Main::post_id_helper( $post );
		$post    = get_post( $post_id );

		// only if tickets are active on this post type
		if ( ! in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types(), true ) ) {
			return $actions;
		}

		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $edd */
		$edd = tribe( 'tickets-plus.commerce.edd' );

		$has_tickets = count( $edd->get_tickets_ids( $post->ID ) );

		if ( ! $has_tickets ) {
			return $actions;
		}

		$url         = self::get_tickets_report_link( $post );
		$post_labels = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$post_type   = strtolower( $post_labels->singular_name );

		$actions['tickets_orders'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			sprintf( esc_html_x( 'See EDD purchases for this %s', 'order row action', 'event-tickets-plus' ), $post_type ),
			esc_url( $url ),
			esc_html_x( 'Orders', 'order row action', 'event-tickets-plus' )
		);

		return $actions;
	}

	/**
	 * Registers the EDD orders page as a plugin options page.
	 *
	 * @since 4.10
	 */
	public function register_orders_page() {

		$candidate_post_id = Tribe__Utils__Array::get( $_GET, 'post_id', Tribe__Utils__Array::get( $_GET, 'event_id', 0 ) );

		if ( ( $post_id = absint( $candidate_post_id ) ) != $candidate_post_id ) {
			return;
		}

		$cap     = 'edit_posts';
		if ( ! current_user_can( 'edit_posts' ) && $post_id ) {
			$post = get_post( $post_id );

			if ( $post instanceof WP_Post && get_current_user_id() === (int) $post->post_author ) {
				$cap = 'read';
			}
		}

		$page_title        = __( 'Orders', 'event-tickets-plus' );
		$this->orders_page = add_submenu_page(
			null,
			$page_title,
			$page_title,
			$cap,
			self::$orders_slug,
			array( $this, 'orders_page_inside' )
		);

		add_filter( 'tribe_filter_attendee_page_slug', array( $this, 'add_attendee_resources_page_slug' ) );
		add_action( 'admin_enqueue_scripts', array( tribe( 'tickets.attendees' ), 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( tribe( 'tickets.attendees' ), 'load_pointers' ) );
		add_action( 'load-' . $this->orders_page, array( $this, 'attendees_page_screen_setup' ) );
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

		if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === $provider ) {
			$url = remove_query_arg( 'page', $url );
			$url = add_query_arg( [ 'page' => 'edd-orders' ], $url );
		}

		return $url;
	}


	/**
	 * Filter the page slugs that the attendee resources will load to add the order page
	 *
	 * @since 4.10
	 *
	 * @param $slugs
	 *
	 * @return array
	 */
	public function add_attendee_resources_page_slug( $slugs ) {
		$slugs[] = $this->orders_page;

		return $slugs;
	}

	/**
	 * Sets up the attendees page screen.
	 *
	 * @since 4.10
	 */
	public function attendees_page_screen_setup() {
		$this->orders_table = new Tribe__Tickets_Plus__Commerce__EDD__Orders__Table();
		wp_enqueue_script( 'jquery-ui-dialog' );

		add_filter( 'admin_title', array( $this, 'orders_admin_title' ) );
	}

	/**
	 * Sets the browser title for the Orders admin page.
	 *
	 * @since 4.10
	 *
	 * @param $admin_title
	 *
	 * @return string
	 */
	public function orders_admin_title( $admin_title ) {
		if ( ! empty( $_GET['post_id'] ) ) {
			$event       = get_post( absint( $_GET['post_id'] ) );
			$admin_title = sprintf( esc_html_x( '%s - EDD Orders', 'Browser title', 'event-tickets-plus' ), $event->post_title );
		}

		return $admin_title;
	}

	/**
	 * Renders the order page
	 *
	 * @since 4.10
	 */
	public function orders_page_inside() {
		$this->orders_table->prepare_items();

		$post_id = Tribe__Utils__Array::get( $_GET, 'event_id', Tribe__Utils__Array::get( $_GET, 'post_id', 0 ) );
		$post    = get_post( absint( $post_id ) );

		// Build and render the tabbed view from Event Tickets and set this as the active tab
		$tabbed_view = new Tribe__Tickets__Commerce__Orders_Tabbed_View();
		$tabbed_view->set_active( self::$tab_slug );
		$tabbed_view->render();

		$author     = get_user_by( 'id', $post->post_author );
		$tickets    = Tribe__Tickets__Tickets::get_event_tickets( $post_id );

		/**
		 * Setup the ticket breakdown
		 *
		 * @var Tribe__Tickets__Status__Manager $status_manager
		 */
		$status_manager = tribe( 'tickets.status' );

		$order_overview      = $status_manager->get_providers_status_classes( 'edd' );
		$complete_statuses   = (array) $status_manager->get_statuses_by_action( 'count_completed', 'edd' );
		$incomplete_statuses = (array) $status_manager->get_statuses_by_action( 'count_incomplete', 'edd' );

		$tickets_sold = [];

		/**
		 * Update ticket item counts by order status
		 *
		 * @var Tribe__Tickets__Ticket_Object $ticket
		 */
		foreach ( $tickets as $ticket ) {
			// Only Display if a EDD Ticket otherwise kick out
			if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' != $ticket->provider_class ) {
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

			$orders = $this->get_all_orders_by_download_id( $ticket->ID );
			foreach ( $orders as $order ) {
				foreach ( $order->cart_details as $line_item ) {

					if ( ! $order->status_nicename || ! isset( $line_item ) ) {
						continue;
					}

					// only count the item if it matches the current ticket id
					if ( $ticket->ID !== $line_item['id'] ) {
						continue;
					}

					if ( in_array( $order->status, $complete_statuses, true ) ) {
						$tickets_sold[ $ticket->name ]['completed'] += $line_item['quantity'];
					}

					if ( in_array( $order->status, $incomplete_statuses, true ) ) {
						$tickets_sold[ $ticket->name ]['incomplete'] += $line_item['quantity'];
					}

					/** @var Tribe__Tickets__Status__Abstract $status_class */
					$status_class = $order_overview->statuses[ $order->status_nicename ];
					$status_class->add_qty( $line_item['quantity'] );
					$status_class->add_line_total( $line_item['subtotal'] );
					$order_overview->add_qty( $line_item['quantity'] );
					$order_overview->add_line_total( $line_item['subtotal'] );
				}
			}
		}

		$post_type_object = get_post_type_object( $post->post_type );
		$post_singular_label = $post_type_object->labels->singular_name;

		// Render the table buffering its output; it will be used in the template below
		$this->orders_table->prepare_items();

		ob_start();
		$this->orders_table->search_box( __( 'Search Orders', 'event-tickets-plus' ), 'event-tickets-plus' );
		$this->orders_table->display();
		$table = ob_get_clean();

		include Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/edd-orders.php';
	}

	/**
	 * Get all orders for a download id and return array of order objects
	 *
	 * @since 4.10
	 *
	 * @param $ID int an ID for a edd download
	 *
	 * @return array an array of order objects
	 */
	public function get_all_orders_by_download_id( $ID ) {

		$all_statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'edd' );
		$args = array(
			'post_type'      => 'tribe_eddticket',
			'posts_per_page' => -1,
			'post_status'    => $all_statuses,
			'meta_query'     => array(
				array(
					'key'   => tribe( 'tickets-plus.commerce.edd' )->attendee_product_key,
					'value' => $ID,
				),
			),
			'fields'         => 'ids',
		);

		$all_attendees_for_ticket  = new WP_Query( $args );
		$order_ids = $all_attendees_for_ticket->posts;
		if ( empty ( $order_ids ) ) {
			return array();
		}

		$orders = array();
		foreach ( $order_ids as $id ) {

			$order_id = get_post_meta( $id, tribe( 'tickets-plus.commerce.edd' )->attendee_order_key, true );

			$order = edd_get_payment( $order_id );

			//prevent fatal error if no orders
			if ( $order && ! is_wp_error( $order ) ) {
				$orders[ $order->ID ] = $order;
			}
		}

		return $orders;
	}

}
