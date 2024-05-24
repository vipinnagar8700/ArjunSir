<?php

/**
 * Public-facing m2m API.
 *
 * Note: This file is included only when m2m is active, so there's no point in checking that anymore.
 *
 * @refactoring Exctract all remaining code into command classes under inc/autoloaded/interop/commands
 */

use OTGS\Toolset\Common\M2M as m2m;
use OTGS\Toolset\Common\Interop\Commands as commands;
use \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;

/**
 * Query related post if many-to-many relationship functionality is enabled.
 *
 * By default, this function accepts an argument array as its third parameter ($args_or_query_by_role), but for backward
 * compatibility reasons, the third argument can be also a query limit, and a number of other arguments is supported.
 * If you need documentation for those, look into an older version of this file before Types 3.1.
 *
 * For our purposes, $args_or_query_by_role is the argument array and following function parameters are completely ignored.
 *
 * @param int|\WP_Post|int[]|\WP_Post[]|int[][]|\WP_Post[][] $query_by_elements One or more posts to query by.
 *     There are several formats accepted:
 *     - single post (ID or a post object): The function will return only posts that are connected to this one
 *       in the role provided by the query_by_role argument.
 *     - array of posts indexed by role names: The function will return only posts that are connected to all of these
 *       posts in given roles.
 *     - arrays of arrays of posts indexed by role names: The function will return only posts that are connected to
 *       any of the provided posts for each role.
 *     Example:
 *         array( 'parent' => array( $parent1, $parent2 ), 'intermediary' => $intermediary1 )
 *         -> returns posts connected to $parent1 OR $parent2 in the parent role, AND to the $intermediary1 in the
 *         intermediary role.
 *
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 *
 * @param int|array $args_or_query_by_role
 *    - 'query_by_role' string - Name of the element role to query by. This argument is required $query_by_elements, and in other
 *        cases, it must not be present at all. Accepted values: 'parent'|'child'|'intermediary'.
 *    - 'limit': int - Maximum number of returned results ("posts per page").
 *    - 'offset': int - Result offset ("page number")
 *    - 'args': array - Additional query arguments. Accepted arguments:
 *        - meta_key, meta_value and meta_compare: Works exactly like in WP_Query. Only limited values are supported for meta_compare ('='|'LIKE').
 *        - s: Text search in the posts.
 *        - post_status: Array of post status values, or a string with one or more statuses separated by commas.
 *          The passed statuses need to be among the values returned by get_post_statuses() or added by the
 *          toolset_accepted_post_statuses_for_api filter.
 *          If this argument is not empty, only post with matching status will be returned.
 *    - 'return': string - Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of \WP_Post objects.
 *    - 'role_to_return' string|array - Which posts from the relationship should be returned. Accepted values
 *        are 'parent'|'child'|'intermediary'|'other'|'all' or an array of them, but the value must be different from $query_by_role_name.
 *        If the query_by_role argument is 'parent' or 'child', it is also possible to pass 'other' here.
 *    - 'orderby' : null|string - Determine how the results will be ordered. Accepted values: null, 'title',
 *      'meta_value', 'meta_value_num', 'rfg_order'.
 *       - If the 'meta_value' or 'meta_value_num' is used, there also needs to be a 'meta_key' argument in 'args'.
 *       - 'rfg_order' is applicable only for repeatable field groups and it means the order which has been
 *          set manually by the user. Using it overrides the 'meta_key' value in 'args'.
 *       - Passing null means no ordering.
 *    - 'orderby_role' : null|string - Name of the role by which ordering should happen. If no value is provided,
 *          the first value from the 'role_to_return' argument will be used.
 *    - 'order': string - Accepted values: 'ASC' or 'DESC'.
 *    - 'need_found_rows': bool - Signal if the query should also determine the total number of results (disregarding pagination).
 *
 * @param int $legacy_limit Ignored.
 * @param int $legacy_offset Ignored.
 * @param array $legacy_args Ignored.
 * @param string $legacy_return Ignored.
 * @param string $legacy_role_name_to_return Ignored.
 * @param null $legacy_orderby Ignored.
 * @param string $legacy_order Ignored.
 * @param bool $legacy_need_found_rows Ignored.
 * @param null $legacy_found_rows Ignored.
 *
 * @return int[]|\WP_Post[]|array If need_founds_rows is true, the array returned will be [ 'results' => $actual_results, 'found_rows' => $n ].
 *     Otherwise, only $actual_results results will be returned (array of post IDs or posts according to the "return" argument).
 */
