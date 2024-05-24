<?php

namespace OTGS\Toolset\Common\Interop\Commands;


use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Class for getting related content from m2m relationships
 *
 * See the constructor for accepted query arguments.
 *
 * Warning: Each instance of this class is meant to be used only once. Implement caching in get_results() if that's a
 * problem for you.
 *
 * @see toolset_get_related_post
 * @see toolset_get_related_posts
 * @since 3.1
 */
class RelatedPosts {


	const RETURN_POST_ID = 'post_id';

	const RETURN_POST_OBJECT = 'post_object';

	const ROLE_OTHER = 'other';

	const ROLE_ALL = 'all';

	const ORDERBY_RFG_ORDER = 'rfg_order';


	/**
	 * Query by elements, indexed by role names.
	 *
	 * @var int|\WP_Post|int[]|\WP_Post[]|int[][]|\WP_Post[][]
	 */
	private $query_by_elements;


	/**
	 * Relationships slug or post types belonging to a relationship for legacy ones.
	 *
	 * @var string|false
	 */
	private $relationship;


	/**
	 * @var int Query limit.
	 */
	private $limit = 100;


	/**
	 * Query offset. Default = 0
	 *
	 * @var int|\WP_Post
	 */
	private $offset = 0;


	/**
	 * Query extra arguments
	 *
	 * @var array
	 */
	private $extra_arguments = array();


	/** @var string */
	private $what_to_return = 'post_id';


	/** @var string[] */
	private $role_name_to_return = array( 'other' );


	/** @var string */
	private $orderby = null;


	/** @var string */
	private $order = 'ASC';


	/** @var string */
	private $order_by_role;


	/** @var boolean */
	private $need_found_rows = false;


	/**
	 * Number of rows found
	 *
	 * @var int
	 */
	private $found_rows = null;


	/** @var \Toolset_Relationship_Definition_Repository|null */
	private $_repository;


	/** @var Factory */
	private $relationships_factory;


	/**
	 * Constructor
	 *
	 * @param int|\WP_Post|int[]|\WP_Post[]|int[][]|\WP_Post[][] $query_by_elements One or more posts to query by.
	 *     There are several formats accepted:
	 *     - single post (ID or a post object): The function will return only posts that are connected to this one
	 *       in the role provided by $query_by_role_name.
	 *     - array of posts indexed by role names: The function will return only posts that are connected to all of
	 *     these posts in given roles.
	 *     - arrays of arrays of posts indexed by role names: The function will return only posts that are connected to
	 *       any of the provided posts for each role.
	 *     Example:
	 *         array( 'parent' => array( $parent1, $parent2 ), 'intermediary' => $intermediary1 )
	 *         -> returns posts connected to $parent1 OR $parent2 in the parent role, AND to the $intermediary1 in the
	 *         intermediary role.
	 *
	 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the
	 *     child post type. The array variant can be used only to identify relationships that have been migrated from
	 *     the legacy implementation.
	 *
	 * @param array $arguments
	 *    - 'limit' : int - Maximum number of returned results ("posts per page").
	 *    - 'offset' : int - Result offset ("page number")
	 *    - 'args' : array - Additional query arguments. Accepted arguments:
	 *        - meta_key, meta_value and meta_compare: Works exactly like in WP_Query. Only limited values are
	 *     supported
	 *          for meta_compare ('='|'LIKE').
	 *        - s: Text search in the posts.
	 *        - post_status: Array of post status values, or a string with one or more statuses separated by commas.
	 *          The passed statuses need to be among the values returned by get_post_statuses() or added by the
	 *          toolset_accepted_post_statuses_for_api filter.
	 *          If this argument is not empty, only post with matching status will be returned.
	 *    - 'return' : string - Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of
	 *      \WP_Post objects.
	 *    - 'role_to_return' string|array - Which posts from the relationship should be returned. Accepted values
	 *      are 'parent'|'child'|'intermediary'|'other'|'all' or an array of them, but the value must be different from
	 *      $query_by_role_name. If $query_by_role_name is 'parent' or 'child' (and not ignored), it is also possible
	 *      to pass 'other' here.
	 *    - 'orderby' : null|string - Determine how the results will be ordered. Accepted values: null, 'title',
	 *      'meta_value', 'meta_value_num', 'rfg_order'.
	 *       - If the 'meta_value' or 'meta_value_num' is used, there also needs to be a 'meta_key' argument in 'args'.
	 *       - 'rfg_order' is applicable only for repeatable field groups and it means the order which has been
	 *          set manually by the user. Using it overrides the 'meta_key' value in 'args'.
	 *       - Passing null means no ordering.
	 *    - 'orderby_role' : null|string - Name of the role by which ordering should happen. If no value is provided,
	 *          the first value from the 'role_to_return' argument will be used.
	 *    - 'order' : string - Accepted values: 'ASC' or 'DESC'.
	 *    - 'need_found_rows' : bool - Signal if the query should also determine the total number of results
	 *      (disregarding pagination).
	 *
	 * @param Factory|null $relationships_factory
	 * @param \Toolset_Relationship_Definition_Repository|null $repository_di
	 */
	public function __construct(
		$query_by_elements,
		$relationship,
		$arguments = array(),
		Factory $relationships_factory = null,
		\Toolset_Relationship_Definition_Repository $repository_di = null
	) {
		do_action( 'toolset_do_m2m_full_init' );

		$this->relationships_factory = $relationships_factory ?: new Factory();
		$this->_repository = $repository_di;

		$this->set_query_by_elements( $query_by_elements, toolset_getarr( $arguments, 'query_by_role', null ) );
		$this->set_relationship( $relationship );

		if ( isset( $arguments['limit'] ) ) {
			$this->set_limit( $arguments['limit'] );
		}

		if ( isset( $arguments['offset'] ) ) {
			$this->set_offset( $arguments['offset'] );
		}

		if ( isset( $arguments['args'] ) ) {
			$this->set_extra_arguments( $arguments['args'] );
		}

		if ( isset( $arguments['return'] ) ) {
			$this->set_return_format( $arguments['return'] );
		}

		if ( isset( $arguments['role_to_return'] ) ) {
			$this->set_role_name_to_return( $arguments['role_to_return'] );
		}

		if ( isset( $arguments['orderby'] ) ) {
			$this->set_order_by( $arguments['orderby'] );
		}

		if ( isset( $arguments['order'] ) ) {
			$this->set_order( $arguments['order'] );
		}

		if ( array_key_exists( 'orderby_role', $arguments ) ) {
			$this->set_order_by_role( $arguments['orderby_role'] );
		}

		if ( isset( $arguments['need_found_rows'] ) ) {
			$this->set_need_found_rows( $arguments['need_found_rows'] );
		}
	}


