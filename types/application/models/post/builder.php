<?php

/**
 * @since m2m
 */
class Types_Post_Builder {

	/**
	 * @var Types_Post
	 */
	protected $types_post;


	public function __construct() {
		$this->types_post = new Types_Post();
	}


	public function set_wp_post( WP_Post $wp_post ) {
		$this->types_post->set_wp_post( $wp_post );
	}


	/**
	 * @param int $depth
	 * @param Types_Field_Group_Mapper_Interface|null $mapper_field_group
	 */
	public function load_assigned_field_groups( $depth = 1, Types_Field_Group_Mapper_Interface $mapper_field_group = null ) {
		if ( $this->types_post->get_wp_post() === null ) {
			throw new RuntimeException( 'You need to set_wp_post( WP_Post $post ) before you can load assigned field groups.' );
		}

		// default mapper
		$mapper_field_group = $mapper_field_group ? : new Types_Field_Group_Mapper_Legacy();

		$field_groups = $mapper_field_group->find_by_post( $this->types_post->get_wp_post(), $depth );

		foreach ( $field_groups as $field_group ) {
			if ( ! $field_group instanceof Types_Field_Group_Post ) {
				continue;
			}

			$this->types_post->add_field_group( $field_group );
		}
	}


	/**
	 * @return Types_Post
	 */
	public function get_types_post() {
		return $this->types_post;
	}
}