function toolset_get_related_posts(
	$query_by_elements,
	$relationship,
	$args_or_query_by_role = null,
	$legacy_limit = 100,
	$legacy_offset = 0,
	$legacy_args = array(),
	$legacy_return = 'post_id',
	$legacy_role_name_to_return = 'other',
	$legacy_orderby = null,
	$legacy_order = 'ASC',
	$legacy_need_found_rows = false,
	&$legacy_found_rows = null
) {
	do_action( 'toolset_do_m2m_full_init' );

	$is_legacy_mode = ! is_array( $args_or_query_by_role );

	if ( $is_legacy_mode ) {
		$keys = array( 'query_by_role', 'limit', 'offset', 'args', 'return', 'role_to_return', 'orderby', 'order', 'need_found_rows', 'ignored_found_rows' );
		// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		$elements = array_slice( func_get_args(), 2 );
		$keys = array_slice( $keys, 0, count( $elements ) );
		$arguments = array_combine( $keys, $elements  );
		// Found rows are handled differently.
		unset( $arguments['ignored_found_rows'] );
	} else {
		$arguments = $args_or_query_by_role;
	}

	$related_posts_command = new commands\RelatedPosts( $query_by_elements, $relationship, $arguments );
	$results = $related_posts_command->get_results();

	if( $is_legacy_mode && $legacy_need_found_rows ) {
		$legacy_found_rows = $results['found_rows'];
		$results = $results['results']; // Needed for legacy arguments.
	}

	return $results;
}


/**
 * Retrieve an ID of a single related post.
 *
 * Note: For more complex cases, use toolset_get_related_posts().
 *
 * @param WP_Post|int $post Post whose related post should be returned.
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param string $role_name_to_return Which posts from the relationship should be returned. Accepted values
 *     are 'parent' and 'child'. The relationship needs to have only one possible result in this role,
 *     otherwise an exception will be thrown.
 * @param null|array $args Additional arguments. Accepted values:
 *        - meta_key, meta_value and meta_compare: Works exactly like in WP_Query. Only limited values are supported for meta_compare ('='|'LIKE').
 *        - s: Text search in the posts.
 *        - post_status: Array of post status values, or a string with one or more statuses separated by commas.
 *          The passed statuses need to be among the values returned by get_post_statuses() or added by the
 *          toolset_accepted_post_statuses_for_api filter.
 *          If this argument is not empty, only post with matching status will be returned.
 *
 * @return int Post ID or zero if no related post was found.
 */
function toolset_get_related_post( $post, $relationship, $role_name_to_return = 'parent', $args = null ) {
	do_action( 'toolset_do_m2m_full_init' );

	if( ! in_array( $role_name_to_return, \Toolset_Relationship_Role::parent_child_role_names() ) ) {
		throw new \InvalidArgumentException(
			'The role name to return is not valid. Allowed values are: "' .
			implode( '", "', \Toolset_Relationship_Role::parent_child_role_names() ) .
			'".'
		);
	}

	$query_by_role_name = \Toolset_Relationship_Role::other( $role_name_to_return );

	$related_posts = new commands\RelatedPosts(
		$post,
		$relationship,
		array( 'args' => toolset_ensarr( $args ), 'query_by_role' => $query_by_role_name )
	);
	$results = $related_posts->get_results();

	if( empty( $results ) ) {
		return 0; // No result.
	}

	return (int) array_pop( $results );
}


/**
 * Retrieve an ID of the parent post, using a legacy post relationship (migrated from the legacy implementation).
 *
 * For this to work, there needs to be a relationship between $target_type and the provided post's type.
 *
 * Note: For more complex cases, use toolset_get_related_post() or toolset_get_related_posts().
 *
 * @param WP_Post|int $post Post whose parent should be returned.
 * @param string $target_type Parent post type.
 *
 * @return int Post ID or zero if no related post was found.
 */
