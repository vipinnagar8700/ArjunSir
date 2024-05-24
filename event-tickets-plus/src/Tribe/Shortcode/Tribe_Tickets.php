<?php

namespace Tribe\Tickets\Plus\Shortcode;

use Tribe\Shortcode\Shortcode_Abstract;
use Tribe__Tickets__Editor__Blocks__Tickets;
use Tribe__Tickets__Editor__Template;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class for Shortcode Tribe_Tickets.
 *
 * @package Tribe\Tickets\Plus\Shortcode
 *
 * @since   4.12.1
 */
class Tribe_Tickets extends Shortcode_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tribe_tickets';

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

		$post_id = absint( $this->get_argument( 'post_id' ) );

		return $this->get_tickets_block( $post_id );
	}

	/**
	 * Returns the block template's content.
	 *
	 * @param WP_Post|int $post
	 *
	 * @return string HTML.
	 */
	public function get_tickets_block( $post ) {
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

		$post_id     = $post->ID;
		$provider_id = Tickets::get_event_ticket_provider( $post_id );

		// Protect against ticket that exists but is of a type that is not enabled
		if ( ! method_exists( $provider_id, 'get_instance' ) ) {
			return '';
		}

		$provider = call_user_func( [ $provider_id, 'get_instance' ] );

		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		/** @var Tribe__Tickets__Editor__Blocks__Tickets $blocks_tickets */
		$blocks_tickets = tribe( 'tickets.editor.blocks.tickets' );

		// Load assets manually.
		$blocks_tickets->assets();

		$tickets = $provider->get_tickets( $post_id );

		$args = [
			'post_id'             => $post_id,
			'provider'            => $provider,
			'provider_id'         => $provider_id,
			'tickets'             => $tickets,
			'cart_classes'        => [ 'tribe-block', 'tribe-tickets' ],
			'tickets_on_sale'     => $blocks_tickets->get_tickets_on_sale( $tickets ),
			'has_tickets_on_sale' => tribe_events_has_tickets_on_sale( $post_id ),
			'is_sale_past'        => $blocks_tickets->get_is_sale_past( $tickets ),
		];

		// Add the rendering attributes into global context.
		$template->add_template_globals( $args );

		// Enqueue assets.
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-tickets' );
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-tickets-style' );

		return $template->template( 'blocks/tickets', $args, false );
	}

}
