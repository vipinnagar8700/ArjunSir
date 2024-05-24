<?php

use \OTGS\Toolset\Common\Utils\InMemoryCache;
use \OTGS\Toolset\Common\WpQueryFactory;

use \OTGS\Toolset\Common\WpPostFactory;


/**
 * Abstract factory for field group classes.
 *
 * It ensures that each field group is instantiated only once and it keeps returning that one instance.
 *
 * Note: Cache is indexed by slugs, so if a field group can change it's slug, it is necessary to do
 * an 'wpcf_field_group_renamed' action immediately after renaming.
 *
 * @since 1.9
 */
abstract class Toolset_Field_Group_Factory {

	/**
	 * Cache key:
	 *
	 * @type array Array of field group instances for this post type, indexed by names (post slugs).
	 *
	 * Note that this needs to be cached due to multisite environments.
	 */
	const FIELD_GROUP_MODELS_CACHE_KEY = 'field_group_models';


	/**
	 * Cache key:
	 *
	 * @type WP_Post[][] WP_Post objects representing field groups.
	 * @since Types 3.3
	 *
	 * Note that this needs to be cached due to multisite environments.
	 */
	const GROUP_QUERIES_CACHE_KEY = 'group_queries';


	/** @var WpQueryFactory  */
	protected $wp_query_factory;

	/** @var WpPostFactory  */
	protected $wp_post_factory;

	/** @var InMemoryCache */
	protected $cache;


	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return Toolset_Field_Group_Factory Instance of calling class.
	 */
	public static function get_instance() {
		static $instances = array();
		$called_class = static::class;
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}


	protected function __construct( WpQueryFactory $wp_query_factory = null, WpPostFactory $wp_post_factory = null, InMemoryCache $cache = null ) {
		$this->wp_query_factory = $wp_query_factory ?: new WpQueryFactory();
		$this->wp_post_factory = $wp_post_factory ?: new WpPostFactory();
		$this->cache = $cache ?: InMemoryCache::get_instance();

		add_action( 'wpcf_field_group_renamed', array( $this, 'field_group_renamed' ), 10, 2 );
		add_action( 'wpcf_group_updated', array( $this, 'reset_query_cache' ) );
	}


