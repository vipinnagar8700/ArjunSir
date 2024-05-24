<?php

/**
 * Class Tribe__Tickets_Plus__Service_Providers__Promoter
 *
 * @since 4.12.0
 */
class Tribe__Tickets_Plus__Service_Providers__Promoter extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.12.0
	 */
	public function register() {
		$this->container->bind(
			'tickets-plus.commerce.edd.promoter-observer',
			Tribe__Tickets_Plus__Commerce__EDD__Promoter_Observer::class
		);

		$this->container->bind(
			'tickets-plus.commerce.woo.promoter-observer',
			Tribe__Tickets_Plus__Commerce__WooCommerce__Promoter_Observer::class
		);

		// Make sure hooks are only registered if ET Observer is registered
		if ( tribe()->isBound( 'tickets.promoter.observer' ) ) {
			$this->hook();
		}
	}

	/**
	 * Register different commerce instances.
	 *
	 * @since 4.12.0
	 */
	public function hook() {
		tribe( 'tickets-plus.commerce.edd.promoter-observer' );
		tribe( 'tickets-plus.commerce.woo.promoter-observer' );
	}
}