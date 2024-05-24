<?php
if ( ! function_exists( 'tribe_tickets_is_edd_active' ) ) {
	/**
	 * Check if EDD is active
	 *
	 * @since 4.7.3
	 *
	 * @return bool whether it the plugin is active or not
	 */
	function tribe_tickets_is_edd_active() {
		return class_exists( 'Easy_Digital_Downloads' );
	}
}
