<?php

/**
 * Editor class interface.
 *
 * @since 2.5.0
 */

interface Toolset_User_Editors_Editor_Interface {


	/**
	 * Initialize the basic logic for the user editor, even if it is not installed.
	 *
	 * @since 3.4.8
	 */
	public function initialize();


	public function required_plugin_active();
	public function add_screen( $id, Toolset_User_Editors_Editor_Screen_Interface $screen );


	/**
	 * Initialize the basic logic for the editor, if it is installed, after it is registered.
	 *
	 * @since 3.4.8
	 */
	public function after_editor_added();


	public function run();

	/**
	 * @return false|Toolset_User_Editors_Editor_Screen_Interface
	 */
	public function get_screen_by_id( $id );

	public function get_id();
	public function get_name();
	public function get_option_name();
	public function get_logo_class();
}

