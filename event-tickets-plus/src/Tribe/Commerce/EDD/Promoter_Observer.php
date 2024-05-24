<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Promoter_Observer
 *
 * @since 4.12.0
 */
class Tribe__Tickets_Plus__Commerce__EDD__Promoter_Observer {

	/**
	 * @since 4.12.0
	 *
	 * @var Tribe__Tickets__Promoter__Observer $observer ET Observer reference.
	 */
	private $observer;

	/**
	 * Tribe__Tickets_Plus__Commerce__EDD__Promoter_Observer constructor.
	 *
	 * @param Tribe__Tickets__Promoter__Observer $observer ET Observer.
	 */
	public function __construct( Tribe__Tickets__Promoter__Observer $observer ) {
		$this->observer = $observer;
		$this->hook();
	}

	/**
	 * Hooks on which this observer notifies promoter
	 *
	 * @since 4.12.0
	 */
	private function hook() {
		add_action( 'event_ticket_edd_attendee_created', [ $this->observer, 'notify_event_id' ], 10, 2 );
		add_action( 'eddtickets_ticket_deleted', [ $this->observer, 'notify_event_id' ], 10, 2 );

		// Only act if observer has notify_ticket_event method
		if ( method_exists( $this->observer, 'notify_ticket_event' ) ) {
			$this->notify_ticket_event();
		}
	}

	/**
	 * H0ok into different actions specifically for EDD.
	 *
	 * @since 4.12.0
	 */
	private function notify_ticket_event() {
		// Downloads
		add_action( 'save_post_download', [ $this->observer, 'notify_ticket_event' ], 10, 1 );
		// Ticket
		add_action( 'save_post_tribe_eddticket', [ $this->observer, 'notify_ticket_event' ], 10, 1 );
		// Payments
		add_action( 'save_post_edd_payment', [ $this, 'payment_updated' ], 10, 1 );
		add_action( 'edd_customer_post_update', [ $this, 'customer_updated' ], 10, 2 );
	}

	/**
	 * Callback if an EDD customer has been updated.
	 *
	 * @since 4.12.0
	 *
	 * @param $updated bool If the updated was successful or not.
	 * @param $customer_id int Customer ID updated with the Order.
	 */
	public function customer_updated( $updated, $customer_id ) {
		if ( ! $updated || ! class_exists( 'EDD_Customer' ) ) {
			return;
		}

		$customer = new EDD_Customer( $customer_id );
		$payments = [];

		if ( method_exists( $customer, 'get_payment_ids' ) ) {
			$payments = $customer->get_payment_ids();
		}

		$payments = is_array( $payments ) ? $payments : [];
		foreach ( $payments as $payment_id ) {
			$this->payment_updated( $payment_id );
		}
	}

	/**
	 * If an EDD payment is updated notify to orders that are part of this payment.
	 *
	 * @since 4.12.0
	 *
	 * @param $payment_id int ID of the payment.
	 */
	public function payment_updated( $payment_id ) {
		// Make sure `edd_get_payment_meta_cart_details` exists
		if ( ! function_exists( 'edd_get_payment_meta_cart_details' ) ) {
			return;
		}

		$cart_details = edd_get_payment_meta_cart_details( $payment_id, true );
		$cart_details = is_array( $cart_details ) ? $cart_details : [];

		foreach ( $cart_details as $detail ) {
			if ( is_array( $detail ) && ! empty( $detail['id'] ) ) {
				$this->observer->notify_ticket_event( $detail['id'] );
			}
		}
	}
}