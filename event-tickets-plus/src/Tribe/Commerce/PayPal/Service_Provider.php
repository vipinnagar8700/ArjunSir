<?php

class Tribe__Tickets_Plus__Commerce__PayPal__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		tribe_singleton( 'tickets-plus.commerce.paypal.editor', 'Tribe__Tickets_Plus__Commerce__PayPal__Editor' );
		tribe_singleton( 'tickets-plus.commerce.paypal.meta', 'Tribe__Tickets_Plus__Commerce__PayPal__Meta' );
		tribe_singleton( 'tickets-plus.commerce.paypal.views', 'Tribe__Tickets_Plus__Commerce__PayPal__Views' );
		tribe_singleton( 'tickets-plus.commerce.paypal.attendees', 'Tribe__Tickets_Plus__Commerce__PayPal__Attendees' );

		add_filter( 'tribe_tickets_tpp_metabox_capacity_file', tribe_callback( 'tickets-plus.commerce.paypal.editor', 'filter_tpp_metabox_capacity_file' ) );
		add_action( 'event_tickets_tpp_after_ticket_row', tribe_callback( 'tickets-plus.commerce.paypal.meta', 'front_end_meta_fields' ), 10, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_custom_args', tribe_callback( 'tickets-plus.commerce.paypal.meta', 'filter_custom_args' ) );
		add_action( 'tribe_tickets_tpp_before_attendee_ticket_creation', tribe_callback( 'tickets-plus.commerce.paypal.meta', 'listen_for_ticket_creation' ), 10, 3 );
		add_action( 'event_tickets_tpp_after_ticket_row', tribe_callback( 'tickets-plus.commerce.paypal.attendees', 'render_optout_input' ), 10, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_custom_args', tribe_callback( 'tickets-plus.commerce.paypal.attendees', 'register_optout_choice' ) );

		add_action( 'tribe_tickets_commerce_paypal_gateway_pre_add_to_cart', tribe_callback( 'tickets-plus.commerce.paypal.meta', 'maybe_alter_post_data' ) );
		add_filter( 'tribe_tickets_commerce_paypal_gateway_add_to_cart_redirect', tribe_callback( 'tickets-plus.commerce.paypal.meta', 'maybe_filter_redirect' ), 10, 3 );
	}
}
