<?php

/**
 * Class Tribe__Tickets_Plus__Service_Provider
 *
 * Provides the Events Tickets Plus service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since 4.6
 */
class Tribe__Tickets_Plus__Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.6
	 */
	public function register() {
		$this->container->singleton( 'tickets-plus.assets', new Tribe__Tickets_Plus__Assets() );
		$this->container->singleton( 'tickets-plus.admin.views', 'Tribe__Tickets_Plus__Admin__Views' );
		$this->container->singleton( 'tickets-plus.admin.notices', 'Tribe__Tickets_Plus__Admin__Notices', [ 'hook' ] );
		$this->container->singleton( 'tickets-plus.editor', 'Tribe__Tickets_Plus__Editor', array( 'hook' ) );

		$this->container->singleton( 'tickets-plus.commerce.warnings', new Tribe__Tickets_Plus__Commerce__Warnings );

		// We use String here to specifically not load it before used
		$this->container->singleton( 'tickets-plus.commerce.woo', 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' );
		$this->container->singleton( 'tickets-plus.commerce.edd', 'Tribe__Tickets_Plus__Commerce__EDD__Main' );

		// Check In Status
		$this->container->singleton( 'tickets-plus.commerce.edd.checkin-stati', 'Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati' );
		$this->container->singleton( 'tickets-plus.commerce.woo.checkin-stati', 'Tribe__Tickets_Plus__Commerce__WooCommerce__CheckIn_Stati' );

		// QR code support
		$this->container->singleton( 'tickets-plus.qr.site-settings', 'Tribe__Tickets_Plus__QR__Settings', array( 'hook' ) );

		// Meta
		$this->container->singleton( 'tickets-plus.meta.contents', 'Tribe__Tickets_Plus__Meta__Contents' );

		// additional service providers
		$this->container->register( 'Tribe__Tickets_Plus__Commerce__PayPal__Service_Provider' );

		// Repositories, we replace the original bindings to expand the repository with ET+ functions
		$this->container->bind( 'tickets.ticket-repository', 'Tribe__Tickets_Plus__Ticket_Repository' );
		$this->container->bind( 'tickets.attendee-repository', 'Tribe__Tickets_Plus__Attendee_Repository' );
		$this->container->bind( 'tickets.event-repository', 'Tribe__Tickets_Plus__Event_Repository' );
		$this->container->bind( 'tickets.post-repository', 'Tribe__Tickets_Plus__Repositories__Post_Repository' );

		// Add custom contexts.
		$this->container->bind( 'tickets-plus.ticket-repository.edd', 'Tribe__Tickets_Plus__Repositories__Ticket__EDD' );
		$this->container->bind( 'tickets-plus.ticket-repository.woo', 'Tribe__Tickets_Plus__Repositories__Ticket__WooCommerce' );
		$this->container->bind( 'tickets-plus.attendee-repository.edd', 'Tribe__Tickets_Plus__Repositories__Attendee__EDD' );
		$this->container->bind( 'tickets-plus.attendee-repository.woo', 'Tribe__Tickets_Plus__Repositories__Attendee__WooCommerce' );

		$this->hook();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.6
	 */
	protected function hook() {
		tribe( 'tickets-plus.editor' );
		tribe( 'tickets-plus.qr.site-settings' );

		/** @var Tribe__Tickets_Plus__Assets $tickets_plus_main */
		$tickets_plus_main = tribe( 'tickets-plus.assets' );

		if ( is_admin() ) {
			tribe( 'tickets-plus.admin.views' );
			tribe( 'tickets-plus.admin.notices' );
		}

		$tickets_plus_main->enqueue_scripts();
		$tickets_plus_main->admin_enqueue_scripts();

		add_filter( 'event_tickets_attendees_edd_checkin_stati', tribe_callback( 'tickets-plus.commerce.edd.checkin-stati', 'filter_attendee_ticket_checkin_stati' ), 10 );

		add_filter( 'tribe_tickets_ticket_repository_map', tribe_callback( 'tickets.ticket-repository', 'filter_ticket_repository_map' ), 10 );
		add_filter( 'tribe_tickets_attendee_repository_map', tribe_callback( 'tickets.attendee-repository', 'filter_attendee_repository_map' ), 10 );
	}
}
