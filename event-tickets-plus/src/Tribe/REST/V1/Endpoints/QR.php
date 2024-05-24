<?php

class Tribe__Tickets_Plus__REST__V1__Endpoints__QR
extends Tribe__Tickets__REST__V1__Endpoints__Base
implements Tribe__REST__Endpoints__READ_Endpoint_Interface, Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * @var Tribe__REST__Main
	 */
	protected $main;

	/**
	 * @var WP_REST_Request
	 */
	protected $serving;

	/**
	 * @var Tribe__Tickets__REST__Interfaces__Post_Repository
	 */
	protected $post_repository;

	/**
	 * @var Tribe__Tickets__REST__V1__Validator__Interface
	 */
	protected $validator;

	/**
	 * Get attendee by id
	 *
	 * @since 4.7.5
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|void|WP_Error|WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {
		$this->serving = $request;

		$ticket = get_post( $request['id'] );
		$ticket_type = tribe( 'tickets.data_api' )->detect_by_id( $request['id'] );

		$cap = get_post_type_object( $ticket_type['post_type'] )->cap->read_post;
		if ( ! ( 'publish' === $ticket->post_status || current_user_can( $cap, $request['id'] ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'ticket-not-accessible', $message, [ 'status' => 403 ] );
		}

		$data = $this->post_repository->get_qr_data( $request['id'], 'single' );

		/**
		 * Filters the data that will be returned for a single qr ticket request.
		 *
		 * @since 4.5.13
		 *
		 * @param array           $data    The retrieved data.
		 * @param WP_REST_Request $request The original request.
		 */
		$data = apply_filters( 'tribe_tickets_plus_rest_qr_data', $data, $request );

		return is_wp_error( $data ) ? $data : new WP_REST_Response( $data );
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of informations rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @since 4.7.5
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$POST_defaults = [
			'in'      => 'formData',
			'default' => '',
			'type'    => 'string',
		];
		$post_args     = array_merge( $this->READ_args(), $this->CHECK_IN_args() );

		return [
			'post' => [
				'consumes'   => [ 'application/x-www-form-urlencoded' ],
				'parameters' => $this->swaggerize_args( $post_args, $POST_defaults ),
				'responses'  => [
					'201' => [
						'description' => __( 'Returns successful check in', 'event-tickets-plus' ),
						'schema'      => [
							'$ref' => '#/definitions/Ticket',
						],
					],
					'400' => [
						'description' => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets-plus' ),
					],
					'403' => [
						'description' => esc_html( sprintf( __( 'The %s is already checked in', 'event-tickets-plus' ), tribe_get_ticket_label_singular_lowercase( 'rest_qr' ) ) ),
					],
				],
			],
		];
	}

	/**
	 * Provides the content of the `args` array to register the endpoint support for GET requests.
	 *
	 * @since 4.7.5
	 *
	 * @return array
	 */
	public function READ_args() {
		return [
			'id' => [
				'in'                => 'path',
				'type'              => 'integer',
				'description'       => esc_html( sprintf( __( 'The %s id.', 'event-tickets-plus' ), tribe_get_ticket_label_singular_lowercase( 'rest_qr' ) ) ),
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_ticket_id' ],
			],
		];
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since 4.7.5
	 *
	 * @return array
	 */
	public function CHECK_IN_args() {
		$ticket_label_singular_lower = esc_html( tribe_get_ticket_label_singular_lowercase( 'rest_qr' ) );

		return [
			// QR fields
			'api_key'       => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => __( 'The API key to authorize check in.', 'event-tickets-plus' ),
			],
			'ticket_id'     => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_numeric' ],
				'type'              => 'string',
				'description'       => esc_html( sprintf( __( 'The ID of the %s to check in.', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
			],
			'security_code' => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => esc_html( sprintf( __( 'The security code of the %s to verify for check in.', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
			],
		];
	}

	/**
	 * Check in attendee
	 *
	 * @since 4.7.5
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function check_in( WP_REST_Request $request ) {

		$this->serving = $request;

		$qr_arr = $this->prepare_qr_arr( $request );

		if ( is_wp_error( $qr_arr ) ) {
			$response = new WP_REST_Response( $qr_arr );
			$response->set_status( 400 );

			return $response;
		}

		$api_check = $this->has_api( $qr_arr );

		// Check all the data we need is there
		if ( empty( $api_check ) || empty( $qr_arr['ticket_id'] ) ) {
			$response = new WP_REST_Response( $qr_arr );
			$response->set_status( 400 );

			return $response;
		}

		$ticket_id = (int) $qr_arr['ticket_id'];
		$security_code = (string) $qr_arr['security_code'];
		$service_provider = tribe( 'tickets.data_api' )->get_ticket_provider( $ticket_id );
		if ( empty( $service_provider->security_code ) || (  $security_code !== get_post_meta( $ticket_id, $service_provider->security_code, true )  )   ) {
			$response = new WP_REST_Response( [ 'msg' => __( 'Security code is not valid!', 'event-tickets-plus' ) ] );
			$response->set_status( 403 );

			return $response;
		}

		// add check attendee data
		$attendee = $service_provider->get_attendees_by_id( $ticket_id );
		$attendee = reset( $attendee );
		if ( ! is_array( $attendee ) ) {
			$response = new WP_REST_Response( [ 'msg' => __( 'An attendee is not found with this ID.', 'event-tickets-plus' ) ] );
			$response->set_status( 403 );

			return $response;
		}

		// add check for completed attendee status
		$complete_statuses = (array) tribe( 'tickets.status' )->get_completed_status_by_provider_name( $service_provider );
		if ( ! in_array( $attendee['order_status'], $complete_statuses ) ) {
			$response = new WP_REST_Response( [
				'msg' => esc_html( sprintf(
					__( "This attendee's %s is not authorized to be Checked in", 'event-tickets-plus' ),
					tribe_get_ticket_label_singular_lowercase( 'rest_qr' )
				) )
			] );
			$response->set_status( 403 );

			return $response;
		}

		// check if attendee is checked in
		$checked_status = get_post_meta( $ticket_id, '_tribe_qr_status', true );
		if ( $checked_status ) {
			$response = new WP_REST_Response( [ 'msg' => __( 'Already checked in!', 'event-tickets-plus' ) ] );
			$response->set_status( 403 );

			return $response;
		}

		$checked = $this->_check_in( $ticket_id, $service_provider );
		if ( ! $checked ) {
			$msg_arr = [
				'msg'             => esc_html( sprintf( __( '%s not checked in!', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'rest_qr' ) ) ),
				'tribe_qr_status' => get_post_meta( $ticket_id, '_tribe_qr_status', 1 ),
			];
			$result  = array_merge( $msg_arr, $qr_arr );

			$response = new WP_REST_Response( $result );
			$response->set_status( 403 );

			return $response;
		}

		$response = new WP_REST_Response( [ 'msg' => __( 'Checked In!', 'event-tickets-plus' ) ] );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Check if API is present and matches key is settings
	 *
	 * @since 4.7.5
	 *
	 * @param $qr_arr
	 *
	 * @return bool
	 */
	public function has_api( $qr_arr ) {

		if ( empty( $qr_arr['api_key'] ) ) {
			return false;
		}

		$tec_options = Tribe__Settings_Manager::get_options();
		if ( ! is_array( $tec_options ) ) {
			return false;
		}

		if ( $tec_options['tickets-plus-qr-options-api-key'] !== esc_attr( $qr_arr['api_key'] ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Setup array of variables for check in
	 *
	 * @since 4.7.5
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|mixed|void
	 */
	protected function prepare_qr_arr( WP_REST_Request $request ) {

		$qr_arr = [
			'api_key'       => $request['api_key'],
			'ticket_id'     => $request['ticket_id'],
			'security_code' => $request['security_code'],
		];

		/**
		 * Allow filtering of $postarr data with additional $request arguments.
		 *
		 * @param array           $qr_arr  Post array used for check in
		 * @param WP_REST_Request $request REST request object
		 *
		 * @since 4.7.5
		 */
		$qr_arr = apply_filters( 'tribe_tickets_plus_rest_qr_prepare_qr_arr', $qr_arr, $request );

		return $qr_arr;
	}

	/**
	 * Check in attendee and on first success return
	 *
	 * @since 4.7.5
	 *
	 * @param $ticket_id
	 *
	 * @return boolean
	 */
	private function _check_in( $attendee_id, $service_provider ) {

		if ( empty( $service_provider ) ) {
			return false;
		}

		// set parameter to true for the QR app - it is false for the original url so that the message displays
		$success = $service_provider->checkin( $attendee_id, true );
		if ( $success ) {
			return $success;
		}

		return false;
	}
}
