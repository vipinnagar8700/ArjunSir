<?php
/**
 * Handles all of the post querying.
 *
 * @since 4.12.1
 */

use Tribe\Tickets\Repositories\Post_Repository;

/**
 * Class Tribe__Tickets_Plus__Repositories__Post_Repository
 *
 * Extension of the base Post repository that ET sets up to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Repositories__Post_Repository extends Post_Repository {

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
