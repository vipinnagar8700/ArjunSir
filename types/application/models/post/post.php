<?php

/**
 * Class Types_Post
 *
 * @since m2m
 */
class Types_Post implements Types_Post_Interface {

	/**
	 * @var WP_Post
	 */
	protected $wp_post;

	/**
	 * This can be a collection of "usual" field groups OR (NOT AND) a collection of repeatable field groups
	 * If it is a collection of repeatable groups it means the the post is an item of a repeatable group and
	 * the $field_groups are nested repeatable groups of the item.
	 *
	 * @var Types_Field_Group_Post[]
	 */
	protected $field_groups = array();

	/**
	 * @return WP_Post|null
	 */
	public function get_wp_post() {
		return $this->wp_post;
	}

	/**
	 * @param WP_Post $post
	 */
	public function set_wp_post( WP_Post $post ) {
		$this->wp_post = $post;
	}

	/**
	 * @return Types_Field_Group_Post[]
	 */
	public function get_field_groups() {
		return $this->field_groups;
	}

	/**
	 * @param Types_Field_Group_Post $group
	 */
	public function add_field_group( Types_Field_Group_Post $group ) {
		if( $group instanceof Types_Field_Group_Repeatable ){
			// this post is NOT an item of a repeatable group, means no Repeatable Group can be added to the post
			// because all repeatable groups belong to the field group of the post.
			// $current_post -> Field_Group -> Repeatable Group
			return;
		}

		$this->field_groups[ $group->get_wp_post()->post_name ] = $group;
	}
}
