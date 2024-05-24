<?php

if (
	class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' )
	|| ! class_exists( 'Tribe__Tickets__Tickets' )
) {
	return;
}


class Tribe__Tickets_Plus__Commerce__WooCommerce__Main extends Tribe__Tickets_Plus__Tickets {

	/**
	 * {@inheritdoc}
	 */
	public $orm_provider = 'woo';

	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @deprecated 4.7 Use $attendee_object variable instead
	 */
	const ATTENDEE_OBJECT = 'tribe_wooticket';

	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @var string
	 */
	public $attendee_object = 'tribe_wooticket';

	/**
	 * Prefix used to generate the key of the transient to be associated with a different user session to avoid
	 * redirection for users with no transient present.
	 *
	 * @since 4.7.3
	 *
	 * @var string
	 */
	private $cart_location_cache_prefix = 'tribe_woo_cart_hash_';

	/**
	 * Name of the CPT that holds Orders
	 *
	 * @deprecated 4.7 Use $order_object variable instead
	 */
	const ORDER_OBJECT = 'shop_order';

	/**
	 * Name of the CPT that holds Orders
	 *
	 * @var string
	 */
	public $order_object = 'shop_order';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @deprecated 4.7 Use $attendee_product_key variable instead
	 */
	const ATTENDEE_PRODUCT_KEY = '_tribe_wooticket_product';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @var string
	 */
	public $attendee_product_key = '_tribe_wooticket_product';

	/**
	 * Meta key that relates Attendees and Orders.
	 *
	 * @deprecated 4.7 Use $attendee_order_key variable instead
	 */
	const ATTENDEE_ORDER_KEY = '_tribe_wooticket_order';

	/**
	 * Meta key that relates Attendees and Orders.
	 *
	 * @var string
	 */
	public $attendee_order_key = '_tribe_wooticket_order';

	/**
	 * Meta key that relates Attendees and Order Items.
	 *
	 * @since 4.3.2
	 * @var   string
	 */
	public $attendee_order_item_key = '_tribe_wooticket_order_item';

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @deprecated 4.7 Use $attendee_event_key variable instead
	 */
	const ATTENDEE_EVENT_KEY = '_tribe_wooticket_event';

	/**
	 * Meta key that relates Attendees and Events.
	 */
	public $attendee_event_key = '_tribe_wooticket_event';

	/**
	 * Meta key that relates Products and Events
	 *
	 * @var string
	 */
	public $event_key = '_tribe_wooticket_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 *
	 * @var string
	 */
	public $checkin_key = '_tribe_wooticket_checkedin';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 *
	 * @var string
	 */
	public $security_code = '_tribe_wooticket_security_code';

	/**
	 * Meta key that holds if an order has tickets (for performance)
	 *
	 * @var string
	 */
	public $order_has_tickets = '_tribe_has_tickets';

	/**
	 * Meta key that will keep track of whether the confirmation mail for a ticket has been sent to the user or not.
	 *
	 * @var string
	 */
	public $mail_sent_meta_key = '_tribe_mail_sent';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 *
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * Name of the ticket commerce CPT.
	 *
	 * @var string
	 */
	public $ticket_object = 'product';

	/**
	 * Meta key that holds if the attendee has opted out of the front-end listing
	 *
	 * @deprecated 4.7 Use static $attendee_optout_key variable instead
	 *
	 * @var string
	 */
	const ATTENDEE_OPTOUT_KEY = '_tribe_wooticket_attendee_optout';

	/**
	 * Meta key that holds if the attendee has opted out of the front-end listing
	 *
	 * @var string
	 */
	public $attendee_optout_key = '_tribe_wooticket_attendee_optout';

	/**
	 * Order count cache key.
	 *
	 * @since 4.11.0.2
	 *
	 * @var string
	 */
	const ORDER_COUNT_CACHE_KEY = '_tribe_woo_order_item_count';

	/**
	 * If we've displayed the cart notice yet.
	 *
	 * @since 4.11.0
	 *
	 * @var boolean
	 */
	public $cart_change_notice_displayed = false;

	/**
	 * @var WC_Product|WC_Product_Simple
	 */
	protected $product;

	/**
	 * Holds an instance of the Tribe__Tickets_Plus__Commerce__WooCommerce__Email class
	 *
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Email
	 */
	private $mailer = null;

	/** @var Tribe__Tickets_Plus__Commerce__WooCommerce__Settings */
	private $settings;

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Instance of Tribe__Tickets_Plus__Commerce__WooCommerce__Meta
	 */
	private static $meta;

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Global_Stock
	 */
	private static $global_stock;

	/**
	 * For each ticket, stores the total number of pending orders.
	 *
	 * Populates lazily and on-demand.
	 *
	 * @since 4.4.9
	 *
	 * @var array
	 */
	protected $pending_orders_by_ticket = [];

	/**
	 * Current version of this plugin
	 */
	const VERSION = '4.5.0.1';

	/**
	 * Min required The Events Calendar version
	 */
	const REQUIRED_TEC_VERSION = '4.6.20';

	/**
	 * Min required WooCommerce version
	 */
	const REQUIRED_WC_VERSION = '3.7';


	/**
	 * Creates the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::$instance = self::get_instance();
	}

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Main
	 */
	public static function get_instance() {
		return tribe( 'tickets-plus.commerce.woo' );
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		/* Set up parent vars */
		$this->plugin_name = $this->pluginName = _x( 'WooCommerce', 'ticket provider', 'event-tickets-plus' );
		$this->plugin_slug = $this->pluginSlug = 'wootickets';
		$this->plugin_path = $this->pluginPath = trailingslashit( EVENT_TICKETS_PLUS_DIR );
		$this->plugin_dir  = $this->pluginDir = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = $this->pluginUrl = trailingslashit( plugins_url( $this->plugin_dir ) );

		parent::__construct();

		$this->bind_implementations();
		$this->hooks();
		$this->orders_report();
		$this->global_stock();
		$this->meta();
		$this->settings();
	}

	/**
	 * Binds implementations that are specific to WooCommerce
	 *
	 * @see \Tribe__Tickets_Plus__Commerce__Woocommerce__Cart
	 */
	public function bind_implementations() {
		tribe_singleton( 'tickets-plus.commerce.woo.cart', 'Tribe__Tickets_Plus__Commerce__WooCommerce__Cart', [ 'hook' ] );
		tribe( 'tickets-plus.commerce.woo.cart' );
	}