function toolset_get_parent_post_by_type( $post, $target_type ) {

	$post = get_post( $post );

	if( ! $post instanceof WP_Post ) {
		return 0;
	}

	return toolset_get_related_post( $post, array( $target_type, $post->post_type ) );
}


/**
 * Get extended information about a single relationship definition.
 *
 * The relationship array contains following elements:
 * array(
 *     'slug' (only if m2m is enabled) => Unique slug identifying the relationship.
 *     'labels' (only if m2m is enabled) => array(
 *         'plural' => Plural display name of the relationship.
 *         'singular' => Singular display name of the relationship.
 *     ),
 *     'roles' => array(
 *         'parent' => array(
 *             'domain' => Domain of parent elements. Currently, only 'posts' is supported.
 *             'types' => Array of (post) types involved in a single relationship. Currently, there's always
 *                 only a single post type, but that may change in the future.
 *         ),
 *         'child' => Analogic to the parent role information.
 *         'intermediary' (present only if m2m is enabled and if the relationship is of the many-to-many type)
 *             => Analogic to the parent role information. The domain is always 'posts' and there is always a single post type.
 *     ),
 *     'cardinality' => array(
 *         'type' => 'many-to-many'|'one-to-many'|'one-to-one',
 *         'limits' => array(
 *             'parent' => array(
 *                 'min' => The minimal amount of connected parent ("left side") posts for each child ("right side") post.
 *                     Currently, this is always 0, but it may change in the future.
 *                 'max' => The maximal amount of connected parent posts for each child post.
 *                     If there is no limit, it's represented by the value -1.
 *             ),
 *             'child' => Analogic to the parent role information.
 *         )
 *     ),
 *     'origin' => 'post_reference_field'|'repeatable_group'|'standard' How was the relationship created. "standard" is the standard one.
 * )
 *
 * @param string|string[] $identification Relationship slug or a pair of post type slugs identifying a legacy relationship.
 *
 * @return array|null Relationship information or null if it doesn't exist.
 */
function toolset_get_relationship( $identification ) {
	do_action( 'toolset_do_m2m_full_init' );

	$service = new m2m\PublicApiService();
	$definition = null;

	// gently handle invalid argument error and log it
	try{
		$definition = $service->get_relationship_definition( $identification );
	} catch( InvalidArgumentException $exception ){
		/** @noinspection ForgottenDebugOutputInspection */
		error_log( $exception->getMessage() );
		return null;
	}

	// gently handle case if definition is null without breaking execution
	if( ! $definition instanceof IToolset_Relationship_Definition ) {
		return null;
	}

	return $service->format_relationship_definition( $definition );
}


/**
 * Query relationships by provided arguments.
 *
 * @param array $args Query arguments. Accepted values are:
 *     - 'include_inactive': If this is true, also relationships which are deactivate or have unregistered post types will appear.
 *     - 'type_constraints': Array of constraints where each item has a role as index. Role can be 'parent', 'child', 'intermediary'
 *           or 'any' to match relationships where any of its roles fulfills the constrants.
 *           Value of the constraint is an array which may contain following elements:
 *           - 'domain': Name of the domain. Currently, only 'posts' are supported.
 *           - 'type': A single (post) type.
 *           - 'types': An array of (post) types. The constraint will be fulfilled if the relationship
 *                 has one of the provided types in the given role.
 *                 This is ignored if 'type' is provided.
 *     - 'origin': 'post_reference_field'|'repeatable_group'|'standard'|'any' How was the relationship created ("standard" is the standard one).
 *     - 'cardinality': Accepted values are 'one-to-one', 'one-to-many', 'one-to-something', 'many-to-many'
 *           or a string defining a specific cardinality: "{$parent_min}..{$parent_max}:{$child_min}..{$child_max}.
 *           Each of these values must be an integer or "*" for infinity.
 *
 * @return array Array of matching relationship definitions in the same format as in toolset_get_relationship().
 * @since 2.6.4
 */
