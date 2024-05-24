<?php

if ( class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' ) || ! class_exists( 'Tribe__Tickets__Tickets' ) ) {
	return;
}

class Tribe__Tickets_Plus__Commerce__EDD__Main extends Tribe__Tickets_Plus__Tickets {
	/**
	 * {@inheritdoc}
	 */
	public $orm_provider = 'edd';

	/**
	 * Value indicating there is no limit on the number of tickets that can be sold.
	 */
	const UNLIMITED = '_unlimited';

	/**
	 * Current version of this plugin
	 */
	const VERSION = '4.5.0.1';
	/**
	 * Min required The Events Calendar version
	 */
	const REQUIRED_TEC_VERSION = '4.6.20';
	/**
	 * Min required Easy Digital Downloads version
	 */
	const REQUIRED_EDD_VERSION = '1.8.3';

	/**
	 * Label used to identify the false "downloadable product" used to
	 * facilitate printing tickets - this will be used as the download
	 * file URL.
	 */
	const TICKET_DOWNLOAD = 'tribe://edd.tickets/print';

	/**
	 * In previous versions of EDD Tickets the printable ticket download
	 * was identifiable by virtue of having an empty string as the file
	 * name.
	 *
	 * We've moved to a more reliable approach, but we still need to
	 * be cognizant of tickets set up with older versions of the plugin.
	 */
	const LEGACY_TICKET_DOWNLOAD = '';

	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @deprecated 4.7 Use $attendee_object variable instead
	 */
	const ATTENDEE_OBJECT = 'tribe_eddticket';

	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 */
	public $attendee_object = 'tribe_eddticket';

	/**
	 * Name of the CPT that holds Orders
	 *
	 * @deprecated 4.7 Use $order_object variable instead
	 */
	const ORDER_OBJECT = 'edd_payment';

	/**
	 * Name of the CPT that holds Orders
	 */
	public $order_object = 'edd_payment';

	/**
	 * Meta key that relates Attendees and Orders.
	 *
	 * @deprecated 4.7 Use $attendee_order_key variable instead
	 */
	const ATTENDEE_ORDER_KEY = '_tribe_eddticket_order';

	/**
	 * Meta key that relates Attendees and Orders.
	 */
	public $attendee_order_key = '_tribe_eddticket_order';

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @deprecated 4.7 Use $attendee_event_key variable instead
	 */
	const ATTENDEE_EVENT_KEY = '_tribe_eddticket_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 */
	public $attendee_event_key = '_tribe_eddticket_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @deprecated 4.7.3 Use $attendee_product_key variable instead
	 */
	const ATTENDEE_PRODUCT_KEY = '_tribe_eddticket_product';
	public $attendee_product_key = '_tribe_eddticket_product';

	/**
	 * Meta key that relates Products and Events
	 * @var string
	 */
	public $event_key = '_tribe_eddticket_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 * @var string
	 */
	public $checkin_key = '_tribe_eddticket_checkedin';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_eddticket_security_code';

	/**
	 * Meta key that holds if an order has tickets (for performance)
	 * @var string
	 */
	public $order_has_tickets = '_tribe_has_tickets';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * Name of the ticket commerce CPT.
	 *
	 * @var string
	 */
	public $ticket_object = 'download';

	/**
	 * Meta key that holds if the attendee has opted out of the front-end listing
	 *
	 * @deprecated 4.7 Use $attendee_optout_key variable instead
	 *
	 * @var string
	 */
	const ATTENDEE_OPTOUT_KEY = '_tribe_eddticket_attendee_optout';

	/**
	 * Meta key that holds if the attendee has opted out of the front-end listing
	 *
	 * @var string
	 */
	public $attendee_optout_key = '_tribe_eddticket_attendee_optout';

	/**
	 * Holds an instance of the Tribe__Tickets_Plus__Commerce__EDD__Email class
	 * @var Tribe__Tickets_Plus__Commerce__EDD__Email
	 */
	private $mailer = null;

	/**
	 * Helps to manage stock for EDD Tickets sales.
	 *
	 * @var Tribe__Tickets_Plus__Commerce__EDD__Stock_Control
	 */
	protected $stock_control;

	/**
	 * @var Tribe__Tickets_Plus__Commerce__EDD__Global_Stock
	 */
	protected static $global_stock;

	/**
	 * Instance of Tribe__Tickets_Plus__Commerce__EDD__Meta
	 */
	private static $meta;

	/**
	 * Class constructor
	 */
	public function __construct() {
		/* Set up parent vars */
		$this->plugin_name   = $this->pluginName = _x( 'Easy Digital Downloads', 'ticket provider', 'event-tickets-plus' );
		$this->plugin_slug   = $this->pluginSlug = 'eddtickets';
		$this->plugin_path   = $this->pluginPath = trailingslashit( EVENT_TICKETS_PLUS_DIR );
		$this->plugin_dir    = $this->pluginDir = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url    = $this->pluginUrl = trailingslashit( plugins_url( $this->plugin_dir ) );
		$this->mailer        = new Tribe__Tickets_Plus__Commerce__EDD__Email;
		$this->stock_control = new Tribe__Tickets_Plus__Commerce__EDD__Stock_Control();

		parent::__construct();

		$this->bind_implementations();
		$this->hooks();
		$this->orders_report();
		$this->meta();
		$this->global_stock();
	}

	/**
	 * Binds implementations that are specific to EDD
	 *
	 * @since 4.9
	 */
	public function bind_implementations() {
		tribe_singleton( 'tickets-plus.commerce.edd.cart', 'Tribe__Tickets_Plus__Commerce__EDD__Cart', array( 'hook' ) );
		tribe( 'tickets-plus.commerce.edd.cart' );
	}

