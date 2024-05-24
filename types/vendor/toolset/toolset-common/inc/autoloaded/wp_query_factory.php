<?php

namespace OTGS\Toolset\Common;


/**
 * Factory for WP_Query, to be used in dependency injection.
 *
 * @since 3.0.4
 * @package OTGS\Toolset\Common
 */
class WpQueryFactory {

	/**
	 * @param string|array $args Arguments for the WP_Query.
	 *
	 * @return \WP_Query
	 */
	public function create( $args = '') {
		return new \WP_Query( $args );
	}

}