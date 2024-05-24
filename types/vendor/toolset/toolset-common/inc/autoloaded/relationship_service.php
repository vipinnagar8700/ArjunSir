<?php

use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;

/**
 * Class Toolset_Relationship_Service
 *
 * Most provided services here require m2m and are useless if "toolset_is_m2m_enabled" is false.
 *
 * @since 2.5.2
 */
class Toolset_Relationship_Service {

	const CACHE_GROUP_KEY = 'toolset_rel_serv';

	/**
	 * @var bool
	 */
	private $m2m_enabled;

	/** @var array */
	private $cache_keys_to_clear = array();

	public function __construct() {
		add_action( 'shutdown', array( $this, 'clear_non_persistent_cache' ) );
	}

	/**
	 * @return bool
	 */
	private function is_m2m_enabled() {
		if ( $this->m2m_enabled === null ) {
			$this->m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );

			if ( $this->m2m_enabled ) {
				do_action( 'toolset_do_m2m_full_init' );
			}
		}

		return $this->m2m_enabled;
	}

	/**
	 * @param false|int[] $results
	 * @return false|int[]
	 */
	private function filter_by_status( $results ) {
		if ( ! $results || empty( $results ) ) {
			return $results;
		}

		$results_count = count( $results );

		if ( $results_count < 5 ) {
			return $this->filter_short_by_status( $results );
		}

		return $this->filter_large_by_status( $results );
	}

	/**
	 * Only for queries with a low limit.
	 *
	 * @param int[] $results
	 * @return int[]
	 */
	private function filter_short_by_status( $results ) {
		$results = array_filter( $results, function( $item ) {
			$status = get_post_status( $item );
			if ( false === $status ) {
				return false;
			}
			return in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true );
		} );

		return $results;
	}

	/**
	 * Only for queries with a high limit.
	 *
	 * @param int[] $results
	 * @return int[]
	 */
	private function filter_large_by_status( $results ) {
		// Sanitize values to ensure INT
		// Build query to get IDs and status, or just IDs taking out the undesired status.
		// Return results.
		$results = array_map( 'esc_attr', $results );
		$results = array_map( 'trim', $results );
		// is_numeric does sanitization
		$results = array_filter( $results, 'is_numeric' );
		$results = array_map( 'intval', $results );

		if ( 0 === count( $results ) ) {
			return array();
		}

		global $wpdb;

		$sql_statement = "SELECT ID, post_status FROM {$wpdb->posts} WHERE ID IN ( '" . implode("','", $results) . "' ) LIMIT %d";

		$cached_filtered_results = wp_cache_get( md5( $sql_statement ), 'toolset_rel_status_filter' );

		if ( false !== $cached_filtered_results ) {
			return $cached_filtered_results;
		}

		$filtered_results = array();
		$results_count = count( $results );
		$results_with_status = $wpdb->get_results(
			$wpdb->prepare(
				$sql_statement,
				$results_count
			)
		);
		foreach ( $results_with_status as $result_and_status ) {
			if ( in_array( $result_and_status->post_status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
				$filtered_results[] = $result_and_status->ID;
			}
		}

		wp_cache_set( md5( $sql_statement ), $filtered_results, 'toolset_rel_status_filter' );

		return $filtered_results;
	}

	/**
	 * @param $string
	 *
	 * @return false|IToolset_Relationship_Definition
	 */
	public function find_by_string( $string ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		\OTGS\Toolset\Common\Relationships\MainController::get_instance()->initialize();
		$factory = Toolset_Relationship_Definition_Repository::get_instance();

		if ( $relationship = $factory->get_definition( $string ) ) {
			return $relationship;
		}

		return false;
	}

	/**
	 * Function to find parend id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $child_id
	 * @param null $parent_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 */
	public function find_parent_id_by_relationship_and_child_id(
		IToolset_Relationship_Definition $relationship,
		$child_id,
		$parent_slug = null
	) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Pid_x_Cid_Rel';
		$cache_subkey = $child_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		if ( $parent_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $parent_slug, new Toolset_Relationship_Role_Parent() ) );
		}

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * Function to find parent ID by relationship and intermediary post ID
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $intermediary_post_id
	 * @param null $parent_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 * @since 2.6.7
	 */
	public function find_parent_id_by_relationship_and_intermediary_post_id(
		IToolset_Relationship_Definition $relationship,
		$intermediary_post_id,
		$parent_slug = null
	) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Pid_x_Iid_Rel';
		$cache_subkey = $intermediary_post_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->intermediary_id( $intermediary_post_id ) );

		if ( $parent_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $parent_slug, new Toolset_Relationship_Role_Parent() ) );
		}

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}


	/**
	 * Function to find parend id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $parent_id
	 * @param null $child_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 */
	public function find_child_id_by_relationship_and_parent_id(
		IToolset_Relationship_Definition $relationship,
		$parent_id,
		$child_slug = null
	) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Cid_x_Pid_Rel';
		$cache_subkey = $parent_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		if ( $child_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $child_slug, new Toolset_Relationship_Role_Child() ) );
		}

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * Function to find intermediary post id by relationship and child id.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $child_id
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 * @deprecated Use find_intermediary_id_by_relationship_and_child_id instead.
	 */
	public function find_intermediary_by_relationship_and_child_id( IToolset_Relationship_Definition $relationship, $child_id ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		$results = $query
			->return_association_instances()
			->limit( 1 )
			->get_results();

		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find intermediary post id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $child_id
	 *
	 * @return bool|int[]
	 */
	public function find_intermediary_id_by_relationship_and_child_id( IToolset_Relationship_Definition $relationship, $child_id ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Iid_x_Cid_Rel';
		$cache_subkey = $child_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Intermediary() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * Function to find Child ID by relationship and intermediary post ID
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $intermediary_post_id
	 * @param null $child_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 * @since 2.6.7
	 */
	public function find_child_id_by_relationship_and_intermediary_post_id(
		IToolset_Relationship_Definition $relationship,
		$intermediary_post_id,
		$child_slug = null
	) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Cid_x_Iid_Rel';
		$cache_subkey = $intermediary_post_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->intermediary_id( $intermediary_post_id ) );

		if ( $child_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $child_slug, new Toolset_Relationship_Role_Child() ) );
		}

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * Function to find intermediary post id by relationship and parent id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $parent_id
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 * @deprecated Use find_intermediary_id_by_relationship_and_child_id instead.
	 */
	public function find_intermediary_by_relationship_and_parent_id( IToolset_Relationship_Definition $relationship, $parent_id ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		$results = $query
			->return_association_instances()
			->limit( 1 )
			->get_results();

		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find intermediary post id by relationship and parent id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $parent_id
	 *
	 * @return bool|int[]
	 */
	public function find_intermediary_id_by_relationship_and_parent_id( IToolset_Relationship_Definition $relationship, $parent_id ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Iid_x_Pid_Rel';
		$cache_subkey = $parent_id . '@' . $relationship->get_slug();
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new \Toolset_Relationship_Role_Intermediary() )
			->limit( 1 )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		// TODO we do not need the instance but the ID in the cache
		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * @param $qry_args
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 * @deprecated Use the AssociationQuery instead.
	 */
	private function query_association( $qry_args ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		\OTGS\Toolset\Common\Relationships\MainController::get_instance()->initialize();
		$query   = new Toolset_Association_Query( $qry_args );
		$results = $query->get_results();

		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * @param $parent_id
	 * @param array $children_args
	 *
	 * @return bool|int[]
	 * @internal param string $child_slug
	 * @deprecated This method doesn't scale, avoid it.
	 */
	public function find_children_ids_by_parent_id( $parent_id, $children_args = array() ) {
		if ( ! $this->is_m2m_enabled() ) {
			return false;
		}

		$cache_key = 'Cid_x_Pid';
		$cache_subkey = $parent_id . '#ANY';
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( PHP_INT_MAX )
			->get_results();

		$results = $this->filter_by_status( $results );

		if ( ! $results || empty( $results ) ) {
			$cached_group[ $cache_subkey ] = false;
		} else {
			$cached_group[ $cache_subkey ] = $results;
		}

		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * @param $post_id
	 *
	 * @return IToolset_Association[]
	 * @deprecated This method doesn't scale, avoid it.
	 */
	public function find_associations_by_id( $post_id ) {
		if ( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$associations_parent = $this->find_associations_by_parent_id( $post_id );
		$associations_child = $this->find_associations_by_child_id( $post_id );

		return array_merge( $associations_parent, $associations_child );
	}

	/**
	 * Find associations (IToolset_Associations[]) by parent id
	 *
	 * @param $parent_id
	 *
	 * @deprecated This method doesn't scale, avoid it.
	 * @return IToolset_Association[]
	 */
	private function find_associations_by_parent_id( $parent_id ) {
		$query = new Toolset_Association_Query_V2();

		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		$results = $query
			->return_association_instances()
			->limit( PHP_INT_MAX )
			->get_results();

		if ( ! $results || empty( $results ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Find associations (IToolset_Associations[]) by child id
	 *
	 * @param $child_id
	 *
	 * @deprecated This method doesn't scale, avoid it.
	 * @return IToolset_Association[]
	 */
	private function find_associations_by_child_id( $child_id, $limit = 2000 ) {
		$query = new Toolset_Association_Query_V2();

		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		$results = $query
			->return_association_instances()
			->limit( $limit ?: PHP_INT_MAX )
			->get_results();

		if ( ! $results || empty( $results ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Function to find parents (Toolset_Element[]) by child id and parent slug.
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return Toolset_Element[]
	 * @deprecated This method doesn't scale, avoid it.
	 */
	public function find_parents_by_child_id_and_parent_slug( $child_id, $parent_slug, $limit = 2000 ) {
		if ( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$associations = $this->find_associations_by_child_id( $child_id, $limit );
		$associations_matched = array();

		foreach( $associations as $association ) {
			$parent = $association->get_element( new Toolset_Relationship_Role_Parent() );
			if ( null === $parent ) {
				continue;
			}
			$parent_underlying_obj = $parent->get_underlying_object();

			if ( ! property_exists( $parent_underlying_obj, 'post_type' ) ) {
				// only post elements supported
				continue;
			}

			if ( $parent_underlying_obj->post_type == $parent_slug ) {
				$associations_matched[] = $parent;
			}
		}

		return $associations_matched;
	}

	/**
	 * Function to find parents (Toolset_Element[]) by child id and parent slug, knowing they relate on a legacy relationship.
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return Toolset_Element[]
	 * @deprecated Use find_legacy_parent_id_by_child_id_and_parent_slug instead.
	 */
	public function find_legacy_parents_by_child_id_and_parent_slug( $child_id, $parent_slug ) {
		if ( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$child_type = get_post_type( $child_id );

		$relationship_query = new Toolset_Relationship_Query_V2();
		$relationship_query->do_not_add_default_conditions();
		$conditions = array();
		$conditions[] = $relationship_query->has_domain_and_type( $parent_slug, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent() );
		$conditions[] = $relationship_query->has_domain_and_type( $child_type, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() );
		$definitions = $relationship_query->add(
			$relationship_query->do_and(
				$relationship_query->is_legacy( true ),
				$relationship_query->do_and( $conditions )
			)
		)->get_results();

		if ( empty( $definitions ) ) {
			return array();
		}
		$relationship = reset( $definitions );

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		$associations = $query
			->return_association_instances()
			->limit( 2 )
			->get_results();

		if ( ! $associations || empty( $associations ) ) {
			return array();
		}

		$associations_matched = array();

		foreach( $associations as $association ) {
			$association_element = $association->get_element( new Toolset_Relationship_Role_Parent() );
			if ( null !== $association_element ) {
				$associations_matched[] = $association_element;
			}
		}

		return $associations_matched;
	}

	/**
	 * Function to find parents (int[]) by child id and parent slug, knowing they relate on a legacy relationship.
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return int[]
	 */
	public function find_legacy_parent_id_by_child_id_and_parent_slug( $child_id, $parent_slug ) {
		if ( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$cache_key = 'LPid_x_Cid_Pslug';
		$cache_subkey = $child_id . '#' . $parent_slug;
		$this->cache_keys_to_clear[] = $cache_key;

		$cached_group = wp_cache_get( $cache_key, self::CACHE_GROUP_KEY );

		if (
			is_array( $cached_group )
			&& array_key_exists( $cache_subkey, $cached_group )
		) {
			return $cached_group[ $cache_subkey ];
		}

		if ( false === $cached_group ) {
			$cached_group = array();
		}

		$child_type = get_post_type( $child_id );

		$relationship_cache_key = 'LRel_x_P_C';
		$relationship_cache_subkey = $parent_slug . '#' . $child_type;
		$this->cache_keys_to_clear[] = $relationship_cache_key;

		$cached_list = wp_cache_get( $relationship_cache_key, self::CACHE_GROUP_KEY );
		if ( false === $cached_list ) {
			$cached_list = array();
		}

		$relationship = $this->get_legacy_relationship_between_post_types( $parent_slug, $child_type, $cached_list, $relationship_cache_subkey );

		if ( ! $relationship instanceof Toolset_Relationship_Definition ) {
			$cached_group[ $cache_subkey ] = array();
			wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
			$cached_list[ $relationship_cache_subkey ] = false;
			wp_cache_set( $relationship_cache_key, $cached_list, self::CACHE_GROUP_KEY );
			return $cached_group[ $cache_subkey ];
		}

		$cached_list[ $relationship_cache_subkey ] = $relationship->get_slug();
		wp_cache_set( $relationship_cache_key, $cached_list, self::CACHE_GROUP_KEY );

		$query = new Toolset_Association_Query_V2();

		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );
		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$parent = $query
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 2 )
			->get_results();

		$parent = $this->filter_by_status( $parent );

		if ( ! $parent || empty( $parent ) ) {
			$cached_group[ $cache_subkey ] = array();
			wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
			return $cached_group[ $cache_subkey ];
		}

		$cached_group[ $cache_subkey ] = $parent;
		wp_cache_set( $cache_key, $cached_group, self::CACHE_GROUP_KEY );
		return $cached_group[ $cache_subkey ];
	}

	/**
	 * Calculate the legacy o2m relationship between two post types.
	 * Consider that we might have cached its slug already.
	 *
	 * @param string $parent_type
	 * @param string $child_type
	 * @param string[] $available_cache List of cached relationships per post type pairs..
	 * @param string $cache_entry_key Key maybe holding the relationship slug cached for the post type pair.
	 * @return null|Toolset_Relationship_Definition
	 */
	private function get_legacy_relationship_between_post_types( $parent_type, $child_type, $available_cache, $cache_entry_key ) {
		if ( array_key_exists( $cache_entry_key, $available_cache ) ) {
			$relationship_slug = $available_cache[ $cache_entry_key ];
			$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
			$relationship = $relationship_repository->get_definition( $relationship_slug );

			// Note that this might be null
			return $relationship;
		}

		$relationship_query = new Toolset_Relationship_Query_V2();
		$relationship_query->do_not_add_default_conditions();
		$conditions = array();
		$conditions[] = $relationship_query->has_domain_and_type( $parent_type, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent() );
		$conditions[] = $relationship_query->has_domain_and_type( $child_type, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() );
		$definitions = $relationship_query->add(
			$relationship_query->do_and(
				$relationship_query->is_legacy( true ),
				$relationship_query->do_and( $conditions )
			)
		)->get_results();

		if ( empty( $definitions ) ) {
			return null;
		}

		$relationship = reset( $definitions );

		return $relationship;
	}

	/**
	 * Function uses legacy structure to find parent id by child id and parent slug.
	 * NOTE: always check "m2m" relationship table before you try to find a legacy relationship
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return bool|int
	 */
	public function legacy_find_parent_id_by_child_id_and_parent_slug( $child_id, $parent_slug ) {
		if ( $this->is_m2m_enabled() ) {
			return false;
		}

		$parent_slug = sanitize_title( $parent_slug );

		$option_key = '_wpcf_belongs_' . $parent_slug . '_id';

		return get_post_meta( $child_id, $option_key, false );
	}

	/**
	 * Clear the non persistent cache in case it is indeed persistent by a caching plugin.
	 */
	public function clear_non_persistent_cache() {
		if ( ! is_array( $this->cache_keys_to_clear ) ) {
			$this->cache_keys_to_clear = array();
			return;
		}

		foreach ( $this->cache_keys_to_clear as $cache_key ) {
			wp_cache_delete( $cache_key, self::CACHE_GROUP_KEY );
		}

		$this->cache_keys_to_clear = array();
	}
}
