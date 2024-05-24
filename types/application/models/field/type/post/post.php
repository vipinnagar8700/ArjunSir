<?php

/**
 * @since 2.3
 */
class Types_Field_Type_Post extends Types_Field_Abstract {

	/**
	 * The chosen post type for this field
	 *
	 * @var WP_Post_Type
	 */
	private $post_type;

	/**
	 * The selected post
	 *
	 * @var WP_Post
	 */
	private $post;


	/**
	 * @return string
	 */
	public function get_type() {
		return 'post';
	}


	/**
	 * Types_Field_Type_Post constructor.
	 *
	 * @param array $data (see getDefaultProperties() for used keys)
	 *
	 * @throws Exception
	 */
	public function __construct( $data ) {
		// merge user data with default data
		$data = array_merge( $this->get_default_properties(), $data );

		// slug / title / description / value
		parent::__construct( $data );

		if ( isset( $data['post_reference_type'] ) ) {
			$this->set_post_type( $data['post_reference_type'] );
		}
	}


	/**
	 * @return array
	 */
	private function get_default_properties() {
		return array(
			'slug' => null,
			'title' => null,
			'description' => null,
			'value' => null,
		);
	}


	/**
	 * Returns the object of the post type. Null if no post type isset yet.
	 *
	 * @return WP_Post_Type|null
	 */
	public function get_post_type() {
		return $this->post_type;
	}


	/**
	 * Return the post's status if the post is set.
	 *
	 * @return string|null
	 */
	public function get_post_status() {
		if ( ! $this->get_post() ) {
			return null;
		}

		return $this->get_post()->post_status;
	}


	/**
	 * @return WP_Post|false
	 */
	public function get_post() {
		if ( $this->post === null ) {
			$this->post = $this->fetch_post();
		}

		return $this->post;
	}


	/**
	 * @return WP_Post|false
	 */
	private function fetch_post() {
		if ( ! $this->get_post_type() ) {
			return false;
		}

		$value = $this->get_value();
		if ( ! $value || ! is_numeric( $value ) ) {
			return false;
		}

		$post = get_post( (int) $value );
		if ( ! $post ) {
			return false;
		}

		return $post;
	}


	/**
	 * Set field post type with a post type slug
	 *
	 * @param string $post_type_slug
	 *
	 * @throws Exception
	 */
	public function set_post_type( $post_type_slug ) {
		if ( ! $post_type = get_post_type_object( $post_type_slug ) ) {
			throw new RuntimeException( 'No valid post type found for slug ' . $post_type_slug );
		}

		$this->post_type = $post_type;
	}
}