	/**
	 * Sets query by element. Post or array of Posts to query by. All results will be connected to these ones.
	 *
	 * @param $query_by_elements
	 * @param $query_by_role_name
	 */
	private function set_query_by_elements( $query_by_elements, $query_by_role_name ) {

		// Normalize $query_by_role_name
		if ( ! is_array( $query_by_elements ) ) {
			if ( ! in_array( $query_by_role_name, \Toolset_Relationship_Role::all_role_names(), true ) ) {
				throw new \InvalidArgumentException( 'The role name to query by is not valid. Allowed values are: "' . implode( '", "', \Toolset_Relationship_Role::all_role_names() ) . '".' );
			}
			$query_by_elements = array( $query_by_role_name => $query_by_elements );
		} elseif ( null !== $query_by_role_name ) {
			throw new \InvalidArgumentException( 'The query_by_role argument must not be set when passing an array to the $query_by_elements parameter.' );
		}

		foreach ( $query_by_elements as $role_name => $elements ) {
			if ( ! \Toolset_Relationship_Role::is_valid( $role_name ) ) {
				throw new \InvalidArgumentException( 'All provided arguments for a related element must must have a role name as the array key.' );
			}
			if ( ! is_array( $elements ) ) {
				$elements = array( $elements );
				$query_by_elements[ $role_name ] = $elements;
			}
			foreach ( $elements as $element ) {
				if ( ! \Toolset_Utils::is_natural_numeric( $element ) && ! $element instanceof \WP_Post ) {
					throw new \InvalidArgumentException( 'All provided arguments for a related element must be either an ID or a WP_Post object.' );
				}
			}
		}
		$this->query_by_elements = $query_by_elements;
	}


	/**
	 * Sets relationsip. Slug of the relationship to query by or an array with the parent and the child post type.
	 *   The array variant can be used only to identify relationships that have been migrated from the legacy
	 * implementation.
	 *
	 * @param string|array $relationship Relationship
	 */
	private function set_relationship( $relationship ) {
		if ( ! $relationship || ( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) ) {
			throw new \InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
		}
		if ( is_array( $relationship ) ) {
			$relationship_definition = $this->get_relationship_repository()->get_legacy_definition( $relationship[0], $relationship[1] );
			if ( null === $relationship_definition ) {
				$this->relationship = false;
			} else {
				$this->relationship = $relationship_definition->get_slug();
			}
		} else {
			$this->relationship = $relationship;
		}
	}


