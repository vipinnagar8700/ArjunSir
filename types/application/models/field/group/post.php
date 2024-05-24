<?php

/**
 * Class Types_Field_Group_Post
 *
 * Extends Toolset_Field_Group_Post by nested groups and fields
 * @since m2m
 */
class Types_Field_Group_Post extends Toolset_Field_Group_Post {

	/**
	 * @var Types_Field_Abstract[]
	 */
	private $fields;

	/**
	 * @var Types_Field_Group_Repeatable[]
	 */
	private $repeatable_field_groups;

	/**
	 * Types_Field_Group_Post constructor.
	 *
	 * @param WP_Post $field_group_post
	 */
	public function __construct( WP_Post $field_group_post ) {
		parent::__construct( $field_group_post );
	}

	/**
	 * @return WP_Post
	 */
	public function get_wp_post() {
		return $this->get_post();
	}

	/**
	 * @param Types_Field_Abstract $field
	 */
	public function add_field( Types_Field_Abstract $field ) {
		$this->fields[ $field->get_slug() ] = $field;
	}

	/**
	 * @return Types_Field_Abstract[]
	 */
	public function get_fields(){
		return $this->fields;
	}

	/**
	 * @param Types_Field_Group_Repeatable $group
	 */
	public function add_repeatable_group( Types_Field_Group_Repeatable $group ) {
		$this->repeatable_field_groups[ $group->get_slug() ] = $group;
	}

	/**
	 * @return Types_Field_Group_Repeatable[]
	 */
	public function get_repeatable_groups() {
		return $this->repeatable_field_groups;
	}
}
