<?php

/**
 * The ORM/Repository class for WooCommerce tickets.
 *
 * @since 4.10.5
 */
class Tribe__Tickets_Plus__Repositories__Ticket__WooCommerce extends Tribe__Tickets_Plus__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		$types = parent::ticket_types();

		$types = [
			'woo' => $types['woo'],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		$keys = parent::ticket_to_event_keys();

		$keys = [
			'woo' => $keys['woo'],
		];

		return $keys;
	}

}
