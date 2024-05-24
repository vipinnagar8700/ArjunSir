<?php

namespace OTGS\Toolset\Common\Relationships\UserPermissions;

/**
 * Factory for instantiating relationship user permissions.
 *
 * @since Types 3.4.2
 */
class Factory {

	/**
	 * @param string[] ...$post_types Array of post type slugs.
	 *
	 * @return PermissionService
	 *
	 * @throws \InvalidArgumentException Thrown in case any of the post types isn't a string.
	 */
	public function create( ...$post_types ) {
		return new PermissionService( ...$post_types );
	}

}
