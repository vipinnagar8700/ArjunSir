<?php


class Tribe__Tickets_Plus__REST__V1__Main extends Tribe__Tickets__REST__V1__Main {

	/**
	 * Tribe__Tickets_Plus__REST__V1__Main constructor.
	 *
	 * @since 4.7.5
	 *
	 */
	public function __construct() {

		/** @var Tribe__Events__REST__V1__System $system */
		$system = tribe( 'tickets.rest-v1.system' );

		if ( ! $system->supports_et_rest_api() ) {
			return;
		}

	}

}