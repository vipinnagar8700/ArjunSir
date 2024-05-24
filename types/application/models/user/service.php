<?php

/**
 * Class Types_User_Service
 *
 * @since 2.3
 */
class Types_User_Service {

	/**
	 * Returns WP_User of $params['user_id'] | $params['user_name'] | $params['user_current'].
	 * If all keys not set it will return the author of the current post.
	 *
	 * @param array $params
	 *
	 * @return WP_User|false|null
	 */
	public function find_by_user_params( $params ) {
		if ( isset( $params['user_id'] ) && $params['user_id'] ) {
			// by id
			return get_user_by( 'id', $params['user_id'] );
		}

		if ( isset( $params['user_name'] ) && $params['user_name'] ) {
			// by login
			return get_user_by( 'login', $params['user_name'] );
		}

		if ( ( isset( $params['user_current'] ) && $params['user_current'] )
			|| ( isset( $params['current_user'] ) && $params['current_user'] ) ) {
			// current logged-in user
			return wp_get_current_user();
		}

		// check Views loop item
		// @refactoring Remove the hard dependency on WP_Views.
		global $WP_Views;

		if ( $WP_Views instanceof WP_Views
			&& isset( $WP_Views->users_data['term']->ID )
			&& ! empty( $WP_Views->users_data['term']->ID )
		) {
			// value delivered by Toolset Views
			return get_user_by( 'id', $WP_Views->users_data['term']->ID );
		}

		$post_service = new Types_Post_Service();
		$post = $post_service->find_by_user_params( $params );

		if ( $post instanceof WP_Post ) {
			return get_user_by( 'id', $post->post_author );
		}

		return false;
	}
}
