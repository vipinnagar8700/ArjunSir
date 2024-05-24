<?php

/**
 * This controller extends all post edit pages
 *
 * @since 2.0
 */
final class Types_Page_Extension_Edit_Post_Fields {

	private static $instance;

	private $group_id;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		

		$this->group_id = (int) toolset_getget( 'group_id' );

		$field_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
		$group = $field_group_factory->load( $this->group_id );

		if( null === $group ) {
			return;
		}

		if ( $group->has_special_purpose() ) {
			// don't show as group is assigned to intermediary post type or a RFG
			return;
		}

		// Here, empty array means either "all post types" for generic-purpose field groups
		// or "nothing" for special-purpose field group.
		$assigned_post_types = $group->get_assigned_to_types();
		if( count( $assigned_post_types ) !== 1 ) {
			// don't show as group is assigned to more than one post type
			return;
		}

		Types_Helper_Placeholder::set_post_type( $assigned_post_types[0] );
		Types_Helper_Condition::set_post_type( $assigned_post_types[0] );

		$this->prepare();
	}

	private function __clone() { }


	public function prepare() {
		// documentation urls
		Types_Helper_Url::load_documentation_urls();

		// set analytics medium
		Types_Helper_Url::set_medium( 'field_group_editor' );
	}
}