<?php

/**
 * Interface Types_Post_Interface
 *
 * @since m2m
 */
interface Types_Post_Interface {
	/**
	 * @return WP_Post
	 */
	public function get_wp_post();

	/**
	 * @return Toolset_Field_Group[]
	 */
	public function get_field_groups();

	/**
	 * @param Types_Field_Group_Post $group
	 */
	public function add_field_group( Types_Field_Group_Post $group );
}
