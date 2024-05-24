<?php

/**
 * Toolset Gutenberg Blocks REST helper class.
 *
 * @since 2.6.0
 *
 * @deprecated Use the endpoint provided by the "ToolsetCommonEs\Rest\Route\PostSearch" class.
 */
class Toolset_Gutenberg_Block_REST_Helper {

	public function __construct() {
		$this->register_search_post_rest_api_endpoint();
	}

	/**
	 * Registering a custom REST API endpoint that returns all the posts of any type after narrowing the
	 * results down using a keyword.
	 * This functionality is not included in the native WP REST API and there is a ticket for that
	 * https://github.com/WordPress/gutenberg/issues/2084
	 * https://core.trac.wordpress.org/ticket/39965
	 */
	public function register_search_post_rest_api_endpoint() {
		add_action( 'rest_api_init', array( $this, 'register_search_post_rest_api_routes' ) );
	}

	/**
	 * Register the custom REST API endpoint routes.
	 */
	public function register_search_post_rest_api_routes() {
		$namespace = 'toolset/v2';
		$base = 'search-posts';
		$route = '/' . $base;
		$args = array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'search_for_posts' ),
			'permission_callback' => '__return_true',
		);

		register_rest_route( $namespace, $route, $args );
	}

	/**
	 * Custom REST API endpoint callback, that returns all the posts of any type after narrowing the
	 * results down using a keyword.
	 *
	 * @param WP_REST_Request $request The request of the REST API call.
	 *
	 * @return mixed|WP_REST_Response  The list of the posts that resulted after the keyword search.
	 */
	public function search_for_posts( WP_REST_Request $request ) {
		$response = array();
		$empty_response = array(
			'id' => '',
			'name' => '',
		);
		$search = '';
		$posts_per_page = 20;
		$ignore_sticky_posts = 1;
		$post_status = 'publish';
		$params = $request->get_params();

		if ( isset( $params['search'] ) ) {
			$search = sanitize_text_field( $params['search'] );
		}

		if ( isset( $params['posts_per_page'] ) ) {
			$posts_per_page = sanitize_text_field( $params['posts_per_page'] );
		}

		if ( isset( $params['ignore_sticky_posts'] ) ) {
			$ignore_sticky_posts = sanitize_text_field( $params['ignore_sticky_posts'] );
		}

		if ( isset( $params['post_status'] ) ) {
			$post_status = sanitize_text_field( $params['post_status'] );
		}

		$query_args = array(
			's' => $search,
			'post_status' => $post_status,
			'ignore_sticky_posts' => $ignore_sticky_posts,
			'posts_per_page' => $posts_per_page,
		);

		if ( isset( $params['post_type'] ) ) {
			$query_args['post_type'] = sanitize_text_field( $params['post_type'] );
		}

		$search_results = new WP_Query( $query_args );

		if ( $search_results->have_posts() ) {
			while ( $search_results->have_posts() ) {
				$search_results->the_post();
				$title = $this->tokenize_string( get_the_title(), 50 ); // Truncate the post title to 50 characters
				$response[] = array(
					'id' => get_the_ID(),
					'name' => ( $title ? $title : __( '(no title)', 'wpv-views' ) ) . ' (#' . get_the_ID() . ')',
				);
			}
		} else {
			$response[] = $empty_response;
		}

		wp_reset_postdata();

		return rest_ensure_response( $response );
	}

	public function tokenize_string( $string, $char_count = 50 ) {
		return strtok( wordwrap( $string, $char_count, "...\n" ), "\n" );
	}
}
