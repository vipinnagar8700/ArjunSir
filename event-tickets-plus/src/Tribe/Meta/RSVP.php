<?php

if ( class_exists( 'Tribe__Tickets_Plus__Meta__RSVP' ) ) {
	return;
}

class Tribe__Tickets_Plus__Meta__RSVP {
	/**
	 * Hooks the actions and filters required by the class to work as intended.
	 *
	 * @since 4.7
	 */
	public function hook() {
		add_action( 'wp_loaded', array( $this, 'process_front_end_tickets_form' ), 50 );
		add_action( 'event_tickets_rsvp_ticket_created', array( $this, 'save_attendee_meta_to_ticket' ), 10, 4 );
		add_action( 'event_tickets_rsvp_tickets_generated_for_product', array( $this, 'clear_meta_for_ticket' ) );
		add_action( 'event_tickets_rsvp_after_ticket_row', array( $this, 'front_end_meta_fields' ), 10, 2 );
	}

	/**
	 * Sets attendee data on attendee posts
	 *
	 * @since 4.1
	 *
	 * @param int $attendee_id Attendee Ticket Post ID
	 * @param int $order_id RSVP Order ID
	 * @param int $product_id RSVP Product ID
	 * @param int $order_attendee_id Attendee number in submitted order
	 */
	public function save_attendee_meta_to_ticket( $attendee_id, $order_id, $product_id, $order_attendee_id ) {
		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		// build the custom meta data that will be stored in the order meta
		if ( ! $meta = $meta_object->build_order_meta( array( $product_id ) ) ) {
			return;
		}

		if ( ! isset( $meta[ $product_id ] ) ) {
			return;
		}

		if ( ! isset( $meta[ $product_id ][ $order_attendee_id ] ) ) {
			return;
		}

		update_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, $meta[ $product_id ][ $order_attendee_id ] );
	}

	/**
	 * Clear meta for the tickets generated.
	 *
	 * @since 4.11.0
	 *
	 * @param int $product_id RSVP Product ID.
	 */
	public function clear_meta_for_ticket( $product_id ) {
		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();
		$meta_object->clear_meta_cookie_data( $product_id );
	}

	/**
	 * Outputs the meta fields for the ticket
	 */
	public function front_end_meta_fields( $post, $ticket ) {
		include Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( 'meta.php' );
	}

	/**
	 * Processes the front-end tickets form data.
	 */
	public function process_front_end_tickets_form() {
		$storage = new Tribe__Tickets_Plus__Meta__Storage();
		$storage->maybe_set_attendee_meta_cookie();
	}
}
