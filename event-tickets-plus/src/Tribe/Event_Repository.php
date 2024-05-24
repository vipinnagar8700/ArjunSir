<?php
/**
 * Handles all of the event querying.
 *
 * @since 4.12.1
 */

/**
 * Class Tribe__Tickets_Plus__Attendee_Repository
 *
 * Extension of the base Event repository that ET sets up to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Event_Repository extends Tribe__Tickets__Event_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		$types = parent::attendee_types();

		// Easy Digital Downloads attendee post type.
		$types['edd'] = 'tribe_eddticket';

		// WooCommerce attendee post type.
		$types['woo'] = 'tribe_wooticket';

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		$keys = parent::attendee_to_event_keys();

		// Easy Digital Downloads event meta key.
		$keys['edd'] = '_tribe_eddticket_event';

		// WooCommerce event meta key.
		$keys['woo'] = '_tribe_wooticket_event';

		return $keys;
	}
}
