<?php

namespace OTGS\Toolset\Types\Wordpress;

use function add_filter;
use function remove_filter;

/**
 * Gateway to WordPress action and filter functions.
 *
 * Extend at will, but keep it very simple.
 *
 * @codeCoverageIgnore
 * @since 3.4.2
 */
class Hooks {

	/**
	 * @param string $tag
	 * @param callable $function_to_add
	 * @param int $priority
	 * @param int $accepted_args
	 *
	 * @return bool|mixed|true|void
	 * @see \add_filter()
	 */
	public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}


	/**
	 * @param string $tag
	 * @param callable $function_to_remove
	 * @param int $priority
	 *
	 * @return bool|mixed
	 * @see \remove_filter()
	 */
	public function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
		return remove_filter( $tag, $function_to_remove, $priority );
	}


	/**
	 * @see \add_action()
	 * @param string $tag
	 * @param callable $function_to_add
	 * @param int $priority
	 * @param int $accepted_args
	 *
	 * @return bool|true
	 */
	public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_action( $tag, $function_to_add, $priority, $accepted_args );
	}
}
