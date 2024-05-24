<?php
/**
 * Shortcode [tribe_tickets_protected_content].
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */

namespace Tribe\Tickets\Plus\Shortcode;

use Tribe\Shortcode\Shortcode_Abstract;
use Tribe\Tickets\Plus\Shortcode\Traits\Protected_Content;

/**
 * Class for Shortcode Tribe_Tickets_Protected_Content.
 *
 * @package Tribe\Tickets\Plus\Shortcode
 * @since   4.12.1
 */
class Tribe_Tickets_Protected_Content extends Shortcode_Abstract {

	use Protected_Content;

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tribe_tickets_protected_content';

	/**
	 * {@inheritDoc}
	 */
	protected $default_arguments = [
		'post_id'        => null,
		'ticket_ids'     => null,
		'not_ticket_ids' => null,
		'on'             => null,
		'type'           => 'ticket',
		'ticketed'       => 1,
	];

	/**
	 * {@inheritDoc}
	 */
	public $validate_arguments_map = [
		'post_id'        => 'tribe_post_exists',
		'type'           => 'sanitize_title_with_dashes',
		'ticketed'       => 'boolval',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$args = $this->get_arguments();

		// Let the trait know what called this.
		$args['context'] = $this->get_registration_slug();

		// Can they see the content?
		if ( ! $this->can_see_content( $args ) ) {
			return '';
		}

		// Return content with shortcodes processed (support embedded shortcodes).
		return do_shortcode( $this->content );
	}

}
