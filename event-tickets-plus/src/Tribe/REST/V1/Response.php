<?php

/**
 * Class Tribe__Tickets_Plus__REST__V1__Response
 *
 * Filters the REST API v1 response to add fields and information managed by Event Tickets Plus.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__REST__V1__Response {
	/**
	 * Filters the data that will be returned for a single ticket.
	 *
	 * @since 4.8
	 *
	 * @param array|WP_Error  $data             The ticket data or a WP_Error if the request
	 *                                          generated errors.
	 *
	 * @return array|WP_Error  The modified ticket data.
	 */
	public function filter_single_ticket_data( $data ) {
		if ( $data instanceof WP_Error ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		$ticket_id            = $data['id'];
		$ticket_meta_enabled  = tribe_is_truthy( get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::ENABLE_META_KEY, true ) );
		$ticket_meta          = (array) get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::META_KEY, true );
		$required_ticket_meta = wp_list_filter( $ticket_meta, array( 'required' => 'on' ) );

		$supports_attendee_information         = $ticket_meta_enabled && count( $ticket_meta ) > 0;
		$data['supports_attendee_information'] = $supports_attendee_information;
		$data['requires_attendee_information'] = $data['supports_attendee_information'] && count( $required_ticket_meta ) > 0;
		$data['attendee_information_fields']   = $supports_attendee_information
			? array_filter( array_map( array( $this, 'build_field_information' ), $ticket_meta ) )
			: array();

		return $data;
	}

	/**
	 * Builds an attendee meta field information.
	 *
	 * @since 4.8
	 *
	 * @param array|stdClass $field
	 *
	 * @return array|bool The attendee meta field information if valid, `false` otherwise.
	 */
	protected function build_field_information( $field ) {
		$field = (array) $field;

		if ( ! isset( $field['slug'], $field['type'], $field['label'] ) ) {
			return false;
		}

		$field_data = array(
			'slug'     => $field['slug'],
			'type'     => $field['type'],
			'required' => tribe_is_truthy( Tribe__Utils__Array::get( $field, 'required', false ) ),
			'label'    => $field['label'],
			'extra'    => Tribe__Utils__Array::get( $field, 'extra', array() ),
		);

		return $field_data;
	}

	/**
	 * Filters the data that will be returned for a single attendee.
	 *
	 * @since 4.8
	 *
	 * @param array|WP_Error  $data             The attendee data or a WP_Error if the request
	 *                                          generated errors.
	 *
	 * @return array|WP_Error  The modified attendee data.
	 */
	public function filter_single_attendee_data( $data ) {
		if ( $data instanceof WP_Error ) {
			return $data;
		}

		if ( ! current_user_can( 'read_private_posts' ) ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		$attendee_id   = $data['id'];
		$attendee_meta = get_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		$data['information'] = empty( $attendee_meta ) ? array() : (array) $attendee_meta;

		return $data;
	}
}
