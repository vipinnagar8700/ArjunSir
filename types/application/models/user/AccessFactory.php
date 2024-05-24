<?php

namespace OTGS\Toolset\Types\User;

/**
 * Factory for the Access class.
 *
 * @since 3.2
 */
class AccessFactory {


	/**
	 * @param \WP_User $user
	 *
	 * @return Access
	 */
	public function create( \WP_User $user ) {
		return new Access( $user );
	}
}
