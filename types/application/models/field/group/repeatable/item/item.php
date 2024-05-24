<?php

/**
 * Class Types_Field_Group_Repeatable_Item
 *
 * @since m2m
 */
class Types_Field_Group_Repeatable_Item extends Types_Post {
	/**
	 * @var Types_Field_Abstract[]
	 */
	private $fields;

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
	 * @param Types_Field_Group_Post $group
	 *
	 * @return bool
	 */
	public function add_field_group( Types_Field_Group_Post $group ) {
		if( ! $group instanceof Types_Field_Group_Repeatable ) {
			// this post is an item of an repeatable group, so only nested repeatable groups can be added
			return false;
		}

		$this->field_groups[ $group->get_wp_post()->post_name ] = $group;
		return true;
	}
}
