<?php

namespace OTGS\Toolset\Common\Rest;

/**
 * Toolset Common Rest API utils.
 *
 * Provides a number of shared methods for individual plugins.
 *
 * @since 3.4
 */
class Utils {


	/**
	 * Register new API routes.
	 *
	 * @param array $route Route to be registered.
	 */
	public function register_rest_route( $route ) {
		register_rest_route(
			toolset_getarr( $route, 'namespace' ),
			toolset_getarr( $route, 'route' ),
			toolset_getarr( $route, 'endpoints', array() ),
			toolset_getarr( $route, 'override', false )
		);
	}


	/**
	 * Register new fields in the API.
	 *
	 * @param array $field Field to be added.
	 */
	public function register_field( $field ) {
		register_rest_field(
			toolset_getarr( $field, 'object' ),
			toolset_getarr( $field, 'name' ),
			toolset_getarr( $field, 'callbacks' )
		);
	}


	/**
	 * Instantiates a WP_Error.
	 *
	 * @param array $error Error fields.
	 *
	 * @return \WP_Error
	 */
	public function throw_error( $error ) {
		return new \WP_Error( $error['code'], $error['message'], array( 'status' => $error['status'] ) );
	}


	/**
	 * Generate an API response.
	 *
	 * @param array $response Expected elements are 'response' and 'status'.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_response( $response ) {
		return new \WP_REST_Response( $response['response'], $response['status'] );
	}

}
