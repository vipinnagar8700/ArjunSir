<?php

namespace OTGS\Toolset\Types\Controller;

use Types_Page_Custom_Fields;
use Types_Page_Dashboard;
use Types_Page_Extension_Meta_Box_Related_Content;
use Types_Page_Field_Control;

/**
 * The storage of screen options works via the filter 'set-screen-option'. The filter gives the "to_be_stored_value",
 * which is by default false, the option name and the user value. If the applied functions returning a value other than
 * false the option is stored. This design given by WordPress makes the storing of screen options very fragile. Because
 * if a value is returned without checking the option we have a security hole (see types-1801).
 *
 * The filter used by WP: $value = apply_filters( 'set-screen-option', false ($to_be_stored_value), $option, $value );
 *
 * In addition to all this chaos, it's required to apply the callbacks very very early. 'admin_init' is too late, so
 * 'init' is the way to go. To prevent loading all classes on 'init', which may want to add screen_option we use
 * this class to save resources.
 *
 * This class should only be loaded when is_admin() returns true.
 *
 * @since 3.1.2
 */
class ScreenOptions {

	public function __construct() {
		if ( ! is_admin() ) {
			// just for the case someone calls it without is_admin() check.
			return;
		}

		$this->for_toolset_dashboard();
		$this->for_field_groups_page();
		$this->for_field_control_page();
		$this->for_related_content_meta_box();
	}


	/**
	 * Screen options for Toolset -> Dashboard page
	 */
	private function for_toolset_dashboard() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'toolset-dashboard' ) {
			return;
		}

		$dashboard = Types_Page_Dashboard::get_instance();
		add_filter( 'set-screen-option', array( $dashboard, 'screen_settings_save' ), 10, 3 );
	}


	/**
	 * Screen options for all field groups
	 */
	private function for_field_groups_page() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'types-custom-fields' ) {
			return;
		}

		add_filter( 'set-screen-option', array( Types_Page_Custom_Fields::class, 'set_screen_option' ), 10, 3 );
	}


	/**
	 * Screen options for all field control pages (post/user/term fields)
	 */
	private function for_field_control_page() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'types-field-control' ) {
			return;
		}

		$page = Types_Page_Field_Control::get_instance();
		add_filter( 'set-screen-option', array( $page, 'set_screen_option' ), 10, 3 );
	}


	/**
	 * Screen options for the related content table on post edit screens
	 */
	private function for_related_content_meta_box() {
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		add_filter(
			'set-screen-option', array( Types_Page_Extension_Meta_Box_Related_Content::class, 'set_screen_option' ),
			10,
			3
		);
	}

}
