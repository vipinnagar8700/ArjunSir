<?php

class Tribe__Tickets_Plus__Meta {

	const ENABLE_META_KEY = '_tribe_tickets_meta_enabled';

	/**
	 * This meta key is used for 3 slightly different purposes depending on post_type
	 *
	 * product - the meta fields configuration for ticket
	 * shop_order - the meta values at the time of the order, not updated on future edits
	 * tribe_wooticket - the current meta values for each attendee
	 */
	const META_KEY = '_tribe_tickets_meta';

	private $path;
	private $meta_fieldset;
	private $rsvp_meta;
	private $render;

	/**
	 * @var Tribe__Tickets_Plus__Meta__Storage
	 */
	protected $storage;

	/**
	 * @var Tribe__Tickets_Plus__Meta__Export
	 */
	protected $export;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Tribe__Tickets_Plus__Meta constructor.
	 *
	 * @param string                                   $path
	 * @param Tribe__Tickets_Plus__Meta__Storage|null $storage An instance of the meta storage handler.
	 */
	public function __construct( $path = null, Tribe__Tickets_Plus__Meta__Storage $storage = null ) {
		$this->storage = $storage ? $storage : new Tribe__Tickets_Plus__Meta__Storage();

		if ( ! is_null( $path ) ) {
			$this->path = trailingslashit( $path );
		}


		add_action( 'event_tickets_after_save_ticket', array( $this, 'save_meta' ), 10, 3 );

		add_action( 'event_tickets_ticket_list_after_ticket_name', array( $this, 'maybe_render_custom_meta_icon' ) );
		add_action( 'tribe_events_tickets_metabox_edit_accordion_content', array( $this, 'accordion_content' ), 10, 2 );

		/* Ajax filters and actions */
		add_filter( 'tribe_events_tickets_metabox_edit_attendee', array( $this, 'ajax_attendee_meta' ), 10, 2 );
		add_action( 'wp_ajax_tribe-tickets-info-render-field', array( $this, 'ajax_render_fields' ) );
		add_action( 'wp_ajax_tribe-tickets-load-saved-fields', array( $this, 'ajax_render_saved_fields' ) );
		add_action( 'woocommerce_remove_cart_item', array( $this, 'clear_storage_on_remove_cart_item' ), 10, 2 );

		// Check if the attendee registration cart has required meta
		add_filter( 'tribe_tickets_attendee_registration_has_required_meta', array( $this, 'filter_cart_has_required_meta' ), 20, 2 );

		// Commerce hooks.
		add_filter( 'tribe_tickets_commerce_cart_get_data', [ $this, 'get_cart_data' ], 10, 3 );
		add_filter( 'tribe_tickets_commerce_cart_get_ticket_meta', [ $this, 'get_ticket_meta' ], 10, 2 );
		add_action( 'tribe_tickets_commerce_cart_update_ticket_meta', [ $this, 'update_ticket_meta' ], 10, 5 );

		$this->meta_fieldset();
		$this->render();
		$this->rsvp_meta();
		$this->export();
	}

	public function meta_fieldset() {
		if ( ! $this->meta_fieldset ) {
			$this->meta_fieldset = new Tribe__Tickets_Plus__Meta__Fieldset;
		}

		return $this->meta_fieldset;
	}

	/**
	 * Object accessor method for the RSVP meta
	 *
	 * @return Tribe__Tickets_Plus__Meta__RSVP
	 */
	public function rsvp_meta() {
		if ( ! $this->rsvp_meta ) {
			$this->rsvp_meta = new Tribe__Tickets_Plus__Meta__RSVP;
			$this->rsvp_meta->hook();
		}

		return $this->rsvp_meta;
	}

	public function render() {
		if ( ! $this->render ) {
			$this->render = new Tribe__Tickets_Plus__Meta__Render;
		}

		return $this->render;
	}

