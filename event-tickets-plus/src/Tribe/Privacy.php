<?php

/**
 * Class Tribe__Tickets_Plus__Privacy
 */
class Tribe__Tickets_Plus__Privacy {

	/**
	 * Class initialization
	 *
	 * @since 4.7.6
	 */
	public function hook() {

		// register exporter
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ), 10 );

		// register erasers
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ), 10 );

		// Add filters to add meta fields into RSVPs and TPP orders
		add_filter( 'tribe_tickets_personal_data_export_rsvp', array( $this, 'add_meta_fields_rsvp' ), 10, 2 );
		add_filter( 'tribe_tickets_personal_data_export_tpp', array( $this, 'add_meta_fields_tpp' ), 10, 2 );
	}

	/**
	 * Register erasers for ET+ saved data.
	 *
	 * @since 4.8.0
	 * @param $erasers
	 *
	 * @return array
	 */
	public function register_erasers( $erasers ) {
		$erasers[] = array(
			'eraser_friendly_name' => __( 'WooTicket Eraser', 'event-tickets-plus' ),
			'callback'             => array( $this, 'wooticket_eraser' ),
		);

		$erasers[] = array(
			'eraser_friendly_name' => __( 'EDD Ticket Eraser', 'event-tickets-plus' ),
			'callback'             => array( $this, 'edd_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Register exporter for Event Tickets Plus attendees saved data.
	 *
	 * @since 4.7.6
	 * @param $exporters
	 *
	 * @return array
	 */
	public function register_exporters( $exporters ) {

		$exporters[] = array(
			'exporter_friendly_name' => __( 'WooTickets Attendee', 'event-tickets-plus' ),
			'callback'               => array( $this, 'wooticket_exporter' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event EDD Attendee', 'event-tickets-plus' ),
			'callback'               => array( $this, 'edd_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Eraser for Events Ticket Plus WooTicket Attendee Meta Data
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.8.1
	 *
	 * @return array
	 */
	public function wooticket_eraser( $email_address, $page = 1 ) {

		$number = 50; // Limit us to avoid timing out
		$page   = (int) $page;

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		// bail if the email is empty or if we don't have WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		// get the orders of the given email
		$order_query    = array(
			'limit'    => $number,
			'page'     => $page,
			'customer' => array( $email_address ),
		);

		$orders = wc_get_orders( $order_query );

		foreach ( $orders as $order ) {

			// Find WooTickets for that order
			$wootickets = new WP_Query( array(
				'post_type'      => 'tribe_wooticket',
				'meta_key'       => '_tribe_wooticket_order',
				'meta_value'     => $order->get_id(),
				'posts_per_page' => -1,
			) );

			foreach ( $wootickets->posts as $ticket ) {

				// Check if the ticket has meta fields
				$ticket_meta = get_post_meta( $ticket->ID, '_tribe_tickets_meta', true );

				// if it doesn't, we continue with th next one
				if ( '' === $ticket_meta ) {
					continue;
				}

				// Delete only the data saved by our plugin.
				// The order/products are handled by the Ecommerce vendor
				$deleted = delete_post_meta( $ticket->ID, '_tribe_tickets_meta' );

				if ( $deleted ) {
					$items_removed = true;
				} else {
					$items_retained = true;
					$messages[]     = __( 'WooTicket fields information was not removed. A database error may have occurred during deletion.', 'event-tickets-plus' );
				}
			}
		}

		// Tell core if we have more elements to work on still
		$done = count( $orders ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Eraser for Events Ticket Plus EDDTicket Attendee Meta Data
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.8.1
	 *
	 * @return array
	 */
	public function edd_eraser( $email_address, $page = 1 ) {

		$number = 50; // Limit us to avoid timing out
		$page   = (int) $page;

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		// Get the EDD Orders for that email
		$orders = new WP_Query( array(
			'post_type'      => 'edd_payment',
			'meta_key'       => '_edd_payment_user_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $orders->posts as $order ) {

			// Find WooTickets for that order
			$eddtickets = new WP_Query( array(
				'post_type'      => 'tribe_eddticket',
				'meta_key'       => '_tribe_eddticket_order',
				'meta_value'     => $order->ID,
				'posts_per_page' => -1,
			) );

			foreach ( $eddtickets->posts as $ticket ) {

				// Check if the ticket has meta fields
				$ticket_meta = get_post_meta( $ticket->ID, '_tribe_tickets_meta', true );

				// if it doesn't, we continue with th next one
				if ( '' === $ticket_meta ) {
					continue;
				}

				// Delete only the data saved by our plugin.
				// The order/products are handled by the Ecommerce vendor
				$deleted = delete_post_meta( $ticket->ID, '_tribe_tickets_meta' );

				if ( $deleted ) {
					$items_removed = true;
				} else {
					$items_retained = true;
					$messages[]     = __( 'EDD Ticket fields information was not removed. A database error may have occurred during deletion.', 'event-tickets-plus' );
				}
			}
		}

		// Tell core if we have more elements to work on still
		$done = count( $orders->posts ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Exporter for Events Ticket Plus WooTicket Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function wooticket_exporter( $email_address, $page = 1 ) {
		$number = 50; // Limit us to avoid timing out
		$page   = (int) $page;

		// bail if we don't have WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'data' => array(), 'done' => true );
		}

		$export_items = array();

		// get the orders of the given email
		$order_query    = array(
			'limit'    => $number,
			'page'     => $page,
			'customer' => array( $email_address ),
		);

		$orders = wc_get_orders( $order_query );

		foreach ( $orders as $order ) {

			// Find WooTickets for that order
			$wootickets = new WP_Query( array(
				'post_type'      => 'tribe_wooticket',
				'meta_key'       => '_tribe_wooticket_order',
				'meta_value'     => $order->get_id(),
				'posts_per_page' => -1,
			) );

			foreach ( $wootickets->posts as $ticket ) {

				// Check if the ticket has meta fields
				$ticket_meta = get_post_meta( $ticket->ID, '_tribe_tickets_meta', true );
				$product_id  = get_post_meta( $ticket->ID, '_tribe_wooticket_product', true );

				// if it doesn't, we continue with th next one
				if ( '' === $ticket_meta ) {
					continue;
				}

				$item_id = "tribe_wooticket_attendees-{$ticket->ID}";

				// Set our own group for WooTicket attendees
				$group_id = 'tribe-wooticket-attendees';

				// Set a label for the group
				$group_label = __( 'WooTicket Attendee Data', 'event-tickets-plus' );

				$data = array();

				// Get the set of data for that attendee ticket
				$data = $this->add_meta_fields( $data, $ticket, $product_id );

				/**
				 * Allow filtering for the WooTicket attendee data export.
				 *
				 * @since 4.7.6
				 * @param array  $data      The data array to export
				 * @param object $attendee  The attendee object
				 */
				$data = apply_filters( 'tribe_tickets_personal_data_export_wooticket', $data, $ticket );

				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);

			}
		}

		// Tell core if we have more orders to work on still
		$done = count( $orders ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Exporter for Events Ticket Plus EDD Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function edd_exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		// bail if we don't have EDD
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return array( 'data' => array(), 'done' => true );
		}

		// Get the EDD Orders for that email
		$orders = new WP_Query( array(
			'post_type'      => 'edd_payment',
			'meta_key'       => '_edd_payment_user_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $orders->posts as $order ) {

			// Find EDD tickets for that order
			$eddtickets = new WP_Query( array(
				'post_type'      => 'tribe_eddticket',
				'meta_key'       => '_tribe_eddticket_order',
				'meta_value'     => $order->ID,
				'posts_per_page' => -1,
			) );

			foreach ( $eddtickets->posts as $ticket ) {

				// Check if the ticket has meta fields
				$ticket_meta = get_post_meta( $ticket->ID, '_tribe_tickets_meta', true );
				$product_id  = get_post_meta( $ticket->ID, '_tribe_eddticket_product', true );

				// if it doesn't, we continue with th next one
				if ( '' === $ticket_meta ) {
					continue;
				}

				$item_id = "tribe_eddticket_attendees-{$ticket->ID}";

				// Set our own group for EDD ticket attendees
				$group_id = 'tribe-eddticket-attendees';

				// Set a label for the group
				$group_label = __( 'EDD Attendee Data', 'event-tickets-plus' );

				$data = array();

				// Get the data they inserted
				$data = $this->add_meta_fields( $data, $ticket, $product_id );

				/**
				 * Allow filtering for the EDD Ticket attendee data export.
				 *
				 * @since 4.7.6
				 * @param array  $data      The data array to export
				 * @param object $attendee  The attendee object
				 */
				$data = apply_filters( 'tribe_tickets_personal_data_export_eddticket', $data, $ticket );

				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $item_id,
					'data'        => $data,
				);

			}
		}

		// Tell core if we have more orders to work on still
		$done = count( $orders->posts ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Add custom meta fields to the RSVP data export
	 *
	 * @param        $data
	 * @param object $page
	 * @since        4.7.6
	 *
	 * @return array
	 */
	public function add_meta_fields_rsvp( $data, $attendee ) {

		$product_id = get_post_meta( $attendee->ID, '_tribe_rsvp_product', true );
		$data       = $this->add_meta_fields( $data, $attendee, $product_id );

		return $data;
	}

	/**
	 * Add custom meta fields to the Tribe Commerce data export
	 *
	 * @param        $data
	 * @param object $page
	 * @since        4.7.6
	 *
	 * @return array
	 */
	public function add_meta_fields_tpp( $data, $attendee ) {

		$product_id = get_post_meta( $attendee->ID, '_tribe_tpp_product', true );
		$data       = $this->add_meta_fields( $data, $attendee, $product_id );

		return $data;
	}

	/**
	 * Fill the $data export array with the custom fields for a
	 * certain product.
	 *
	 * @param        $data
	 * @param object $page
	 * @since        4.7.6
	 *
	 * @return array
	 */
	public function add_meta_fields( $data, $attendee, $product_id ) {

		$meta_fields   = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $product_id );
		$meta_data     = get_post_meta( $attendee->ID, '_tribe_tickets_meta', true );

		foreach ( $meta_fields as $field ) {
			if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
				$values = array();
				foreach ( $field->extra['options'] as $option ) {
					$key = $field->slug . '_' . sanitize_title( $option );
					if ( isset( $meta_data[ $key ] ) ) {
						$values[] = $meta_data[ $key ];
					}
				}
				$value = implode( ', ', $values );
			} elseif ( isset( $meta_data[ $field->slug ] ) ) {
				$value = $meta_data[ $field->slug ];
			} else {
				continue;
			}

			if ( '' === trim( $value ) ) {
				$value = '&nbsp;';
			}

			$value = $value ? wp_kses_post( $value ) : '&nbsp;';

			$data[] = array(
				'name'  => wp_kses_post( $field->label ),
				'value' => $value,
			);
		}

		return $data;
	}
}