	/**
	 * Registers all actions/filters
	 */
	public function hooks() {

		add_action( 'init', array( $this, 'register_eddtickets_type' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'edd_meta_box' ) );
		add_action( 'before_delete_post', array( $this, 'handle_delete_post' ) );
		add_action( 'edd_complete_purchase', array( $this, 'generate_tickets' ), 12 );
		add_action( 'pre_get_posts', array( $this, 'hide_tickets_from_shop' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_ticket_reports' ) );
		add_action( 'edd_cart_footer_buttons', '__return_true' );
		add_action( 'edd_before_checkout_cart', array( $this, 'pre_checkout_errors' ) );
		add_action( 'edd_checkout_error_checks', array( $this, 'checkout_errors' ) );
		add_action( 'template_redirect', array( $this, 'render_ticket_print_view' ), 10, 2 );

		add_action( 'tribe_tickets_attendees_page_inside', array( $this, 'render_tabbed_view' ) );

		add_filter( 'edd_url_token_allowed_params', array( $this, 'add_allowed_param' ) );
		add_filter( 'edd_purchase_receipt', array( $this, 'add_tickets_msg_to_email' ), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'hijack_ticket_link' ), 10, 4 );
		add_filter( 'edd_item_quantities_enabled', '__return_true' );
		add_filter( 'edd_download_quantity_disabled', [ $this, 'maybe_disable_download_quantity' ], 10, 2 );
		add_action( 'edd_checkout_cart_item_title_after', [ $this, 'cart_item_title_after' ], 10, 2 );
		add_filter( 'edd_download_files', [ $this, 'ticket_downloads' ], 10, 2 );
		add_filter( 'edd_download_file_url_args', [ $this, 'print_ticket_url' ], 10 );

		add_filter( 'edd_add_to_cart_item', [ $this, 'set_attendee_optout_choice' ], 10 );
		add_filter( 'tribe_tickets_settings_post_types', [ $this, 'exclude_product_post_type' ] );
		add_action( 'tribe_events_tickets_metabox_edit_advanced', [ $this, 'do_metabox_advanced_options' ], 10, 2 );
		add_filter( 'tribe_tickets_get_default_module', [ $this, 'override_default_module' ], 10, 2 );

		add_filter( 'tribe_tickets_get_modules', [ $this, 'maybe_remove_as_active_module' ] );

		add_action( 'eddtickets_checkin', [ $this, 'purge_attendees_transient' ] );
		add_action( 'eddtickets_uncheckin', [ $this, 'purge_attendees_transient' ] );

		add_filter( 'tribe_attendee_registration_form_classes', [ $this, 'tribe_attendee_registration_form_class' ] );
		add_filter( 'tribe_attendee_registration_cart_provider', [ $this, 'tribe_attendee_registration_cart_provider' ], 10, 2 );

		add_action( 'edd_purchase_form_before_submit', [ $this, 'add_checkout_links' ] );

		add_filter( 'tribe_tickets_cart_urls', [ $this, 'add_cart_url' ] );
		add_filter( 'tribe_tickets_checkout_urls', [ $this, 'add_checkout_url' ] );
	}

	/**
	 * If Easy Digital Downloads is not active, remove EDD Tickets as an active provider.
	 *
	 * Protects against running code for past EDD Tickets attendees and tickets after EDD is deactivated.
	 *
	 * @see   \Tribe__Tickets__Tickets::modules()
	 *
	 * @since 4.12.0
	 *
	 * @param array $active_modules The array of active provider modules.
	 *
	 * @return array
	 */
	public function maybe_remove_as_active_module( $active_modules ) {
		if ( ! function_exists( 'EDD' ) ) {
			unset( $active_modules[ $this->class_name ] );
		}

		return $active_modules;
	}

	/**
	 * Return whether we're currently on the checkout page.
	 *
	 * @since 4.9
	 *
	 * @return bool
	 */
	public function is_checkout_page() {
		return edd_is_checkout();
	}

	/**
	 * Provides a copy of the global stock integration object.
	 *
	 * @since 4.1
	 *
	 * @return Tribe__Tickets_Plus__Commerce__EDD__Global_Stock
	 */
	public function global_stock() {
		if ( ! self::$global_stock ) {
			self::$global_stock = new Tribe__Tickets_Plus__Commerce__EDD__Global_Stock;
		}

		return self::$global_stock;
	}

	/**
	 * Indicates if global stock support is enabled (for Easy Digital Downloads the
	 * default is true).
	 *
	 * @return bool
	 */
	public function supports_global_stock() {
		/**
		 * Allows the declaration of global stock support for Easy Digital Downloads
		 * tickets to be overridden.
		 *
		 * @param bool $enable_global_stock_support
		 */
		return (bool) apply_filters( 'tribe_tickets_edd_enable_global_stock', true );
	}

	/**
	 * Configure the option optout from attendees
	 *
	 * @param array $item Cart Item
	 */
	public function set_attendee_optout_choice( $item ) {
		if ( isset( $item['options'][ $this->attendee_optout_key ] ) && ! isset( $_POST[ 'optout_' . $item['id'] ] ) ) {
			return $item;
		}

		$is_ticket = get_post_meta( $item['id'], $this->event_key, true );

		if ( ! $is_ticket ) {
			return $item;
		}

		$optout = isset( $_POST[ 'optout_' . $item['id'] ] ) ? $_POST[ 'optout_' . $item['id'] ] : false;
		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
		$optout = $optout ? 'yes' : 'no';

		$item['options'][ $this->attendee_optout_key ] = $optout;

		return $item;
	}

	/**
	 * Custom meta integration object accessor method
	 *
	 * @since 4.1
	 *
	 * @return Tribe__Tickets_Plus__Commerce__EDD__Meta
	 */
	public function meta() {
		if ( ! self::$meta ) {
			self::$meta = new Tribe__Tickets_Plus__Commerce__EDD__Meta;
		}

		return self::$meta;
	}

	/**
	 * When a user deletes a ticket (product) we want to store
	 * a copy of the product name, so we can show it in the
	 * attendee list for an event.
	 *
	 * @param int|WP_Post $post
	 *
	 * @return bool|void
	 */
	public function handle_delete_post( $post ) {
		if ( is_numeric( $post ) ) {
			$post = WP_Post::get_instance( $post );
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		// Bail if it's not a Product
		if ( $this->ticket_object !== $post->post_type ) {
			return;
		}

		// Bail if the product is not a Ticket
		$event = get_post_meta( $post->ID, $this->event_key, true );

		if ( ! $event ) {
			return;
		}

		$attendees = $this->get_attendees_by_id( $event );

		foreach ( (array) $attendees as $attendee ) {
			if ( $attendee['product_id'] == $post->ID ) {
				update_post_meta( $attendee['attendee_id'], $this->deleted_product, esc_html( $post->post_title ) );
			}
		}
	}

	/**
	 * Register our custom post type
	 */
	public function register_eddtickets_type() {

		$args = [
			'label'           => esc_html( tribe_get_ticket_label_plural( 'edd_post_type_label' ) ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => true,
		];

		register_post_type( $this->attendee_object, $args );
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 *
	 * @param int $order_id
	 */
	public function generate_tickets( $order_id ) {

		/**
		 * Hook before WooCommerce Tickets are generated in Event Tickets Plus.
		 *
		 * @since 4.10.4
		 *
		 * @param int $order_id The order ID.
		 */
		do_action( 'tribe_tickets_plus_woo_before_generate_tickets', $order_id );

		// Bail if we already generated the info for this order
		$done = get_post_meta( $order_id, $this->order_has_tickets, true );

		if ( ! empty( $done ) ) {
			return;
		}

		$has_tickets = false;
		// Get the items purchased in this order

		$order_items = edd_get_payment_meta_cart_details( $order_id );

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		// Iterate over each product
		foreach ( (array) $order_items as $item ) {
			$has_tickets |= (bool) $this->generate_attendees_for_order_entry( $order_id, $item );
		}

		if ( $has_tickets ) {
			update_post_meta( $order_id, $this->order_has_tickets, '1' );

			// Send the email to the user
			do_action( 'eddtickets-send-tickets-email', $order_id );

			/**
			 * An Action fired when an email is sent after successful generation of attendees for an order.
			 *
			 * @since 4.11.0
			 *
			 * @param int $order_id The EDD order number.
			 */
			do_action( 'event_tickets_plus_edd_send_ticket_emails', $order_id );
		}
	}

	/**
	 * Generally speaking we want to hide the ticket products from the "storefront" and
	 * only expose them via the ticket form on single event pages.
	 *
	 * @param string $query
	 */
	public function hide_tickets_from_shop( $query ) {
		// Exceptions: don't interfere in the admin environment, for EDD API requests, etc
		if ( is_admin() ) return;
		if ( defined( 'EDD_DOING_API' ) && EDD_DOING_API ) return;
		if ( empty( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] != $this->ticket_object ) return;
		if ( ! empty( $query->query_vars['meta_key'] ) && $query->query_vars['meta_key'] == $this->event_key ) return;

		// Otherwise, build a list of post IDs representing tickets to ignore
		if ( ! $query->is_singular ) {
			$query->set( 'post__not_in', $this->get_all_tickets_ids() );
		}
	}

	/**
	 * Orders report object accessor method
	 *
	 * @since 4.10
	 *
	 * @return Tribe__Tickets_Plus__Commerce__EDD__Orders__Report
	 */
	public function orders_report() {
		static $report;

		if ( ! $report instanceof self ) {
			$report = new Tribe__Tickets_Plus__Commerce__EDD__Orders__Report;
		}

		return $report;
	}


	/**
	 * Generates the validation code that will be printed in the ticket.
	 * It purpose is to be used to validate the ticket at the door of an event.
	 *
	 * @param int $order_id
	 * @param int $attendee_id
	 *
	 * @return string
	 */
	public function generate_security_code( $order_id, $attendee_id = '' ) {
		return substr( md5( $order_id . '_' . $attendee_id ), 0, 10 );
	}

	/**
	 * Where the cart form should lead the users into
	 *
	 * @since  4.8.1
	 *
	 * @return string
	 */
	public function get_cart_url() {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.edd.cart' );

		return $cart->get_cart_url();
	}

	/**
	 * Get the checkout URL.
	 *
	 * @since 4.11.0
	 *
	 * @return string Checkout URL.
	 */
	public function get_checkout_url() {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.edd.cart' );

		return $cart->get_checkout_url();
	}

	/**
	 * Adds cart url to list used for localized variables.
	 *
	 * @since 4.11.0
	 *
	 * @param array $urls The original array.
	 * @return array
	 */
	public function add_cart_url( $urls = [] ) {
		$cart_url = $this->get_cart_url();
		$urls[ __CLASS__ ]   = $cart_url;

		return $urls;
	}

	/**
	 * Adds checkout url to list used for localized variables.
	 *
	 * @since 4.11.0
	 *
	 * @param array $urls The original array.
	 * @return array
	 */
	public function add_checkout_url( $urls = [] ) {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.edd.cart' );

		$urls[ __CLASS__ ] = $cart->get_checkout_url();

		return $urls;
	}

	/**
	 * Adds a message to EDD's order email confirmation.
	 *
	 * @param string $email_body
	 * @param int    $payment_id
	 * @param array  $unused_payment_data
	 */
	public function add_tickets_msg_to_email( $email_body, $payment_id, $unused_payment_data ) {

		//if( did_action( 'eddtickets-send-tickets-email' ) )
		//return $email_body;

		$order_items = edd_get_payment_meta_downloads( $payment_id );

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return $email_body;
		}

		$has_tickets = false;

		// Iterate over each product
		foreach ( (array) $order_items as $item ) {

			$product_id = isset( $item['id'] ) ? $item['id'] : false;

			// Get the event this tickets is for
			$post_id = get_post_meta( $product_id, $this->event_key, true );

			if ( ! empty( $post_id ) ) {
				$has_tickets = true;
				break;
			}
		}
		if ( ! $has_tickets )
			return $email_body;

		/*$message = esc_html( sprintf( __( "You'll receive your notification in another email.", 'event-tickets-plus' ), tribe_get_ticket_label_plural_lowercase( 'edd_email_confirmation' ) ) );*/
		$message = '';
		return $email_body . '<br/>' . apply_filters( 'eddtickets_email_message', $message );
	}

	/**
	 * Saves a given ticket (EDDCommerce product)
	 *
	 * @param int                           $post_id  Post ID.
	 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket object.
	 * @param array                         $raw_data Ticket data.
	 *
	 * @return bool
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = [] ) {
		// assume we are updating until we find out otherwise
		$save_type = 'update';

		if ( empty( $ticket->ID ) ) {
			$save_type = 'create';

			/* Create main product post */
			$args = [
				'post_status'  => 'publish',
				'post_type'    => $this->ticket_object,
				'post_author'  => get_current_user_id(),
				'post_content' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => tribe_get_request_var( 'menu_order', -1 ),
			];

			$ticket->ID = wp_insert_post( $args );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $post_id );

		} else {
			$args = [
				'ID'           => $ticket->ID,
				'post_content' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => $ticket->menu_order,
			];

			$ticket->ID = wp_update_post( $args );
		}

		if ( ! $ticket->ID ) {
			return false;
		}

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Updates if we should show Description
		$ticket->show_description = isset( $ticket->show_description ) && tribe_is_truthy( $ticket->show_description ) ? 'yes' : 'no';

		update_post_meta( $ticket->ID, $tickets_handler->key_show_description, $ticket->show_description );

		// Fetches all Ticket Form Datas
		$data = Tribe__Utils__Array::get( $raw_data, 'tribe-ticket', [] );

		// Before merging with defaults check the stock data provided
		$stock_provided = ! empty( $data['stock'] ) && '' !== trim( $data['stock'] );

		// By default it is an Unlimited Stock without Global stock
		$defaults = [
			'mode' => 'own',
		];

		$data = wp_parse_args( $data, $defaults );

		// Sanitize Mode
		$data['mode'] = filter_var( $data['mode'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

		// Fetch the Global stock Instance for this Event
		/**
		 * @var Tribe__Tickets__Global_Stock $event_stock
		 */
		$event_stock = new Tribe__Tickets__Global_Stock( $post_id );

		// Only need to do this if we haven't already set one - they shouldn't be able to edit it from here otherwise
		if ( ! $event_stock->is_enabled() ) {
			if ( isset( $data['event_capacity'] ) ) {
				$data['event_capacity'] = trim( filter_var( $data['event_capacity'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) );


				// If empty we need to modify to -1
				if ( '' === $data['event_capacity'] ) {
					$data['event_capacity'] = -1;
				}

				// Makes sure it's an Int after this point
				$data['event_capacity'] = (int) $data['event_capacity'];

				$tickets_handler->remove_hooks();

				// We need to update event post meta - if we've set a global stock
				$event_stock->enable();
				$event_stock->set_stock_level( $data['event_capacity'], true );

				// Update Event capacity
				update_post_meta( $post_id, $tickets_handler->key_capacity, $data['event_capacity'] );
				update_post_meta( $post_id, $event_stock::GLOBAL_STOCK_ENABLED, 1 );

				$tickets_handler->add_hooks();
			}
		} else {
			// If the Global Stock is configured we pull it from the Event
			$global_capacity = tribe_tickets_get_capacity( $post_id );

			if ( ! empty( $data['event_capacity'] ) && $data['event_capacity'] !== $global_capacity ) {
				// Update stock level with $data['event_capacity'].
				$event_stock->set_stock_level( $data['event_capacity'], true );
			} else {
				// Set $data['event_capacity'] with what we know.
				$data['event_capacity'] = $global_capacity;
			}
		}

		// Default Capacity will be 0
		$default_capacity   = 0;
		$is_capacity_passed = true;

		// If we have Event Global stock we fetch that Stock
		if ( $event_stock->is_enabled() ) {
			$default_capacity = $data['event_capacity'];
		}

		// Fetch capacity field, if we don't have it use default (defined above)
		$data['capacity'] = trim( Tribe__Utils__Array::get( $data, 'capacity', $default_capacity ) );

		// If empty we need to modify to the default
		if ( '' !== $data['capacity'] ) {
			// Makes sure it's an Int after this point
			$data['capacity'] = (int) $data['capacity'];

			// The only available value lower than zero is -1 which is unlimited
			if ( 0 > $data['capacity'] ) {
				$data['capacity'] = -1;
			}

			$default_capacity = $data['capacity'];
		}

		// Fetch the stock if defined, otherwise use Capacity field
		$data['stock'] = trim( Tribe__Utils__Array::get( $data, 'stock', $default_capacity ) );

		// If empty we need to modify to what every capacity was
		if ( '' === $data['stock'] ) {
			$data['stock'] = $default_capacity;
		}

		// Makes sure it's an Int after this point
		$data['stock'] = (int) $data['stock'];

		// The only available value lower than zero is -1 which is unlimited
		if ( 0 > $data['stock'] ) {
			$data['stock'] = -1;
		}

		$mode = isset( $data['mode'] ) ? $data['mode'] : 'own';

		if ( '' !== $mode ) {
			if ( 'update' === $save_type ) {
				$totals         = $tickets_handler->get_ticket_totals( $ticket->ID );
				$data['stock'] -= $totals['pending'] + $totals['sold'];
			}

			// In here is safe to check because we don't have unlimited = -1
			$status = ( 0 < $data['stock'] ) ? 'instock' : 'outofstock';

			update_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, $mode );
			update_post_meta( $ticket->ID, '_stock', $data['stock'] );
			update_post_meta( $ticket->ID, '_stock_status', $status );
			update_post_meta( $ticket->ID, '_backorders', 'no' );
			update_post_meta( $ticket->ID, '_manage_stock', 'yes' );

			// Prevent Ticket Capacity from going higher then Event Capacity
			if (
				$event_stock->is_enabled()
				&& Tribe__Tickets__Global_Stock::OWN_STOCK_MODE !== $mode
				&& (
					'' === $data['capacity']
					|| $data['event_capacity'] < $data['capacity']
				)
			) {
				$data['capacity'] = $data['event_capacity'];
			}
		} else {
			// Unlimited Tickets
			// Besides setting _manage_stock to "no" we should remove the associated stock fields if set previously
			update_post_meta( $ticket->ID, '_manage_stock', 'no' );
			delete_post_meta( $ticket->ID, '_stock_status' );
			delete_post_meta( $ticket->ID, '_stock' );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE );

			// Set Capacity -1 when we don't have a stock mode, which means unlimited
			$data['capacity'] = -1;
		}

		if ( '' !== $data['capacity'] ) {
			// Update Ticket capacity
			update_post_meta( $ticket->ID, $tickets_handler->key_capacity, $data['capacity'] );
		}

		update_post_meta( $ticket->ID, 'ticket_price', $ticket->price );
		update_post_meta( $ticket->ID, 'edd_price', $ticket->price );

		if ( ! empty( $raw_data['ticket_sku'] ) ) {
			update_post_meta( $ticket->ID, '_sku', $raw_data['ticket_sku'] );
		}

		if ( ! empty( $raw_data['ticket_start_date'] ) ) {
			$start_date = Tribe__Date_Utils::maybe_format_from_datepicker( $raw_data['ticket_start_date'] );

			if ( isset( $raw_data['ticket_start_time'] ) ) {
				$start_date .= ' ' . $raw_data['ticket_start_time'];
			}

			$ticket->start_date  = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $start_date ) );
			$previous_start_date = get_post_meta( $ticket->ID, $tickets_handler->key_start_date, true );

			// Only update when we are modifying
			if ( $ticket->start_date !== $previous_start_date ) {
				update_post_meta( $ticket->ID, $tickets_handler->key_start_date, $ticket->start_date );
			}
		} else {
			delete_post_meta( $ticket->ID, $tickets_handler->key_start_date );
		}