	/**
	 * For a given field domain, return the appropriate field group factory instance.
	 *
	 * @param string $domain Valid field domain
	 *
	 * @return Toolset_Field_Group_Factory
	 * @since 2.1
	 */
	public static function get_factory_by_domain( $domain ) {
		switch( $domain ) {
			case Toolset_Element_Domain::POSTS:
				return Toolset_Field_Group_Post_Factory::get_instance();
			case Toolset_Element_Domain::USERS:
				return Toolset_Field_Group_User_Factory::get_instance();
			case Toolset_Element_Domain::TERMS:
				return Toolset_Field_Group_Term_Factory::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * @return string Post type that holds information about this field group type.
	 */
	abstract public function get_post_type();


	/**
	 * Get the name of the domain for which this factory is intended.
	 *
	 * @return string
	 */
	abstract public function get_domain();


	/**
	 * @return string Name of the class that represents this field group type (and that will be instantiated). It must
	 * be a child of Toolset_Field_Group.
	 */
	abstract protected function get_field_group_class_name();


	/**
	 * Get a post object that represents a field group.
	 *
	 * @param int|string|WP_Post $field_group Numeric ID of the post, post slug or a post object.
	 *
	 * @param bool $force_query_by_name Useful to query field groups with numbers only title
	 *
	 * @return null|WP_Post Requested post object when the post exists and has correct post type. Null otherwise.
	 */
	final protected function get_post( $field_group, $force_query_by_name = false ) {
		$fg_post = null;

		// when $force_query_by_name is not used, check if a post id is given
		if ( ! $force_query_by_name && ( ctype_digit( $field_group ) || is_int( $field_group ) ) && (int) $field_group > 0 ) {
			// query by post id
			$fg_post = $this->wp_post_factory->load( $field_group );
		} else if ( $fg_post = $this->get_field_group_by_name( $field_group ) ) {
			// field group found
			return $fg_post;
		} else if ( $rfg_post = $this->get_repeatable_field_group_by_name( $field_group ) ) {
			// rfg found
			return $rfg_post;
		} else {
			// object is already given
			$fg_post = $field_group;
		}

		if( $fg_post instanceof WP_Post && $this->get_post_type() === $fg_post->post_type ) {
			return $fg_post;
		}

		return null;
	}

	/**
	 * Get Field Group by name (does not include RFGs)
	 * @param $field_group_name
	 *
	 * @return false|WP_Post
	 */
	private function get_field_group_by_name( $field_group_name ) {
		return $this->_get_group_post_by_name( $field_group_name );
	}

	/**
	 * Get RFG by name
	 * @param $field_group_name
	 *
	 * @return false|WP_Post
	 */
	private function get_repeatable_field_group_by_name( $field_group_name ) {
		return $this->_get_group_post_by_name( $field_group_name, true );
	}

	/**
	 * Returns post for field group or false if no field group is found.
	 * To search for a RFG the second paramaeter must be true
	 *
	 * @param $field_group_name
	 * @param bool $rfg Search for RFG
	 *
	 * @return false|WP_Post
	 */
	private function _get_group_post_by_name( $field_group_name, $rfg = false ) {
		if( is_object( $field_group_name ) ) {
			return false;
		}
		$query_args = array(
			'post_type' => $this->get_post_type(),
			'name' => $field_group_name,
			'posts_per_page' => 1
		);

		if( $rfg ) {
			// rfgs have the post status 'hidden'
			$query_args['post_status'] = 'hidden';
		}

		$posts = $this->get_cache_for_query_args( $query_args );

		if( null === $posts ) {
			$posts = $this->wp_query_factory->create( $query_args )->posts;
			$this->set_cache_for_query_args( $query_args, $posts );
		}

		if( empty( $posts ) ) {
			return false;
		}

		return array_pop( $posts );
	}


	/**
	 * @param string $field_group_name Name of the field group.
	 *
     * @return null|Toolset_Field_Group Field group instance or null if it's not cached.
	 */
	private function get_from_cache( $field_group_name ) {
		$field_groups = toolset_ensarr( $this->cache->get( static::class, self::FIELD_GROUP_MODELS_CACHE_KEY ) );
		return toolset_getarr( $field_groups, $field_group_name, null );
	}


	/**
	 * Save a field group instance to cache.
	 *
	 * @param Toolset_Field_Group $field_group
	 */
	private function save_to_cache( $field_group ) {
		$field_groups = toolset_ensarr( $this->cache->get( static::class, self::FIELD_GROUP_MODELS_CACHE_KEY ) );
		$field_groups[ $field_group->get_slug() ] = $field_group;

		$this->cache->set( $field_groups, static::class, self::FIELD_GROUP_MODELS_CACHE_KEY );
	}


	/**
	 * Remove field group instance from cache.
	 * @param string $field_group_name
	 */
	private function clear_from_cache( $field_group_name ) {
		$field_groups = toolset_ensarr( $this->cache->get( static::class, self::FIELD_GROUP_MODELS_CACHE_KEY ) );
		unset( $field_groups[ $field_group_name ] );
		$this->cache->set( $field_groups, static::class, self::FIELD_GROUP_MODELS_CACHE_KEY );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group_source Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @param bool $force_query_by_name
	 *
	 * @return null|Toolset_Field_Group Field group or null if it can't be loaded.
	 */
	final public function load_field_group( $field_group_source, $force_query_by_name = false ) {
		$post = null;

		// If we didn't get a field group name, we first need to get the post so we can look into the cache.
		if ( ! is_string( $field_group_source ) ) {
			$post = $this->get_post( $field_group_source );
			if( null === $post ) {
				// There is no such post (or has wrong type).
				return null;
			}
			$field_group_name = $post->post_name;
		} else {
			$field_group_name = $field_group_source;
		}

		// Try to get an existing instance.
		$field_group = $this->get_from_cache( $field_group_name );
		if( null !== $field_group ) {
			return $field_group;
		}

		// We might already have the post by now.
		if( null === $post ) {
			$post = $this->get_post( $field_group_source, $force_query_by_name );
		}

		// There is no such post (or has wrong type).
		if( null === $post ) {
			return null;
		}

		// Create new field group instance
		try {
			$class_name = $this->get_field_group_class_name();
			$field_group = new $class_name( $post );
		} catch( Exception $e ) {
			return null;
		}

		$this->save_to_cache( $field_group );

		return $field_group;
	}


	/**
	 * Update cache after a field group is renamed.
	 *
	 * @param string $original_name The old name of the field group.
	 * @param Toolset_Field_Group $field_group The field group involved, with already updated name.
	 */
	public function field_group_renamed( $original_name, $field_group ) {
		if( $field_group->get_post_type() === $this->get_post_type() ) {
			$this->clear_from_cache( $original_name );
			$this->save_to_cache( $field_group );
		}

		$this->reset_query_cache();
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 * @param string $status Only 'draft'|'publish' are expected. Groups with the 'draft' status will appear as deactivated.
	 * @param string|null $purpose Field group purpose. Defaults to PURPOSE_GENERIC. Accepted values depend on the type of the field group.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 *
	 * @since 1.9
	 * @since m2m Added the 'purpose' argument.
	 *
	 * @refactoring ! Make this testable.
	 */
	final public function create_field_group( $name, $title = '', $status = 'draft', $purpose = null ) {

		if( sanitize_title( $name ) !== $name ) {
			return null;
		}

		$title = wp_strip_all_tags( $title );

		$post_id = (int) wp_insert_post(
			array(
				'post_type' => $this->get_post_type(),
				'post_name' => $name,
				'post_title' => empty( $title ) ? $name : $title,
				'post_status' => $status,
			)
		);

		if( 0 === $post_id ) {
			return null;
		}

		// Store the mandatory postmeta, just to be safe. I'm not sure about invariants here.
		update_post_meta( $post_id, Toolset_Field_Group::POSTMETA_FIELD_SLUGS_LIST, '' );

		$this->reset_query_cache();

		$field_group = $this->load_field_group( $post_id );

		if ( ! $field_group ) {
			return null;
		}

		if( null === $purpose ) {
			$purpose = Toolset_Field_Group::PURPOSE_GENERIC;
		}

		$field_group->set_purpose( $purpose );
		$field_group->execute_group_updated_action();

		return $field_group;
	}


	/**
	 * Get field groups based on query arguments.
	 *
	 * @param array $query_args Optional arguments for the WP_Query that will be applied on the underlying posts.
	 *     Post type query is added automatically.
	 *     Additional arguments are allowed.
	 *     - 'types_search': String for extended search. See WPCF_Field_Group::is_match() for details.
	 *     - 'is_active' bool: If defined, only active/inactive field groups will be returned.
	 *     - 'purpose' string: See Toolset_Field_Group::get_purpose(). Default is Toolset_Field_Group::PURPOSE_GENERIC.
	 *        Special value '*' will return groups of all purposes.
	 *     - 'assigned_to_post_type' string: For post field groups only, filter results by being assinged to a particular post type.
	 *
	 * @return Toolset_Field_Group[]
	 * @since 1.9
	 * @since m2m Added the 'purpose' argument.
	 * @refactoring ! Make the code testable.
	 */
	public function query_groups( $query_args = array() ) {

		// Read specific arguments
		$search_string = toolset_getarr( $query_args, 'types_search' );
		$is_active = toolset_getarr( $query_args, 'is_active', null );
		$purpose = toolset_getarr( $query_args, 'purpose', Toolset_Field_Group::PURPOSE_GENERIC );
		$assigned_to_post_type = toolset_getarr( $query_args, 'assigned_to_post_type', null );

		// Query posts
		$query_args = array_merge( $query_args, array( 'post_type' => $this->get_post_type(), 'posts_per_page' => -1 ) );
		$meta_query = array();

		// Group's "activeness" is defined by the post status.
		if( null !== $is_active && ! isset( $query_args['post_status'] ) ) {
			unset( $query_args['is_active'] );
			$query_args['post_status'] = ( $is_active ? 'publish' : 'draft' );
		}

		// Group's purpose is stored in a postmeta
		if( '*' !== $purpose || is_array( $purpose ) ) {
			$meta_query[] = array(
				'key' => Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
				'value' => $purpose,
			);

			// Also handle the missing postmeta for generic groups
			if (
				Toolset_Field_Group::PURPOSE_GENERIC === $purpose ||
				( is_array( $purpose ) && in_array( Toolset_Field_Group::PURPOSE_GENERIC, $purpose ) )
			) {
				$meta_query[] = array(
					'key' => Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
					'compare' => 'NOT EXISTS',
					'value' => Toolset_Field_Group::PURPOSE_GENERIC, // we might need any non-empty value for the meta query to work because bug #23268
				);

				$meta_query['relation'] = 'OR';
			}
		}
		unset( $query_args['purpose'] );

		if( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query_args['ignore_sticky_posts'] = true;

		$posts = $this->get_cache_for_query_args( $query_args );

		if( null === $posts ) {
			$posts = $this->wp_query_factory->create( $query_args )->posts;
			$this->set_cache_for_query_args( $query_args, $posts );
		}

		// Transform posts into Toolset_Field_Group
		$all_groups = array();
		foreach( $posts as $post ) {
			$field_group = $this->load_field_group( $post );
			if( null !== $field_group ) {
				$all_groups[] = $field_group;
			}
		}

		// Filter groups by the search string.
		$selected_groups = array();
		if( empty( $search_string ) ) {
			$selected_groups = $all_groups;
		} else {
			/** @var Toolset_Field_Group $group */
			foreach ( $all_groups as $group ) {
				if ( $group->is_match( $search_string ) ) {
					$selected_groups[] = $group;
				}
			}
		}

		// Filter groups by being assigned to a post type
		if ( null !== $assigned_to_post_type && $this->get_domain() === Toolset_Element_Domain::POSTS ) {
			$selected_groups = array_filter(
				$selected_groups,
				static function ( Toolset_Field_Group_Post $field_group ) use ( $assigned_to_post_type ) {
					return $field_group->is_assigned_to_type( $assigned_to_post_type );
				}
			);
		}

		return $selected_groups;
	}


	/**
	 * Get a map of all field group slugs to their display names.
	 *
	 * @return string[]
	 * @since 2.0
	 */
	public function get_group_slug_to_displayname_map() {
		$groups = $this->query_groups( array( 'purpose' => '*' ) );
		$group_names = array();
		foreach( $groups as $group ) {
			$group_names[ $group->get_slug() ] = $group->get_display_name();
		}
		return $group_names;
	}


	/**
	 * Retrieve groups that should be displayed with a certain element, taking all possible conditions into account.
	 *
	 * @param IToolset_Element $element Element of the domain matching the field group.
	 * @return Toolset_Field_Group[]
	 * @throws InvalidArgumentException On invalid input (e.g. if the element's domain doesn't match the factory domain).
	 * @since Types 3.3
	 */
	abstract public function get_groups_for_element( IToolset_Element $element );


	/**
	 * Recursively sort a multidimensional associative array by its keys.
	 *
	 * @param array &$array_to_sort Array to be sorted.
	 * @since Types 3.3
	 */
	private function recursive_ksort( & $array_to_sort ) {
		foreach( $array_to_sort as $key => $value ) {
			if( is_array( $value ) ) {
				$this->recursive_ksort( $value );
				$array_to_sort[ $key ] = $value;
			}
		}
		ksort( $array_to_sort );
	}


	/**
	 * Convert an array with query arguments to a cache key.
	 *
	 * @param array $query_args
	 * @return string
	 * @since Types 3.3
	 */
	private function query_args_to_cache_key( $query_args ) {
		$this->recursive_ksort( $query_args );
		return md5( json_encode( $query_args ) );
	}


	/**
	 * @param array $query_args
	 *
	 * @return WP_Post[]|null
	 * @since Types 3.3
	 */
	private function get_cache_for_query_args( $query_args ) {
		$query_cache = toolset_ensarr( $this->cache->get( static::class, self::GROUP_QUERIES_CACHE_KEY ) );

		$cache_key = $this->query_args_to_cache_key( $query_args );
		if( ! array_key_exists( $cache_key, $query_cache ) ) {
			return null;
		}

		return $query_cache[ $cache_key ];
	}


	/**
	 * @param array $query_args
	 * @param WP_Post[] $value
	 * @since Types 3.3
	 */
	private function set_cache_for_query_args( $query_args, $value ) {
		$cache_key = $this->query_args_to_cache_key( $query_args );

		$query_cache = toolset_ensarr( $this->cache->get( static::class, self::GROUP_QUERIES_CACHE_KEY ) );
		$query_cache[ $cache_key ] = $value;

		$this->cache->set( $query_cache, static::class, self::GROUP_QUERIES_CACHE_KEY );
	}


	/**
	 * Clear the query cache. This must be called after every field group change that isn't immediately followed by a
	 * page reload.
	 *
	 * @since Types 3.3
	 */
	public function reset_query_cache() {
		$this->cache->clear( static::class, self::GROUP_QUERIES_CACHE_KEY );
	}

}
