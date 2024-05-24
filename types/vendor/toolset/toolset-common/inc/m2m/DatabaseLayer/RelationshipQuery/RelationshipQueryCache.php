<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\RelationshipQuery;

use OTGS\Toolset\Common\Utils\InMemoryCache;

/**
 * Cache for relationship query results. Handle as a singleton.
 *
 * @since Types 3.4.7
 */
class RelationshipQueryCache {


	/** @var RelationshipQueryCache */
	private static $instance;


	/** @var InMemoryCache */
	private $in_memory_cache;


	/**
	 * @return RelationshipQueryCache
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self( InMemoryCache::get_instance() );
			self::$instance->initialize();
		}

		return self::$instance;
	}


	/**
	 * RelationshipQueryCache constructor.
	 *
	 * @param InMemoryCache $in_memory_cache
	 */
	public function __construct( InMemoryCache $in_memory_cache ) {
		$this->in_memory_cache = $in_memory_cache;
	}


	/**
	 * Initialize the cache, add invalidation hooks.
	 */
	public function initialize() {
		add_action( 'toolset_relationship_updated', array( $this, 'flush' ) );
		add_action( 'toolset_relationship_created', array( $this, 'flush' ) );
		add_action( 'toolset_relationship_deleted', array( $this, 'flush' ) );
	}


	/**
	 * @param string $key
	 * @param CachedQueryResult $value
	 */
	public function push( $key, CachedQueryResult $value ) {
		$this->in_memory_cache->set( $value, static::class, $key );
	}


	/**
	 * @param string $key
	 *
	 * @return CachedQueryResult|null
	 */
	public function get( $key ) {
		return $this->in_memory_cache->get( static::class, $key );
	}


	/**
	 * Delete all used cache records.
	 */
	public function flush() {
		$this->in_memory_cache->clear( static::class );
	}

}
