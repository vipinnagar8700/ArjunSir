<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__PayPal__Attendees
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Commerce__PayPal__Attendees {

	/**
	 * Renders the optout inputs in the front-end tickets form.
	 *
	 * @since 4.7
	 *
	 * @param WP_Post $event
	 * @param \Tribe__Tickets__Ticket_Object $ticket
	 */
	public function render_optout_input( $event, $ticket ) {
		/** @var Tribe__Tickets_Plus__Commerce__PayPal__Views $template */
		$views = tribe( 'tickets-plus.commerce.paypal.views' );
		$views->template( 'attendees-list-optout', array( 'ticket' => $ticket ) );
	}

	/**
	 * Filters the PayPal custom arguments to add the optout options if required.
	 *
	 * @since 4.7
	 *
	 * @param array $custom_args
	 *
	 * @return array
	 */
	public function register_optout_choice( array $custom_args = array() ) {
		$optout = Tribe__Utils__Array::get_in_any( array( $_POST, $_REQUEST ), 'tpp_optout' );

		if ( ! empty( $optout ) ) {
			// the space in the custom array is limited so we try to reduce the size
			$custom_args['oo'] = Tribe__Utils__Array::to_list( $optout, ',' );
		}

		return $custom_args;
	}
}
