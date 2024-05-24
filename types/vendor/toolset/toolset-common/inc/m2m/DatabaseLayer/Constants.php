<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

/**
 * Constants used internally in the DatabaseLayer sub-namespace.
 */
abstract class Constants {

	/**
	 * Delimiter used in GROUP_CONCAT MySQL function.
	 */
	const GROUP_CONCAT_DELIMITER = ',';

	/**
	 * Filter that can be used to indicate that an intermediary post is deleted
	 * purposefully, and that the association shouldn't be removed.
	 *
	 * @since 2.6.8
	 */
	const IS_DELETING_INTERMEDIARY_POST_FILTER = 'toolset_is_deleting_intermediary_post_purposefully';

	const DELETE_POSTS_PER_BATCH = 25;


}