function toolset_get_relationships( $args ) {
	if( ! is_array( $args ) ) {
		throw new InvalidArgumentException( 'Invalid input, expected an array of query arguments.' );
	}

	do_action( 'toolset_do_m2m_full_init' );

	$service = new m2m\PublicApiService();
	$query = $service->get_factory()->relationship_query();

	if( true === (bool) toolset_getarr( $args, 'include_inactive' ) ) {
		$query->do_not_add_default_conditions();
	}

	if( array_key_exists( 'type_constraints', $args ) ) {
		$type_constraints = toolset_ensarr( toolset_getarr( $args, 'type_constraints' ) );
		foreach( $type_constraints as $role_name => $type_query ) {

			$role = ( 'any' === $role_name ? null : Toolset_Relationship_Role::role_from_name( $role_name ) );

			$domain = toolset_getarr( $type_query, 'domain', Toolset_Element_Domain::POSTS );
			if( $domain !== Toolset_Element_Domain::POSTS ) {
				throw new InvalidArgumentException( 'Invalid element domain. Only "posts" are allowed at the moment.' );
			}

			if( array_key_exists( 'type', $type_query ) ) {
				$types = array( toolset_getarr( $type_query, 'type' ) );
			} else {
				$types = toolset_ensarr( toolset_getarr( $type_query, 'types' ) );
			}
			if( empty( $types ) ) {
				continue;
			}

			if( count( $types ) === 1 ) {
				$query->add( $query->has_domain_and_type( array_pop( $types ), $domain, $role ) );
			} else {
				$query->add( $query->do_or( array_map( function( $type ) use( $domain, $role, $query ) {
					return $query->has_domain_and_type( $type, $domain, $role );
				}, $types ) ) );
			}
		}
	}

	$origin = toolset_getarr( $args, 'origin', 'standard' );
	if( 'standard' === $origin ) {
		$origin = 'wizard';
	} elseif ( 'any' === $origin ) {
		$origin = null;
	}
	$query->add( $query->origin( $origin ) );

	if( array_key_exists( 'cardinality', $args ) ) {
		$cardinality_query = toolset_getarr( $args, 'cardinality' );
		switch( $cardinality_query ) {
			case 'one-to-one':
				$cardinality = $query->cardinality()->one_to_one();
				break;
			case 'one-to-many':
				$cardinality = $query->cardinality()->one_to_many();
				break;
			case 'one-to-something':
				$cardinality = $query->cardinality()->one_to_something();
				break;
			case 'many-to-many':
				$cardinality = $query->cardinality()->many_to_many();
				break;
			default:
				$cardinality = $query->cardinality()->by_cardinality(
					Toolset_Relationship_Cardinality::from_string( $cardinality_query )
				);
				break;
		}

		$query->add( $query->has_cardinality( $cardinality ) );
	}

	$definitions = $query->get_results();

	return array_map( function( $relationship_definition ) use( $service ) {
		return $service->format_relationship_definition( $relationship_definition );
	}, $definitions );
}


/**
 * Get post types related to the provided one.
 *
 * @param string $return_role Role that the results have in a relationship.
 * @param string $for_post_type Post type slug in the opposite role.
 *
 * @return string[][] An associative array where each post type has one key, and its value
 *     is an array of relationship slugs (in m2m) /post type pairs (in legacy implementation)
 *     that have matched the query.
 *
 * For example, if there is a relationship "appointment" between "doctor" and "patient" post types,
 * toolset_get_related_post_types( 'parent', 'patient' ) will return:
 * array( 'doctor' => array( 'appointment' ) )
 *
 * @since 2.6.4
 */
function toolset_get_related_post_types( $return_role, $for_post_type ) {
	do_action( 'toolset_do_m2m_full_init' );

	$role = Toolset_Relationship_Role::role_from_name( $return_role );
	if( ! $role instanceof RelationshipRoleParentChild ) {
		throw new InvalidArgumentException( 'Invalid role value. Accepted values are "parent" and "child".' );
	}

	$service = new m2m\PublicApiService();
	$query = $service->get_factory()->relationship_query();
	$relationships = $query->add(
		$query->has_domain_and_type( $for_post_type, Toolset_Element_Domain::POSTS, $role->other() )
	)->get_results();

	$results = array();

	foreach( $relationships as $relationship ) {
		$post_types = $relationship->get_element_type( $return_role )->get_types();
		foreach( $post_types as $post_type ) {
			if( ! array_key_exists( $post_type, $results ) ) {
				$results[ $post_type ] = array();
			}
			$results[ $post_type ][] = $relationship->get_slug();
		}
	}

	return $results;
}