	/**
	 * Sets limit. Maximum number of returned results ("posts per page").
	 *
	 * @param int $limit Limit
	 */
	public function set_limit( $limit ) {
		$limit = (int) $limit;
		if ( - 1 === $limit ) {
			// Well, if they want it, they shall have it...
			$limit = PHP_INT_MAX;
		}
		if ( ! \Toolset_Utils::is_natural_numeric( $limit ) ) {
			throw new \InvalidArgumentException( 'Limit must be a non-negative integer or -1.' );
		}
		$this->limit = $limit;
	}


	/**
	 * Sets offset. Result offset ("page number").
	 *
	 * @param int $offset Offset
	 */
	public function set_offset( $offset ) {
		$offset = (int) $offset;
		if ( ! \Toolset_Utils::is_nonnegative_integer( $offset ) ) {
			throw new \InvalidArgumentException( 'Offset must be non-negative integer.' );
		}
		$this->offset = $offset;
	}


	/**
	 * @param array $args Extra arguments
	 */
	public function set_extra_arguments( $args ) {
		// In case some args have been already set manually, keep them and override whatever the caller has
		// provided.
		$this->extra_arguments = array_merge( $args, $this->extra_arguments );
	}


	/**
	 * Sets return format. Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of
	 * \WP_Post objects.
	 *
	 * @param string $return Return format
	 */
	public function set_return_format( $return ) {
		if ( ! in_array( $return, array( self::RETURN_POST_ID, self::RETURN_POST_OBJECT ) ) ) {
			throw new \InvalidArgumentException( 'The provided argument for a return type must be either "post_id" or "post_object".' );
		}
		$this->what_to_return = $return;
	}


	/**
	 * Sets return format. Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of
	 * \WP_Post objects.
	 *
	 * @param string|array $role_name_to_return Roles to return
	 */
	public function set_role_name_to_return( $role_name_to_return ) {
		if ( ! is_array( $role_name_to_return ) ) {
			$role_name_to_return = array( $role_name_to_return );
		}
		$roles = array_merge(
			\Toolset_Relationship_Role::all_role_names(),
			array( self::ROLE_OTHER, self::ROLE_ALL )
		);
		if ( array_intersect( $role_name_to_return, $roles ) !== $role_name_to_return ) {
			throw new \InvalidArgumentException(
				'The role name to return is not valid. Allowed values are: "' .
				implode( '", "', $roles ) .
				'"'
			);
		}
		if ( in_array( self::ROLE_ALL, $role_name_to_return, true ) ) {
			$this->role_name_to_return = \Toolset_Relationship_Role::all_role_names();
		} else {
			$this->role_name_to_return = $role_name_to_return;
		}
	}


	/**
	 * @param array $orderby Order by
	 */
	public function set_order_by( $orderby ) {
		if ( self::ORDERBY_RFG_ORDER === $orderby ) {
			$this->orderby = 'meta_value_num';
			$this->extra_arguments['meta_key'] = \Toolset_Post::SORTORDER_META_KEY;

			return;
		}

		$this->orderby = $orderby;
	}


	/**
	 * Sets order. Accepted values: 'ASC' or 'DESC'
	 *
	 * @param string $order Order
	 */
	public function set_order( $order ) {
		if ( ! in_array( strtoupper( $order ), array( 'ASC', 'DESC' ) ) ) {
			throw new \InvalidArgumentException( 'Allowed order values are only ASC and DESC.' );
		}
		$this->order = $order;
	}


	private function set_order_by_role( $order_by_role ) {
		if ( ! in_array( $order_by_role, \Toolset_Relationship_Role::all_role_names(), true ) ) {
			throw new \InvalidArgumentException(
				'The role name to order by is not valid. Accepted values are "parent", "child" or "intermediary".'
			);
		}

		$this->order_by_role = $order_by_role;
	}


