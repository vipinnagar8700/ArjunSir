<?php
/**
 * Shortcode [tribe_tickets_attendees].
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */

namespace Tribe\Tickets\Plus\Shortcode;

use Tribe\Shortcode\Shortcode_Abstract;
use Tribe__Tickets__Editor__Blocks__Attendees;
use Tribe__Tickets__Editor__Template;
use WP_Post;

/**
 * Class for Shortcode Tribe_Tickets_Attendees.
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */
class Tribe_Tickets_Attendees extends Shortcode_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tribe_tickets_attendees';

	/**
	 * {@inheritDoc}
	 */
	protected $default_arguments = [
		'post_id' => null,
		'title'   => '',
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

		return $this->get_attendees_block( $post_id );
	}

	/**
	 * Returns the block template's content.
	 *
	 * @param WP_Post|int $post
	 *
	 * @return string HTML.
	 */
	public function get_attendees_block( $post ) {
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

		/** @var Tribe__Tickets__Editor__Blocks__Attendees $attendees_block */
		$attendees_block = tribe( 'tickets.editor.blocks.attendees' );

		$post_id             = $post->ID;
		$title               = $this->get_argument( 'title' );
		$attributes          = [];
		$attributes['title'] = empty( $title ) ? esc_html__( "Who's coming?", 'tribe-ext-tickets-shortcodes' ) : $title;
		$args['post_id']     = $post_id;
		$args['attributes']  = $attendees_block->attributes( $attributes );
		$args['attendees']   = $attendees_block->get_attendees( $post_id );

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );

		// Enqueue assets.
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-attendees-style' );

		return $template->template( 'blocks/attendees', $args, false );
	}

}
