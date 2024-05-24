<?php

/**
 * Helper class for a very easy interaction with the post type API.
 *
 * Useful for legacy code where we often don't have more than a post type slug.
 *
 * @since m2m
 */
class Types_Post_Type_Helper {

	/**
	 * @var null|string
	 */
	private $slug;


	/**
	 * @var null|IToolset_Post_Type
	 */
	private $post_type_object;


	public function __construct( $post_type_input ) {
		if( is_string( $post_type_input ) ) {
			$this->slug = $post_type_input;
		} elseif( is_array( $post_type_input ) && array_key_exists( 'slug', $post_type_input ) ) {
			$this->slug = $post_type_input['slug'];
		}
	}


	private function post_type() {
		if( null === $this->slug ) {
			return null;
		}

		if( null === $this->post_type_object ) {
			$post_type_repository = Toolset_Post_Type_Repository::get_instance();
			$this->post_type_object = $post_type_repository->get( $this->slug );
		}

		return $this->post_type_object;
	}


	private function exists() {
		return ( null !== $this->post_type() );
	}


	public function has_special_purpose() {
		return ( $this->exists() && $this->post_type()->has_special_purpose() );
	}


	public function is_intermediary() {
		return ( $this->exists() && $this->post_type()->is_intermediary() );
	}


	public function is_repeating_field_group() {
		return ( $this->exists() && $this->post_type()->is_repeating_field_group() );
	}
}