/**
 * Will collect all associations of the $child_id and return as a array of relationships and associations
 * These list can be used to export associations as postmeta.
 *
 * @param $child_id
 *
 * @return false|array
 *      false if child_id could not be found
 *      array empty if no associations there
 *
 *      example of response with associations (meta_key => meta_value)
 *      '_toolset_associations_%relationship_1_slug%' => "%association_1_parent_guid% + %association_1_intermediary_guid%, %association_2_parent_guid% + %association_2_intermediary_guid%, ..."
 *      '_toolset_associations_%relationship_2_slug%' => "%association_1_parent_guid%, %association_2_parent_guid%, ..."
 *      '_toolset_associations_%relationship_3_slug%' => "%association_1_parent_guid%, %association_2_parent_guid%, ..."
 *
 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
 */
function toolset_export_associations_of_child( $child_id ) {
	if ( ! $child_post = get_post( $child_id ) ) {
		return false;
	}

	do_action( 'toolset_do_m2m_full_init' );

	/** @var Toolset_Element_Factory $toolset_element_factory */
	$toolset_element_factory = Toolset_Singleton_Factory::get( 'Toolset_Element_Factory' );

	try {
		$child_element = $toolset_element_factory->get_post( $child_post );
	} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
		// element could not be found
		return false;
	}

	/** @var \OTGS\Toolset\Common\M2M\Association\Repository $association_repository */
	$association_repository = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Common\M2M\Association\Repository',
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Query_Factory' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Parent' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Child' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Intermediary' ),
		Toolset_Singleton_Factory::get( 'Toolset_Element_Domain' )
	);

	$association_repository->addAssociationsByChild( $child_element );

	/** @var \OTGS\Toolset\Types\Post\Export\Associations $export_associations */
	$export_associations = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Export\Associations',
		$association_repository,
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Meta\Associations' )
	);

	return $export_associations->getExportArray( $child_element );
}


/**
 * Will search for associations in meta of $child_id and import them.
 * To make sure the data is correctly formated use toolset_export_associations_of_child to export data.
 *
 * @param $child_id
 *
 * @return array|false
 * 		false if child_id could not be found
 *      'success' => array of succesfully imported associations
 *      'error' => array of associations which could not be imported
 *
 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
 */
function toolset_import_associations_of_child( $child_id ) {
	if ( ! $child_post = get_post( $child_id ) ) {
		return false;
	}

	do_action( 'toolset_do_m2m_full_init' );
	global $wpdb;

	$association_repository = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Common\M2M\Association\Repository',
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Query_Factory' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Parent' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Child' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Intermediary' ),
		Toolset_Singleton_Factory::get( 'Toolset_Element_Domain' )
	);

	/** @var \OTGS\Toolset\Types\Post\Import\Associations $import_associations */
	$import_associations = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Import\Associations',
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Meta\Associations' ),
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Wordpress\Post\Storage', $wpdb ),
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Wordpress\Postmeta\Storage', $wpdb ),
		Toolset_Relationship_Definition_Repository::get_instance(),
		$association_repository,
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Import\Association\Factory' ),
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Common\Relationships\API\Factory' )
	);

	// as we use singeleton factory, make sure we start with a clean set of associations
	$import_associations->resetAssociations();

	// load all associations by child
	$import_associations->loadAssociationsByChildPost( $child_post );

	return $import_associations->importAssociations( true, false );
}


/**
 * Connect two posts in a given relationship.
 *
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param int|WP_Post $parent Parent post to connect.
 * @param int|WP_Post $child Child post to connect.
 * @param null|int|WP_Post $intermediary Intermediary post to use for a many-to-many post relationship. If none is
 *     provided and it is needed, a new post will be created.
 *
 * @return array
 *		- (bool) 'success': Always present.
 *      - (string) 'message': A message describing the result. May not be always present.
 *      - (int) 'intermediary_post': Present only if the operation has succeeded. ID of the newly created intermediary post
 *          or zero if there is none.
 *
 * @since 2.7
 * @since Types 3.3.4 added the fourth parameter $intermediary.
 */
