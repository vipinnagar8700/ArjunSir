<?php

/**
 * Class Tribe__Tickets_Plus__Ticket_Repository
 *
 * Extension of the base Ticket repository to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__Ticket_Repository extends Tribe__Tickets__Ticket_Repository {

	/**
	 * Filters the map relating ticket repository slugs to service container bindings.
	 *
	 * @since 4.10.5
	 *
	 * @param array $map A map in the shape [ <repository_slug> => <service_name> ]
	 *
	 * @return array A map in the shape [ <repository_slug> => <service_name> ]
	 */
	public function filter_ticket_repository_map( $map ) {
		// Easy Digital Downloads
		$map['edd'] = 'tickets-plus.ticket-repository.edd';

		// WooCommerce
		$map['woo'] = 'tickets-plus.ticket-repository.woo';

		return $map;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		$types = parent::ticket_types();

		// Easy Digital Downloads
		$types['edd'] = 'download';

		// WooCommerce
		$types['woo'] = 'product';

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		$keys = parent::ticket_to_event_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_for_event';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_for_event';

		return $keys;
	}
}