	/**
	 * @return Tribe__Tickets_Plus__Meta__Export
	 */
	public function export() {
		if ( ! $this->export ) {
			$this->export = new Tribe__Tickets_Plus__Meta__Export;
		}

		return $this->export;
	}

	public function register_resources() {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__Assets::admin_enqueue_scripts' );
	}

	public function wp_enqueue_scripts() {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__Assets::admin_enqueue_scripts' );
	}

	/**
	 * Retrieves custom meta fields for a given ticket
	 *
	 * @param int $ticket_id ID of ticket post
	 * @return array
	 */
	public function get_meta_fields_by_ticket( $ticket_id ) {
		$fields = array();

		if ( empty( $ticket_id ) ) {
			return $fields;
		}

		$field_meta = get_post_meta( $ticket_id, self::META_KEY, true );
		$fields     = array();

		if ( $field_meta ) {
			foreach ( (array) $field_meta as $field ) {
				if ( empty( $field['type'] ) ) {
					continue;
				}

				$field_object = $this->generate_field( $ticket_id, $field['type'], $field );

				if ( ! $field_object ) {
					continue;
				}

				$fields[] = $field_object;
			}
		}

		/**
		 * Filters the fields for a ticket
		 *
		 * @var array $fields array of fields to filter
		 * @param int $ticket_id ID of ticket post
		 * @return array $fields the filtered array
		 */
		$fields = apply_filters( 'event_tickets_plus_meta_fields_by_ticket', $fields, $ticket_id );

		return $fields;
	}

	/**
	 * Retrieves the meta fields for all tickets associated with the specified event.
	 *
	 * @param int $post_id ID of parent "event" post
	 * @return array
	 */
	public function get_meta_fields_by_event( $post_id ) {
		$fields = array();

		foreach ( Tribe__Tickets__Tickets::get_event_tickets( $post_id ) as $ticket ) {
			$meta_fields = $this->get_meta_fields_by_ticket( $ticket->ID );

			if ( is_array( $meta_fields ) && ! empty( $meta_fields ) ) {
				$fields = array_merge( $fields, $meta_fields );
			}
		}

		/**
		 * Returns a list of meta fields in use with various tickets associated with
		 * a specific event.
		 *
		 * @var array $fields
		 * @param int $post_id ID of parent "event" post
		 */
		return apply_filters( 'tribe_tickets_plus_get_meta_fields_by_event', $fields, $post_id );
	}

	/**
	 * Metabox to output the Custom Meta fields
	 *
	 * @since 4.1
	 *
	 * @deprecated 4.6
	 */
	public function metabox( $unused_post_id, $unused_ticket_id ) {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__Meta::accordion_content' );

		$this->accordion_content( $unused_post_id, $ticket_id );
	}

	/**
	 * Function to output accordion button & content to edit ticket panel
	 *
	 * @since 4.6
	 *
	 * @param int $unused_post_id ID of parent "event" post
	 * @param int $ticket_id ID of ticket post
	 */
	public function accordion_content( $unused_post_id, $ticket_id = null ) {
		$is_admin = tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );

		if ( ! $is_admin ) {
			return;
		}

		$enable_meta = $this->meta_enabled( $ticket_id );
		$active_meta = $this->get_meta_fields_by_ticket( $ticket_id );
		$templates   = $this->meta_fieldset()->get_fieldsets();

