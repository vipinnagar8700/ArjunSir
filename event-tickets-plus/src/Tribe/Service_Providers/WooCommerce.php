<?php
class Tribe__Tickets_Plus__Service_Providers__WooCommerce extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		$this->container->singleton( 'ticket-plus.woocommerce.screen-options', 'Tribe__Tickets_Plus__Commerce__WooCommerce__Screen_Options' );

		add_filter(
			'set-screen-option',
			array( $this->container->make( 'ticket-plus.woocommerce.screen-options' ), 'filter_set_screen_options' ),
			10,
			3
		);
	}

	/**
	 * Binds and sets up implementations at boot time.
	 */
	public function boot() {
		// no-op
	}
}