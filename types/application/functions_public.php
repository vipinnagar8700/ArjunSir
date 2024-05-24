<?php
/**
 * Public functions of Types.
 *
 * Important: All functions added to this file must have a public documentation.
 *
 * @since 2.3
 */

/**
 * types_render_field() returns the value of a field.
 * This function can be used for post, user and term fields.
 *
 * @see https://toolset.com/documentation/customizing-sites-using-php/functions/
 *
 * @param int $field_id
 * @param array $user_params
 * @param string|null $content
 *
 * @return string
 */
function types_render_field( $field_id, $user_params = array(), $content = null ) {
	if ( isset( $user_params['usermeta'] ) && ! empty( $user_params['usermeta'] ) ) {
		// user field
		return types_render_usermeta( $field_id, $user_params, $content );
	}

	if ( isset( $user_params['termmeta'] ) && ! empty( $user_params['termmeta'] ) ) {
		// term field
		return types_render_termmeta( $field_id, $user_params, $content );
	}

	// post field
	return types_render_postmeta( $field_id, $user_params, $content );
}


/**
 * types_render_postmeta() returns the value of a post field.
 *
 * @see https://toolset.com/documentation/customizing-sites-using-php/functions/
 *
 * @param int $field_id
 * @param array $user_params
 * @param string|null $content
 *
 * @return string
 */
function types_render_postmeta( $field_id, $user_params = array(), $content = null ) {
	$post = new Types_Post_Service();
	$post = $post->find_by_user_params( $user_params );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$field_service = Types_Field_Service_Store::get_instance()->get_service( false );

	return $field_service->render_frontend( new Types_Field_Gateway_Wordpress_Post(), $post, $field_id, $user_params, $content );
}


/**
 * types_render_usermeta() return the value of a user field
 *
 * @see https://toolset.com/documentation/customizing-sites-using-php/functions/
 *
 * @param int $field_id
 * @param array $user_params
 * @param string|null $content
 *
 * @return string
 */
function types_render_usermeta( $field_id, $user_params = array(), $content = null ) {
	$user = new Types_User_Service();

	$user = $user->find_by_user_params( $user_params );

	if ( ! $user instanceof WP_User ) {
		return '';
	}

	$field_service = Types_Field_Service_Store::get_instance()->get_service( false );

	return $field_service->render_frontend( new Types_Field_Gateway_Wordpress_User(), $user, $field_id, $user_params, $content );
}


/**
 * types_render_termmeta() return the value of a user field
 *
 * @see https://toolset.com/documentation/customizing-sites-using-php/functions/
 *
 * @param int $field_id
 * @param array $user_params
 * @param string|null $content
 *
 * @return string
 */
function types_render_termmeta( $field_id, $user_params = array(), $content = null ) {
	$term = new Types_Term_Service();

	$term = $term->find_by_user_params( $user_params );

	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$field_service = Types_Field_Service_Store::get_instance()->get_service( false );

	return $field_service->render_frontend( new Types_Field_Gateway_Wordpress_Term(), $term, $field_id, $user_params, $content );
}


/**
 * types_child_posts() gets the child posts of the current post by given post_type
 *
 * @see https://toolset.com/documentation/user-guides/querying-and-displaying-child-posts/
 *
 * @param string $post_type post_type of the requested child posts
 * @param array $args
 *   'post_id'      ID of parent post. Default: current post id is used
 *   'post_status'  Status of requested child posts. Default: 'publish'
 *
 * @return array|WP_Post[]|false
 */
function types_child_posts( $post_type, $args = array() ) {
	$service_relationship = new Toolset_Relationship_Service();
	$service_post = new Types_Post_Service();

	if ( isset( $args['post_id'] ) ) {
		// parent id defined
		$parent_id = $args['post_id'];
	} else {
		// no parent id, use current post
		global $post;

		if ( ! is_object( $post ) || ! property_exists( $post, 'ID' ) ) {
			return false;
		}

		$parent_id = $post->ID;
	}

	// post type of children posts
	$children_args = array( 'post_type' => $post_type );

	if ( isset( $args['post_status'] ) ) {
		$children_args['post_status'] = esc_attr( $args['post_status'] );
	}

	if ( isset( $args['numberposts'] ) ) {
		$children_args['numberposts'] = esc_attr( $args['numberposts'] );
	}

	// get children ids
	$children_ids = $service_relationship->find_children_ids_by_parent_id( $parent_id, $children_args );

	if ( empty( $children_ids ) ) {
		// no m2m child posts, try getting legacy child posts
		$children_posts = legacy_types_child_posts( $post_type, $args );

		if ( empty( $children_posts ) ) {
			// also no legacy child posts
			return array();
		}

		// available legacy children posts
		return $children_posts;
	}

	// available m2m child posts
	$children_posts = array();
	foreach ( $children_ids as $id ) {
		$children_post = $service_post->find_by_id( $id );

		if ( ! $children_post ) {
			// post not available (shouldn't happen and means outdated relationship table)
			continue;
		}

		$children_post = $service_post->load_fields_to_post_object( $children_post );
		$children_posts[] = $children_post;
	}

	return $children_posts;
}
