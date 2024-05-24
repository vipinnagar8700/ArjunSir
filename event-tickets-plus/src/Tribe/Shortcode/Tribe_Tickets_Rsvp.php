<?php
/**
 * Shortcode [tribe_tickets_rsvp].
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */

namespace Tribe\Tickets\Plus\Shortcode;

use Tribe\Shortcode\Shortcode_Abstract;
use Tribe__Tickets__Editor__Template;
use Tribe__Tickets__RSVP;
use WP_Post;

/**
 * Class for Shortcode Tribe_Tickets_Rsvp.
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */
class Tribe_Tickets_Rsvp extends Shortcode_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tribe_tickets_rsvp';

	/**
	 * {@inheritDoc}
	 */
	protected $default_arguments = [
		'post_id' => null,
	];

	/**
	 * {@inheritDoc}
	 */
	public $validate_arguments_map = [
		'post_id' => 'tribe_post_exists',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_default_arguments() {
		$default_arguments = parent::get_default_arguments();

		/**
		 * Default to current Post ID, even if zero, since validation via tribe_post_exists() requires passing some
		 * value. Respect if the attribute got set via filter from parent method.
		 */
		$default_arguments['post_id'] = absint( $default_arguments['post_id'] );

		if ( empty( $default_arguments['post_id'] ) ) {
			$default_arguments['post_id'] = absint( get_the_ID() );
		}

		return $default_arguments;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$context = tribe_context();

		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}

		$post_id = $this->get_argument( 'post_id' );

		return $this->get_rsvp_block( $post_id );
	}

	/**
	 * Gets the block template and return it.
	 *
	 * @param WP_Post|int $post the post/event we're viewing.
	 *
	 * @return string HTML.
	 */
	public function get_rsvp_block( $post ) {

		if ( empty( $post ) ) {
			return '';
		}

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		// If password protected, then do not display content.
		if ( post_password_required( $post ) ) {
			return '';
		}

		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$post_id                  = $post->ID;
		$args['post_id']          = $post_id;
		$rsvps                    = $this->get_rsvps( $post_id );
		$args['active_rsvps']     = $this->get_active_tickets( $rsvps );
		$args['has_active_rsvps'] = ! empty( $args['active_rsvps'] );
		$args['has_rsvps']        = ! empty( $rsvps );
		$args['all_past']         = $this->get_all_tickets_past( $rsvps );

		// Add the rendering attributes into global context.
		$template->add_template_globals( $args );

		// enqueue assets.
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-rsvp' );
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-rsvp-style' );

		return $template->template( 'blocks/rsvp', $args, false );
	}

	/**
	 * Method to get all RSVP tickets
	 *
	 * @since 4.12.1
	 *
	 * @return array
	 */
	protected function get_rsvps( $post_id ) {
		$tickets = [];

		// Bail if there's no event id
		if ( ! $post_id ) {
			return $tickets;
		}

		/** @var Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		// Get the tickets IDs for this event
		$ticket_ids = $rsvp->get_tickets_ids( $post_id );

		// Bail if we don't have tickets.
		if ( ! $ticket_ids ) {
			return $tickets;
		}

		foreach ( $ticket_ids as $post ) {
			// Get the ticket
			$ticket = $rsvp->get_ticket( $post_id, $post );

			// Continue if is not RSVP, we only want RSVP tickets
			if ( $rsvp->class_name !== $ticket->provider_class ) {
				continue;
			}

			$tickets[] = $ticket;
		}

		return $tickets;
	}

	/**
	 * Method to get the active RSVP tickets
	 *
	 * @since 4.12.1
	 *
	 * @return array
	 */
	protected function get_active_tickets( $tickets ) {
		$active_tickets = [];

		foreach ( $tickets as $ticket ) {
			// continue if it's not in date range
			if ( ! $ticket->date_in_range() ) {
				continue;
			}

			$active_tickets[] = $ticket;
		}

		return $active_tickets;
	}

	/**
	 * Method to get the all RSVPs past flag
	 * All RSVPs past flag is true if all RSVPs end date is earlier than current date
	 * If there are no RSVPs, false is returned
	 *
	 * @since 4.12.1
	 *
	 * @return bool
	 */
	protected function get_all_tickets_past( $tickets ) {
		if ( empty( $tickets ) ) {
			return false;
		}

		$all_past = true;

		foreach ( $tickets as $ticket ) {
			$all_past = $all_past && $ticket->date_is_later();
		}

		return $all_past;
	}

}
