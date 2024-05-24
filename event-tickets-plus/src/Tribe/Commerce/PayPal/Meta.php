<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__PayPal__Meta
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Commerce__PayPal__Meta extends Tribe__Tickets_Plus__Meta__RSVP {

	/**
	 * @var string
	 */
	protected $meta_id;

	/**
	 * @var array
	 */
	protected $ticket_meta = array();

	/**
	 * The key used to identify the id of the attendee meta transient in PayPal custom arguments array.
	 *
	 * @var string
	 */
	protected $attendee_meta_custom_key = 'tppm';
	/**
	 * @var \Tribe__Tickets_Plus__Meta__Storage
	 */
	protected $storage;
	/**
	 * @var  string
	 */
	protected $transient_name = '';

	/**
	 * Tribe__Tickets_Plus__Commerce__PayPal__Meta constructor.
	 *
	 * @since 4.7
	 *
	 * @param \Tribe__Tickets_Plus__Meta__Storage $storage
	 */
	public function __construct( Tribe__Tickets_Plus__Meta__Storage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Outputs the meta fields for the ticket.
	 *
	 * @since 4.7
	 *
	 * @param $post
	 * @param $ticket
	 */
	public function front_end_meta_fields( $post, $ticket ) {
		/**
		 * Allow for the addition of content (namely the "Who's Attending?" list) above the ticket form.
		 *
		 * @since 4.5.4
		 */
		do_action( 'tribe_tickets_before_front_end_ticket_form' );
	}

	/**
	 * Filters the custom arguments that will be sent to the PayPal "Add to Cart" request.
	 *
	 * @since 4.7
	 *
	 * @param array $custom_args
	 *
	 * @return array
	 */
	public function filter_custom_args( array $custom_args ) {
		$storage = new Tribe__Tickets_Plus__Meta__Storage;
		$data = $storage->get_meta_data();
		if ( empty( $data ) ) {
			return $custom_args;
		}

		$this->meta_id = $storage->get_hash_cookie();
		$this->transient_name = $this->get_transient_name( $this->meta_id );

		// keep it short as PayPal has a number limit on custom arguments
		$custom_args[ $this->attendee_meta_custom_key ] = $this->meta_id;

		return $custom_args;
	}

	/**
	 * Processes the data that might have been sent along with a front-end ticket form.
	 *
	 * @since 4.7
	 */
	public function process_front_end_tickets_form() {
		if ( empty( $_POST[ Tribe__Tickets_Plus__Meta__Storage::META_DATA_KEY ] ) ) {
			return;
		}

		$id = $this->storage->maybe_set_attendee_meta_cookie();

		if ( ! empty( $id ) ) {
			$this->meta_id        = $id;
			$this->transient_name = $this->get_transient_name( $id );
		}

		// Store the current PayPal redirect so we can return to it after dealing with ticket meta.
		$redirect_key = tribe_get_request_var( 'event_tickets_redirect_to', '' );
		$url          = $this->storage->retrieve_temporary_data( $redirect_key );

		if ( empty( $url ) ) {
			return;
		}

		$url = base64_decode( $url );
		wp_redirect( $url );
		tribe_exit();
	}

	/**
	 * Returns the name of the transient storing the attendee meta information.
	 *
	 * @since 4.7
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function get_transient_name( $id ) {
		return Tribe__Tickets_Plus__Meta__Storage::TRANSIENT_PREFIX . $id;
	}

	/**
	 * Will start listening for the update of PayPal tickets attendees to, maybe, save the attached attendee information.
	 *
	 * @since 4.7
	 *
	 * @param int    $post_id          The post ID
	 * @param string $ticket_type      The
	 * @param array  $transaction_data The transaction data and information, includes the `custom` information.
	 */
	public function listen_for_ticket_creation( $post_id, $ticket_type, $transaction_data ) {
		$custom = Tribe__Utils__Array::get( $transaction_data, 'custom', false );
		if ( empty( $custom ) ) {
			return;
		}

		$decoded_custom = Tribe__Tickets__Commerce__PayPal__Custom_Argument::decode( $custom, true );

		if ( empty( $decoded_custom ) ) {
			return;
		}

		$meta_id = Tribe__Utils__Array::get( $decoded_custom, $this->attendee_meta_custom_key, false );
		if ( empty( $meta_id ) ) {
			return;
		}

		$ticket_meta = get_transient( $this->get_transient_name( $meta_id ) );

		if ( empty( $ticket_meta ) ) {
			return;
		}

		$this->ticket_meta = $ticket_meta;

		add_action( 'event_tickets_tpp_attendee_updated', array( $this, 'save_attendee_meta' ), 10, 3 );
	}

	/**
	 * Saves the meta information for an attendee.
	 *
	 * @since 4.7
	 *
	 * @param int    $attendee_id The attendee post ID
	 * @param string $order_id    The order identification number
	 * @param int    $product_id  The PayPal ticket post ID
	 */
	public function save_attendee_meta( $attendee_id, $order_id, $product_id ) {
		// we rely on the order of the attendees to save the correct meta
		$attendee_meta = $this->get_next_attendee_meta_for( $product_id );

		if ( empty( $attendee_meta ) ) {
			return;
		}

		update_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, $attendee_meta );
	}

	/**
	 * Hooked to a PayPal action right before add-to-cart to potentially manipulate data for attendee-registration
	 *
	 * @since 4.9
	 *
	 * @param string $url
	 * @param string $cart_url
	 * @param array $post_data
	 *
	 * @return string
	 */
	public function maybe_alter_post_data( $post_data ) {
		global $post;

		if ( empty( $post_data['tribe_tickets_saving_attendees'] ) ) {
			return;
		}

		$storage = new Tribe__Tickets_Plus__Meta__Storage;
		$data = $storage->get_meta_data();

		if ( ! $data ) {
			return;
		}

		$keys                              = array_keys( $data );
		$product_id                        = current( $keys );
		$_POST['product_id']               = $keys;
		$_POST['quantity_' . $product_id ] = count( $data[ $product_id ] );
		$event_ids                         = tribe_tickets_get_event_ids( $product_id );
		$post                              = get_post( current( $event_ids ) );
		if ( $provider = tribe_tickets_get_ticket_provider( $product_id ) ) {
			$_POST['provider'] = get_class( $provider );
		}
	}

	/**
	 * Filters the add-to-cart url used for redirection
	 *
	 * If we are saving data on the attendee registration page, we need to redirect to paypal
	 *
	 * @since 4.9
	 *
	 * @param string $url
	 * @param string $cart_url
	 * @param array $post_data
	 *
	 * @return string
	 */
	public function maybe_filter_redirect( $url, $cart_url, $post_data ) {
		if ( empty( $post_data['tribe_tickets_saving_attendees'] ) ) {
			return $url;
		}

		return $cart_url;
	}

	/**
	 * Returns the first entry of attendee meta for the ticket, if any.
	 *
	 * This will consume the attendee meta variable stored in the class property.
	 *
	 * @param int $product_id
	 *
	 * @return array
	 */
	protected function get_next_attendee_meta_for( $product_id ) {
		$product_id = (int) $product_id;

		if ( empty( $this->ticket_meta ) || ! isset( $this->ticket_meta[ $product_id ] ) ) {
			return array();
		}

		$all_ticket_attendee_meta = $this->ticket_meta[ $product_id ];

		if ( empty( $all_ticket_attendee_meta ) ) {
			return array();
		}

		$first = array_shift( $all_ticket_attendee_meta );

		$first = is_array( $first ) ? $first : array();

		$this->ticket_meta[ $product_id ] = $all_ticket_attendee_meta;

		return $first;
	}
}
