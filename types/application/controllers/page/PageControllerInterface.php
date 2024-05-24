<?php

namespace OTGS\Toolset\Types\Controller\Page;


/**
 * Interface of an admin page controller.
 *
 * All page controllers must implement this one.
 *
 * @since 2.0
 * @since 3.4 extracted the interface form an abstract class.
 */
interface PageControllerInterface {

	/**
	 * Prepare the page controller for displaying a page.
	 *
	 * This should be the first time where any kind of preparation happens (it shouldn't happen during
	 * instantiating the class).
	 *
	 * By default, this disables the WordPress Heartbeat API in order to save resources and speed up AJAX calls.
	 *
	 * @since 2.0
	 */
	public function prepare();


	/**
	 * Title to be displayed on the menu as well in the page title.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_title();


	/**
	 * Callback for the page rendering action.
	 *
	 * @return callable
	 * @since 2.0
	 */
	public function get_render_callback();


	/**
	 * Callback for the page load action (load-{$hook}).
	 *
	 * @return null|callable Optional.
	 * @since 2.0
	 */
	public function get_load_callback();


	/**
	 * Page name slug.
	 *
	 * Should be taken directly from constants in Types_Admin_Menu.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_page_name();


	/**
	 * User capability required to display the submenu item and access the page.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_required_capability();
}
