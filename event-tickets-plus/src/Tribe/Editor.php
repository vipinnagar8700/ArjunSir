<?php

/**
 * Class Tribe__Tickets_Plus__Editor
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Editor extends Tribe__Tickets__Editor {

	/**
	 * Configure all action and filters user by this Class
	 *
	 * @since  4.6.2
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_settings_content_before', tribe_callback( 'tickets-plus.admin.views', 'template', 'editor/fieldset/settings-capacity' ) );
		add_action( 'tribe_events_tickets_settings_content', tribe_callback( 'tickets-plus.admin.views', 'template', 'editor/settings-attendees' ) );
		add_action( 'tribe_events_tickets_capacity', tribe_callback( 'tickets-plus.admin.views', 'template', 'editor/total-capacity' ) );
	}

	/**
	 * Filters the link to the Orders page to show the correct one.
	 *
	 * By default the link would point to PayPal ticket orders.
	 *
	 * @since 4.7
	 * @deprecated 4.10
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return string The updated Orders page URL
	 */
	public function filter_attendee_order_link( $url, $post_id ) {
		_deprecated_function( __METHOD__, '4.10', 'Method moved to each Commerce to modify filter tribe_filter_attendee_order_link' );

		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		if ( 'Tribe__Tickets__Commerce__PayPal__Main' === $provider ) {
			return $url;
		}

		$url = remove_query_arg( 'page', $url );
		$url = add_query_arg( array( 'page' => 'tickets-orders' ), $url );

		return $url;
	}
}
