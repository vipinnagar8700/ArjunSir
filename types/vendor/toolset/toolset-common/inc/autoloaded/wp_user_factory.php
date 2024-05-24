<?php

namespace OTGS\Toolset\Common;

/**
 * Factory for WP_User, to be used in dependency injection.
 *
 * @since 3.4.4
 * @package OTGS\Toolset\Common
 */
class WpUserFactory {

	/**
	 * @param int $user_id
	 *
	 * @return false|\WP_User
	 */
	public function load( $user_id ) {
		return new \WP_User( $user_id );
	}

}
