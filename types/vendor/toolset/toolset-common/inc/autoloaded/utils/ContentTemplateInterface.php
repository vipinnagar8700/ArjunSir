<?php

namespace OTGS\Toolset\Common\Utils;

/**
 * Represents a Content Template model coming from Views.
 *
 * That allows us to prevent a hard dependency on Views code in Toolset Common and elsewhere.
 * Extend as needed, but very carefully.
 *
 * @since Types 3.3.4
 */
interface ContentTemplateInterface {

	/**
	 * Get slugs of post types where the content template is assigned as a single post template.
	 *
	 * @return string[]
	 */
	public function get_assigned_single_post_type_slugs();

}
