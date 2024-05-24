<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Helper class to hide other admin notices.
 *
 * @since 1.2.0
 *
 * @package WordPress
 */

namespace PPMWP\Helpers;

/**
 *  Helper class to hide other admin notices.
 *
 * @since 1.2.0
 */
class HideAdminNotices {

	/**
	 * Check whether we are on an admin and plugin page.
	 *
	 * @since 1.2.0
	 *
	 * @param array|string $slug ID(s) of a plugin page. Possible values: 'general', 'logs', 'about' or array of them.
	 *
	 * @return bool
	 */
	public static function is_admin_page( $slug = array() ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cur_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		$check    = 'ppm';

		return \is_admin() && ( false !== strpos( $cur_page, $check ) );
	}

	/**
	 * Remove all non MLS plugin notices from our plugin pages.
	 *
	 * @since 1.2.0
	 */
	public static function hide_unrelated_notices() {

		// Bail if we're not on our screen or page.
		if ( ! self::is_admin_page() ) {
			return;
		}

		self::remove_unrelated_actions( 'user_admin_notices' );
		self::remove_unrelated_actions( 'admin_notices' );
		self::remove_unrelated_actions( 'all_admin_notices' );
		self::remove_unrelated_actions( 'network_admin_notices' );
	}

	/**
	 * Remove all non-WP Mail SMTP notices from the our plugin pages based on the provided action hook.
	 *
	 * @since 1.2.0
	 *
	 * @param string $action The name of the action.
	 */
	private static function remove_unrelated_actions( $action ) {

		global $wp_filter;

		if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if (
				( // Cover object method callback case.
					is_array( $arr['function'] ) &&
					isset( $arr['function'][0] ) &&
					is_object( $arr['function'][0] ) &&
					strpos( strtolower( get_class( $arr['function'][0] ) ), 'ppm' ) !== false
				) ||
				( // Cover class static method callback case.
					! empty( $name ) &&
					strpos( strtolower( $name ), 'ppm' ) !== false
				)
				) {
					continue;
				}

				unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
			}
		}
	}
}
