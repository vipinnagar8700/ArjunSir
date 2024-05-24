<?php

class Tribe__Tickets_Plus__REST__V1__Documentation__QR_Definition_Provider
	implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * @since 4.7.5
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of informations rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$documentation = array(
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'type'        => 'integer',
					'description' => __( 'The ticket WordPress post ID', 'event-tickets-plus' ),
				),
				'api_key'       => array(
					'type'        => 'string',
					'description' => __( 'The API key to authorize check in', 'event-tickets-plus' ),
				),
				'security_code' => array(
					'type'        => 'string',
					'description' => __( 'The security code of the ticket to verify for check in', 'event-tickets-plus' ),
				),
				'event_id'      => array(
					'type'        => 'integer',
					'description' => __( 'The event WordPress post ID', 'event-tickets-plus' ),
				),
			),
		);

		/**
		 * Filters the Swagger documentation generated for an QR in the ET+ REST API.
		 *
		 * @since 4.7.5
		 *
		 * @param array $documentation An associative PHP array in the format supported by Swagger.
		 *
		 * @link http://swagger.io/
		 */
		$documentation = apply_filters( 'tribe_rest_swagger_qr_documentation', $documentation );

		return $documentation;
	}
}