	/**
	 * Registers all actions/filters
	 */
	public function hooks() {
		add_action( 'init', [ $this, 'register_wootickets_type' ] );
		add_action( 'init', [ $this, 'register_resources' ] );
		add_action( 'add_meta_boxes', [ $this, 'woocommerce_meta_box' ] );
		add_action( 'before_delete_post', [ $this, 'handle_delete_post' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'delayed_ticket_generation' ], 12, 3 );
		add_action( 'tribe_wc_delayed_ticket_generation', [ $this, 'generate_tickets' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'reset_attendees_cache' ] );
		add_action( 'woocommerce_email_header', [ $this, 'maybe_add_tickets_msg_to_email' ], 10, 2 );
		add_action( 'tribe_events_tickets_metabox_edit_advanced', [ $this, 'do_metabox_advanced_options' ], 10, 2 );

		if ( class_exists( 'Tribe__Events__API' ) ) {
			add_action( 'woocommerce_product_quick_edit_save', [ $this, 'syncronize_product_editor_changes' ] );
			add_action( 'woocommerce_process_product_meta_simple', [ $this, 'syncronize_product_editor_changes' ] );
		}

		add_filter( 'tribe_tickets_get_modules', [ $this, 'maybe_remove_as_active_module' ] );

		if (
			function_exists( 'WC' ) // In case this gets called when WooCommerce is deactivated.
			&& version_compare( WC()->version, '3.0', '<' ) // Pre-3.0 installs.
		) {
			add_action( 'woocommerce_add_order_item_meta', [ $this, 'set_attendee_optout_choice' ], 15, 2 );
		} else {
			add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'set_attendee_optout_value' ], 10, 3 );
		}

		add_action( 'woocommerce_checkout_before_order_review', [ $this, 'add_checkout_links' ] );

		// Enqueue styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ], 11 );

		add_filter( 'post_type_link', [ $this, 'hijack_ticket_link' ], 10, 4 );
		add_filter( 'woocommerce_email_classes', [ $this, 'add_email_class_to_woocommerce' ] );

		add_action( 'woocommerce_resend_order_emails_available', [ $this, 'add_resend_tickets_action' ] ); // WC 3.1.x
		add_action( 'woocommerce_order_actions', [ $this, 'add_resend_tickets_action' ] ); // WC 3.2.x
		add_action( 'woocommerce_order_action_resend_tickets_email', [ $this, 'send_tickets_email' ] ); // WC 3.2.x

		add_filter( 'event_tickets_attendees_woo_checkin_stati', tribe_callback( 'tickets-plus.commerce.woo.checkin-stati', 'filter_attendee_ticket_checkin_stati' ), 10 );
		add_action( 'wootickets_checkin', [ $this, 'purge_attendees_transient' ] );
		add_action( 'wootickets_uncheckin', [ $this, 'purge_attendees_transient' ] );
		add_filter( 'tribe_tickets_settings_post_types', [ $this, 'exclude_product_post_type' ] );

		add_action( 'tribe_tickets_attendees_page_inside', [ $this, 'render_tabbed_view' ] );
		add_action( 'woocommerce_check_cart_items', [ $this, 'validate_tickets' ] );
		add_action( 'template_redirect', [ $this, 'redirect_to_cart' ] );

		add_action( 'wc_after_products_starting_sales', [ $this, 'syncronize_products' ] );
		add_action( 'wc_after_products_ending_sales', [ $this, 'syncronize_products' ] );

		add_action( 'tribe_ticket_available_warnings', [ $this, 'get_ticket_table_warnings' ], 10, 2 );

		add_filter( 'tribe_tickets_get_ticket_max_purchase', [ $this, 'filter_ticket_max_purchase' ], 10, 2 );

		tribe_singleton( 'commerce.woo.order.refunded', 'Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded' );

		add_filter( 'tribe_attendee_registration_form_classes', [ $this, 'tribe_attendee_registration_form_class' ] );
		add_filter( 'tribe_attendee_registration_cart_provider', [ $this, 'tribe_attendee_registration_cart_provider' ], 10, 2 );
		add_action( 'tribe_tickets_registration_content_before_all_events', [ $this, 'woo_attendee_registration_notice_cart_qty_change' ], 10, 3 );
		add_action( 'woocommerce_after_checkout_form', [ $this, 'clear_tribe_ar_ticket_updated' ] );
		add_filter( 'tribe_tickets_plus_meta_cookie_flag', [ $this, 'clear_tribe_ar_ticket_updated' ] );

		add_filter( 'tribe_tickets_cart_urls', [ $this, 'add_cart_url' ] );
		add_filter( 'tribe_tickets_checkout_urls', [ $this, 'add_checkout_url' ] );
	}

	public function register_resources() {
		$stylesheet_url = $this->plugin_url . 'src/resources/css/wootickets.css';

		// Get minified CSS if it exists
		$stylesheet_url = Tribe__Template_Factory::getMinFile( $stylesheet_url, true );

		// apply filters
		$stylesheet_url = apply_filters( 'tribe_wootickets_stylesheet_url', $stylesheet_url );

		wp_register_style( 'TribeEventsWooTickets', $stylesheet_url, [], apply_filters( 'tribe_events_wootickets_css_version', self::VERSION ) );

		//Check for override stylesheet
		$user_stylesheet_url = Tribe__Tickets__Templates::locate_stylesheet( 'tribe-events/wootickets/wootickets.css' );
		$user_stylesheet_url = apply_filters( 'tribe_events_wootickets_stylesheet_url', $user_stylesheet_url );

		//If override stylesheet exists, then enqueue it
		if ( $user_stylesheet_url ) {
			wp_register_style( 'tribe-events-wootickets-override-style', $user_stylesheet_url );
		}
	}

	/**
	 * If WooCommerce is not active, remove WooTickets as an active provider.
	 *
	 * Protects against running code for past WooTicket attendees and tickets after WooCommerce is deactivated.
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
		if ( ! function_exists( 'WC' ) ) {
			unset( $active_modules[ $this->class_name ] );
		}

		return $active_modules;
	}

	/**
	 * After placing the Order make sure we store the users option to show the Attendee Optout.
	 *
	 * This method should only be used if a version of WooCommerce lower than 3.0 is in use.
	 *
	 * @param int   $item_id
	 * @param array $item
	 */
	public function set_attendee_optout_choice( $item_id, $item ) {
		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $commerce_woo */
		$commerce_woo = tribe( 'tickets-plus.commerce.woo' );

		$optout_key = $commerce_woo->attendee_optout_key;

		// If this option is not here just drop
		if ( ! isset( $item[ $optout_key ] ) && ! isset( $item['attendee_optout'] ) ) {
			return;
		}

		$optout = 'no';

		if ( isset( $item[ $optout_key ] ) ) {
			$optout = $item[ $optout_key ];
		} elseif ( isset( $item['attendee_optout'] ) ) {
			$optout = $item['attendee_optout'];
		}

		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
		$optout = $optout ? 'yes' : 'no';

		wc_add_order_item_meta( $item_id, $this->attendee_optout_key, $optout );
	}

	/**
	 * Store the attendee optout value for each order item.
	 *
	 * This method should only be used if a version of WooCommerce greater than or equal
	 * to 3.0 is in use.
	 *
	 * @since 4.4.6
	 *
	 * @param WC_Order_Item $item
	 * @param string        $cart_item_key
	 * @param array         $values
	 */
	public function set_attendee_optout_value( $item, $cart_item_key, $values ) {
		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $commerce_woo */
		$commerce_woo = tribe( 'tickets-plus.commerce.woo' );

		$optout_key = $commerce_woo->attendee_optout_key;

		// If this option is not here just drop
		if ( ! isset( $values[ $optout_key ] ) && ! isset( $values['attendee_optout'] ) ) {
			return;
		}

		$optout = 'no';

		if ( isset( $values[ $optout_key ] ) ) {
			$optout = $values[ $optout_key ];
		} elseif ( isset( $values['attendee_optout'] ) ) {
			$optout = $values['attendee_optout'];
		}

		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
		$optout = $optout ? 'yes' : 'no';

		$item->add_meta_data( $this->attendee_optout_key, $optout );
	}

	/**
	 * Hide the Attendee Output Choice in the Order Page
	 *
	 * @param $order_items
	 *
	 * @return array
	 */
	public function hide_attendee_optout_choice( $order_items ) {
		$order_items[] = $this->attendee_optout_key;

		return $order_items;
	}

	/**
	 * Orders report object accessor method
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report
	 */
	public function orders_report() {
		static $report;

		if ( ! $report instanceof self ) {
			$report = new Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report;
		}

		return $report;
	}

	/**
	 * Custom meta integration object accessor method
	 *
	 * @since 4.1
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Meta
	 */
	public function meta() {
		if ( ! self::$meta ) {
			self::$meta = new Tribe__Tickets_Plus__Commerce__WooCommerce__Meta;
		}

		return self::$meta;
	}

	/**
	 * Provides a copy of the global stock integration object.
	 *
	 * @since 4.1
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Global_Stock
	 */
	public function global_stock() {
		if ( ! self::$global_stock ) {
			self::$global_stock = new Tribe__Tickets_Plus__Commerce__WooCommerce__Global_Stock;
		}

		return self::$global_stock;
	}

	public function settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Tribe__Tickets_Plus__Commerce__WooCommerce__Settings;
		}

		return $this->settings;
	}

	/**
	 * Enqueue the plugin stylesheet(s).
	 *
	 * @author caseypicker
	 * @since  3.9
	 * @return void
	 */
	public function enqueue_styles() {
		//Only enqueue wootickets styles on singular event page
		if ( is_singular( Tribe__Tickets__Main::instance()->post_types() ) ) {
			wp_enqueue_style( 'TribeEventsWooTickets' );
			wp_enqueue_style( 'tribe-events-wootickets-override-style' );
		}
	}

	public function admin_enqueue_styles() {
		wp_enqueue_style( 'TribeEventsWooTickets' );
		wp_enqueue_style( 'tribe-events-wootickets-override-style' );
	}

	/**
	 * Where the cart form should lead the users into
	 *
	 * @since  4.8.1
	 *
	 * @return string
	 */
	public function get_cart_url() {
		/** @var \Tribe__Tickets_Plus__Commerce__Woocommerce__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.woo.cart' );

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
		/** @var \Tribe__Tickets_Plus__Commerce__Woocommerce__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.woo.cart' );

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
		/** @var \Tribe__Tickets_Plus__Commerce__Woocommerce__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.woo.cart' );

		$urls[ __CLASS__ ] = $cart->get_checkout_url();

		return $urls;
	}

	/**
	 * If a ticket is edited via the WooCommerce product editor (vs the ticket meta
	 * box exposed in the event editor) then we need to trigger an update to ensure
	 * cost meta in particular stays up-to-date on our side.
	 *
	 * @param $product_id
	 */
	public function syncronize_product_editor_changes( $product_id ) {
		$event = $this->get_event_for_ticket( $product_id );

		// This product is not connected with an event
		if ( ! $event ) {
			return;
		}

		// Trigger an update
		Tribe__Events__API::update_event_cost( $event->ID );
	}

	/**
	 * When a user deletes a ticket (product) we want to store
	 * a copy of the product name, so we can show it in the
	 * attendee list for an event.
	 *
	 * @param int|WP_Post $post
	 */
	public function handle_delete_post( $post ) {
		if ( is_numeric( $post ) ) {
			$post = WP_Post::get_instance( $post );
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		// Bail if it's not a Product
		if ( 'product' !== $post->post_type ) {
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
	 * Add a custom email handler to WooCommerce email system
	 *
	 * @param array $classes of WC_Email objects
	 *
	 * @return array of WC_Email objects
	 */
	public function add_email_class_to_woocommerce( $classes ) {

		if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Email' ) ) {
			return $classes;
		}

		$this->mailer                          = new Tribe__Tickets_Plus__Commerce__WooCommerce__Email();
		$classes['Tribe__Tickets__Woo__Email'] = $this->mailer;

		return $classes;
	}

	/**
	 * Register our custom post type
	 */
	public function register_wootickets_type() {
		$args = [
			'label'           => esc_html( tribe_get_ticket_label_plural( 'woo_post_type_label' ) ),
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

	public static function is_wc_paypal_gateway_active() {
		// Add PayPal delay settings if PayPal is active and enabled.
		$woo_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$pp_gateways  = [ 'paypal', 'ppec_paypal' ];

		foreach ( $pp_gateways as $gateway ) {
			// check if gateway exists and is enabled
			if ( ! empty( $woo_gateways[ $gateway ] ) && $woo_gateways[ $gateway ]->enabled ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a Order has Tickets
	 *
	 * @param  int $order_id
	 *
	 * @return boolean
	 */
	public function order_has_tickets( $order_id ) {
		$has_tickets = false;

		$done = get_post_meta( $order_id, $this->order_has_tickets, true );
		/**
		 * get_post_meta returns empty string when the meta doesn't exists
		 * in support 2 possible values:
		 * - Empty string which will do the logic using WC_Order below
		 * - Cast boolean the return of the get_post_meta
		 */
		if ( '' !== $done ) {
			return (bool) $done;
		}

		// Get the items purchased in this order
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return $has_tickets;
		}

		// Iterate over each product
		foreach ( (array) $order_items as $item_id => $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];
			// Get the event this tickets is for
			$post_id = get_post_meta( $product_id, $this->event_key, true );

			if ( ! empty( $post_id ) ) {

				$has_tickets = true;
				break;
			}
		}

		return $has_tickets;
	}

	/**
	 * Adds a 5-second delay to ticket generation to help protect against race conditions.
	 *
	 * @since 4.9.2
	 *
	 * @param int    $order_id The id of the ticket order.
	 * @param string $unused_from     Deprecated. The status the order is transitioning from.
	 * @param string $unused_to       Deprecated. The status the order is transitioning to.
	 */
	public function delayed_ticket_generation( $order_id, $unused_from = null, $unused_to = null ) {
		// If we're not using PayPal, we don't need to delay, just generate tickets
		if ( 'paypal' !== strtolower( get_post_meta( $order_id, '_payment_method', true ) ) ) {
			$this->generate_tickets( $order_id );
			return;
		}

		/**
		 * Allows users to customize the delay put in place
		 *
		 * @since 4.9.2
		 *
		 * @param string A date/time string. Note it must be positive!
		 */
		$ticket_delay = apply_filters( 'tribe_ticket_generation_delay', '+5 seconds', $order_id );
		$timestamp = strtotime( $ticket_delay );

		// In case the timestamp is borked, fall back to 5 seconds
		if ( ! $timestamp || $timestamp < time() ) {
			$timestamp = strtotime( '+5 seconds' );
		}

		if ( self::is_wc_paypal_gateway_active() && tribe_get_option( 'tickets-woo-paypal-delay', false ) ) {
			wp_schedule_single_event( $timestamp, 'tribe_wc_delayed_ticket_generation', [ $order_id ] );
		} else {
			$this->generate_tickets( $order_id );
		}
	}

	/**
	 * Generates the tickets.
	 *
	 * This happens only once, as soon as an order reaches a suitable status (which is set in
	 * the WooCommerce-specific ticket settings).
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

		$order_status = get_post_status( $order_id );

		$generation_statuses = (array) tribe_get_option(
			'tickets-woo-generation-status',
			$this->settings->get_default_ticket_generation_statuses()
		);

		$dispatch_statuses = (array) tribe_get_option(
			'tickets-woo-dispatch-status',
			$this->settings->get_default_ticket_dispatch_statuses()
		);

		/**
		 * Filters the list of ticket order stati that should trigger the ticket generation.
		 *
		 * By default the WooCommerced default ones that will affect the ticket stock.
		 *
		 * @since 4.2
		 *
		 * @param array $generation_statuses
		 */
		$generation_statuses = (array) apply_filters( 'event_tickets_woo_ticket_generating_order_stati', $generation_statuses );

		/**
		 * Controls the list of order post statuses used to trigger dispatch of ticket emails.
		 *
		 * @since 4.2
		 *
		 * @param array $trigger_statuses order post statuses
		 */
		$dispatch_statuses = apply_filters( 'event_tickets_woo_complete_order_stati', $dispatch_statuses );

		$should_generate    = in_array( $order_status, $generation_statuses, true ) || in_array( 'immediate', $generation_statuses, true );
		$should_dispatch    = in_array( $order_status, $dispatch_statuses, true ) || in_array( 'immediate', $dispatch_statuses, true );
		$already_generated  = get_post_meta( $order_id, $this->order_has_tickets, true );
		$already_dispatched = get_post_meta( $order_id, $this->mail_sent_meta_key, true );

		$has_tickets       = false;
		$created_attendees = false;

		// Get the items purchased in this order
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		// Iterate over each product
		foreach ( (array) $order_items as $item_id => $item ) {
			// order attendee IDs in the meta are per ticket type
			$order_attendee_id = 0;

			$product = $this->get_product_from_item( $order, $item );

			if ( empty( $product ) ) {
				continue;
			}

			$product_id = $this->get_product_id( $product );

			// Check if the order item contains attendee optout information
			$optout_data = isset( $item['item_meta'][ $this->attendee_optout_key ] )
				? $item['item_meta'][ $this->attendee_optout_key ]
				: false;

			$optout = is_array( $optout_data )
				? reset( $optout_data ) // WC 2.x
				: $optout_data;         // WC 3.x

			$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
			$optout = (int) $optout;

			// Get the event this ticket is for
			$post_id = (int) get_post_meta( $product_id, $this->event_key, true );

			$quantity = empty( $item['qty'] ) ? 0 : intval( $item['qty'] );

			if ( ! empty( $post_id ) ) {

				$has_tickets = true;

				if ( $already_generated || ! $should_generate ) {
					break;
				}

				// Iterate over all the amount of tickets purchased (for this product)
				$quantity = (int) $item['qty'];

				/** @var Tribe__Tickets__Commerce__Currency $currency */
				$currency        = tribe( 'tickets.commerce.currency' );
				$currency_symbol = $currency->get_currency_symbol( $product_id, true );

				for ( $i = 0; $i < $quantity; $i++ ) {

					$attendee = [
						'post_status' => 'publish',
						'post_title'  => $order_id . ' | ' . $item['name'] . ' | ' . ( $i + 1 ),
						'post_type'   => $this->attendee_object,
						'ping_status' => 'closed',
					];

					// Insert individual ticket purchased
					$attendee = apply_filters( 'wootickets_attendee_insert_args', $attendee, $order_id, $product_id, $post_id );

					if ( $attendee_id = wp_insert_post( $attendee ) ) {
						update_post_meta( $attendee_id, self::ATTENDEE_PRODUCT_KEY, $product_id );
						update_post_meta( $attendee_id, self::ATTENDEE_ORDER_KEY, $order_id );
						update_post_meta( $attendee_id, $this->attendee_order_item_key, $item_id );
						update_post_meta( $attendee_id, self::ATTENDEE_EVENT_KEY, $post_id );
						update_post_meta( $attendee_id, self::ATTENDEE_OPTOUT_KEY, $optout );
						update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $order_id, $attendee_id ) );
						update_post_meta( $attendee_id, '_paid_price', $this->get_price_value( $product_id ) );
						update_post_meta( $attendee_id, '_price_currency_symbol', $currency_symbol );

						/**
						 * WooCommerce-specific action fired when a WooCommerce-driven attendee ticket for an event is generated
						 *
						 * @param $attendee_id ID of attendee ticket
						 * @param $post_id    ID of event
						 * @param $order       WooCommerce order
						 * @param $product_id  WooCommerce product ID
						 */
						do_action( 'event_ticket_woo_attendee_created', $attendee_id, $post_id, $order, $product_id );

						/**
						 * Action fired when an attendee ticket is generated
						 *
						 * @param int $attendee_id       ID of attendee ticket
						 * @param int $order_id          WooCommerce order ID
						 * @param int $product_id        Product ID attendee is "purchasing"
						 * @param int $order_attendee_id Attendee # for order
						 */
						do_action( 'event_tickets_woocommerce_ticket_created', $attendee_id, $order_id, $product_id, $order_attendee_id );

						$customer_id = method_exists( $order, 'get_customer_id' )
							? $order->get_customer_id()  // WC 3.x
							: $order->customer_user; // WC 2.2.x

						$this->record_attendee_user_id( $attendee_id, $customer_id );
						$order_attendee_id++;

						$created_attendees = true;
					}
				}
			}

			if ( ! $already_generated && $should_generate ) {
				if ( ! isset( $quantity ) ) {
					$quantity = null;
				}
				/**
				 * Action fired when a WooCommerce has had attendee tickets generated for it
				 *
				 * @param int $product_id RSVP ticket post ID
				 * @param int $order_id   ID of the WooCommerce order
				 * @param int $quantity   Quantity ordered
				 * @param int $post_id    ID of event
				 */
				do_action( 'event_tickets_woocommerce_tickets_generated_for_product', $product_id, $order_id, $quantity, $post_id );

				update_post_meta( $order_id, $this->order_has_tickets, '1' );
			}
		}

		// Disallow the dispatch of emails before attendees have been created
		$attendees_generated = $already_generated || $created_attendees;

		if ( $has_tickets && $attendees_generated && ! $already_dispatched && $should_dispatch ) {
			$this->complete_order( $order_id );
		}

		if ( ! $already_generated && $should_generate ) {
			/**
			 * Action fired when a WooCommerce attendee tickets have been generated
			 *
			 * @param $order_id ID of the WooCommerce order
			 */
			do_action( 'event_tickets_woocommerce_tickets_generated', $order_id );
		}
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
	 * Watches to see if the email being generated is a customer order email and sets up
	 * the addition of ticket-specific messaging if it is.
	 *
	 * @param string $heading
	 * @param object $email
	 */
	public function maybe_add_tickets_msg_to_email( $heading, $email = null ) {
		// If the email object wasn't passed, go no further
		if ( null === $email ) {
			return;
		}

		// Do nothing unless this is a "customer_*" type email
		if ( ! isset( $email->id ) || 0 !== strpos( $email->id, 'customer_' ) ) {
			return;
		}

		// Do nothing if this is a refund notification
		if ( false !== strpos( $email->id, 'refunded' ) ) {
			return;
		}

		// Setup our tickets advisory message
		add_action( 'woocommerce_email_after_order_table', [ $this, 'add_tickets_msg_to_email' ], 10, 2 );
	}

	/**
	 * Adds a message to WooCommerce's order email confirmation.
	 *
	 * @param WC_Order $order
	 */
	public function add_tickets_msg_to_email( $order ) {
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		$has_tickets = false;

		// Iterate over each product
		foreach ( (array) $order_items as $item ) {

			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];

			// Get the event this tickets is for
			$post_id = get_post_meta( $product_id, $this->event_key, true );

			if ( ! empty( $post_id ) ) {
				$has_tickets = true;
				break;
			}
		}

		if ( ! $has_tickets ) {
			return;
		}

		echo '<br/>';

		/**
		 * WooCommerce ticket email content.
		 *
		 * @todo Deprecate filter because of outdated filter name prefix.
		 *
		 * @param string $message The email message.
		 *
		 * @return string
		 */
		/*echo apply_filters(
			'wootickets_email_message',
			esc_html(
				sprintf(
					__( "You'll receive your notification in another email.", 'event-tickets-plus' ),
					tribe_get_ticket_label_plural_lowercase( 'woo_email_confirmation' )
				)
			)
		);*/
	}

	/**
	 * Saves a ticket (WooCommerce product).
	 *
	 * @param int                           $post_id  Post ID.
	 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket object.
	 * @param array                         $raw_data Ticket data.
	 *
	 * @throws WC_Data_Exception Throws exception when invalid data is found.
	 * @return int|bool
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = [] ) {
		// assume we are updating until we find out otherwise
		$save_type = 'update';

		if ( empty( $ticket->ID ) ) {
			$save_type = 'create';

			/* Create main product post */
			$args = [
				'post_status'  => 'publish',
				'post_type'    => 'product',
				'post_author'  => get_current_user_id(),
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => tribe_get_request_var( 'menu_order', -1 ),
			];

			$ticket->ID = wp_insert_post( $args );
			$product    = wc_get_product( $ticket->ID );

			if ( ! $product ) {
				return false;
			}

			$product->set_sale_price( '' );
			$product->set_total_sales( 0 );
			$product->set_tax_status( 'taxable' );
			$product->set_tax_class( '' );
			$product->set_virtual( true );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_downloadable( false );
			$product->set_purchase_note( '' );
			$product->set_weight( '' );
			$product->set_length( '' );
			$product->set_height( '' );
			$product->set_width( '' );
			$product->set_attributes( [] );
			$product->set_props( [
				'date_on_sale_from' => '',
				'date_on_sale_to'   => '',
			] );
			$product->save();

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $post_id );
		} else {
			$args = [
				'ID'           => $ticket->ID,
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => $ticket->menu_order,
			];

			$ticket->ID = wp_update_post( $args );
		}

		if ( ! $ticket->ID ) {
			return false;
		}

		/**
		 * Toggle filter to allow skipping the automatic SKU generation.
		 *
		 * @param bool $should_default_ticket_sku
		 */
		$should_default_ticket_sku = apply_filters( 'event_tickets_woo_should_default_ticket_sku', true );
		if ( $should_default_ticket_sku ) {
			// make sure the SKU is set to the correct value
			if ( ! empty( $raw_data['ticket_sku'] ) ) {
				$sku = $raw_data['ticket_sku'];
			} else {
				$post_author = get_post_field( 'post_author', $ticket->ID );
				$str         = $raw_data['ticket_name'];
				$str         = mb_strtoupper( $str, mb_detect_encoding( $str ) );
				$sku         = "{$ticket->ID}-{$post_author}-" . str_replace( ' ', '-', $str );
			}

			update_post_meta( $ticket->ID, '_sku', $sku );
		}

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Updates if we should show Description
		$ticket->show_description = isset( $ticket->show_description ) && tribe_is_truthy( $ticket->show_description ) ? 'yes' : 'no';

		update_post_meta( $ticket->ID, $tickets_handler->key_show_description, $ticket->show_description );

		/**
		 * Allow for the prevention of updating ticket price on update.
		 *
		 * @param boolean
		 * @param WP_Post
		 */
		$can_update_ticket_price = apply_filters( 'tribe_tickets_can_update_ticket_price', true, $ticket );

		if ( $can_update_ticket_price ) {
			update_post_meta( $ticket->ID, '_regular_price', $ticket->price );

			// Do not update _price if the ticket is on sale: the user should edit this in the WC product editor
			if ( ! wc_get_product( $ticket->ID )->is_on_sale() || 'create' === $save_type ) {
				update_post_meta( $ticket->ID, '_price', $ticket->price );
			}
		}

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

		// The only available value lower than zero is -1 which is unlimited.
		if ( 0 > $data['stock'] ) {
			$data['stock'] = -1;
		}

		$mode = isset( $data['mode'] ) ? $data['mode'] : 'own';

		if ( '' !== $mode ) {
			if ( 'update' === $save_type ) {
				$totals        = $tickets_handler->get_ticket_totals( $ticket->ID );
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

		// Delete total Stock cache
		delete_transient( 'wc_product_total_stock_' . $ticket->ID );

		if ( ! empty( $raw_data['ticket_start_date'] ) ) {
			$start_date = Tribe__Date_Utils::maybe_format_from_datepicker( $raw_data['ticket_start_date'] );

			if ( isset( $raw_data['ticket_start_time'] ) ) {
				$start_date .= ' ' . $raw_data['ticket_start_time'];
			}

			$ticket->start_date = date( Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( $start_date ) );

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

		/** @var Tribe__Tickets__Version $version */
		$version = tribe( 'tickets.version' );

		$version->update( $ticket->ID );

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
	 * Deletes a ticket
	 *
	 * @param $post_id
	 * @param $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $post_id, $ticket_id ) {

		// Ensure we know the event and product IDs (the event ID may not have been passed in)
		if ( empty( $post_id ) ) {
			$post_id = (int) get_post_meta( $ticket_id, self::ATTENDEE_EVENT_KEY, true );
		}

		$product_id = (int) get_post_meta( $ticket_id, $this->attendee_product_key, true );

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

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		Tribe__Tickets__Attendance::instance( $post_id )->increment_deleted_attendees_count();

		// Re-stock the product inventory (on the basis that a "seat" has just been freed)
		$this->increment_product_inventory( $product_id );

		$this->clear_attendees_cache( $post_id );

		$has_shared_tickets = 0 !== count( $tickets_handler->get_event_shared_tickets( $post_id ) );

		if ( ! $has_shared_tickets ) {
			tribe_tickets_delete_capacity( $post_id );
		}

		do_action( 'wootickets_ticket_deleted', $ticket_id, $post_id, $product_id );

		return true;
	}

	/**
	 * Increments the inventory of the specified product by 1 (or by the optional
	 * $increment_by value).
	 *
	 * @param int $product_id
	 * @param int $increment_by
	 *
	 * @return bool
	 */
	protected function increment_product_inventory( $product_id, $increment_by = 1 ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->managing_stock() ) {
			return false;
		}

		// WooCommerce 3.x
		if ( function_exists( 'wc_update_product_stock' ) ) {
			$success = wc_update_product_stock( $product, (int) $product->get_stock_quantity() + $increment_by );
		} // WooCommerce 2.x
		else {
			$success = $product->set_stock( (int) $product->stock + $increment_by );
		}

		return null !== $success;
	}

	/**
	 * Replaces the link to the WC product with a link to the Event in the
	 * order confirmation page.
	 *
	 * @param $post_link
	 * @param $post
	 * @param $unused_leavename
	 * @param $unused_sample
	 *
	 * @return string
	 */
	public function hijack_ticket_link( $post_link, $post, $unused_leavename, $unused_sample ) {
		if ( $post->post_type === 'product' ) {
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
	 * @param $content
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
			 * add_action( 'tribe_tickets_expired_front_end_ticket_form', [ Tribe__Tickets_Plus__Attendees_List::instance(), 'render' ] );
			 *
			 * @since 4.7.3
			 *
			 * @param boolean $must_login
			 * @param array   $tickets
			 */
			do_action( 'tribe_tickets_expired_front_end_ticket_form', $must_login, $tickets );
		}

		$global_stock_enabled = $this->uses_global_stock( $post->ID );

		/**
		 * Allow for the addition of content (namely the "Who's Attening?" list) above the ticket form.
		 *
		 * @since 4.5.4
		 */
		do_action( 'tribe_tickets_before_front_end_ticket_form' );

		Tribe__Tickets__Tickets_View::instance()->get_tickets_block( $post->ID );
	}

	/**
	 * Grabs the submitted front end tickets form and adds the products
	 * to the cart
	 */
	public function process_front_end_tickets_form() {
		parent::process_front_end_tickets_form();

		global $woocommerce;

		// We just want to process wootickets submissions here.
		if (
			empty( $_REQUEST['wootickets_process'] )
			|| intval( $_REQUEST['wootickets_process'] ) !== 1
			|| empty( $_POST['product_id'] )
		) {
			return;
		}

		foreach ( (array) $_POST['product_id'] as $product_id ) {
			$quantity = isset( $_POST[ 'quantity_' . $product_id ] ) ? (int) $_POST[ 'quantity_' . $product_id ] : 0;
			$optout   = isset( $_POST[ 'optout_' . $product_id ] ) ? $_POST[ 'optout_' . $product_id ] : false;

			/**
			 * Allow hooking into WooCommerce Add to Cart validation.
			 *
			 * Note: This is a WooCommerce filter that is not abstracted for API usage so we have to run it manually.
			 *
			 * @param bool $passed_validation Whether the item can be added to the cart.
			 * @param int  $ticket_id         Ticket ID.
			 * @param int  $quantity          Ticket quantity.
			 */
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

			$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
			$optout = $optout ? 'yes' : 'no';

			$cart_data = [
				$this->attendee_optout_key => $optout,
			];

			if ( $passed_validation && $quantity > 0 ) {
				$woocommerce->cart->add_to_cart( $product_id, $quantity, 0, [], $cart_data );
			}
		}

		if ( empty( $_POST[ Tribe__Tickets_Plus__Meta__Storage::META_DATA_KEY ] ) ) {
			return;
		}

		$cart_url = $this->get_cart_url();

 		set_transient( $this->get_cart_transient_key(), $cart_url );

 		wp_safe_redirect( $cart_url );
		tribe_exit();
	}

	/**
	 * Return whether we're currently on the checkout page.
	 *
	 * @return bool
	 */
	public function is_checkout_page() {
		return is_checkout() && ! is_order_received_page();
	}

	/**
	 * Gets an individual ticket
	 *
	 * @param $post_id
	 * @param $ticket_id
	 *
	 * @return null|Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket( $post_id, $ticket_id ) {
		if (
			empty( $ticket_id )
			|| ! function_exists( 'wc_get_product' )
		) {
			return null;
		}

		$product = wc_get_product( $ticket_id );

		if ( ! $product ) {
			return null;
		}

		$return       = new Tribe__Tickets__Ticket_Object();
		$product_post = get_post( $this->get_product_id( $product ) );
		$qty_sold     = get_post_meta( $ticket_id, 'total_sales', true );

		$return->description   = $product_post->post_excerpt;
		$return->frontend_link = get_permalink( $ticket_id );
		$return->ID            = $ticket_id;
		$return->name          = $product->get_title();
		$return->menu_order    = $product->get_menu_order();
		$return->price         = $this->get_price_value_for( $product, $return );
		$return->regular_price = $product->get_regular_price( 'edit' );
		$return->on_sale       = (bool) $product->is_on_sale( 'edit' );
		if ( $return->on_sale ) {
			$return->price = $product->get_sale_price( 'edit' );
		}
		$return->capacity         = tribe_tickets_get_capacity( $ticket_id );
		$return->provider_class   = get_class( $this );
		$return->admin_link       = admin_url( sprintf( get_post_type_object( $product_post->post_type )->_edit_link . '&action=edit', $ticket_id ) );
		$return->report_link      = $this->get_ticket_reports_link( null, $ticket_id );
		$return->sku              = $product->get_sku();
		$return->show_description = $return->show_description();
		$return->price_suffix     = $this->get_price_suffix( $product, $this->get_price_value_for( $product, $return ), 1 );

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

		// If the quantity sold wasn't set, default to zero
		$qty_sold = $qty_sold ? $qty_sold : 0;

		// Ticket stock is a simple reflection of remaining inventory for this item...
		$stock = $product->get_stock_quantity();

		// If we don't have a stock value, then stock should be considered 'unlimited'
		if ( null === $stock ) {
			$stock = -1;
		}

		$return->manage_stock( $product->managing_stock() );
		$return->stock( $stock );
		$return->global_stock_mode( get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) );
		$capped = get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );

		if ( '' !== $capped ) {
			$return->global_stock_cap( $capped );
		}

		$return->qty_sold( $qty_sold );
		$return->qty_cancelled( $this->get_cancelled( $ticket_id ) );
		$return->qty_refunded( $this->get_refunded( $ticket_id ) );


		// From Event Tickets 4.4.9 onwards we can supply a callback to calculate the number of
		// pending items per ticket on demand (since determining this is expensive and the data isn't
		// always required, it makes sense not to do it unless required)
		if ( version_compare( Tribe__Tickets__Main::VERSION, '4.4.9', '>=' ) ) {
			$return->qty_pending( [ $this, 'get_qty_pending' ] );
			$qty_pending = $return->qty_pending();

			// Removes pending sales from total sold
			$return->qty_sold( $qty_sold - $qty_pending );
		} else {
			// If an earlier version of Event Tickets is activated we'll need to calculate this up front
			$pending_totals = $this->count_order_items_by_status( $ticket_id, 'incomplete' );
			$return->qty_pending( $pending_totals['total'] ? $pending_totals['total'] : 0 );
		}

		/**
		 * Use this Filter to change any information you want about this ticket
		 *
		 * @param object $ticket
		 * @param int    $post_id
		 * @param int    $ticket_id
		 */
		$ticket = apply_filters( 'tribe_tickets_plus_woo_get_ticket', $return, $post_id, $ticket_id );

		return $ticket;
	}

	/**
	 * Lazily calculates the quantity of pending sales for the specified ticket.
	 *
	 * @param int  $ticket_id
	 * @param bool $refresh
	 *
	 * @return int
	 */
	public function get_qty_pending( $ticket_id, $refresh = false ) {
		if ( $refresh || empty( $this->pending_orders_by_ticket[ $ticket_id ] ) ) {
			$pending_totals                               = $this->count_order_items_by_status( $ticket_id, 'incomplete' );
			$this->pending_orders_by_ticket[ $ticket_id ] = $pending_totals['total'] ? $pending_totals['total'] : 0;
		}

		return $this->pending_orders_by_ticket[ $ticket_id ];
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
		// If this event does not have a global stock then do not modify the existing stock level
		if ( ! $this->uses_global_stock( $post_id ) ) {
			return $existing_stock;
		}

		// If this specific ticket maintains its own independent stock then again do not interfere
		if ( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) ) {
			return $existing_stock;
		}

		$product = wc_get_product( $ticket_id );

		// Otherwise the ticket stock ought to match the current global stock
		$actual_stock = $product ? $product->get_stock_quantity() : 0;
		$global_stock = $this->global_stock_level( $post_id );

		// Look out for and correct discrepancies where the actual stock is zero but the global stock is non-zero
		if ( 0 == $actual_stock && 0 < $global_stock ) {
			update_post_meta( $ticket_id, '_stock', $global_stock );
			update_post_meta( $ticket_id, '_stock_status', 'instock' );
		}

		return $global_stock;
	}

	/**
	 * Determine the total number of the specified ticket contained in orders which have
	 * progressed to a "completed" or "incomplete" status.
	 *
	 * Essentially this returns the total quantity of tickets held within orders that are
	 * complete or incomplete (incomplete are: "pending", "on hold" or "processing").
	 *
	 * @param int    $ticket_id
	 * @param string $status Types of orders: incomplete or complete
	 *
	 * @return int
	 */
	public function count_order_items_by_status( $ticket_id, $status = 'incomplete' ) {
		/** @var Tribe__Post_Transient $transient */
		$transient = tribe( 'post-transient' );

		$cache_key = self::ORDER_COUNT_CACHE_KEY . '_' . $status;

		$totals = $transient->get( $ticket_id, $cache_key );

		if ( is_array( $totals ) ) {
			return $totals;
		}

		$totals = [
			'total'          => 0,
			'recorded_sales' => 0,
			'reduced_stock'  => 0,
		];

		$incomplete_order_items = $this->get_orders_by_status( $ticket_id, $status );

		$order_ids      = wp_list_pluck( $incomplete_order_items, 'order_id' );
		$order_item_ids = wp_list_pluck( $incomplete_order_items, 'order_item_id' );

		$order_meta      = $this->get_orders_meta( $order_ids );
		$order_item_meta = $this->get_order_items_meta( $order_item_ids );

		foreach ( $incomplete_order_items as $item ) {
			$order_item_id = (int) $item->order_item_id;
			$order_id      = (int) $item->order_id;

			$has_recorded_sales = false;
			$has_reduced_stock  = false;

			if ( empty( $order_item_meta[ $order_item_id ] ) ) {
				continue;
			}

			$quantity = (int) $order_item_meta[ $order_item_id ]->_qty;

			if ( ! empty( $order_meta[ $order_id ] ) ) {
				$has_recorded_sales = filter_var( $order_meta[ $order_id ]->_recorded_sales, FILTER_VALIDATE_BOOLEAN );
				$has_reduced_stock  = filter_var( $order_meta[ $order_id ]->_order_stock_reduced, FILTER_VALIDATE_BOOLEAN );
			}

			$totals['total'] += $quantity;

			if ( $has_recorded_sales ) {
				$totals['recorded_sales'] += $quantity;
			}

			if ( $has_reduced_stock ) {
				$totals['reduced_stock'] += $quantity;
			}
		}

		$transient->set( $ticket_id, $cache_key, $totals, WEEK_IN_SECONDS );

		return $totals;
	}

	/**
	 * Get list of order items for ticket ID by order status.
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $status    Order status.
	 *
	 * @return array List of order items.
	 */
	protected function get_orders_by_status( $ticket_id, $status = 'incomplete' ) {
		global $wpdb;

		$order_state_sql   = '';
		$incomplete_states = $this->incomplete_order_states();

		if ( ! empty( $incomplete_states ) ) {
			if ( 'incomplete' === $status ) {
				$order_state_sql = "AND `posts`.`post_status` IN ( $incomplete_states )";
			} else {
				$order_state_sql = "AND `posts`.`post_status` NOT IN ( $incomplete_states )";
			}
		}

		$query = "
			SELECT
			    `wc_itemmeta`.`order_item_id`,
			    `wc_items`.`order_id`
			FROM
			    `{$wpdb->prefix}woocommerce_order_itemmeta` AS `wc_itemmeta`
			INNER JOIN
				`{$wpdb->prefix}woocommerce_order_items` AS `wc_items`
					ON
						`wc_itemmeta`.`order_item_id` = `wc_items`.`order_item_id`
			INNER JOIN
			    `{$wpdb->prefix}posts` AS `posts`
					ON
						`wc_items`.`order_id` = `posts`.`ID`
			WHERE
				`wc_itemmeta`.`meta_key` = '_product_id'
				AND `wc_itemmeta`.`meta_value` = %d
				$order_state_sql
		";

		return $wpdb->get_results( $wpdb->prepare( $query, $ticket_id ) );
	}

	/**
	 * Returns a comma separated list of term IDs representing incomplete order
	 * states.
	 *
	 * @return string
	 */
	protected function incomplete_order_states() {
		$considered_incomplete = (array) apply_filters( 'wootickets_incomplete_order_states', [
			'wc-on-hold',
			'wc-pending',
			'wc-processing',
		] );

		foreach ( $considered_incomplete as &$incomplete ) {
			$incomplete = '"' . $incomplete . '"';
		}

		return implode( ', ', $considered_incomplete );
	}

	/**
	 * Get list of Order Meta for specific Order IDs.
	 *
	 * @since 4.11.0.2
	 *
	 * @param array $order_ids List of Order IDs.
	 *
	 * @return array List of Order Meta.
	 */
	protected function get_orders_meta( $order_ids ) {
		global $wpdb;

		if ( empty( $order_ids ) ) {
			return [];
		}

		$post_id_in = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );

		$query = "
			SELECT
				`posts`.`ID` AS `order_id`,
				`meta_recorded_sales`.`meta_value` AS `_recorded_sales`,
				`meta_order_stock_reduced`.`meta_value` AS `_order_stock_reduced`
			FROM
				`{$wpdb->posts}` AS `posts`
			LEFT JOIN
				`{$wpdb->postmeta}` AS `meta_recorded_sales`
				ON `meta_recorded_sales`.`post_id` = `posts`.`ID`
					AND `meta_recorded_sales`.`meta_key` = '_recorded_sales'
			LEFT JOIN
				`{$wpdb->postmeta}` AS `meta_order_stock_reduced`
				ON `meta_order_stock_reduced`.`post_id` = `posts`.`ID`
					AND `meta_order_stock_reduced`.`meta_key` = '_order_stock_reduced'
			WHERE
				`posts`.`ID` IN ( {$post_id_in} )
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $order_ids ) );

		$order_meta = [];

		// Build result array by Order ID.
		foreach ( $results as $result ) {
			$order_meta[ (int) $result->order_id ] = $result;
		}

		return $order_meta;
	}

	/**
	 * Get list of Order Item Meta for specific Order Item IDs.
	 *
	 * @since 4.11.0.2
	 *
	 * @param array $order_item_ids List of Order Item IDs.
	 *
	 * @return array List of Order Item Meta.
	 */
	protected function get_order_items_meta( $order_item_ids ) {
		global $wpdb;

		if ( empty( $order_item_ids ) ) {
			return [];
		}

		$order_item_ids_in = implode( ', ', array_fill( 0, count( $order_item_ids ), '%d' ) );

		$query = "
			SELECT
				`meta_qty`.`order_item_id`,
				`meta_qty`.`meta_value` AS `_qty`
			FROM
			    `{$wpdb->prefix}woocommerce_order_itemmeta` AS `meta_qty`
			WHERE
				`meta_qty`.`meta_key` = '_qty'
				AND `meta_qty`.`order_item_id` IN ( {$order_item_ids_in} )
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $order_item_ids ) );

		$order_item_meta = [];

		// Build result array by Order Item ID.
		foreach ( $results as $result ) {
			$order_item_meta[ (int) $result->order_item_id ] = $result;
		}

		return $order_item_meta;
	}

	/**
	 * Accepts a reference to a product (either an object or a numeric ID) and
	 * tests to see if it functions as a ticket: if so, the corresponding event
	 * object is returned. If not, boolean false is returned.
	 *
	 * @param $ticket_product
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

		$event = get_post_meta( $ticket_product, $this->event_key, true );

		if ( empty( $event ) ) {
			return false;
		}

		if ( in_array( get_post_type( $event ), Tribe__Tickets__Main::instance()->post_types() ) ) {
			return get_post( $event );
		}

		return false;
	}

	/**
	 * Get attendees by id and associated post type
	 * or default to using $post_id
	 *
	 * @param      $post_id
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

		/**
		 * Filters the attendees returned after a query.
		 *
		 * @since 4.7
		 *
		 * @param array  $attendees
		 * @param int    $post_id The post ID attendees were requested for.
		 * @param string $post_type
		 */
		return apply_filters( 'tribe_tickets_plus_woo_get_attendees', $attendees, $post_id, $post_type );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_attendee( $attendee, $post_id = 0 ) {
		if ( is_numeric( $attendee ) ) {
			$attendee = get_post( $attendee );
		}

		if (
			! $attendee instanceof WP_Post
			|| $this->attendee_object !== $attendee->post_type
		) {
			return false;
		}

		$order_id      = get_post_meta( $attendee->ID, $this->attendee_order_key, true );
		$order_item_id = get_post_meta( $attendee->ID, $this->attendee_order_item_key, true );
		$checkin       = get_post_meta( $attendee->ID, $this->checkin_key, true );
		$optout        = get_post_meta( $attendee->ID, $this->attendee_optout_key, true );
		$security      = get_post_meta( $attendee->ID, $this->security_code, true );
		$product_id    = get_post_meta( $attendee->ID, $this->attendee_product_key, true );
		$user_id       = get_post_meta( $attendee->ID, $this->attendee_user_id, true );
		//$ticket_sent   = (bool) get_post_meta( $attendee->ID, $this->attendee_ticket_sent, true );

		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );

		if ( empty( $product_id ) ) {
			return false;
		}

		$product          = get_post( $product_id );
		$product_title    = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product, true ) . ' ' . __( '(deleted)', 'wootickets' );
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
		$attendee_data = array_merge( $this->get_order_data( $order_id ), [
			'ticket'        => $product_title,
			'attendee_id'   => $attendee->ID,
			'order_item_id' => $order_item_id,
			'security'      => $security,
			'product_id'    => $product_id,
			'check_in'      => $checkin,
			'optout'        => $optout,
			'user_id'       => $user_id,
			//'ticket_sent'   => $ticket_sent,

			// Fields for Email Tickets
			'event_id'      => get_post_meta( $attendee->ID, $this->attendee_event_key, true ),
			'ticket_name'   => ! empty( $product ) ? $product->post_title : false,
			'holder_name'   => $this->get_holder_name( $attendee, $order_id ),
			'ticket_id'     => $ticket_unique_id,
			'qr_ticket_id'  => $attendee->ID,
			'security_code' => $security,

			// Attendee Meta
			'attendee_meta' => $meta,
		] );

		/**
		 * Allow users to filter the Attendee Data
		 *
		 * @param array An associative array with the Information of the Attendee
		 * @param string What Provider is been used
		 * @param WP_Post Attendee Object
		 * @param int Post ID
		 *
		 */
		$attendee_data = apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'woo', $attendee, $post_id );

		return $attendee_data;
	}

	/**
	 * Get holder name, from existing meta if possible.
	 *
	 * @param $attendee
	 * @param $order_id
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	protected function get_holder_name( $attendee, $order_id ) {
		return get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true );
	}

	/**
	 * Retreive only order related information
	 *
	 *     order_id
	 *     order_id_display
	 *     order_id_link
	 *     order_id_link_src
	 *     order_status
	 *     order_status_label
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
		$name               = get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true );
		$email              = get_post_meta( $order_id, '_billing_email', true );
		$status             = get_post_status( $order_id );
		$order_status       = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
		$order_status_label = wc_get_order_status_name( $order_status );
		$order_warning      = false;

		// Warning flag for refunded, cancelled and failed orders
		$warning_statues = tribe( 'tickets.status' )->get_statuses_by_action( 'warning', 'woo' );
		if ( in_array( $status, $warning_statues, true ) ) {
			$order_warning = true;
		}

		// Warning flag where the order post was trashed
		if ( ! empty( $order_status ) && get_post_status( $order_id ) == 'trash' ) {
			$order_status_label = sprintf( __( 'In trash (was %s)', 'event-tickets-plus' ), $order_status_label );
			$order_warning      = true;
		}

		// Warning flag where the order has been completely deleted
		if ( empty( $order_status ) && ! get_post( $order_id ) ) {
			$order_status_label = __( 'Deleted', 'event-tickets-plus' );
			$order_warning      = true;
		}

		$order            = wc_get_order( $order_id );
		$display_order_id = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order_id;
		$order_link_src   = esc_url( get_edit_post_link( $order_id, true ) );
		$order_link       = sprintf( '<a class="row-title" href="%s">%s</a>', $order_link_src, esc_html( $display_order_id ) );

		$data = [
			'order_id'           => $order_id,
			'order_id_display'   => $display_order_id,
			'order_id_link'      => $order_link,
			'order_id_link_src'  => $order_link_src,
			'order_status'       => $order_status,
			'order_status_label' => $order_status_label,
			'order_warning'      => $order_warning,
			'purchaser_name'     => $name,
			'purchaser_email'    => $email,
			'provider'           => __CLASS__,
			'provider_slug'      => 'woo',
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
		$data = apply_filters( 'tribe_tickets_order_data', $data, 'woo', $order_id );

		return $data;
	}

	/**
	 * Returns the order status.
	 *
	 * @todo remove safety check against existence of wc_get_order_status_name() in future release
	 *       (exists for backward compatibility with versions of WC below 2.2)
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	protected function order_status( $order_id ) {
		if ( ! function_exists( 'wc_get_order_status_name' ) ) {
			return __( 'Unknown', 'event-tickets-plus' );
		}

		return wc_get_order_status_name( get_post_status( $order_id ) );
	}

	/**
	 * Marks an attendee as checked in for an event
	 *
	 * @param $attendee_id
	 * @param $qr true if from QR checkin process
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
		do_action( 'wootickets_checkin', $attendee_id, $qr );

		return true;
	}

	/**
	 * Remove the Post Transients when a WooCommerce Ticket is Checked In
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
	 * Add the extra options in the admin's new/edit ticket metabox
	 *
	 * @param $post_id
	 * @param $ticket_id
	 *
	 * @return void
	 */
	public function do_metabox_capacity_options( $post_id, $ticket_id ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$is_correct_provider = $tickets_handler->is_correct_provider( $post_id, $this );

		$url               = '';
		$stock             = '';
		$global_stock_mode = $tickets_handler->get_default_capacity_mode();
		$global_stock_cap  = 0;
		$capacity          = null;
		$event_capacity    = null;

		$stock_object = new Tribe__Tickets__Global_Stock( $post_id );

		if ( $stock_object->is_enabled() ) {
			$event_capacity = tribe_tickets_get_capacity( $post_id );
		}

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = $tickets_handler->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$stock             = $ticket->managing_stock() ? $ticket->stock() : '';
				$capacity          = tribe_tickets_get_capacity( $ticket->ID );
				$global_stock_mode = ( method_exists( $ticket, 'global_stock_mode' ) ) ? $ticket->global_stock_mode() : '';
				$global_stock_cap  = ( method_exists( $ticket, 'global_stock_cap' ) ) ? $ticket->global_stock_cap() : 0;
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/woocommerce-metabox-capacity.php';
	}

	/**
	 * Add the extra options in the admin's new/edit ticket metabox portion that is loaded via ajax
	 * Currently, that includes the sku, ecommerce links, and ticket history
	 *
	 * @since 4.6
	 *
	 * @param int $post_id id of the event post
	 * @param int $ticket_id (null) id of the ticket
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
	 * Add the sku field in the admin's new/edit ticket metabox
	 *
	 * @since 4.6
	 *
	 * @param     $post_id int id of the event post
	 * @param int $ticket_id (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_sku_options( $post_id, $ticket_id = null ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$sku                 = '';
		$is_correct_provider = $tickets_handler->is_correct_provider( $post_id, $this );

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = $tickets_handler->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$sku = get_post_meta( $ticket_id, '_sku', true );
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/woocommerce-metabox-sku.php';
	}

	/**
	 * Add the extra options in the admin's new/edit ticket metabox
	 *
	 * @since 4.6
	 *
	 * @param     $post_id int id of the event post
	 * @param int $ticket_id (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_ecommerce_links( $post_id, $ticket_id = null ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$is_correct_provider = $tickets_handler->is_correct_provider( $post_id, $this );

		if ( empty( $ticket_id ) ) {
			$ticket_id = tribe_get_request_var( 'ticket_id' );
		}

		$ticket              = $this->get_ticket( $post_id, $ticket_id );
		$is_correct_provider = $tickets_handler->is_correct_provider( $ticket_id, $this );

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/woocommerce-metabox-ecommerce.php';
	}

	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_event_reports_link( $post_id ) {
		$ticket_ids = (array) $this->get_tickets_ids( $post_id );
		if ( empty( $ticket_ids ) ) {
			return '';
		}

		$query = [
			'post_type' => 'tribe_events',
			'page'      => 'tickets-orders',
			'event_id'  => $post_id,
		];

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		/**
		 * Filter the Event Ticket Orders (Sales) Report URL
		 *
		 * @param string Report URL
		 * @param int Event ID
		 * @param array Ticket IDs
		 *
		 * @return string
		 */
		$report_url = apply_filters( 'tribe_events_tickets_report_url', $report_url, $post_id, $ticket_ids );

		return '<small> <a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Event sales report', 'event-tickets-plus' ) . '</a> </small>';
	}

	/**
	 * Links to the sales report for this product.
	 * As of 4.6 we reversed the params and deprecated $event_id as it was never used
	 *
	 * @param deprecated $event_id_deprecated ID of the event post
	 * @param int        $ticket_id (null) id of the ticket
	 *
	 * @return string
	 */
	public function get_ticket_reports_link( $event_id_deprecated, $ticket_id ) {
		if ( ! empty( $event_id_deprecated ) ) {
			_deprecated_argument( __METHOD__, '4.6' );
		}

		if ( empty( $ticket_id ) ) {
			return '';
		}

		$query = [
			'page'        => 'wc-reports',
			'tab'         => 'orders',
			'report'      => 'sales_by_product',
			'product_ids' => $ticket_id,
		];

		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	/**
	 * Registers a metabox in the WooCommerce product edit screen
	 * with a link back to the product related Event.
	 *
	 */
	public function woocommerce_meta_box() {
		$post_id = get_post_meta( get_the_ID(), $this->event_key, true );

		if ( ! empty( $post_id ) ) {
			add_meta_box( 'wootickets-linkback', 'Event', [ $this, 'woocommerce_meta_box_inside' ], 'product', 'normal', 'high' );
		}
	}

	/**
	 * Contents for the metabox in the WooCommerce product edit screen
	 * with a link back to the product related Event.
	 */
	public function woocommerce_meta_box_inside() {
		$post_id = get_post_meta( get_the_ID(), $this->event_key, true );
		if ( ! empty( $post_id ) ) {
			$text = esc_html( sprintf(
				__( 'This is a %s for the event:', 'event-tickets-plus' ),
				tribe_get_ticket_label_singular_lowercase( 'woo_meta_box' )
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
	 * Indicates if global stock support is enabled (for WooCommerce the default is
	 * true).
	 *
	 * @return bool
	 */
	public function supports_global_stock() {
		/**
		 * Allows the declaration of global stock support for WooCommerce tickets
		 * to be overridden.
		 *
		 * @param bool $enable_global_stock_support
		 */
		return (bool) apply_filters( 'tribe_tickets_woo_enable_global_stock', true );
	}

	/**
	 * Determine if the event is set to use global stock for its tickets.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function uses_global_stock( $post_id ) {
		// In some cases (version mismatch with Event Tickets) the Global Stock class may not be available
		if ( ! class_exists( 'Tribe__Tickets__Global_Stock' ) ) {
			return false;
		}

		$global_stock = new Tribe__Tickets__Global_Stock( $post_id );

		return $global_stock->is_enabled();
	}

	/**
	 * Get's the WC product price html
	 *
	 * @param int|object $product
	 * @param array      $attendee
	 *
	 * @return string
	 */
	public function get_price_html( $product, $attendee = false ) {
		if ( is_numeric( $product ) ) {
			if ( class_exists( 'WC_Product_Simple' ) ) {
				$product = new WC_Product_Simple( $product );
			} else {
				$product = new WC_Product( $product );
			}
		}

		if ( ! method_exists( $product, 'get_price_html' ) ) {
			return '';
		}

		$price_html = $product->get_price_html();

		/**
		 * Allow filtering of the Price HTML
		 *
		 * @since 4.3.2
		 *
		 * @param string $price_html
		 * @param mixed  $product
		 * @param mixed  $attendee
		 *
		 */
		return apply_filters( 'tribe_events_wootickets_ticket_price_html', $price_html, $product, $attendee );
	}

	/**
	 * Gets the product price value
	 *
	 * @since  4.6
	 *
	 * @param  int|WP_Post $product
	 *
	 * @return string
	 */
	public function get_price_value( $product ) {
		if ( ! $product instanceof WP_Post ) {
			$product = get_post( $product );
		}

		if ( ! $product instanceof WP_Post ) {
			return false;
		}

		$product = wc_get_product( $product->ID );

		return $product->get_price();
	}

	/**
	 * Adds an action to resend the tickets to the customer
	 * in the WooCommerce actions dropdown, in the order edit screen.
	 *
	 * @param $emails
	 *
	 * @return array
	 */
	public function add_resend_tickets_action( $emails ) {
		$order = get_the_ID();

		if ( empty( $order ) ) {
			return $emails;
		}

		$has_tickets = get_post_meta( $order, $this->order_has_tickets, true );

		if ( ! $has_tickets ) {
			return $emails;
		}

		if ( version_compare( wc()->version, '3.2.0', '>=' ) ) {
			$emails['resend_tickets_email'] = esc_html__( 'Resend tickets email', 'event-tickets-plus' );
		} else {
			$emails[] = 'wootickets';
		}

		return $emails;
	}

	/**
	 * (Re-)sends the tickets email on request.
	 *
	 * Accepts either the order ID or the order object itself.
	 *
	 * @since 4.5.6
	 *
	 * @param WC_Order|int $order_ref
	 **/
	public function send_tickets_email( $order_ref ) {
		$order_id = $order_ref instanceof WC_Order
			? $order_ref->get_id()
			: $order_ref;


		update_post_meta( $order_id, $this->mail_sent_meta_key, '1' );

		// Ensure WC_Emails exists else our attempt to mail out tickets will fail
		WC_Emails::instance();

		/**
		 * Fires when a ticket order is complete.
		 *
		 * Back-compatibility action hook.
		 *
		 * @since 4.1
		 *
		 * @param int $order_id The order post ID for the ticket.
		 */
		do_action( 'wootickets-send-tickets-email', $order_id );
	}

	private function get_cancelled( $ticket_id ) {
		$cancelled = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled::for_ticket( $ticket_id );

		return $cancelled->get_count();
	}

	/*
	 * Get the number of refunded orders for a ticket.
	 * Works with partial and full refunds.
	 *
	 * @since 4.7.3
	 *
	 * @param int $ticket_id Ticket ID
	 *
	 * @return int
	 */
	private function get_refunded( $ticket_id ) {
		/** @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded $refunded */
		$refunded = tribe( 'commerce.woo.order.refunded' );

		return $refunded->get_count( $ticket_id );
	}

	/**
	 * @param $order_id
	 */
	protected function complete_order( $order_id ) {
		$this->send_tickets_email( $order_id );

		// Clear WooCommerce Cart once the order is Done
		if ( null !== WC()->cart ) {
			WC()->cart->empty_cart();
		}

		/**
		 * Fires when a ticket order is complete.
		 *
		 * @since 4.2
		 *
		 * @param int $order_id The order post ID for the ticket.
		 */
		do_action( 'event_tickets_woo_complete_order', $order_id );
	}

	/**
	 * Filter the maximum quantity allowed to purchase at a time for WooCommerce.
	 *
	 * @since 4.8.1
	 * @since 4.11.1 If backorders are not allowed and Stock Quantity is lower than zero, correct it to be zero.
	 * @since 4.12.0 Adjust return value to match how Event Tickets' filter changed (now requires a value of 1+,
	 *               even if Unlimited).
	 *
	 * @param int                           $available_at_a_time Max quantity allowed to be purchased at a time (0+).
	 * @param Tribe__Tickets__Ticket_Object $ticket              Ticket Object.
	 *
	 * @return int Zero is out of stock, else it's limited by ticket stock or max allowed to purchase in a single
	 *             action (always a positive integer, even if Unlimited).
	 */
	public function filter_ticket_max_purchase( $available_at_a_time, $ticket ) {
		// Bails on invalid Ticket ID.
		if (
			empty( $ticket->ID )
			|| ! is_numeric( $ticket->ID )
		) {
			return $available_at_a_time;
		}

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' !== $ticket->provider_class ) {
			return $available_at_a_time;
		}

		if ( class_exists( 'WC_Product_Simple' ) ) {
			$product = new WC_Product_Simple( $ticket->ID );
		} else {
			$product = new WC_Product( $ticket->ID );
		}

		/**
		 * Protect against negative stock quantity.
		 * If backorders are enabled or `null`, will be corrected to `-1` for Unlimited.
		 */
		$stock_qty = $product->get_stock_quantity();

		if ( ! is_numeric( $stock_qty ) ) {
			$stock_qty = - 1;
		} elseif ( 0 > (int) $stock_qty ) {
			// Don't confuse a Stock Qty of -1 as being Unlimited
			$stock_qty = 0;
		}

		/**
		 * Max Quantity will be unlimited if backorders are allowed, restricted to 1 if the product is constrained to
		 * be sold individually, or else set to the available stock quantity.
		 */
		$max_quantity = $product->backorders_allowed() ? - 1 : $stock_qty;
		$max_quantity = $product->is_sold_individually() ? 1 : $max_quantity;

		// If Unlimited, set to Max At A Time.
		if ( - 1 === $max_quantity ) {
			$max_quantity = $available_at_a_time;
		}

		// Quantity in stock may be less than Max At A Time.
		$max_quantity = min( $available_at_a_time, $max_quantity );

		return $max_quantity;
	}

	/**
	 * Excludes WooCommerce product post types from the list of supported post types that Tickets can be attached to
	 *
	 * @since 4.0.5
	 *
	 * @param array $post_types Array of supported post types
	 *
	 * @return array
	 */
	public function exclude_product_post_type( $post_types ) {
		if ( isset( $post_types['product'] ) ) {
			unset( $post_types['product'] );
		}

		return $post_types;
	}

	/**
	 * Returns the ticket price taking the context of the request into account.
	 *
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	protected function get_price_value_for( $product ) {
		return $this->should_show_regular_price() ? $product->get_regular_price() : $product->get_price();
	}

	/**
	 * @return bool
	 */
	protected function should_show_regular_price() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return doing_action( 'wp_ajax_tribe-ticket-edit-' . __CLASS__ )
			|| doing_action( 'wp_ajax_tribe-ticket-add-' . __CLASS__ )
			|| doing_action( 'wp_ajax_tribe-ticket-delete-' . __CLASS__ )
			|| ( is_admin()
				&& ! empty( $screen )
				&& $screen->base === 'post'
				&& $screen->parent_base === 'edit' );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	protected function get_regular_price_html( $product ) {
		$this->product = $product;

		// The hook names are slightly different in WC 3.x vs WC 2.x
		$filter_prefix = 'woocommerce';

		if ( version_compare( WC()->version, '3.0', '>=' ) ) {
			$filter_prefix .= '_product';
		}

		add_filter( "{$filter_prefix}_get_price", [ $this, 'get_regular_price' ], 99, 2 );

		$price_html = $product->get_price_html();

		remove_filter( "{$filter_prefix}_get_price", [ $this, 'get_regular_price' ], 99 );

		return $price_html;
	}

	/**
	 * @param mixed      $price
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function get_regular_price( $price, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $price;
		}

		if ( $this->get_product_id( $product ) == $this->get_product_id( $this->product ) ) {
			return $product->get_regular_price();
		}

		return $price;
	}


	/**
	 * Renders the tabbed view header before the report.
	 *
	 * @param Tribe__Tickets__Attendees $handler
	 */
	public function render_tabbed_view( Tribe__Tickets__Attendees $handler ) {
		$post = $handler->get_post();

		$has_tickets = count( (array) self::get_tickets( $post->ID ) );
		if ( ! $has_tickets ) {
			return;
		}

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$tabbed_view = new Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Report_Tabbed_View();
		$tabbed_view->register();
	}

	/**
	 * Given a WooCommerce product object, returns the product ID.
	 *
	 * This helper allows us to support both WooCommerce 2.x and 3.x, which allow access to
	 * the product ID in slightly different ways.
	 *
	 * @param WC_Data|WC_Product $product
	 *
	 * @return int
	 */
	public function get_product_id( $product ) {
		return method_exists( $product, 'get_id' )
			? (int) $product->get_id()
			: (int) $product->id;
	}

	/**
	 * Given an order and an order item, returns the product associated with the item.
	 *
	 * This helper allows us to support both WooCommerce 2.x and 3.x, which each have different
	 * ways of providing access to that information.
	 *
	 * @param WC_Order      $order
	 * @param WC_Order_Item $item
	 *
	 * @return WC_Product
	 */
	public function get_product_from_item( $order, $item ) {
		return method_exists( $item, 'get_product' )
			? $item->get_product()
			: $order->get_product_from_item( $item );
	}

	/**
	 * If a user saves a ticket in their cart and after a few hours / days the ticket is still on the cart but the ticket has
	 * expired or is no longer available for sales the item on the cart shouldn't be processed.
	 *
	 * Instead of removing the product from the cart we send a notice and avoid to checkout so the user knows exactly why can
	 * move forward and he needs to take an action before doing so.
	 *
	 * @since 4.7.3
	 *
	 * @return bool
	 */
	public function validate_tickets() {

		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$product_id = empty( $values['product_id'] ) ? null : $values['product_id'];

			if ( is_null( $product_id ) ) {
				continue;
			}

			$ticket_type = Tribe__Tickets__Tickets::load_ticket_object( $product_id );

			if ( ! $ticket_type || ! $ticket_type instanceof Tribe__Tickets__Ticket_Object ) {
				continue;
			}

			if ( ! $ticket_type->date_in_range() ) {

				$message = sprintf(
					__( 'The ticket: %1$s, in your cart is no longer available or valid. You need to remove it from your cart in order to continue.', 'event-tickets-plus' ),
					$ticket_type->name
				);

				wc_add_notice( $message, 'error' );

				return false;
			}
		}

		return true;
	}

	/**
	 * Redirect to the cart from a POST type of request to a request with code 303 in order to prevent the browser
	 * to send the same data multiple times on browser refresh.
	 *
	 * @see https://en.wikipedia.org/wiki/Post/Redirect/Get
	 *
	 * @since  4.7.3
	 */
	public function redirect_to_cart() {
		$cart_url = get_transient( $this->get_cart_transient_key() );

		if ( ! empty( $cart_url ) ) {

			/**
			 * Filter to allow the change the URL where the users are redirected by default uses the wc_get_cart_url()
			 * value.
			 *
			 * @since 4.7.3
			 *
			 * @param string $location
			 */
			$location = apply_filters( 'tribe_tickets_plus_woo_cart_location', $cart_url );

			delete_transient( $this->get_cart_transient_key() );

			wp_redirect( $location, WP_Http::SEE_OTHER );
			die();
		}
	}

	/**
	 * Get the key used to store the cart transient URL.
	 *
	 * @since 4.7.3
	 *
	 * @return string
	 */
	public function get_cart_transient_key() {
		return $this->cart_location_cache_prefix . $this->get_session_hash();
	}

	/**
	 * Generates as hash based on the user session, user cart or user ID
	 *
	 * @since 4.7.3
	 *
	 * @return string
	 */
	private function get_session_hash() {

		$hash = get_current_user_id();

		if ( defined( 'COOKIEHASH' ) && isset( $_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ] ) ) {
			$hash = $_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ];
		} elseif ( ! empty( $_COOKIE['woocommerce_cart_hash'] ) ) {
			$hash = $_COOKIE['woocommerce_cart_hash'] . get_current_user_id();
		}

		return md5( $hash );
	}

	/**
	 * Get the default Currency selected for Woo
	 *
	 * @since 4.7.3
	 *
	 * @return string
	 */
	public function get_currency() {
		return function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : parent::get_currency();
	}

	/**
	 * Clean the attendees cache every time an order changes its
	 * status, so the changes are reflected instantly.
	 *
	 * @since 4.7.3
	 *
	 * @param int $order_id
	 */
	public function reset_attendees_cache( $order_id ) {

		// Get the items purchased in this order
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		/** @var Tribe__Post_Transient $transient */
		$ticket_ids = [];
		$transient  = tribe( 'post-transient' );

		// Iterate over each product
		foreach ( (array) $order_items as $item_id => $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];
			// Get the event this tickets is for
			$post_id = get_post_meta( $product_id, $this->event_key, true );

			/**
			 * Action fired when an attendee data is updated when on the cache.
			 *
			 * @since 4.10.1.2
			 *
			 * @param int $post_id ID of the event associated with the order.
			 * @param int $order_id ID of the order attached to this event.
			 * @param array $item Details of the order
			 */
			do_action( 'tribe_tickets_plus_woo_reset_attendee_cache', $post_id, $order_id, $item );

			if ( ! empty( $post_id ) ) {
				$ticket_ids[] = $product_id;

				// Delete the attendees cache for that event
				$transient->delete( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
			}
		}

		$cache_key = self::ORDER_COUNT_CACHE_KEY . '_incomplete';

		// Delete count transients for tickets in the order.
		foreach ( $ticket_ids as $ticket_id ) {
			$transient->delete( $ticket_id, $cache_key );
		}
	}

	/**
	 * Sincronize the Event cost from an array of
	 * product IDs
	 *
	 * @since 4.7.3
	 *
	 * @param array $product_ids
	 */
	public function syncronize_products( $product_ids ) {

		if ( $product_ids ) {

			foreach ( $product_ids as $product_id ) {
				$event = $this->get_event_for_ticket( $product_id );

				// This product is not connected with an event
				if ( ! $event ) {
					continue;
				}

				// Trigger an update
				Tribe__Events__API::update_event_cost( $event->ID );
			}
		}
	}

	/**
	 * Get the warning tooltip HTML for the ticket table
	 *
	 * @param Tribe__Tickets__Ticket_Object|int $ticket ticket object (or ID)
	 * @param int $event_id event ID for the ticket
	 *
	 * @return string HTML string for tooltip insertion
	 */
	public function get_ticket_table_warnings( $ticket, $event_id ) {
		// no ticket, no event? bail
		if ( empty( $ticket ) || empty( $event_id ) ) {
			return;
		}

		// Just in case...
		if ( is_numeric( $ticket ) ) {
			$ticket = $this->get_ticket( $event_id, $ticket );
		}

		if ( __CLASS__ !== $ticket->provider_class ) {
			return;
		}

		$messages  = [];
		$inventory = (int) $ticket->inventory();
		$stock     = (int) $ticket->stock();
		$product   = wc_get_product( $ticket->ID );

		/** @var Tribe__Tickets__Attendees $tickets_attendees */
		$tickets_attendees = tribe( 'tickets.attendees' );

		if ( -1 !== $ticket->capacity() ) {
			$shared_stock = new Tribe__Tickets__Global_Stock( $event_id );

			if (
				$inventory !== $stock
				&& ( ! $shared_stock->is_enabled() || $stock < (int) $shared_stock->get_stock_level() )
			) {
				$messages['mismatch'] = sprintf(
					_x( 'The number of Complete ticket sales does not match the number of attendees. Please check the %1$sAttendees list%2$s and adjust ticket stock in WooCommerce as needed.', 'event-tickets-plus' ),
					'<a href="' . $tickets_attendees->get_report_link( get_post( $event_id ) ) . '">',
					'</a>'
				);
			}
		}

		if ( 'own' === $ticket->global_stock_mode() && ! $product->get_manage_stock() ) {
			$messages['stock'] = sprintf(
				_x( '"Unlimited" will be displayed unless you enable the WooCommerce\'s "Manage stock" setting. You can do so %1$shere%2$s.', 'event-tickets-plus' ),
				'<a href="' . esc_url( $ticket->admin_link ) . '">',
				'</a>'
			);
		}

		if ( empty( $messages ) ) {
			return;
		}

		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-warning required"></span>
			<div class="down" <?php if ( 1 < count( $messages ) ) { echo 'style="width: 370px;"';} ?>>
				<?php foreach( $messages as $type => $message ) : ?>
					<p>
						<span><?php echo $message; ?></span>
					</p>
				<?php endforeach;?>
			</div>
		</div>
		<?php

		echo ob_get_clean();
	}

	/**
	 * Add our class suffix to the list of classes for the attendee registration form
	 * This gets appended to `tribe-tickets__item__attendee__fields__form--` so keep it short and sweet
	 *
	 * @param array $classes existing array of classes
	 * @return array $classes with our class added
	 */
	public function tribe_attendee_registration_form_class( $classes ) {
		$classes[ $this->attendee_object ] = 'woo';

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
			'woo',
			'tribe_wooticket',
			__CLASS__,
		];

		if ( in_array( $provider, $options, true ) ) {
			return $this;
		}

		return $provider_obj;
	}

	/**
	 * Output notice explaining to user that they're at Attendee Registration page instead of Checkout because ticket
	 * quantity was modified in Cart.
	 *
	 * @since 4.11.0
	 *
	 * @param string $passed_provider       The 'provider' $_REQUEST var.
	 * @param string $passed_provider_class The class string or empty string if ticket provider is not found.
	 * @param array  $events                The array of events, which might be empty.
	 */
	public function woo_attendee_registration_notice_cart_qty_change( $passed_provider, $passed_provider_class, $events ) {
		if (
			$this->cart_change_notice_displayed
			|| $this->attendee_object !== $passed_provider
			|| empty( $events )
			|| false === tribe_is_truthy( WC()->session->get( 'tribe_ar_ticket_updated' ) )
			|| ! tribe_is_truthy( tribe_get_option( 'ticket-attendee-modal', false ) )
		) {
			return;
		}

		/** @var Tribe__Tickets__Attendee_Registration__View $view */
		$view = tribe( 'tickets.attendee_registration.view' );
		?>
		<div class="tribe-common">
			<?php
			$view->template(
				'components/notice',
				[
					'id' => 'tribe-tickets-plus__ar-notice__cart-qty-change',
					'notice_classes' => [
						'tribe-tickets-plus-woo-ar-notice__cart-qty-change',
					],
					'title' => _x(
						'Updated Ticket Quantities',
						'Attendee Registration notice heading text when Woo cart quantity changed',
						'event-tickets-plus'
					),
					'content' => sprintf(
						esc_html_x(
							'You\'ve arrived here because you updated ticket quantities in the Cart. Please verify your attendee information, then click "Save Attendee Info" in order to proceed to Checkout.',
							'Attendee Registration notice paragraph text when Woo cart quantity changed',
							'event-tickets-plus'
						)
					)
				]
			);
			?>
		</div>
		<?php
		$this->cart_change_notice_displayed = true;
	}

	/**
	 * Clear the WC session var when we load checkout.
	 *
	 * @since 4.11.0
	 *
	 * @param mixed $unused_var An unused variable we don't need to reference from the hook.
	 *
	 * @return mixed The unused variable just as it was passed from the hook.
	 */
	public function clear_tribe_ar_ticket_updated( $unused_var = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return $unused_var;
		}

		$wc = WC();

		if ( empty( $wc->session ) ) {
			return $unused_var;
		}

		$wc->session->__unset( 'tribe_ar_ticket_updated' );

		return $unused_var;
	}

	/**
	 * Adds a link back to the attendee registration page and cart from checkout.
	 * Adds provider param to links.
	 *
	 * @since 4.11.0
	 */
	public function add_checkout_links() {
		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Cart $cart_tickets */
		$cart_tickets = tribe( 'tickets-plus.commerce.woo.cart')->get_tickets_in_cart();

		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Cart $cart */
		$cart = tribe( 'tickets-plus.commerce.woo.cart' );

		/** @var Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
		$attendee_registration = tribe( 'tickets.attendee_registration' );

		$tickets_in_cart = $cart->get_tickets_in_cart();

		/** @var Tribe__Tickets_Plus__Main $tickets_plus_main */
		$tickets_plus_main = tribe( 'tickets-plus.main' );

		$cart_has_meta = $tickets_plus_main->meta()->cart_has_meta( $tickets_in_cart );

		echo '<div class="tribe-checkout-backlinks">';

		echo sprintf(
			'<a class="tribe-checkout-backlink" href="%1$s">%2$s</a>',
			esc_url( add_query_arg( 'provider', $this->attendee_object, $this->get_cart_url() ) ),
			esc_html__( 'Return to cart', 'event-tickets-plus' )
		);

		// only show the AR link if we have ARI
		if ( ! empty( $cart_has_meta ) ) {
			echo sprintf(
				'<a class="tribe-checkout-backlink" href="%1$s">%2$s</a>',
				esc_url( add_query_arg( 'provider', $this->attendee_object, $attendee_registration->get_url() ) ),
				esc_html__( 'Edit attendee info', 'event-tickets-plus' )
			);
		}

		echo '</div>';
	}

	/**
	 * Get the price suffix from WooCommerce method.
	 *
	 * @param WC_Product $product the WooCommerce product.
	 * @param string     $price to calculate, left blank to just use get_price().
	 * @param integer    $qty   passed on to get_price_including_tax() or get_price_excluding_tax().
	 *
	 * @since 4.12.0
	 *
	 * @return string
	 */
	public function get_price_suffix( $product, $price = '', $qty = 1 ) {
		return $product->get_price_suffix( $price, $qty );
	}
}
