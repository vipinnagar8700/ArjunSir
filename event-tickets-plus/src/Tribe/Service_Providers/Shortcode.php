<?php

namespace Tribe\Tickets\Plus\Service_Providers;

use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets;
use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets_Attendees;
use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets_Protected_Content;
use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets_Rsvp;
use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets_Rsvp_Protected_Content;

/**
 * Class Shortcode.
 *
 * @package Tribe\Tickets\Plus\Service_Providers
 * @since   4.12.1
 */
class Shortcode extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.12.1
	 */
	public function register() {
		$this->container->singleton( 'ticket-plus.service_providers.shortcode', $this );
		$this->container->singleton( static::class, $this );

		$this->hooks();
	}

	protected function hooks() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );
	}

	/**
	 * Register shortcodes.
	 *
	 * @see   \Tribe\Shortcode\Manager::get_registered_shortcodes()
	 *
	 * @since 4.12.1
	 *
	 * @param array $shortcodes An associative array of shortcodes in the shape `[ <slug> => <class> ]`.
	 *
	 * @return array
	 */
	public function filter_register_shortcodes( array $shortcodes ) {
		$shortcodes['tribe_tickets']                        = Tribe_Tickets::class;
		$shortcodes['tribe_tickets_rsvp']                   = Tribe_Tickets_Rsvp::class;
		$shortcodes['tribe_tickets_attendees']              = Tribe_Tickets_Attendees::class;
		$shortcodes['tribe_tickets_protected_content']      = Tribe_Tickets_Protected_Content::class;
		$shortcodes['tribe_tickets_rsvp_protected_content'] = Tribe_Tickets_Rsvp_Protected_Content::class;

		return $shortcodes;
	}
}