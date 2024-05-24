<?php

/**
 * @since 2.3
 */
class Types_Field_Group_Repeatable extends Types_Field_Group_Post {

	const PREFIX = '_repeatable_group_';

	const OPTION_NAME_LINKED_POST_TYPE = '_types_repeatable_field_group_post_type';

	/**
	 * Array fields
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * The post type of the group (not the post type the group is assigned to by the user)
	 *
	 * @var IToolset_Post_Type
	 */
	protected $post_type;

	/**
	 * The posts of the group. Example: Having Countries as RFG, each Country added to the group is a post.
	 *
	 * @var Types_Field_Group_Repeatable_Item[]
	 */
	protected $posts;

	/**
	 * The sortorder of all posts.
	 *
	 * @var array
	 */
	protected $posts_sortorder = array();

	/**
	 * Holds the length of $posts_sortorder after the last sorting process
	 * This way we can detect if re-sorting is required or not
	 *
	 * @var int
	 */
	protected $posts_sortorder_length_after_last_sorting = 0;


	/**
	 * This should ALWAYS be used to get the link name of a group in field group list (_wp_types_group_fields)
	 *
	 * @return string
	 */
	public function get_id_with_prefix() {
		return self::PREFIX . $this->get_id();
	}


	/**
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 *
	 * @return false|IToolset_Post_Type
	 */
	public function get_post_type_model( Toolset_Post_Type_Repository $post_type_repository = null ) {
		if ( $this->post_type === null ) {
			// seems odd having a "default" here, as we're not using an interface,
			// but was required to get rid of hard coded dependency for testing
			$post_type_repository = $post_type_repository ?: Toolset_Post_Type_Repository::get_instance();

			$this->post_type = $this->fetch_post_type( $post_type_repository );
		}

		return $this->post_type;
	}


	/**
	 * Set post type for group
	 *
	 * @param IToolset_Post_Type $post_type
	 */
	public function set_post_type( IToolset_Post_Type $post_type ) {
		$postmeta = get_post_meta( $this->get_id(), self::OPTION_NAME_LINKED_POST_TYPE, true );
		if ( $postmeta !== $post_type->get_slug() ) {
			update_post_meta( $this->get_id(), self::OPTION_NAME_LINKED_POST_TYPE, $post_type->get_slug() );
		}
		$this->post_type = $post_type;
	}


	/**
	 * @param Types_Field_Group_Repeatable_Item $wp_post
	 * @param int $sortorder
	 */
	public function add_post( Types_Field_Group_Repeatable_Item $wp_post, $sortorder = 9999 ) {
		while ( isset( $this->posts_sortorder[ $sortorder ] ) ) {
			// if there already is a child $sortorder (e.g. is 0) we add an 'a' to it...
			// this way it will be displayed after '0' but before '1'
			// 0
			// 0a
			// 0aa
			// ...
			// 1
			$sortorder .= 'a';
		}
		$this->posts_sortorder[ $sortorder ] = $wp_post->get_wp_post()->ID;
		$this->posts[ $wp_post->get_wp_post()->ID ] = $wp_post;
	}


	/**
	 * Return all posts
	 *
	 * @return Types_Field_Group_Repeatable_Item[]
	 */
	public function get_posts() {
		if ( count( $this->posts_sortorder ) > $this->posts_sortorder_length_after_last_sorting ) {
			// sort posts
			ksort( $this->posts_sortorder );

			$sorted = array();
			foreach ( $this->posts_sortorder as $post_id ) {
				$sorted[ $post_id ] = $this->posts[ $post_id ];
			}

			$this->posts = $sorted;
		}


		return $this->posts;
	}


	/**
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 *
	 * @return bool|IToolset_Post_Type
	 * @deprecated Get rid of this and use a factory/builder for the creation of the instance
	 */
	private function fetch_post_type( Toolset_Post_Type_Repository $post_type_repository ) {
		$post_type_slug = get_post_meta( $this->get_id(), self::OPTION_NAME_LINKED_POST_TYPE, true );

		if ( ! $post_type_slug || empty( $post_type_slug ) ) {
			// no linked post type
			return false;
		}

		if ( $post_type = $post_type_repository->get( $post_type_slug ) ) {
			return $post_type;
		}

		return false;
	}
}