function toolset_connect_posts( $relationship, $parent, $child, $intermediary = null ) {

	do_action( 'toolset_do_m2m_full_init' );

	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $parent ) && ! $parent instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The parent must be a post ID or a WP_Post instance.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $child ) && ! $child instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The child must be a post ID or a WP_Post instance.' );
	}

	if( null !== $intermediary && ! Toolset_Utils::is_natural_numeric( $intermediary ) && ! $intermediary instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The intermediary post must be null, a post ID or a WP_Post instance.' );
	}

	if( is_array( $relationship ) && count( $relationship ) === 2 ) {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_legacy_definition( $relationship[0], $relationship[1] );
	} else {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_definition( $relationship );
	}

	// Make sure that a provided intermediary post's type matches what is required by the relationship.
	if( null !== $intermediary ) {
		$element_factory = new Toolset_Element_Factory();
		try {
			$intermediary_element = $element_factory->get_post( $intermediary );
			if( $intermediary_element->get_type() !== $relationship_definition->get_intermediary_post_type() ) {
				throw new \InvalidArgumentException( sprintf(
					'The provided intermediary post has a wrong type. The relationship expects "%s" but the type is "%s".',
					sanitize_title( $relationship_definition->get_intermediary_post_type() ),
					sanitize_title( $intermediary_element->get_type() )
				) );
			}
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			throw new \InvalidArgumentException( 'The provided intermediary post doesn\'t exist.' );
		}

		$intermediary_id = $intermediary_element->get_default_language_id();
	} else {
		$intermediary_id = null;
	}

	if( null === $relationship_definition ) {
		return array(
			'success' => false,
			'message' => "The relationship doesn't exist."
		);
	}

	try {
		$result = $relationship_definition->create_association( $parent, $child, $intermediary_id );
	} catch( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
		return array(
			'success' => false,
			'message' => $e->getMessage()
		);
	}
	if( $result instanceof Toolset_Result ) {
		return array(
			'success' => false,
			'message' => $result->get_message()
		);
	}

	return array(
		'success' => true,
		'intermediary_post' => $result->get_intermediary_id()
	);
}


/**
 * Disconnect two posts in a given relationship.
 *
 * Note: When we introduce non-distinct relationships in the future, the behaviour of this function might change for them.
 * Keep that in mind.
 *
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param int|WP_Post $parent Parent post to connect.
 * @param int|WP_Post $child Child post to connect.
 *
 * @return array
 *		- (bool) 'success': Always present.
 *      - (string) 'message': A message describing the result. May not be always present.
 *
 * @since 2.7
 */
function toolset_disconnect_posts( $relationship, $parent, $child ) {

	do_action( 'toolset_do_m2m_full_init' );

	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $parent ) && ! $parent instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The parent must be a post ID or a WP_Post instance.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $child ) && ! $child instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The child must be a post ID or a WP_Post instance.' );
	}

	if( is_array( $relationship ) && count( $relationship ) === 2 ) {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_legacy_definition( $relationship[0], $relationship[1] );
	} else {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_definition( $relationship );
	}

	if( null === $relationship_definition ) {
		return array(
			'success' => false,
			'message' => "The relationship doesn't exist."
		);
	}

	$query = ( new m2m\PublicApiService() )->get_factory()->association_query();
	$results = $query->add( $query->relationship( $relationship_definition ) )
		->add( $query->parent_id( $parent ) )
		->add( $query->child_id( $child ) )
		->limit( 1 )
		->do_not_add_default_conditions()
		->return_association_instances()
		->get_results();

	if( empty( $results ) ) {
		return array(
			'success' => false,
			'message' => __( 'There is no association between the two given posts that can be deleted', 'wpv-views' )
		);
	}

	$association = array_pop( $results );

	$result = $relationship_definition->get_driver()->delete_association( $association );

	if( $result->is_error() ) {
		return array(
			'success' => false,
			'message' => $result->get_message(),
		);
	}

	return array(
		'success' => true
	);
}
