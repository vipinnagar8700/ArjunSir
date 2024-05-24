<?php


namespace OTGS\Toolset\Types\Model\Wordpress;

use OTGS\Toolset\Types\ScreenId;

/**
 * Wrapper for WordPress core \WP_Screen-related functionality.
 *
 * @since 3.4
 */
class Screen {

	/**
	 * @return \WP_Screen|null
	 */
	public function get_current_screen() {
		return get_current_screen();
	}


	/**
	 * Return the title of the current admin page.
	 *
	 * Note that this is an approximation: We cannot really determine the value printed in the H1 tag of the admin page
	 * from here. But we can extract the page name from the admin menu, which in most cases will be enough.
	 *
	 * @return string
	 */
	public function get_admin_page_title() {
		$screen = $this->get_current_screen();
		if( $screen && ScreenId::WP_DASHBOARD === $screen->base && ScreenId::WP_DASHBOARD === $screen->id ) {
			// For some magical reason, get_admin_page_title() returns 'Home' for the dashboard.
			return __( 'Dashboard', 'wpcf' );
		}

		return get_admin_page_title();
	}
}
