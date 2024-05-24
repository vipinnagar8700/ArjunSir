<?php

namespace OTGS\Toolset\Common\Field\Group\TemplateFilter;

/**
 * Represents an object used for deciding whether a post has a certain template assigned to it.
 * Multiple types of templates can be supported (initial implementation covers native page templates and
 * Content Templates).
 *
 * Specifically, this is being used when determining what field groups should be displayed for a particular post
 * in Toolset_Field_Group_Post::get_groups_for_element().
 *
 * @since Types 3.3
 */
interface TemplateFilterInterface {

	/**
	 * @param \IToolset_Post $post
	 *
	 * @return bool True if the template matches given post.
	 */
	public function is_match_for_post( \IToolset_Post $post );


	/**
	 * Determine if the template is default for a given post type.
	 *
	 * This can be difficult to determine, so false negatives are allowed.
	 * But if a positive result is returned, it must be certain.
	 *
	 * @param string $post_type_slug
	 * @return bool
	 * @since Types 3.3.4
	 */
	public function is_default_for_post_type( $post_type_slug );

}
