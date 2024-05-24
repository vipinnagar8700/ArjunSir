<?php

/**
 * Class Types_Field_Group_Mapper_Legacy
 *
 * @since m2m
 */
class Types_Field_Group_Mapper_Legacy implements Types_Field_Group_Mapper_Interface {

	public function __construct() {
		// load requirements
		require_once( WPCF_EMBEDDED_ABSPATH . '/includes/fields-post.php' );
		do_action( 'toolset_do_m2m_full_init' );
	}


	/**
	 * @param WP_Post $post
	 *
	 * @param int $depth Controls how deep nested rfgs should be loaded.
	 *                   Be careful with this. Loading nested groups can involve lots of posts.
	 *
	 * @return Types_Field_Group_Post[]
	 */
	public function find_by_post( WP_Post $post, $depth = 1 ) {
		$depth = (int) $depth;

		$types_field_service = Types_Field_Service_Store::get_instance()->get_service( false );
		$types_repeatable_group_service = new Types_Field_Group_Repeatable_Service();
		$types_field_gateway = new Types_Field_Gateway_Wordpress_Post();

		// collection of all field groups related to the post
		$field_groups = array();

		// get "usual" field groups
		$field_groups_raw = wpcf_admin_post_get_post_groups_fields( $post );

		foreach ( $field_groups_raw as $group_raw ) {
			if ( ! $post_of_field_group = get_post( $group_raw['id'] ) ) {
				continue;
			}

			$types_field_group = new Types_Field_Group_Post( $post_of_field_group );

			foreach ( $group_raw['fields'] as $field_slug => $field_data ) {
				$repeatable_group = $types_repeatable_group_service->get_object_from_prefixed_string(
					$field_data, $post, $depth - 1
				);

				if ( $repeatable_group ) {
					$types_field_group->add_repeatable_group( $repeatable_group );
					continue;
				}

				$field = $types_field_service->get_field( $types_field_gateway, $field_slug, $post->ID );
				if ( $field ) {
					$types_field_group->add_field( $field );
				}
			}

			$field_groups[ $types_field_group->get_wp_post()->post_name ] = $types_field_group;
		}

		return $field_groups;
	}
}
