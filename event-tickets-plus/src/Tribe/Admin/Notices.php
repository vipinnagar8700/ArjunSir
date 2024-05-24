<?php

/**
 * Class Notices
 *
 * @since 4.11.0.1
 */
class Tribe__Tickets_Plus__Admin__Notices {

	/**
	 * Hooks the actions and filters used by the class.
	 *
	 * Too late to use 'plugins_loaded' or 'tribe_plugins_loaded'
	 * and must be before 'admin_notices' to use tribe_notice().
	 *
	 * @since 4.11.0.1
	 */
	public function hook() {
		add_action( 'admin_init', [ $this, 'maybe_display_ar_modal_options_notice' ] );
	}

	/**
	 * Display dismissible notice about new Attendee Registration (AR) Modal settings if has used AR prior to Modal's
	 * release and hasn't previously dismissed this notice.
	 *
	 * @since 4.11.0.1
	 */
	public function maybe_display_ar_modal_options_notice() {
		global $wpdb;

		// Bail on the unexpected
		if (
			! class_exists( 'Tribe__Admin__Notices' )
			|| ! function_exists( 'tribe_installed_before' )
			|| empty( Tribe__Tickets_Plus__Meta::ENABLE_META_KEY )
		) {
			return;
		}

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Bail if user cannot change settings
		if ( ! current_user_can( $settings->requiredCap ) ) {
			return;
		}

		// Bail if previously dismissed this notice
		if ( Tribe__Admin__Notices::instance()->has_user_dimissed( __FUNCTION__ ) ) {
			return;
		}

		// Bail if already at wp-admin > Events > Settings > Tickets tab to avoid redundancy/confusion by linking to itself
		if (
			'tribe-common' === tribe_get_request_var( 'page' )
			&& 'event-tickets' === tribe_get_request_var( 'tab' )
		) {
			return;
		}

		// Get link to Tickets Tab
		$url = $settings->get_url( [
			'page' => 'tribe-common',
			'tab'  => 'event-tickets',
		] );

		$link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html_x( 'Attendee Registration Settings', __FUNCTION__, 'event-tickets-plus' )
		);

		// Do notice
		$message = sprintf(
			// translators: placeholders are html tags (and one link, translated above).
			__( '%1$sEvent Tickets Plus%2$s%3$sWith this new version, we\'ve made front-end style updates. If you have customized the %7$s section or the Attendee Registration page, this update will likely impact your customizations.%4$s We\'ve also introduced a new Attendee Registration Information flow for %8$s purchasers! If you use Attendee Registration, please select which user flow you prefer for your website in the %5$s.%6$s ', 'event-tickets-plus' ),
			'<h3>',
			'</h3>',
			'<p>',
			'</p><p>',
			$link,
			'</p>',
			tribe_get_ticket_label_plural( 'admin_notices' ),
			tribe_get_ticket_label_singular_lowercase( 'admin_notices' )
		);

		tribe_notice(
			__FUNCTION__,
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			]
		);
	}
}