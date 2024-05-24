<?php

if ( class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Email' ) ) {
	return;
}

class Tribe__Tickets_Plus__Commerce__EDD__Email {

	private $default_subject;

	public function __construct() {

		$this->default_subject = esc_html( sprintf( __( 'Your %s from {sitename}', 'event-tickets-plus' ), tribe_get_ticket_label_plural_lowercase( 'edd_email_default_subject' ) ) );

		// Triggers for this email
		add_action( 'eddtickets-send-tickets-email', [ $this, 'trigger' ] );

		add_filter( 'edd_settings_emails', [ $this, 'settings' ] );
	}

	/**
	 * Register the email settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings( $settings ) {
		$email_settings = [
			'tribe_ticket_email_heading' => [
				'id' => 'tribe_ticket_email_heading',
				'name' => '<strong>' . esc_html( sprintf( __( 'Tribe %s Emails', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'edd_email_heading_name' ) ) ) . '</strong>',
				'desc' => esc_html( sprintf( __( 'Configure the %s receipt emails', 'event-tickets-plus' ), tribe_get_ticket_label_singular_lowercase( 'edd_email_heading_desc' ) ) ),
				'type' => 'header',
			],
			'ticket_subject' => [
				'id' => 'ticket_subject',
				'name' => esc_html( sprintf( __( '%s Email Subject', 'event-tickets-plus' ), tribe_get_ticket_label_plural( 'edd_email_subject_name' ) ) ),
				'desc' => esc_html( sprintf( __( 'Enter the subject line for the %s receipt email', 'event-tickets-plus' ), tribe_get_ticket_label_plural_lowercase( 'edd_email_subject_desc' ) ) ),
				'type' => 'text',
				'std'  => $this->default_subject,
			],
		];

		return array_merge( $settings, $email_settings );
	}

	/**
	 * Trigger the tickets email
	 *
	 * @param int $payment_id
	 */
	public function trigger( $payment_id = 0 ) {

		global $edd_options;

		$payment_data = edd_get_payment_meta( $payment_id );
		$user_id      = edd_get_payment_user_id( $payment_id );
		$user_info    = maybe_unserialize( $payment_data['user_info'] );
		$email        = edd_get_payment_user_email( $payment_id );

		if ( isset( $user_id ) && $user_id > 0 ) {
			$user_data = get_userdata( $user_id );
			$name = $user_data->display_name;
		} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
		} else {
			$name = $email;
		}

		$message = $this->get_content_html( $payment_id );

		$from_name  = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo( 'name' );
		$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option( 'admin_email' );

		$subject = ! empty( $edd_options['ticket_subject'] ) ? wp_strip_all_tags( $edd_options['ticket_subject'], true ) : $this->default_subject;
		$subject = apply_filters( 'edd_ticket_receipt_subject', $subject, $payment_id );
		$subject = edd_email_template_tags( $subject, $payment_data, $payment_id );

		$headers = 'From: ' . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= 'Reply-To: ' . $from_email . "\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$headers = apply_filters( 'edd_ticket_receipt_headers', $headers, $payment_id, $payment_data );

		// Allow add-ons to add file attachments
		$attachments = apply_filters( 'edd_ticket_receipt_attachments', [], $payment_id, $payment_data );

		if ( apply_filters( 'edd_email_ticket_receipt', true ) ) {
			wp_mail( $email, $subject, $message, $headers, $attachments );
		}
	}

	/**
	 * Retrieve the full HTML for the tickets email
	 *
	 * @param int $payment_id
	 *
	 * @return string
	 */
	public function get_content_html( $payment_id = 0 ) {

		$eddtickets = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance();

		$attendees = $eddtickets->get_attendees_by_id( $payment_id );

		return $eddtickets->generate_tickets_email_content( $attendees );
	}
}