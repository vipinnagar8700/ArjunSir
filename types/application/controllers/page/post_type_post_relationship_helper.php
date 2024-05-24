<?php

/**
 * WIP: Not used yet.
 */
class Types_Page_Post_Type_Post_Relationship_Helper {

	// Names of GET arguments
	const RELATIONSHIP_SLUG_KEY = 'return_to_relationship';
	const POST_TYPE_SLUG_KEY = 'wpcf-post-type';


	/** @var Types_Admin_Menu */
	private $admin_menu;


	public function __construct( Types_Admin_Menu $admin_menu_di = null ) {
		$this->admin_menu = ( null === $admin_menu_di ? Types_Admin_Menu::get_instance() : $admin_menu_di );
	}


	public function get_url_to_edit_intermediary_post_type( $post_type_slug, $relationship_slug ) {
		$result_url = $this->admin_menu->get_page_url( Types_Admin_Menu::LEGACY_PAGE_EDIT_POST_TYPE );

		if( null !== $post_type_slug ) {
			$result_url = add_query_arg(
				array( self::POST_TYPE_SLUG_KEY => $post_type_slug ),
				$result_url
			);
		}

		return $result_url;
	}

}