		tribe( 'tickets-plus.admin.views' )->template( 'attendee-meta', get_defined_vars() );
	}

	/**
	 * Function to output meta content to edit ticket panel
	 *
	 * @since 4.10
	 *
	 * @param int $unused_post_id ID of parent "event" post
	 * @param int $ticket_id ID of ticket post
	 */
	public function meta_content( $ticket_id = null ) {
		$is_admin = tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );

		if ( ! $is_admin ) {
			return;
		}

		$enable_meta = $this->meta_enabled( $ticket_id );
		$active_meta = $this->get_meta_fields_by_ticket( $ticket_id );
		$templates   = $this->meta_fieldset()->get_fieldsets();

		tribe( 'tickets-plus.admin.views' )->template( 'meta-content', get_defined_vars() );
	}

	/**
	 * Gets just the meta fields for insertion via ajax
	 *
	 * @param int $unused_post_id ID of parent "event" post
	 * @param int $ticket_id ID of ticket post
	 * @return string The cutom field(s) html
	 */
	public function ajax_attendee_meta( $unused_post_id, $ticket_id ) {
		$output      = '';
		$active_meta = $this->get_meta_fields_by_ticket( $ticket_id );
		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		foreach ( $active_meta as $meta ) {
			$field = $meta_object->generate_field( $ticket_id, $meta->type, (array) $meta );
			// outputs HTML input field - no escaping
			$output .= $field->render_admin_field();
		}

		return $output;
	}

	/**
	 * Returns whether or not custom meta is enabled for the given ticket
	 *
	 * @param int $ticket_id ID of ticket post
	 * @return bool
	 */
	public function meta_enabled( $ticket_id ) {
		$meta_enabled = get_post_meta( $ticket_id, self::ENABLE_META_KEY, true );

		return (
			'true' === strtolower( $meta_enabled )
			|| 'yes' === strtolower( $meta_enabled )
			|| true === strtolower( $meta_enabled )
			|| 1 == strtolower( $meta_enabled )
		);
	}

	/**
	 * Saves meta configuration on a ticket
	 *
	 * @since 4.1
	 *
	 * @param int                           $unused_post_id ID of parent "event" post
	 * @param Tribe__Tickets__Ticket_Object $ticket         Ticket object
	 * @param array                         $data           Post data that was submitted
	 */
	public function save_meta( $unused_post_id, $ticket, $data ) {
		// Bail if we are not saving ticket input data.
		if ( ! isset( $data['tribe-tickets-input'] ) ) {
			return;
		}

		$data['tribe-tickets-input'] = array_filter( $data['tribe-tickets-input'] );

		if ( empty( $data['tribe-tickets-input'] ) ) {
			$meta = array();
		} else {
			$meta = $this->build_field_array( $ticket->ID, $data );
		}

		// this is for the meta fields configuration associated with the "product" post type
		update_post_meta( $ticket->ID, self::META_KEY, $meta );

		if ( ! $meta ) {
			// no meta? Do not enable meta on the ticket.
			delete_post_meta( $ticket->ID, self::ENABLE_META_KEY );

			return;
		}

		// if there is some meta enable meta for the ticket
		update_post_meta( $ticket->ID, self::ENABLE_META_KEY, 'yes' );

		// Save templates too
		if ( isset( $data['tribe-tickets-save-fieldset'] ) ) {
			$fieldset = wp_insert_post( array(
				'post_type'   => Tribe__Tickets_Plus__Meta__Fieldset::POSTTYPE,
				'post_title'  => empty( $data['tribe-tickets-saved-fieldset-name'] ) ? null : $data['tribe-tickets-saved-fieldset-name'],
				'post_status' => 'publish',
			) );

			// This is for the meta fields template
			update_post_meta( $fieldset, Tribe__Tickets_Plus__Meta__Fieldset::META_KEY, $meta );
		}

	}

	/**
	 * Builds an array of fields
	 *
	 * @param int $ticket_id ID of ticket post
	 * @param array $data field data
	 * @return array array of fields
	 */
	public function build_field_array( $ticket_id, $data ) {
		if ( empty( $data['tribe-tickets-input'] ) ) {
			return array();
		}

		$meta = array();

		foreach ( (array) $data['tribe-tickets-input'] as $field_id => $field ) {
			if ( empty( $field ) || ! is_array( $field ) ) {
				continue;
			}

			$field_object = $this->generate_field( $ticket_id, $field['type'], $field );

			if ( ! $field_object ) {
				continue;
			}

			$meta[] = $field_object->build_field_settings( $field );
		}

		return $meta;
	}

	/**
	 * Outputs ticket custom meta admin fields for an Ajax request
	 */
	public function ajax_render_fields() {

		$data = null;

		if ( empty( $_POST['type'] ) ) {
			wp_send_json_error( '' );
		}

		$field = $this->generate_field( null, $_POST['type'] );

		if ( $field ) {
			$data = $field->render_admin_field();
		}

		if ( empty( $data ) ) {
			wp_send_json_error( $data );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Outputs ticket custom meta admin fields loaded from a group of pre-saved fields for an Ajax request
	 */
	public function ajax_render_saved_fields() {

		$data = null;

		if ( empty( $_POST['fieldset'] ) ) {
			wp_send_json_error( '' );
		}

		$fieldset = get_post( $_POST['fieldset'] );

		if ( ! $fieldset ) {
			wp_send_json_error( '' );
		}

		$template = get_post_meta( $fieldset->ID, Tribe__Tickets_Plus__Meta__Fieldset::META_KEY, true );

		if ( ! $template ) {
			wp_send_json_error( '' );
		}

		foreach ( (array) $template as $field ) {
			$field_object = $this->generate_field( null, $field['type'], $field );

			if ( ! $field_object ) {
				continue;
			}

			$data .= $field_object->render_admin_field();
		}

		if ( empty( $data ) ) {
			wp_send_json_error( $data );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Generates a field object
	 *
	 * @since 4.1
	 *
	 * @param int $ticket_id ID of ticket post the field is attached to
	 * @param string $type Type of field being generated
	 * @param array $data Field settings for the field
	 * @return Tribe__Tickets_Plus__Meta__Field__Abstract_Field child class
	 */
	public function generate_field( $ticket_id, $type, $data = array() ) {
		$class = 'Tribe__Tickets_Plus__Meta__Field__' . ucwords( $type );

		if ( ! class_exists( $class ) ) {
			return null;
		}

		return new $class( $ticket_id, $data );
	}

	/**
	 * Retrieves custom meta data from the cookie
	 *
	 * @since 4.1
	 *
	 * @param int $product_id Commerce provider product ID
	 * @return array
	 */
	public function get_meta_cookie_data( $product_id ) {
		$meta_data = $this->storage->get_meta_data_for( $product_id );
		$meta_data = $this->storage->remove_empty_values_recursive( $meta_data );

		return $meta_data;
	}

	/**
	 * Builds the meta data structure for storage in orders
	 *
	 * @since 4.1
	 *
	 * @param array $product_ids Collection of Product IDs in an order
	 * @return array
	 */
	public function build_order_meta( $product_ids ) {
		if ( ! $product_ids ) {
			return array();
		}

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();
		$meta = array();

		foreach ( $product_ids as $product_id ) {
			$data = $meta_object->get_meta_cookie_data( $product_id );

			if ( ! $data ) {
				continue;
			}

			foreach ( $data as $id => $the_meta ) {
				if ( ! isset( $meta[ $id ] ) ) {
					$meta[ $id ] = array();
				}

				foreach ( $the_meta as $mid => $metadata ) {
					$metadata = array_filter( $metadata );

					$the_meta[ $mid ] = $metadata;
				}

				$meta[ $id ] = array_merge_recursive( $meta[ $id ], $the_meta );
			}
		}

		if ( empty( $meta ) ) {
			return array();
		}

		return $meta;
	}

	/**
	 * Clears the custom meta data stored in the cookie
	 *
	 * @since 4.1
	 *
	 * @param int $product_id Commerce product ID
	 */
	public function clear_meta_cookie_data( $product_id ) {
		$this->storage->delete_meta_data_for( $product_id );
	}

	/**
	 * If the given ticket has attendee meta, render an icon to indicate that
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 */
	public function maybe_render_custom_meta_icon( $ticket ) {
		if ( ! is_admin() ) {
			return;
		}

		$meta = $this->get_meta_fields_by_ticket( $ticket->ID );
		if ( ! $meta ) {
			return;
		}
		?>
		<span title="<?php esc_html_e( 'This ticket has custom Attendee Information fields', 'event-tickets-plus' ); ?>" class="dashicons dashicons-id-alt"></span>
		<?php
	}

	/**
	 * Injects fieldsets into JSON data during ticket add ajax output
	 *
	 * @param array $return Data array to be output in the ajax response for ticket adds
	 * @param int $post_id ID of parent "event" post
	 * @return array $return output Data array with added fieldsets
	 */
	public function inject_fieldsets_in_json( $return, $unused_post_id ) {
		$return['fieldsets'] = $this->meta_fieldset()->get_fieldsets();
		return $return;
	}

	/**
	 * Checks if any of the cart tickets has meta
	 *
	 * @since 4.10.1
	 *
	 * @param array $cart_tickets
	 *
	 * @return bool
	 */
	public function cart_has_meta( $cart_tickets ) {
		// Bail if we don't receive an array
		if ( ! is_array( $cart_tickets ) ) {
			return false;
		}

 		// Bail if we receive an empty array
 		if ( empty( $cart_tickets ) ) {
			return false;
		}

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			if ( $this->ticket_has_meta( $ticket_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle filtering `tribe_tickets_attendee_registration_has_required_meta` to determine whether any of the cart tickets has required meta.
	 *
	 * @since 4.11.0
	 *
	 * @param boolean $cart_has_required_meta Whether the cart has required meta.
	 * @param array   $tickets_in_cart        The array containing the cart elements. Format array( 'ticket_id' => 'quantity' ).
	 *
	 * @return bool Whether any of the cart tickets has required meta.
	 */
	public function filter_cart_has_required_meta( $cart_has_required_meta, $tickets_in_cart ) {
		return $this->cart_has_meta( $tickets_in_cart );
	}

	/**
	 * Checks whether any of the cart tickets has required meta.
	 *
	 * @since 4.9
	 *
	 * @param array $cart_tickets The array containing the cart elements. Format array( 'ticket_id' => 'quantity' ).
	 *
	 * @return bool Whether any of the cart tickets has required meta.
	 */
	public function cart_has_required_meta( $cart_tickets ) {
		// Bail if we don't receive an array
		if ( ! is_array( $cart_tickets ) ) {
			return false;
		}

		// Bail if we receive an empty array
		if ( empty( $cart_tickets ) ) {
			return false;
		}

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			if ( $this->ticket_has_required_meta( $ticket_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * See if a ticket has meta
	 *
	 * @since 4.10.1
	 *
	 * @param int $ticket_id
	 *
	 * @return bool
	 */
	public function ticket_has_meta( $ticket_id ) {
		$has_meta = get_post_meta( $ticket_id, self::ENABLE_META_KEY, true );

		return ! empty( $has_meta ) && tribe_is_truthy( $has_meta );
	}

	/**
	 * See if a ticket has required meta
	 *
	 * @since 4.9
	 *
	 * @param int $ticket_id
	 * @return bool
	 */
	public function ticket_has_required_meta( $ticket_id ) {

		// Return false if ticket does not have meta
		if ( ! $this->ticket_has_meta( $ticket_id ) ) {
			return false;
		}

		return $this->meta_has_required_fields( $ticket_id );

	}

	/**
	 * Checks if a ticket has required meta
	 *
	 * @since 4.9
	 *
	 * @param int    $ticket_id
	 * @param string $slug
	 */
	public function meta_has_required_fields( $ticket_id ) {
 		// Get the meta fields for this ticket
		$ticket_meta = $this->get_meta_fields_by_ticket( $ticket_id );
 		foreach ( $ticket_meta as $meta ) {
			if ( 'on' === $meta->required ) {
				return true;
			}
		}
 		return false;
	}

	/**
	 * Checks if the meta field is required, by slug, for a ticket
	 *
	 * @since 4.9
	 *
	 * @param int    $ticket_id
	 * @param string $slug
	 */
	public function meta_is_required( $ticket_id, $slug ) {

		// Get the meta fields for this ticket
		$ticket_meta = $this->get_meta_fields_by_ticket( $ticket_id );

		foreach ( $ticket_meta as $meta ) {

			// Bail if the slug is different from the one we want to check
			if ( $slug !== $meta->slug ) {
				continue;
			}

			// Get the value and get out of the loop
			return ( 'on' === $meta->required );
		}

		return false;
	}

	/************************
	 *                      *
	 *  Deprecated Methods  *
	 *                      *
	 ************************/
	// @codingStandardsIgnoreStart

	/**
	 * Injects additional elements into the main ticket admin panel "header"
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 *
	 * @param int $post_id ID of parent "event" post
	 */
	public function tickets_post_capacity( $post_id ) {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets.admin.views' )->template( 'editor/button-view-orders' )" );
		tribe( 'tickets.admin.views' )->template( 'editor/button-view-orders' );
	}

	/**
	 * Injects "New Ticket" button into initial view
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 */
	public function tickets_new_ticket_button() {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'button-new-ticket' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/button-new-ticket' );
	}

	/**
	 * Injects additional columns into tickets table body
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 *
	 * @param $ticket_ID (obj) the ticket object
	 * @param $provider_obj (obj) the ticket provider object
	 */
	public function ticket_table_add_tbody_column( $ticket, $provider_obj ) {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.editor' )->add_column_content_price( \$ticket, \$provider_obj )" );
		tribe( 'tickets-plus.editor' )->add_column_content_price( $ticket, $provider_obj );
	}

	/**
	 * Injects additional columns into tickets table header
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 */
	public function ticket_table_add_header_column() {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'editor/column-head-price' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/column-head-price' );
	}

	/**
	 * Creates and outputs the capacity table for the ticket settings panel
	 *
	 * @since 4.6
	 * @deprecated 4.6.2
	 *
	 * @param int $post_id ID of parent "event" post
	 *
	 * @return void
	 */
	public function tickets_settings_capacity_table( $post_id ) {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'editor/capacity-table' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/capacity-table' );
	}

	/**
	 * Get the total capacity for the event, format it and display.
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 *
	 * @param int $post_id ID of parent "event" post
	 *
	 * @return void
	 */
	public function display_tickets_capacity( $post_id ) {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'editor/total-capacity' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/total-capacity' );
	}

	/**
	 * Injects additional fields into the event settings form below the capacity table
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 *
	 * @param int $post_id - the post id of the parent "event" post
	 *
	 * @return void
	 */
	public function tickets_settings_content( $post_id ) {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'editor/price-field' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/settings-content' );
	}

	/**
	 * Allows for the insertion of additional content into the ticket edit form - main section
	 *
	 * @since 4.6
	 * @deprecated 4.6.2
	 *
	 */
	public function tickets_edit_main() {
		_deprecated_function( __METHOD__, '4.6.2', "tribe( 'tickets-plus.admin.views' )->template( 'editor/price-field' )" );
		tribe( 'tickets-plus.admin.views' )->template( 'editor/price-field' );
	}

	// @codingStandardsIgnoreEnd

	/**
	 * Clear the storage allocated by a product if the product is removed from the cart.
	 *
	 * @since 4.7.1
	 *
	 * @param $cart_item_key
	 * @param $cart
	 */
	public function clear_storage_on_remove_cart_item( $cart_item_key = '', $cart = null ) {
		$product_id = null;

		if ( $cart instanceof WC_Cart ) {
			$product    = $cart->cart_contents[ $cart_item_key ];
			$product_id = empty( $product['product_id'] ) ? null : $product['product_id'];
		}

		if ( ! is_null( $product_id ) ) {
			$this->storage->delete_meta_data_for( $product_id );
		}
	}

	/**
	 * Get ticket meta for Attendee Registration.
	 *
	 * @since 4.11.0
	 *
	 * @param array $meta    List of meta for each ticket for Attendee Registration.
	 * @param array $tickets List of tickets with their ID and quantity.
	 *
	 * @return array List of meta for each ticket for Attendee Registration.
	 */
	public function get_ticket_meta( $meta, $tickets ) {
		/** @var Tribe__Tickets_Plus__Meta__Contents $contents */
		$contents = tribe( 'tickets-plus.meta.contents' );

		// Get ticket IDs.
		$tickets_for_meta = wp_list_pluck( $tickets, 'quantity', 'ticket_id' );

		$stored_meta = $contents->get_ticket_stored_meta( $tickets_for_meta );

		foreach ( $tickets as $ticket ) {
			$ticket_id = (int) $ticket['ticket_id'];

			$ticket_meta = isset( $stored_meta[ $ticket_id ] ) ? $stored_meta[ $ticket_id ] : [];

			$meta_to_be_added = [
				'ticket_id' => $ticket_id,
				'provider'  => $ticket['provider'],
				'items'     => [],
			];

			if ( ! is_array( $ticket_meta ) || empty( $ticket_meta[ $ticket_id ] ) ) {
				$meta[] = $meta_to_be_added;

				continue;
			}

			$meta_to_be_added['items'] = array_values( $ticket_meta[ $ticket_id ] );

			$meta[] = $meta_to_be_added;
		}

		return $meta;
	}

	/**
	 * Get cart data for Attendee Registration.
	 *
	 * @since 4.11.0
	 *
	 * @param array $data      Cart response data.
	 * @param array $providers List of cart providers.
	 * @param int   $post_id   Post ID for cart.
	 *
	 * @return array Cart data for Attendee Registration.
	 */
	public function get_cart_data( $data, $providers, $post_id ) {
		$data['is_stored_meta_up_to_date'] = 1;
		$data['attendee_registration_url'] = '';

		if ( empty( $data['tickets'] ) ) {
			return $data;
		}

		/** @var Tribe__Tickets_Plus__Meta__Contents $contents */
		$contents = tribe( 'tickets-plus.meta.contents' );
		$tickets  = $data['tickets'];

		// Get ticket IDs.
		$tickets_for_meta = wp_list_pluck( $tickets, 'quantity', 'ticket_id' );

		$data['is_stored_meta_up_to_date'] = (int) $contents->is_stored_meta_up_to_date( $tickets_for_meta );

		/** @var Tribe__Tickets__Attendee_Registration__Main $attendee_reg */
		$attendee_reg = tribe( 'tickets.attendee_registration' );

		$first_provider = current( $providers );

		$data['attendee_registration_url'] = add_query_arg( 'provider', $first_provider, $attendee_reg->get_url() );

		if ( ! empty( $post_id ) ) {
			$data['attendee_registration_url'] = add_query_arg( 'tribe_tickets_post_id', (int) $post_id, $data['attendee_registration_url'] );
		}

		return $data;
	}

	/**
	 * Update ticket meta from Attendee Registration.
	 *
	 * @since 4.11.0
	 *
	 * @param array   $meta     List of meta for each ticket to be saved for Attendee Registration.
	 * @param array   $tickets  List of tickets with their ID and quantity.
	 * @param string  $provider The cart provider.
	 * @param int     $post_id  Post ID for the cart.
	 * @param boolean $additive Whether to add or replace meta.
	 */
	public function update_ticket_meta( $meta, $tickets, $provider, $post_id, $additive ) {
		$ticket_meta = [];

		if ( $additive ) {
			$ticket_meta = $this->storage->get_meta_data();
		}

		foreach ( $meta as $ticket ) {
			$ticket_id = $ticket['ticket_id'];

			if ( ! isset( $ticket_meta[ $ticket_id ] ) ) {
				$ticket_meta[ $ticket_id ] = [];
			}

			foreach ( $ticket['items'] as $item ) {
				$ticket_meta[ $ticket_id ][] = $item;
			}
		}

		// Maybe set attendee meta cookie and handle saving of meta.
		$this->storage->maybe_set_attendee_meta_cookie( $ticket_meta, $provider );
	}
}
