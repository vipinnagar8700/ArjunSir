<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta;

use OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\InvalidatorBase;

/**
 * Abstract base for the invalidator controllers.
 *
 * @since 3.3.6
 */
class Invalidator extends InvalidatorBase {

	/**
	 * Get the key for the transient cache.
	 *
	 * @since 3.3.6
	 */
	protected function get_transient_key() {
		return \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta::TRANSIENT_KEY;
	}

	/**
	 * Get the post type for the items acting as field groups.
	 *
	 * @since 3.3.6
	 */
	protected function get_meta_post_type() {
		return \Toolset_Field_Group_User::POST_TYPE;
	}

}
