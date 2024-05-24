<?php


namespace OTGS\Toolset\Common\Utils;


use Toolset_Utils;

/**
 * Simple in-memory cache that is devoid of wp_cache_* quirks and weirdness but still resilient against
 * switching between blogs in a multisite.
 *
 * @since 4.0.4
 */
class InMemoryCache {

	/**
	 * @var mixed[][][] Cached values indexed by:
	 *    1. the blog ID
	 *    2. namespace (unique within Toolset)
	 *    3. key (optional, defaults to '*')
	 */
	private $cache = [];


	/** @var self */
	private static $instance;


	/**
	 * @depecated Use DIC wherever possible instead of relying on get_instance().
	 *
	 * @return InMemoryCache
	 * @codeCoverageIgnore
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Set a value to the cache.
	 *
	 * @param mixed $value The value to be stored, can be anything.
	 * @param string $namespace Namespace identifier unique within Toolset. This can be "static::class", for example.
	 * @param string $key Cache key within the given namespace. Optional.
	 */
	public function set( $value, $namespace, $key = '*' ) {
		Toolset_Utils::set_nested_value( $this->cache, [
			$this->get_current_blog_id(),
			$namespace,
			$key,
		], $value );
	}


	/**
	 * Obtain a value from the cache.
	 *
	 * @param string $namespace
	 * @param string $key
	 *
	 * @return mixed|null Cached value or null if no cache entry exists.
	 */
	public function get( $namespace, $key = '*' ) {
		return toolset_getnest( $this->cache, [
			$this->get_current_blog_id(),
			$namespace,
			$key,
		], null );
	}


	/**
	 * Clear a part of the cache.
	 *
	 * @param string $namespace
	 * @param string|null $key Cache key within the namespace. If not provided, the whole namespace will be cleared.
	 * @param int|null $blog_id ID of the blog. If not provided, the current blog ID will be used.
	 */
	public function clear( $namespace, $key = null, $blog_id = null ) {
		$blog_id = $blog_id ? : $this->get_current_blog_id();

		if ( ! array_key_exists( $blog_id, $this->cache ) ) {
			return;
		}

		if ( ! array_key_exists( $namespace, $this->cache[ $blog_id ] ) ) {
			return;
		}

		if ( null === $key ) {
			unset( $this->cache[ $blog_id ][ $namespace ] );
		}

		unset( $this->cache[ $blog_id ][ $namespace ][ $key ] );
	}


	private function get_current_blog_id() {
		return get_current_blog_id();
	}

}
