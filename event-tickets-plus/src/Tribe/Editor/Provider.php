<?php
/**
 * Register Event Tickets provider
 *
 * @since 4.9
 */

// Tribe__Tickets_Plus__APM
class Tribe__Tickets_Plus__Editor__Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.9
	 *
	 */
	public function register() {
		if (
			! tribe( 'editor' )->should_load_blocks()
			|| ! class_exists( 'Tribe__Tickets_Plus__Main' )
		) {
			return;
		}

		$this->container->singleton( 'tickets-plus.editor.assets', 'Tribe__Tickets_Plus__Editor__Assets', array( 'register' ) );
		$this->hook();
	}

	/**
	 * Any hooking any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.9
	 *
	 */
	protected function hook() {
		tribe( 'tickets-plus.editor.assets' );
	}

	/**
	 * Binds and sets up implementations at boot time.
	 *
	 * @since 4.9
	 */
	public function boot() {
	}
}
