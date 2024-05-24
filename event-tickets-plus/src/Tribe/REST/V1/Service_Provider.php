<?php


/**
 * Class Tribe__Tickets_Plus__REST__V1__Service_Provider
 *
 * Add Event Tickets Plus REST API
 *
 * @since 4.7.5
 */
class  Tribe__Tickets_Plus__REST__V1__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public $namespace;


	/**
	 * Registers the classes and functionality needed fro REST API
	 *
	 * @since 4.7.5
	 */
	public function register() {

		tribe_singleton( 'tickets-plus.rest-v1.main', new Tribe__Tickets_Plus__REST__V1__Main );
		tribe_singleton( 'tickets-plus.rest-v1.repository', new Tribe__Tickets_Plus__REST__V1__Post_Repository );
		tribe_singleton( 'tickets-plus.rest-v1.response', 'Tribe__Tickets_Plus__REST__V1__Response' );

		$messages        = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets-plus.rest-v1.repository' );
		$validator       = tribe( 'tickets.rest-v1.validator' );

		tribe_singleton( 'tickets-plus.rest-v1.endpoints.qr', new Tribe__Tickets_Plus__REST__V1__Endpoints__QR( $messages, $post_repository, $validator ) );

		$this->hook();
	}

	/**
	 * Registers the REST API endpoints for Event Tickets Plus
	 *
	 * @since 4.7.5
	 */
	public function register_endpoints() {

		$qr_endpoint = tribe( 'tickets-plus.rest-v1.endpoints.qr' );

		$this->namespace = tribe( 'tickets-plus.rest-v1.main' )->get_events_route_namespace();

		register_rest_route( $this->namespace, '/qr/(?P<id>\\d+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'args'     => $qr_endpoint->READ_args(),
				'callback' => array( $qr_endpoint, 'get' ),
			),
		) );

		register_rest_route( $this->namespace, '/qr', array(
			'methods'  => WP_REST_Server::READABLE,
			'args'     => $qr_endpoint->CHECK_IN_args(),
			'callback' => array( $qr_endpoint, 'check_in' ),
		) );

		/** @var Tribe__Documentation__Swagger__Builder_Interface $documentation */
		$doc_endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );
		$doc_endpoint->register_definition_provider( 'QR', new Tribe__Tickets_Plus__REST__V1__Documentation__QR_Definition_Provider() );

	}

	/**
	 * Hooks the actions and filters required for the REST API integration to work.
	 *
	 * @since 4.8
	 */
	protected function hook() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

		add_filter( 'tribe_tickets_rest_api_ticket_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_ticket_data' ) );
		add_filter( 'tribe_tickets_rest_api_attendee_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_attendee_data' ), 10, 2 );
	}

}