		if ( ! empty( $raw_data['ticket_end_date'] ) ) {
			$end_date = Tribe__Date_Utils::maybe_format_from_datepicker( $raw_data['ticket_end_date'] );

			if ( isset( $raw_data['ticket_end_time'] ) ) {
				$end_date .= ' ' . $raw_data['ticket_end_time'];
			}

			$ticket->end_date  = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $end_date ) );
			$previous_end_date = get_post_meta( $ticket->ID, $tickets_handler->key_end_date, true );

			// Only update when we are modifying
			if ( $ticket->end_date !== $previous_end_date ) {
				update_post_meta( $ticket->ID, $tickets_handler->key_end_date, $ticket->end_date );
			}
		} else {
			delete_post_meta( $ticket->ID, '_ticket_end_date' );
		}

		wp_set_object_terms( $ticket->ID, 'Ticket', 'download_category', true );

		tribe( 'tickets.version' )->update( $ticket->ID );

		/**
		 * Generic action fired after saving a ticket (by type)
		 *
		 * @since 4.7
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_' . $save_type . '_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		/**
		 * Generic action fired after saving a ticket
		 *
		 * @since 4.7
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_save_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		return $ticket->ID;
	}

	/**
	 * Deletes a ticket.
	 *
	 * Note that the total sales/purchases figure maintained by EDD is not adjusted on the
	 * basis that deleting an attendee does not mean the sale didn't go through; this is
	 * a change in behaviour from the 4.0.x releases.
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $post_id, $ticket_id ) {

		// Ensure we know the event and product IDs (the event ID may not have been passed in)
		if ( empty( $post_id ) ) {
			$post_id = get_post_meta( $ticket_id, self::ATTENDEE_EVENT_KEY, true );
		}
		$product_id = get_post_meta( $ticket_id, self::ATTENDEE_PRODUCT_KEY, true );

		/**
		 * Use this Filter to choose if you want to trash tickets instead
		 * of deleting them directly
		 *
		 * @param bool   false
		 * @param int $ticket_id
		 */
		if ( apply_filters( 'tribe_tickets_plus_trash_ticket', true, $ticket_id ) ) {
			// Move it to the trash
			$delete = wp_trash_post( $ticket_id );
		} else {
			// Try to kill the actual ticket/attendee post
			$delete = wp_delete_post( $ticket_id, true );
		}

		if ( is_wp_error( $delete ) || ! isset( $delete->ID ) ) {
			return false;
		}

		Tribe__Tickets__Attendance::instance( $post_id )->increment_deleted_attendees_count();

		$this->clear_attendees_cache( $post_id );

		$has_shared_tickets = 0 !== count( tribe( 'tickets.handler' )->get_event_shared_tickets( $post_id ) );

		if ( ! $has_shared_tickets ) {
			tribe_tickets_delete_capacity( $post_id );
		}

		do_action( 'eddtickets_ticket_deleted', $ticket_id, $post_id, $product_id );

		return true;
	}

	/**
	 * Replaces the link to the product with a link to the Event in the
	 * order confirmation page.
	 *
	 * @param string  $post_link
	 * @param WP_Post $post
	 * @param         $unused_leavename
	 * @param         $unused_sample
	 *
	 * @return string
	 */
	public function hijack_ticket_link( $post_link, $post, $unused_leavename, $unused_sample ) {

		if ( $post->post_type === $this->ticket_object ) {
			$event = get_post_meta( $post->ID, $this->event_key, true );
			if ( ! empty( $event ) ) {
				$post_link = get_permalink( $event );
			}
		}

		return $post_link;
	}

	/**
	 * Shows the tickets form in the front end
	 *
	 * @param string $content HTML string
	 *
	 * @return void
	 */
	public function front_end_tickets_form( $content ) {
		$post = $GLOBALS['post'];

		// For recurring events (child instances only), default to loading tickets for the parent event
		if ( ! empty( $post->post_parent ) && function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $post->ID ) ) {
			$post = get_post( $post->post_parent );
		}

		$tickets = $this->get_tickets( $post->ID );

		foreach( $tickets as $index => $ticket ) {
			if ( __CLASS__ !== $ticket->provider_class ) {
				unset( $tickets[ $index ] );
			}
		}

		if ( empty( $tickets ) ) {
			return;
		}

		// Check to see if all available tickets' end-sale dates have passed, in which case no form
		// should show on the front-end.
		$expired_tickets = 0;

		foreach ( $tickets as $ticket ) {
			if ( ! $ticket->date_in_range() ) {
				$expired_tickets++;
			}
		}

		$must_login = ! is_user_logged_in() && $this->login_required();

		if ( $expired_tickets >= count( $tickets ) ) {
			/**
			 * Allow to hook into the FE form of the tickets if tickets has already expired. If the action used the
			 * second value for tickets make sure to use a callback instead of an inline call to the method such as:
			 *
			 * Example:
			 *
			 * add_action( 'tribe_tickets_expired_front_end_ticket_form', function( $must_login, $tickets ) {
			 *  Tribe__Tickets_Plus__Attendees_List::instance()->render();
			 * }, 10, 2 );
			 *
			 * If the tickets are not required to be used on the view you an use instead.
			 *
			 * add_action( 'tribe_tickets_expired_front_end_ticket_form', array( Tribe__Tickets_Plus__Attendees_List::instance(), 'render' ) );
			 *
			 * @since 4.7.3
			 *
			 * @param boolean $must_login
			 * @param array   $tickets
			 */
			do_action( 'tribe_tickets_expired_front_end_ticket_form', $must_login, $tickets );
		}

		$global_stock         = new Tribe__Tickets__Global_Stock( $post->ID );
		$global_stock_enabled = $global_stock->is_enabled();

		/**
		 * Allow for the addition of content (namely the "Who's Attening?" list) above the ticket form.
		 *
		 * @since 4.5.4
		 */
		do_action( 'tribe_tickets_before_front_end_ticket_form' );

		Tribe__Tickets__Tickets_View::instance()->get_tickets_block( $post->ID );
	}

	/**
	 * Grabs the submitted front end tickets form and adds the products to the cart.
	 */
	public function process_front_end_tickets_form() {
		parent::process_front_end_tickets_form();

		if (
			empty( $_REQUEST['eddtickets_process'] )
			|| intval( $_REQUEST['eddtickets_process'] ) !== 1
			|| empty( $_POST['product_id'] )
		) {
			return;
		}

		// Add each ticket product to the cart
		foreach ( (array) $_POST['product_id'] as $product_id ) {
			$quantity = isset( $_POST[ 'quantity_' . $product_id ] ) ? (int) $_POST[ 'quantity_' . $product_id ] : 0;
			if ( $quantity > 0 ) $this->add_ticket_to_cart( $product_id, $quantity );
		}

		$tickets_in_cart = tribe( 'tickets-plus.commerce.edd.cart' )->get_tickets_in_cart();
		$cart_has_meta   = Tribe__Tickets_Plus__Main::instance()->meta()->cart_has_meta( $tickets_in_cart );

		if ( $tickets_in_cart && $cart_has_meta ) {
			$url = add_query_arg( 'provider', $this->attendee_object, tribe( 'tickets.attendee_registration' )->get_url() );
			wp_safe_redirect( $url );
			tribe_exit();
		}

		// To minimize accidental re-submissions, redirect back to self
		wp_safe_redirect( edd_get_checkout_uri() );
		edd_die();
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will add the quantity to the existing quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @see bug #28917
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $quantity  Ticket quantity.
	 */
	public function add_ticket_to_cart( $ticket_id, $quantity ) {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.edd.cart' );

		$existing_quantity = edd_get_cart_item_quantity( $ticket_id );

		$quantity += $existing_quantity;

		return $cart->add_ticket_to_cart( $ticket_id, $quantity );
	}

	/**
	 * Get the URL to the ticket reports
	 * As of 4.6 we reversed the params and deprecated $event_id as it was never used
	 *
	 * @param deprecated $event_id_deprecated
	 * @param int        $ticket_id
	 *
	 * @return null|Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket_reports_link( $event_id_deprecated, $ticket_id ) {
		if ( ! empty( $event_id_deprecated ) ) {
			_deprecated_argument( __METHOD__, '4.6' );
		}
		if ( empty( $ticket_id ) ) {
			return '';
		}

		$query = array(
			'page'      => 'edd-reports',
			'view'      => 'sales',
			'post_type' => $this->ticket_object,
			'tab'       => 'logs',
			'download'  => $ticket_id,
		);

		return add_query_arg( $query, admin_url( 'edit.php' ) );
	}

	/**
	 * Gets an individual ticket
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 *
	 * @return null|Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket( $post_id, $ticket_id ) {
		$product = edd_get_download( $ticket_id );

		if ( ! $product ) {
			return null;
		}

		$return = new Tribe__Tickets__Ticket_Object();

		$purchased_statuses = tribe( 'tickets.status' )->get_statuses_by_action( 'count_completed', 'edd' );
		$purchased     = $this->stock_control->get_purchased_inventory( $ticket_id, $purchased_statuses );
		$pending       = $this->stock_control->count_incomplete_order_items( $ticket_id );
		$refunded      = $this->stock_control->count_refunded_order_items( $ticket_id );
		$product_stock = $this->get_stock_for_product( $product );
		$stock         = ( '' === $product_stock ) ? Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK : $product_stock;

		$return->description      = $product->post_content;
		$return->frontend_link    = get_permalink( $ticket_id );
		$return->ID               = $ticket_id;
		$return->name             = $product->post_title;
		$return->menu_order       = $product->menu_order;
		$return->price            = edd_get_download_price( $product->ID );
		$return->provider_class   = get_class( $this );
		$return->admin_link       = admin_url( sprintf( get_post_type_object( $product->post_type )->_edit_link . '&action=edit', $ticket_id ) );
		$return->report_link      = $this->get_ticket_reports_link( null, $ticket_id );
		$return->show_description = $return->show_description();
		$return->capacity         = tribe_tickets_get_capacity( $ticket_id );
		$return->sku              = get_post_meta( $ticket_id, '_sku', true );

		$start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$end_date   = get_post_meta( $ticket_id, '_ticket_end_date', true );

		if ( ! empty( $start_date ) ) {
			$start_date_unix    = strtotime( $start_date );
			$return->start_date = Tribe__Date_Utils::date_only( $start_date_unix, true );
			$return->start_time = Tribe__Date_Utils::time_only( $start_date_unix );
		}

		if ( ! empty( $end_date ) ) {
			$end_date_unix    = strtotime( $end_date );
			$return->end_date = Tribe__Date_Utils::date_only( $end_date_unix, true );
			$return->end_time = Tribe__Date_Utils::time_only( $end_date_unix );
		}

		$return->manage_stock( is_numeric( $product_stock ) );
		$return->global_stock_mode( get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) );
		$capped = get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );

		if ( '' !== $capped ) {
			$return->global_stock_cap( $capped );
		}

		$return->stock( $stock );
		$return->qty_sold( $purchased );
		$return->qty_pending( $pending );
		$return->qty_refunded( $refunded );

		/**
		 * Use this Filter to change any information you want about this ticket
		 *
		 * @param object $ticket
		 * @param int    $post_id
		 * @param int    $ticket_id
		 */
		$ticket = apply_filters( 'tribe_tickets_plus_edd_get_ticket', $return, $post_id, $ticket_id );

		return $ticket;
	}

	/**
	 * This method is used to lazily set and correct stock levels for tickets which
	 * draw on the global event inventory.
	 *
	 * It's required because, currently, there is a discrepancy between how individual
	 * tickets are created and saved (ie, via ajax) and how event-wide settings such as
	 * global stock are saved - which means a ticket may be saved before the global
	 * stock level and save_tickets() will set the ticket inventory to zero. To avoid
	 * the out-of-stock issues that might otherwise result, we lazily correct this
	 * once the global stock level is known.
	 *
	 * @param int $existing_stock
	 * @param int $post_id
	 * @param int $ticket_id
	 *
	 * @return int
	 */
	protected function set_stock_level_for_global_stock_tickets( $existing_stock, $post_id, $ticket_id ) {
		$global_stock = new Tribe__Tickets__Global_Stock( $post_id );

		// If this event does not have a global stock then do not modify the existing stock level
		if ( ! $global_stock->is_enabled() ) {
			return $existing_stock;
		}

		// If this specific ticket maintains its own independent stock then again do not interfere
		if ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) ) {
			return $existing_stock;
		}

		// Otherwise the ticket stock ought to match the current global stock
		$product_stock = edd_get_download( $ticket_id )->_stock;
		$actual_stock  = ( '' === $product_stock ) ? Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK : $product_stock;
		$global_stock  = $global_stock->get_stock_level();

		// Look out for and correct discrepancies where the actual stock is zero but the global stock is non-zero
		if ( 0 == $actual_stock && 0 < $global_stock ) {
			update_post_meta( $ticket_id, '_stock', $global_stock );
			update_post_meta( $ticket_id, '_stock_status', 'instock' );
		}

		return $global_stock;
	}

	/**
	 * Accepts a reference to a product (either an object or a numeric ID) and
	 * tests to see if it functions as a ticket: if so, the corresponding event
	 * object is returned. If not, boolean false is returned.
	 *
	 * @param object|int $ticket_product
	 *
	 * @return bool|WP_Post
	 */
	public function get_event_for_ticket( $ticket_product ) {
		if ( is_object( $ticket_product ) && isset( $ticket_product->ID ) ) {
			$ticket_product = $ticket_product->ID;
		}

		if ( null === get_post( $ticket_product ) ) {
			return false;
		}

		if ( '' === ( $event = get_post_meta( $ticket_product, $this->event_key, true ) ) ) {
			return false;
		}

		if ( in_array( get_post_type( $event ), Tribe__Tickets__Main::instance()->post_types(), true ) ) {
			return get_post( $event );
		}

		return false;
	}

	/**
	 * Get attendees by id and associated post type
	 * or default to using $post_id
	 *
	 * @param int  $post_id
	 * @param null $post_type
	 *
	 * @return array|mixed
	 */
	public function get_attendees_by_id( $post_id, $post_type = null ) {
		if ( ! $post_type ) {
			$post_type = get_post_type( $post_id );
		}

		switch ( $post_type ) {
			case $this->ticket_object:
				$attendees = $this->get_attendees_by_product_id( $post_id );

				break;
			case $this->attendee_object:
				$attendees = $this->get_all_attendees_by_attendee_id( $post_id );

				break;
			case $this->order_object:
				$attendees = $this->get_attendees_by_order_id( $post_id );

				break;
			default:
				$attendees = $this->get_attendees_by_post_id( $post_id );

				break;
		}

		return $attendees;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_attendee( $attendee, $post_id = 0 ) {
		if ( is_numeric( $attendee ) ) {
			$attendee = get_post( $attendee );
		}

		if ( ! $attendee instanceof WP_Post || $this->attendee_object !== $attendee->post_type ) {
			return false;
		}

		$product_id = get_post_meta( $attendee->ID, $this->attendee_product_key, true );

		if ( empty( $product_id ) ) {
			return false;
		}

		$order_id      = get_post_meta( $attendee->ID, $this->attendee_order_key, true );
		$checkin       = get_post_meta( $attendee->ID, $this->checkin_key, true );
		$security      = get_post_meta( $attendee->ID, $this->security_code, true );
		$optout        = get_post_meta( $attendee->ID, $this->attendee_optout_key, true );
		$user_id       = get_post_meta( $attendee->ID, $this->attendee_user_id, true );
		$product       = get_post( $product_id );
		$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product, true ) . ' ' . __( '(deleted)', 'eddtickets' );

		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );

		$user_info = edd_get_payment_meta_user_info( $order_id );

		$ticket_unique_id = get_post_meta( $attendee->ID, '_unique_id', true );
		$ticket_unique_id = $ticket_unique_id === '' ? $attendee->ID : $ticket_unique_id;

		$meta = '';
		if ( class_exists( 'Tribe__Tickets_Plus__Meta' ) ) {
			$meta = get_post_meta( $attendee->ID, Tribe__Tickets_Plus__Meta::META_KEY, true );

			// Process Meta to include value, slug, and label
			if ( ! empty( $meta ) ) {
				$meta = $this->process_attendee_meta( $product_id, $meta );
			}
		}

		// Add the Attendee Data to the Order data
		$attendee_data = array_merge( $this->get_order_data( $order_id ), array(
			'ticket'        => $product_title,
			'attendee_id'   => $attendee->ID,
			'order_item_id' => '',
			'security'      => $security,
			'product_id'    => $product_id,
			'check_in'      => $checkin,
			'optout'        => $optout,
			'user_id'       => $user_id,
			//'ticket_sent'   => $ticket_sent,

			// Fields for Email Tickets
			'event_id'      => get_post_meta( $attendee->ID, $this->attendee_event_key, true ),
			'ticket_name'   => ! empty( $product ) ? $product->post_title : false,
			'holder_name'   => $this->get_holder_name( $attendee, $user_info ),
			'order_id'      => $order_id,
			'ticket_id'     => $ticket_unique_id,
			'qr_ticket_id'  => $attendee->ID,
			'security_code' => $security,

			// Attendee Meta
			'attendee_meta' => $meta,
		) );

		/**
		 * Allow users to filter the Attendee Data
		 *
		 * @param array An associative array with the Information of the Attendee
		 * @param string What Provider is been used
		 * @param WP_Post Attendee Object
		 * @param int Post ID
		 *
		 */
		$attendee_data = apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'edd', $attendee, $post_id );

		return $attendee_data;
	}

	/**
	 * Get Holder name from existing meta, if possible.
	 *
	 * @param $attendee
	 * @param $user_info
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	protected function get_holder_name( $attendee, $user_info ) {
		if ( empty( $user_info ) ) {
			return null;
		}

		return $user_info['first_name'] . ' ' . $user_info['last_name'];
	}

	/**
	 * Retreive only order related information
	 *
	 *     order_id
	 *     order_status
	 *     order_warning
	 *     purchaser_name
	 *     purchaser_email
	 *     provider
	 *     provider_slug
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function get_order_data( $order_id ) {

		if ( ! tribe_tickets_is_edd_active() ) {
			return array();
		}

		$user_info = edd_get_payment_meta_user_info( $order_id );

		if ( empty( $user_info ) ) {
			return array();
		}

		$name          = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email         = $user_info['email'];
		$order_status  = get_post_field( 'post_status', $order_id );
		$status_label  = edd_get_payment_status( get_post( $order_id ), true );

		// Warning flag for refunded, cancelled, failed, and revoked orders
		$order_warning      = false;
		$warning_statues = tribe( 'tickets.status' )->get_statuses_by_action( 'warning', 'edd' );
		if ( in_array( $order_status, $warning_statues, true ) ) {
			$order_warning = true;
		}

		$data = [
			'order_id'           => $order_id,
			'order_status'       => $status_label,
			'order_status_label' => $status_label,
			'order_warning'      => $order_warning,
			'purchaser_name'     => $name,
			'purchaser_email'    => $email,
			'provider'           => __CLASS__,
			'provider_slug'      => 'edd',
			'purchase_time'      => get_post_time( Tribe__Date_Utils::DBDATETIMEFORMAT, false, $order_id ),
		];

		/**
		 * Allow users to filter the Order Data
		 *
		 * @param array An associative array with the Information of the Order
		 * @param string What Provider is been used
		 * @param int Order ID
		 *
		 */
		$data = apply_filters( 'tribe_tickets_order_data', $data, 'edd', $order_id );

		return $data;
	}

	/**
	 * Marks an attendee as checked in for an event
	 *
	 * Because we must still support our legacy ticket plugins, we cannot change the abstract
	 * checkin() method's signature. However, the QR checkin process needs to move forward
	 * so we get around that problem by leveraging func_get_arg() to pass a second argument.
	 *
	 * It is hacky, but we'll aim to resolve this issue when we end-of-life our legacy ticket plugins
	 * OR write around it in a future major release
	 *
	 * @param int  $attendee_id
	 * @param bool $qr true if from QR checkin process (NOTE: this is a param-less parameter for backward compatibility)
	 *
	 * @return bool
	 */
	public function checkin( $attendee_id, $qr = false ) {
		update_post_meta( $attendee_id, $this->checkin_key, 1 );

		if ( func_num_args() > 1 && $qr = func_get_arg( 1 ) ) {
			update_post_meta( $attendee_id, '_tribe_qr_status', 1 );
		}

		/**
		 * Fires a checkin action
		 *
		 * @deprecated 4.7 Use event_tickets_checkin instead
		 *
		 * @param int       $attendee_id
		 * @param bool|null $qr
		 */
		do_action( 'eddtickets_checkin', $attendee_id, $qr );

		return true;
	}

	/**
	 * Marks an attendee as not checked in for an event
	 *
	 * @param int $attendee_id
	 *
	 * @return bool
	 */
	public function uncheckin( $attendee_id ) {
		parent::uncheckin( $attendee_id );

		/**
		 * Fires an uncheckin action
		 *
		 * @deprecated 4.7 Use event_tickets_uncheckin instead
		 *
		 * @param int $attendee_id
		 */
		do_action( 'eddtickets_uncheckin', $attendee_id );

		return true;
	}

	/**
	 * Remove the Post Transients when a EDD Ticket is Checked In
	 *
	 * @since 4.8.0
	 *
	 * @param  int $attendee_id
	 *
	 * @return void
	 */
	public function purge_attendees_transient( $attendee_id ) {

		$event_id = get_post_meta( $attendee_id, $this->attendee_event_key, true );
		if ( ! $event_id ) {
			return;
		}

		$current_transient = Tribe__Post_Transient::instance()->get( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
		if ( ! $current_transient ) {
			return;
		}

		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE, $current_transient );

	}


	/**
	 * Add the extra options in the admin's new/edit ticket metabox portion that is loaded via ajax
	 * Currently, that includes the sku
	 *
	 * @since 4.6
	 *
	 * @param int $post_id int id of the event post
	 * @param int $ticket_id int (null) id of the ticket
	 */
	public function do_metabox_advanced_options( $post_id, $ticket_id = null ) {
		$provider = __CLASS__;

		echo '<div id="' . sanitize_html_class( $provider ) . '_advanced" class="tribe-dependent" data-depends="#' . sanitize_html_class( $provider ) . '_radio" data-condition-is-checked>';
		if ( ! tribe_is_frontend() ) {
			$this->do_metabox_sku_options( $post_id, $ticket_id );
			$this->do_metabox_ecommerce_links( $post_id, $ticket_id );
		}
		/**
		 * Allows for the insertion of additional content into the ticket edit form - advanced section
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 * @param string the provider class name
		 */
		do_action( 'tribe_events_tickets_metabox_edit_ajax_advanced', $post_id, $provider );

		echo '</div>';
	}

	/**
	 * Add the extra options in the admin's new/edit ticket metabox
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 *
	 * @since 4.6
	 *
	 * @return void
	 */
	public function do_metabox_capacity_options( $post_id, $ticket_id ) {
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		$url               = $stock = $sku = '';
		$global_stock_mode = tribe( 'tickets.handler' )->get_default_capacity_mode();
		$global_stock_cap  = 0;
		$capacity          = null;
		$event_capacity    = null;

		$stock_object = new Tribe__Tickets__Global_Stock( $post_id );

		if ( $stock_object->is_enabled() ) {
			$event_capacity = tribe_tickets_get_capacity( $post_id );
		}

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$stock             = $ticket->managing_stock() ? $ticket->stock() : '';
				$sku               = get_post_meta( $ticket_id, '_sku', true );
				$capacity          = tribe_tickets_get_capacity( $ticket->ID );
				$global_stock_mode = $ticket->global_stock_mode();
				$global_stock_cap  = $ticket->global_stock_cap();
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/edd-metabox-capacity.php';
	}

	/**
	 * Add the sku options in the admin's new/edit ticket metabox
	 *
	 * @since 4.6
	 *
	 * @param int $post_id int id of the event post
	 * @param int $ticket_id int (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_sku_options( $post_id, $ticket_id = null ) {
		$sku                 = '';
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );
			if ( ! empty( $ticket ) ) {
				$sku = get_post_meta( $ticket_id, '_sku', true );
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/edd-metabox-sku.php';
	}

	/**
	 * Add the ecommerce options in the admin's new/edit ticket metabox
	 *
	 * @since 4.6
	 *
	 * @param int $post_id int id of the event post
	 * @param int $ticket_id (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_ecommerce_links( $post_id, $ticket_id = null ) {
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		if ( empty( $ticket_id ) ) {
			$ticket_id = tribe_get_request_var( 'ticket_id' );
		}

		$ticket              = $this->get_ticket( $post_id, $ticket_id );
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

		// set our links for the template
		$edit_href   = get_edit_post_link( $ticket_id, 'admin' );
		$report_href = $this->get_ticket_reports_link( null, $ticket_id );

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/edd-metabox-ecommerce.php';
	}

	/**
	 * Insert a link to the report.
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function get_event_reports_link( $post_id ) {
		$ticket_ids = $this->get_tickets_ids( $post_id );
		if ( empty( $ticket_ids ) ) {
			return '';
		}

		$term = get_term_by( 'name', 'Ticket', 'download_category' );

		ob_start();
		?>

        <small>
            <a href="<?php echo esc_url( admin_url( 'edit.php?view=downloads&post_type=download&page=edd-reports&category=' . $term->term_id . '&event=' . $post_id ) ); ?>"
               id="eddtickets_event_reports"><?php esc_html_e( 'Event sales report', 'event-tickets-plus' ); ?></a>
        </small>

		<?php

		return ob_get_clean();
	}

	/**
	 * Filters the product reports to only show tickets for the specified event
	 *
	 * @param object $query
	 *
	 * @return void
	 */
	public function filter_ticket_reports( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || 'edd-reports' != $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['category'] ) || ! isset( $_GET['event'] ) ) {
			return;
		}

		$query->set( 'meta_query', array(
			array(
				'key'   => $this->event_key,
				'value' => absint( $_GET['event'] ),
			),
		) );
	}

	/**
	 * Registers a metabox in the EDD product edit screen
	 * with a link back to the product related Event.
	 *
	 * @return void
	 */
	public function edd_meta_box() {
		$post_id = get_post_meta( get_the_ID(), $this->event_key, true );

		if ( empty( $post_id ) ) {
			return;
		}

		add_meta_box(
			'eddtickets-linkback',
			__( 'Event', 'event-tickets-plus' ),
			array( $this, 'edd_meta_box_inside' ),
			$this->ticket_object,
			'normal',
			'high'
		);
	}

	/**
	 * Contents for the metabox in the EDD product edit screen
	 * with a link back to the product related Event.
	 *
	 * @return void
	 */
	public function edd_meta_box_inside() {

		$post_id = get_post_meta( get_the_ID(), $this->event_key, true );
		if ( ! empty( $post_id ) ) {
			$text = esc_html( sprintf(
				__( 'This is a %s for the event:', 'event-tickets-plus' ),
				tribe_get_ticket_label_singular_lowercase( 'edd_meta_box' )
			) );

			echo sprintf(
				'%s <a href="%s">%s</a>',
				$text,
				esc_url( get_edit_post_link( $post_id ) ),
				esc_html( get_the_title( $post_id ) )
			);
		}
	}

	/**
	 * Get's the product price html
	 *
	 * @param int|object    $product
	 * @param array|boolean $attendee
	 *
	 * @return string
	 */
	public function get_price_html( $product, $attendee = false ) {

		if ( ! tribe_tickets_is_edd_active() ) {
			return;
		}

		$product_id = $product;

		// Avoid Catchable Fatal on EDD for using product_id as a possible string
		if ( $product instanceof WP_Post || $product instanceof EDD_Download ) {
			$product_id = $product->ID;
		}

		$price_html = edd_price( $product_id, false );

		/**
		 * Allows filtering of the HTML that renders an EDD ticket price.
		 *
		 * @param string               $price_html The HTML of the EDD ticket price.
		 * @param WP_Post|EDD_Download $product The WP_Post or EDD_Download object of the "ticket" product.
		 * @param array|boolean        $attendee Defaults to false. Could be an array of attendee information.
		 */
		return apply_filters( 'eddtickets_ticket_price_html', $price_html, $product, $attendee );
	}

	/**
	 * Get's the product price value
	 *
	 * @since  4.6
	 *
	 * @param  int|object $product
	 *
	 * @return string
	 */
	public function get_price_value( $product ) {
		$product_id = $product;

		// Avoid Catchable Fatal on EDD for using product_id as a possible string
		if ( $product instanceof WP_Post ) {
			$product_id = $product->ID;
		}

		$price = edd_get_download_price( $product_id, false );

		return $price;
	}

	/**
	 * Get an array of IDs of all tickets
	 *
	 * @return array
	 */
	public function get_all_tickets_ids() {
		global $wpdb;
		return $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '" . $this->event_key . "'" );
	}

	/**
	 * Inspects the cart in order to catch out-of-stock issues etc and display them to the customer
	 * before they go on to complete their personal and payment details, etc.
	 *
	 * If this is undesirable or different formatting etc is needed
	 */
	public function pre_checkout_errors() {
		ob_start();
		$this->checkout_errors();
		edd_print_errors();
		echo apply_filters( 'eddtickets_pre_checkout_errors', ob_get_clean() );
	}

	/**
	 * Ensure out of stock tickets cannot be purchased even if they manage to get added to the cart
	 */
	public function checkout_errors() {
		$this->global_stock()->check_stock();

		/**
		 * Run setup to prevent no statuses on ajax checkout.
		 *
		 * @var Tribe__Tickets__Status__Manager $status
		 */
		$status = tribe( 'tickets.status' );
		$status->setup();

		foreach ( (array) edd_get_cart_contents() as $item ) {
			$remaining = $this->stock_control->available_units( $item['id'] );

			// We have to append the item IDs otherwise if we have multiple errors of the same type one will overwrite
			// the other
			if ( ! $remaining ) {
				edd_set_error( 'no_stock_' . $item['id'], sprintf( __( '%s ticket is sold out', 'event-tickets-plus' ), get_the_title( $item['id'] ) ) );
			} elseif (
				self::UNLIMITED !== $remaining
				&& $item['quantity'] > $remaining
			) {
				edd_set_error( 'insufficient_stock_' . $item['id'], sprintf( __( 'Sorry! Only %d tickets remaining for %s', 'event-tickets-plus' ), $remaining, get_the_title( $item['id'] ) ) );
			}
		}
	}

	/**
	 * Disable EDD download quantity changes in checkout
	 *
	 * @since 4.10.2
	 *
	 * @param boolean $ret
	 * @param int $item_id
	 * @return void
	 */
	public function maybe_disable_download_quantity( $ret, $item_id ) {
		$is_ticket = get_post_meta( $item_id, $this->event_key, true );

		// disable quantity manipulation in checkout
		if ( edd_is_checkout() && $is_ticket ) {
			return true;
		}

		return $ret;
	}

	/**
	 * Add quantity to title in EDD checkout
	 *
	 * @since 4.10.2
	 *
	 * @param array $item
	 * @param string $key
	 * @return void
	 */
	public function cart_item_title_after( $item, $key ) {
		if ( ! isset( $item['quantity'] ) || 2 > $item['quantity'] ) {
			return;
		}

		// test if  we have a ticket
		if ( isset(  $item['options']['_tribe_eddticket_attendee_optout'] ) ) {
			?>
			x<strong> <?php echo esc_html( edd_get_cart_item_quantity( $item['id'], $item['options'] ) ); ?></strong>
			<input disabled type="hidden" name="edd-cart-download-<?php echo esc_attr( $key ); ?>-quantity" data-key="<?php echo esc_attr( $key ); ?>" class="edd-item-quantity" value="<?php echo esc_attr( edd_get_cart_item_quantity( $item['id'], $item['options'] ) ); ?>"/>
		<?php
		}
	}

	/**
	 * Trick EDD into thinking the ticket has a download file. If one already exists, we need not add
	 * another.
	 *
	 * @param array $files
	 * @param int   $download_id
	 * @param int   $unused_price_id
	 *
	 * @return array
	 */
	public function ticket_downloads( $files = array(), $download_id = 0, $unused_price_id = null ) {
		// Determine if this is a ticket product or if it already has a download file
		if ( ! get_post_meta( $download_id, $this->event_key, true ) ) {
			return $files;
		}

		if ( ! empty( $files ) ) {
			return $files;
		}

		$files[] = array(
			'name' => __( 'Print Ticket', 'event-tickets-plus' ),
			'file' => self::TICKET_DOWNLOAD,
		);

		return $files;
	}

	/**
	 * Setup the print ticket URL so that a print view is rendered instead of a download file
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function print_ticket_url( $args = array() ) {
		// Determine if this is a ticket product
		if ( ! get_post_meta( $args['download_id'], $this->event_key, true ) ) {
			return $args;
		}

		// Interfere only with the tickets link (thus allowing additional files to be downloaded
		// as part of the purchase)
		if ( ! $this->is_print_ticket_item( $args ) ) {
			return $args;
		}

		$args = array(
			'edd_action'   => 'print_ticket',
			'download_id'  => $args['download_id'],
			'download_key' => $args['download_key'],
			'file'         => self::TICKET_DOWNLOAD,
		);

		return $args;
	}

	/**
	 * @param array $item
	 *
	 * @return bool
	 */
	protected function is_print_ticket_item( $item ) {
		static $download_files = array();

		if ( empty( $download_files ) ) {
			$download_files = edd_get_download_files( $item['download_id'] );
		}

		foreach ( $download_files as $index => $download ) {
			if ( $item['file'] != $index ) continue;
			if ( self::TICKET_DOWNLOAD === $download['file'] ) return true;
			if ( self::LEGACY_TICKET_DOWNLOAD === $download['file'] ) return true;
		}

		return false;
	}

	/**
	 * Render the print ticket view, based on the email template
	 *
	 * @return void
	 */
	public function render_ticket_print_view() {

		// Is this a print-ticket request?
		if ( ! isset( $_GET['eddfile'] ) || ! isset( $_GET['edd_action'] ) || $_GET['edd_action'] !== 'print_ticket' ) {
			return;
		}

		// As of EDD 2.3 a token should be available to help verify if the link is valid
		if ( ! $this->passed_token_validation( $_GET ) ) {
			return;
		}

		// Decompile the eddfile argument into its base components
		$order_parts = array_values( explode( ':', rawurldecode( $_GET['eddfile'] ) ) );

		// We expect there to be at least two components (payment and download IDs)
		if ( count( $order_parts ) < 2 ) {
			return;
		}

		$payment_id = $order_parts[0];
		$attendees  = $this->get_attendees_by_id( $payment_id );

		$content = $this->generate_tickets_email_content( $attendees );
		$content .= '<script>window.onload = function(){ window.print(); }</script>';
		echo $content;
		exit;
	}

	/**
	 * Renders the tabbed view header before the report.
	 *
	 * @since 4.10
	 *
	 * @param Tribe__Tickets__Tickets_Handler $handler
	 */
	public function render_tabbed_view( Tribe__Tickets__Attendees $handler  ) {
		$post = $handler->get_post();

		$has_tickets = count( (array) self::get_tickets( $post->ID ) );
		if ( ! $has_tickets ) {
			return;
		}

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$tabbed_view = new Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Report_Tabbed_View();
		$tabbed_view->register();
	}

	/**
	 * Add edd_action as a possible param in EDD print url
	 *
	 * @param array $params Arrat of allowed params
	 *
	 * @return array
	 */
	public function add_allowed_param( $params ) {
		$params[] = 'edd_action';
		return $params;
	}

	protected function passed_token_validation( array $url_query ) {
		$query = array_map( 'urlencode', $url_query );

		$url = untrailingslashit( home_url() );
		$url = add_query_arg( $query, $url );

		$result = edd_validate_url_token( $url );

		return apply_filters( 'edd_tickets_passed_token_validation', $result );
	}

	/**
	 * @return Tribe__Tickets_Plus__Commerce__EDD__Stock_Control
	 */
	public function stock() {
		return $this->stock_control;
	}

	/**
	 * Excludes EDD product post types from the list of supported post types that Tickets can be attached to
	 *
	 * @since 4.0.5
	 *
	 * @param array $post_types Array of supported post types
	 *
	 * @return array
	 */
	public function exclude_product_post_type( $post_types ) {
		if ( isset( $post_types['download'] ) ) {
			unset( $post_types['download'] );
		}

		return $post_types;
	}

	/**
	 * Sets the default module to EDD and
	 * returns the class name of the default module/provider.
	 *
	 * @since 4.6
	 *
	 * @return string
	 */
	public function override_default_module( $default_module, $modules = array() ) {
		$is_admin             = tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );
		self::$default_module = __CLASS__;

		return $is_admin ? self::$default_module : $default_module;
	}

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return Tribe__Tickets_Plus__Commerce__EDD__Main
	 */
	public static function get_instance() {
		return tribe( 'tickets-plus.commerce.edd' );
	}

	/**
	 * Generates attendees from an order data.
	 *
	 * @since 4.7
	 *
	 * @param int   $order_id
	 * @param array $item
	 *
	 * @return bool `true` if at least one attendee was generated, `false` otherwise
	 */
	public function generate_attendees_for_order_entry( $order_id, array $item = array() ) {
		$product_id = isset( $item['id'] ) ? $item['id'] : false;
		$optout     = 0;

		if ( isset( $item['item_number']['options'][ $this->attendee_optout_key ] ) ) {
			$optout = filter_var( $item['item_number']['options'][ $this->attendee_optout_key ], FILTER_VALIDATE_BOOLEAN );
			$optout = (int) $optout;
		}

		// Get the event this tickets is for
		$post_id = get_post_meta( $product_id, $this->event_key, true );

		if ( empty( $post_id ) ) {
			return false;
		}

		/** @var Tribe__Tickets__Commerce__Currency $currency */
		$currency        = tribe( 'tickets.commerce.currency' );
		$currency_symbol = $currency->get_currency_symbol( $product_id, true );

		// Iterate over all the amount of tickets purchased (for this product)
		$quantity = (int) $item['quantity'];
		for ( $i = 0; $i < $quantity; $i++ ) {

			$attendee = array(
				'post_status' => 'publish',
				'post_title'  => $order_id . ' | ' . $item['name'] . ' | ' . ( $i + 1 ),
				'post_type'   => $this->attendee_object,
				'ping_status' => 'closed',
			);

			// Insert individual ticket purchased
			$attendee_id = wp_insert_post( $attendee );

			update_post_meta( $attendee_id, self::ATTENDEE_PRODUCT_KEY, $product_id );
			update_post_meta( $attendee_id, self::ATTENDEE_ORDER_KEY, $order_id );
			update_post_meta( $attendee_id, self::ATTENDEE_EVENT_KEY, $post_id );
			update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $order_id, $attendee_id ) );
			update_post_meta( $attendee_id, $this->attendee_optout_key, $optout );
			update_post_meta( $attendee_id, '_paid_price', $this->get_price_value( $product_id ) );
			update_post_meta( $attendee_id, '_price_currency_symbol', $currency_symbol );

			// The ID of the customer who paid for the tickets.
			$user_id = get_post_meta( $order_id, '_edd_payment_user_id', true );

			// If no EDD-provided user id is found, set to null. The record_attendee_user_id method will handle it from there.
			if ( empty( $user_id ) ) {
				$user_id = null;
			}

			/**
			 * Easy Digital Downloads specific action fired when an EDD-driven attendee ticket for an event is generated
			 *
			 * @param int $attendee_id ID of attendee ticket
			 * @param int $post_id ID of event
			 * @param int $order_id Easy Digital Downloads order ID
			 * @param int $product_id Easy Digital Downloads product ID
			 */
			do_action( 'event_ticket_edd_attendee_created', $attendee_id, $post_id, $order_id, $product_id );

			/**
			 * Action fired when an attendee ticket is generated
			 *
			 * @param int $attendee_id ID of attendee ticket
			 * @param int $order EDD order ID
			 * @param int $product_id Product ID attendee is "purchasing"
			 * @param int $order_attendee_id Attendee # for order
			 */
			do_action( 'event_tickets_edd_ticket_created', $attendee_id, $order_id, $product_id, $i );

			$this->record_attendee_user_id( $attendee_id, $user_id );
			$this->clear_attendees_cache( $post_id );
		}

		return true;
	}

	/**
	 * Depending on the available version of EDD the product might be a post object or a Download
	 * object and it might not implement the `get__stock` method.
	 *
	 * @since 4.7.3
	 *
	 * @param WP_Post|EDD_Download $product
	 *
	 * @return string
	 */
	protected function get_stock_for_product( $product ) {
		$product_stock = property_exists( $product, '_stock' ) ?
			$product->_stock
			: false;

		if ( empty( $product_stock ) || $product_stock instanceof WP_Error ) {
			$product_stock = get_post_meta( $product->ID, '_stock', true );
		}

		return $product_stock;
	}

	/**
	 * Get the value of the currency selected for EDD
	 *
	 * @since 4.7.3
	 *
	 * @return string
	 */
	public function get_currency() {
		return function_exists( 'edd_get_currency' ) ? edd_get_currency() : parent::get_currency();
	}

	/**
	 * Add our class suffix to the list of classes for the attendee registration form
	 * This gets appended to `tribe-tickets__item__attendee__fields__form--` so keep it short and sweet
	 *
	 * @param array $classes existing array of classes
	 * @return array $classes with our class added
	 */
	public function tribe_attendee_registration_form_class( $classes ) {
		$classes[ $this->attendee_object ] = 'edd';

		return $classes;
	}

	/**
	 * Filter the provider object to return this class if tickets are for this provider.
	 *
	 * @since 4.11.0
	 *
	 * @param object $provider_obj
	 * @param string $provider
	 *
	 * @return object
	 */
	function tribe_attendee_registration_cart_provider( $provider_obj, $provider ) {
		$options = [
			'edd',
			'tribe_eddticket',
			__CLASS__,
		];

		if ( in_array( $provider, $options, true ) ) {
			return $this;
		}

		return $provider_obj;
	}

	/**
	 * Adds a link back to the attendee registration page from checkout.
	 * Adds provider param to links.
	 *
	 * @since 4.11.0
	 */
	public function add_checkout_links() {
		/** @var \Tribe__Tickets_Plus__Commerce__EDD__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.edd.cart' );

		/** @var Tribe__Tickets_Plus__Commerce__EDD__Main $commerce_edd */
		$commerce_edd = tribe( 'tickets-plus.commerce.edd' );

		/** @var Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
		$attendee_registration = tribe( 'tickets.attendee_registration' );

		$tickets_in_cart = $cart->get_tickets_in_cart();

		/** @var Tribe__Tickets_Plus__Main $tickets_plus_main */
		$tickets_plus_main = tribe( 'tickets-plus.main' );

		$cart_has_meta = $tickets_plus_main->meta()->cart_has_meta( $tickets_in_cart );

		// only show the AR link if we have ARI
		if ( ! $cart_has_meta ) {
			return;
		}

		echo '<div class="tribe-checkout-backlinks">';

		echo sprintf(
			'<a class="tribe-checkout-backlink" href="%1$s">%2$s</a>',
			esc_url( add_query_arg( 'provider', $commerce_edd->attendee_object, $attendee_registration->get_url() ) ),
			esc_html__( 'Edit attendee info', 'event-tickets-plus' )
		);

		echo '</div>';
	}
}
