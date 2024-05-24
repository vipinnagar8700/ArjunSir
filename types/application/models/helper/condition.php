<?php

/**
 * Types_Helper_Condition
 *
 * FIXME please document this!
 *
 * @since 2.0
 */
abstract class Types_Helper_Condition {

	public static $post_type;

	/** @var string[] */
	public static $post_type_taxonomies;

	/**
	 * @var Types_Helper_Twig
	 */
	public static $twig;

	protected $condition;

	/**
	 * Set the post type related taxonomies to the current Post Type.
	 *
	 * @param Types_Taxonomy[] $post_type_taxonomies
	 */
	public static function set_post_type_taxonomies( $post_type_taxonomies ) {
		$taxonomies = [];
		foreach ( $post_type_taxonomies as $post_type_taxonomy ) {
			if ( $post_type_taxonomy instanceof Types_Taxonomy ) {
				$taxonomies[] = $post_type_taxonomy->get_name();
			}
		}
		self::$post_type_taxonomies = $taxonomies;
	}

	/**
	 * Returns post type taxonomies.
	 *
	 * @return string[]
	 */
	protected static function get_post_type_taxonomies() {
		return self::$post_type_taxonomies;
	}

	protected static function get_type_name() {
		// per post
		if( isset( $_GET['post'] ) ) {
			$get_type_name_id = (int) $_GET['post'];
			return get_post_type( $get_type_name_id );
		}

		return self::$post_type->name;
	}

	public function set_condition( $value ) {
		$this->condition = $value;
	}

	public function valid() {}

	public static function set_post_type( $posttype = false ) {
		if( ! $posttype ) {
			global $typenow;

			$posttype = isset( $typenow ) && ! empty( $typenow ) ? $typenow : false;
		}

		if( $posttype )
			self::$post_type = get_post_type_object( $posttype );
	}

	public static function get_post_type() {
		if( self::$post_type === null )
			self::set_post_type();

		return self::$post_type;
	}

	public static function get_twig() {
		if( self::$twig === null )
			self::$twig = new Types_Helper_Twig();

		return self::$twig;
	}
}
