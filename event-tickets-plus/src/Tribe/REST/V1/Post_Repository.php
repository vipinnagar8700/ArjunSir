<?php


class Tribe__Tickets_Plus__REST__V1__Post_Repository extends Tribe__Tickets__REST__V1__Post_Repository  {

	/**
	 * Returns an array representation of an attendee.
	 *
	 * @since 4.7.5
	 *
	 * @param int    $attendee_id A attendee post ID.
	 * @param string $context  Context of data.
	 *
	 * @return array|WP_Error Either the array representation of an attendee or an error object.
	 *
	 */
	public function get_qr_data( $attendee_id, $context = '' ) {
		$attendee      = get_post( $attendee_id );
		$attendee_type = tribe( 'tickets.data_api' )->detect_by_id( $attendee_id );

		if ( empty( $attendee ) || empty( $attendee_type['class'] ) ) {
			return new WP_Error( 'attendee-not-found', $this->messages->get_message( 'attendee-not-found' ) );
		}

		$service_provider = tribe( 'tickets.data_api' )->get_ticket_provider( $attendee_id );
		if ( empty( $service_provider->checkin_key ) ) {
			return new WP_Error( 'attendee-check-in-not-found', $this->messages->get_message( 'attendee-check-in-not-found' ) );
		}

		$meta = array_map( 'reset', get_post_custom( $attendee_id ) );
		$data = array(
			'id'         => $attendee_id,
			'checked_in' => isset( $meta[ $service_provider->checkin_key ] ) ? $meta[ $service_provider->checkin_key ] : '',
		);

		/**
		 * Filters the data that will be returned for a single attendee.
		 *
		 * @since 4.7.5
		 *
		 * @param array   $data  The data that will be returned in the response.
		 * @param WP_Post $attendee The requested attendee.
		 */
		$data = apply_filters( 'tribe_tickets_plus_rest_qr_data', $data, $attendee );

		return $data;
	}
}
