<?php

if ( class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Email' ) || ! class_exists( 'WC_Email' ) ) {
	return;
}

class Tribe__Tickets_Plus__Commerce__WooCommerce__Email extends WC_Email {

	/**
	 * The email format type.
	 *
	 * The parent class declares the property dynamically so there's no docblock to inherit.
	 *
	 * @var string html, plain
	 */
	public $email_type;

	/**
	 * {@inheritdoc}
	 */
	public $enabled;

	public function __construct() {
		$this->id = 'wootickets';

		$this->title = esc_html( tribe_get_ticket_label_plural( 'woo_email_title' ) );

		$this->description = esc_html(
			sprintf(
			// Translators: dynamic 'tickets' text.
				__( 'Email the user will receive after a completed order with the %s they purchased.', 'event-tickets-plus' ),
				tribe_get_ticket_label_plural_lowercase( 'woo_email_description' )
			)
		);

		$this->subject = esc_html(
			sprintf(
			// Translators: dynamic 'tickets' text.
				__( 'Your %s from {site_title}', 'event-tickets-plus' ),
				tribe_get_ticket_label_plural_lowercase( 'woo_email_subject' )
			)
		);

		$this->customer_email = true;

		// Triggers for this email
		add_action( 'wootickets-send-tickets-email', [ $this, 'trigger' ] );

		// Call parent constructor.
		parent::__construct();

		$this->email_type = 'html';

		$this->enabled = 'yes';

		/**
		 * Allows for filtering whether the Woo tickets email is enabled.
		 *
		 * @deprecated 4.7.1
		 *
		 * @param string $is_enabled Defaults to 'yes'; whether the Woo tickets email is enabled.
		 */
		$this->enabled = apply_filters_deprecated(
			'wootickets-tickets-email-enabled',
			[ $this->enabled ],
			'4.7.1',
			'tribe_tickets_plus_email_enabled',
			'The filter "wootickets-tickets-email-enabled" has been renamed to "tribe_tickets_plus_email_enabled" to match plugin namespacing.'
		);

		/**
		 * Allows for filtering whether the Woo tickets email is enabled.
		 *
		 * @since 4.7.1
		 *
		 * @param string $is_enabled Defaults to 'yes'; whether the Woo tickets email is enabled.
		 */
		$this->enabled = apply_filters( 'tribe_tickets_plus_email_enabled', $this->enabled );
	}

	/**
	 * The callback fired on the wootickets-send-tickets-email action.
	 *
	 * @param int $order_id The ID of the WooCommerce order whose tickets are being emailed.
	 */
	public function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object    = new WC_Order( $order_id );
			$this->recipient = method_exists( $this->object, 'get_billing_email' )
				? $this->object->get_billing_email() // WC 3.x
				: $this->object->billing_email; // WC 2.x
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$sent = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		if ( $sent ) {
			$this->maybe_add_order_note_for_manual_email( $order_id );
		}
	}

	/**
	 * Gets the subject for the email, defaulting to "Your tickets from {site_title}".
	 *
	 * @return string
	 */
	public function get_subject() {

		$subject      = $this->subject;
		$woo_settings = get_option( 'woocommerce_wootickets_settings' );

		if ( ! empty( $woo_settings['subject'] ) ) {
			$subject = $woo_settings['subject'];
		}

		/**
		 * Allows for filtering the WooCommerce Tickets email subject.
		 *
		 * @param string $subject The email subject.
		 * @param WC_Order $ticket The WC_Order for this ticket purchase.
		 */
		return apply_filters( 'wootickets_ticket_email_subject', $this->format_string( $subject ), $this->object );
	}

	/**
	 * Gets an array of attachments (each item to be a full path file name) to attach to the email.
	 *
	 * @return array
	 */
	public function get_attachments() {
		/**
		 * Filters the array of files to be attached to the WooCommmerce Ticket
		 * email.
		 *
		 * Example use case is the PDF Tickets extension.
		 *
		 * @param array  $attachments  An array of full path file names.
		 * @param int    $this->id     The email method ID.
		 * @param object $this->object Object this email is for, for example a customer, product, or email.
		 */
		return apply_filters( 'tribe_tickets_plus_woo_email_attachments', [], $this->id, $this->object );
	}

	/**
	 * Retrieve the full HTML for the tickets email
	 *
	 * @return string
	 */
	public function get_content_html() {

		$wootickets = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();

		$attendees = method_exists( $this->object, 'get_id' )
			? $wootickets->get_attendees_by_id( $this->object->get_id() ) // WC 3.x
			: $wootickets->get_attendees_by_id( $this->object->id ); // WC 2.x

		return $wootickets->generate_tickets_email_content( $attendees );
	}

	/**
	 * Initialise Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'subject' => [
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			],
		];
	}

	/**
	 * Adds an Order Note to the WooCommerce order if we're manually re-sending a tickets email.
	 *
	 * @since 4.7.3
	 *
	 * @param int $order_id The WooCommerce order ID.
	 *
	 * @return bool|int|WP_Error
	 */
	public function maybe_add_order_note_for_manual_email( $order_id ) {

		if ( ! function_exists( 'wc_create_order_note' ) ) {
			return false;
		}

		if ( 'resend_tickets_email' !== tribe_get_request_var( 'wc_order_action' ) ) {
			return false;
		}

		return wc_create_order_note(
			$order_id,
			esc_html( sprintf(
				__( '%s email notification manually sent to user.', 'event-tickets-plus' ),
				tribe_get_ticket_label_plural( 'woo_email_order_note' )
			) ),
			false,
			false
		);
	}
}