	/**
	 * @return \Toolset_Relationship_Definition_Repository
	 */
	private function get_relationship_repository() {
		if ( null === $this->_repository ) {
			$this->_repository = \Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_repository;
	}


	/**
	 * Sets need_found_rows. Signal if the query should also determine the total number of results (disregarding
	 * pagination)
	 *
	 * @param boolean $need_found_rows Flag
	 */
	public function set_need_found_rows( $need_found_rows ) {
		$this->need_found_rows = $need_found_rows;
	}


	/**
	 * Get results
	 *
	 * @return int[]|\WP_Post[]
	 */
	public function get_results() {
		$query_by_roles = array_keys( $this->query_by_elements );
		$is_querying_by_single_role = ( count( $query_by_roles ) === 1 );
		$single_query_by_role_name = reset( $query_by_roles );

		if (
			in_array( self::ROLE_OTHER, $this->role_name_to_return )
			&& ( ! $is_querying_by_single_role || ! in_array( $single_query_by_role_name, \Toolset_Relationship_Role::parent_child_role_names() ) )
		) {
			{
				throw new \InvalidArgumentException(
					'The role name to return is not valid. Value "other" must used only if you query by a single role which is either a parent or a child.'
				);
			}
		}

		if ( 'other' === $this->role_name_to_return
			&& ( ! $is_querying_by_single_role || \Toolset_Relationship_Role::INTERMEDIARY === $single_query_by_role_name )
		) {
			throw new \InvalidArgumentException(
				'The role name to return is not valid. "other" can be used if you query by a single role which is either a parent or a child.'
			);
		}

		if ( 'meta_key' === $this->orderby && ! array_key_exists( 'meta_key', $this->extra_arguments ) ) {
			throw new \InvalidArgumentException( 'Cannot use ordering by a meta_key if no meta_key argument is provided.' );
		}

		// Input post-processing
		//
		//
		$element_ids = array();
		foreach ( $this->query_by_elements as $query_by_role_name => $elements ) {
			$element_ids[ $query_by_role_name ] = array_map(
				static function ( $element ) {
					return (int) ( $element instanceof \WP_Post ? $element->ID : $element );
				}, $elements
			);
		}
		$search = toolset_getarr( $this->extra_arguments, 's' );
		$has_meta_condition = ( array_key_exists( 'meta_key', $this->extra_arguments ) && array_key_exists( 'meta_value', $this->extra_arguments ) );

		if ( in_array( 'other', $this->role_name_to_return ) ) {
			// This will happen only if the $is_querying_by_single_role is true and $single_query_by_role_name is not intermediary.
			// Otherwise, an exception would have been thrown.
			$roles_to_return = array( \Toolset_Relationship_Role::role_from_name( $single_query_by_role_name )->other() );
		} else {
			$roles_to_return = array();
			foreach ( $this->role_name_to_return as $role_name ) {
				$roles_to_return[] = \Toolset_Relationship_Role::role_from_name( $role_name );
			}
		}

		$post_statuses = $this->get_post_statuses_to_query_by();

		// Build the query
		//
		//
		try {
			$query = $this->relationships_factory->association_query();

			$query->add( $query->relationship_slug( $this->relationship ) );

			foreach ( $element_ids as $query_by_role_name => $element_ids_per_role ) {
				/** @var \OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition[] $conditions */
				$conditions = array_map(
					static function ( $element_id ) use ( $query, $query_by_role_name ) {
						return $query->element_id_and_domain(
							$element_id,
							\Toolset_Element_Domain::POSTS,
							\Toolset_Relationship_Role::role_from_name( $query_by_role_name )
						);
					}, $element_ids_per_role
				);

				$query->add( $query->do_or( $conditions ) );
			}

			$query->limit( $this->limit )
				->offset( $this->offset )
				->order( $this->order )
				->need_found_rows( $this->need_found_rows );

			if ( ! empty( $search ) ) {
				$search_conditions = array();
				foreach ( $roles_to_return as $role_to_return ) {
					$search_conditions[] = $query->search( $search, $role_to_return );
				}
				$query->add( $query->do_or( $search_conditions ) );
			}

			if ( $has_meta_condition ) {
				$meta_conditions = array();
				foreach ( $roles_to_return as $role_to_return ) {
					$meta_conditions[] = $query->meta(
						toolset_getarr( $this->extra_arguments, 'meta_key' ),
						toolset_getarr( $this->extra_arguments, 'meta_value' ),
						\Toolset_Element_Domain::POSTS,
						$role_to_return,
						toolset_getarr( $this->extra_arguments, 'meta_compare', \Toolset_Query_Comparison_Operator::EQUALS )
					);
				}
				$query->add( $query->do_or( $meta_conditions ) );
			}

			if ( count( $roles_to_return ) === 1 ) {
				$role_to_return = reset( $roles_to_return );
				if ( 'post_id' === $this->what_to_return ) {
					$query->return_element_ids( $role_to_return );
				} else {
					$query->return_element_instances( $role_to_return );
				}
			} else {
				$per_role_transformation = $query->return_per_role();
				foreach ( $roles_to_return as $role_to_return ) {
					if ( 'post_id' === $this->what_to_return ) {
						$per_role_transformation->return_element_ids( $role_to_return );
					} else {
						$per_role_transformation->return_element_instances( $role_to_return );
					}
				}
				$per_role_transformation->done();
			}

			$order_by_role = $this->order_by_role
				? \Toolset_Relationship_Role::role_from_name( $this->order_by_role )
				: reset( $roles_to_return );

			switch ( $this->orderby ) {
				case 'title':
					$query->order_by_title( $order_by_role );
					break;
				case 'meta_value':
					$query->order_by_meta( toolset_getarr( $this->extra_arguments, 'meta_key' ), \Toolset_Element_Domain::POSTS, $order_by_role );
					break;
				case 'meta_value_num':
					$query->order_by_meta( toolset_getarr( $this->extra_arguments, 'meta_key' ), \Toolset_Element_Domain::POSTS, $order_by_role, true );
					break;
				default:
					$query->dont_order();
					break;
			}

			if ( ! empty( $post_statuses ) ) {
				// This will replace the default condition to show only "available" posts.
				$post_status_conditions = array();
				foreach ( $roles_to_return as $role_to_return ) {
					$post_status_conditions[] = $query->element_status( $post_statuses, $role_to_return );
				}
				$query->add( $query->do_or( $post_status_conditions ) );
			}

			// Get results and post-process them
			//
			//
			$results = $query->get_results();

			if ( $this->need_found_rows ) {
				$this->found_rows = $query->get_found_rows();
			}

			if ( 'post_object' === $this->what_to_return ) {
				$results = array_map(
					function ( $result ) {
						/** @var \IToolset_Post $result */
						if ( is_array( $result ) ) {
							$objects = array();
							foreach ( $result as $role => $post ) {
								$objects[ $role ] = $post ? $post->get_underlying_object() : null;
							}

							return $objects;
						}

						return $result ? $result->get_underlying_object() : null;
					}, $results
				);
			}

			if ( null !== $this->found_rows ) {
				return array(
					'results' => $results,
					'found_rows' => $this->found_rows,
				);
			}

			return $results;
		} catch ( \Exception $e ) {
			// This is most probably caused by an element not existing, an exception raised from the depth of
			// the association query - otherwise, there are no reasons for it to fail, all the inputs should be valid.
			return array();
		}
	}


	/**
	 * Determine the set of post statuses to query by.
	 *
	 * Thanks to Cliff!
	 *
	 * @return string[] Post statuses. Empty array means no querying by post status at all.
	 */
	private function get_post_statuses_to_query_by() {
		// get_post_stati() would get everything, including WooCommerce's and all other custom statuses,
		// but it also includes future, trash, auto-draft, inherit, etc... so, instead, just use get_post_statuses(),
		// append Woo's, and then make this filterable
		$accepted_post_statuses = get_post_statuses();

		$accepted_post_statuses = array_keys( $accepted_post_statuses );

		/**
		 * toolset_accepted_post_statuses
		 *
		 * Filter an array of post statuses that can be used in Toolset API functions.
		 * At the moment, this involves only functions for retrieving related posts.
		 *
		 * Note: This is also used by us to include WooCommerce post statuses
		 *
		 * @param string[] $accepted_post_statuses
		 *
		 * @since 3.2
		 */
		$accepted_post_statuses = apply_filters( 'toolset_accepted_post_statuses_for_api', $accepted_post_statuses );

		$post_statuses = toolset_getarr( $this->extra_arguments, 'post_status' );

		if ( is_string( $post_statuses ) ) {
			$post_statuses = trim( $post_statuses );

			if ( '' === $post_statuses ) {
				// this part avoids array( 0 => '' ), which is not an empty array for such a check later
				$post_statuses = array();
			} else {
				// added this for extra flexibility
				$post_statuses = explode( ',', $post_statuses );
			}
		}

		$filtered_post_statuses = array_filter(
			toolset_ensarr( $post_statuses ),
			function ( $post_status ) use ( $accepted_post_statuses ) {
				return in_array( $post_status, $accepted_post_statuses );
			}
		);

		// If no post statuses have been provided, do not filter by a post status
		if (
			empty( $filtered_post_statuses )
			// checking this too because maybe user passed a status that was not acceptable, in which case
			// we should not be defaulting to using all stati, as that would be unexpected
			&& empty( $post_statuses )
		) {
			return array(); // this means no post status query will be performed
		}

		return $filtered_post_statuses;
	}

}
