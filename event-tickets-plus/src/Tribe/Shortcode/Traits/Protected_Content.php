<?php

namespace Tribe\Tickets\Plus\Shortcode\Traits;

use DateTimeZone;
use Tribe__Date_Utils as Date_Utils;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_View as Tickets_View;
use Tribe__Timezones as Timezones;
use Tribe__Utils__Array;
use WP_Post;

/**
 * Trait functionality for the Protected Content shortcodes.
 *
 * @package Tribe\Tickets\Plus\Shortcode\Traits
 * @since   4.12.1
 */
trait Protected_Content {

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
	 * Given a comma-separated string (or array) of Post IDs, remove all array values that aren't IDs of existent Posts.
	 *
	 * @param string|array|null $value
	 *
	 * @return array
	 */
	private function sanitize_list_of_existent_post_ids( $value ) {
		$result = Tribe__Utils__Array::list_to_array( $value );

		$result = array_map( 'tribe_post_exists', $result );

		$result = array_filter( $result );

		return $result;
	}

	/**
	 * Determine whether the user can see the content.
	 *
	 * @param array $args List of arguments to check content with.
	 *
	 * @return boolean Whether the user can see the content.
	 */
	public function can_see_content( array $args ) {
		$post_id        = $args['post_id'];
		$user_id        = get_current_user_id();
		$ticket_ids     = $this->sanitize_list_of_existent_post_ids( $args['ticket_ids'] );
		$not_ticket_ids = $this->sanitize_list_of_existent_post_ids( $args['not_ticket_ids'] );
		$on_date        = ! empty( $args['on'] ) ? $args['on'] : null;
		$ticketed       = (bool) $args['ticketed'];
		$type           = ! empty( $args['type'] ) ? $args['type'] : 'ticket';
		$context        = ! empty( $args['context'] ) ? $args['context'] : null;

		// Post ID was validated upstream so don't let get_post() get the value from the Current Global Post.
		if ( empty( $post_id ) ) {
			$post = null;
		} else {
			$post = get_post( $post_id );
		}

		$filter_args = compact( [
			'post_id',
			'post',
			'user_id',
			'ticket_ids',
			'not_ticket_ids',
			'on_date',
			'ticketed',
			'type',
			'context',
			'args',
		] );

		// Is the post valid?
		if ( ! $post instanceof WP_Post ) {
			return $this->can_see_content_filtered( false, $filter_args, 'invalid_post' );
		}

		// Update post ID var.
		$post_id                = $post->ID;
		$filter_args['post_id'] = $post_id;

		// Are we checking for ticketed but user is not logged in?
		if (
			$ticketed
			&& empty( $user_id )
		) {
			return $this->can_see_content_filtered( false, $filter_args, 'not_logged_in' );
		}

		// Check date logic.
		if ( $on_date ) {
			if ( 'event_start_date' === $on_date ) {
				// Is TEC active if we're trying to use a TEC custom field?
				if ( ! function_exists( 'tribe_is_event' ) ) {
					return $this->can_see_content_filtered( false, $filter_args, 'the_events_calendar_not_active' );
				}

				// Do we not have a start date to check?
				if ( ! tribe_is_event( $post ) ) {
					return $this->can_see_content_filtered( false, $filter_args, 'invalid_event_for_date' );
				}

				$event = tribe_get_event( $post );

				// Is the event in the future?
				if ( ! $event->is_past ) {
					return $this->can_see_content_filtered( false, $filter_args, 'event_date_in_future' );
				}
			} else {
				$wp_timezone = Timezones::wp_timezone_string();

				if ( Timezones::is_utc_offset( $wp_timezone ) ) {
					$wp_timezone = Timezones::generate_timezone_string_from_utc_offset( $wp_timezone );
				}

				$timezone = new DateTimeZone( $wp_timezone );

				$date = Date_Utils::build_date_object( $on_date, $timezone );
				$now  = Date_Utils::build_date_object( 'now', $timezone );

				// Is the event in the future?
				if ( $now < $date ) {
					return $this->can_see_content_filtered( false, $filter_args, 'date_in_future' );
				}
			}
		}

		$tickets_view = Tickets_View::instance();

		$method = 'rsvp' === $type ? 'has_rsvp_attendees' : 'has_ticket_attendees';

		if (
			empty( $ticket_ids )
			&& empty( $not_ticket_ids )
		) {
			$has_ticket_attendees = $tickets_view->$method( $post_id, $user_id );
		} else {
			$args = [
				'by' => [
					'user' => $user_id,
				],
			];

			if ( $ticket_ids ) {
				$args['by']['ticket'] = $ticket_ids;
			}

			if ( $not_ticket_ids ) {
				$args['by']['ticket__not_in'] = $not_ticket_ids;
			}

			if ( 'ticket' !== $type ) {
				$args['by']['provider'] = $type;
			}

			$has_ticket_attendees = (boolean) Tickets::get_event_attendees_count( $post_id, $args );
		}

		// Are we checking for ticketed but they have no matching attendees?
		if (
			$ticketed &&
			! $has_ticket_attendees
		) {
			return $this->can_see_content_filtered( false, $filter_args, 'no_attendees' );
		}

		// Are we checking for non-ticketed but they have matching attendees?
		if (
			! $ticketed
			&& $has_ticket_attendees
		) {
			return $this->can_see_content_filtered( false, $filter_args, 'has_attendees' );
		}

		return $this->can_see_content_filtered( true, $filter_args, 'pass' );
	}

	/**
	 * Handle hooking into whether someone can see the content whether it passed or failed.
	 *
	 * @param boolean $can_see_content Whether someone can see the content.
	 * @param string  $reason          The reason why they can see or cannot see the content
	 *                                 (not a user friendly message).
	 * @param array   $filter_args     {
	 *      List of arguments that can be used to check the status.
	 *
	 *      @type int     $post_id        The post ID.
	 *      @type WP_Post $post           The post object.
	 *      @type int     $user_id        The user ID.
	 *      @type array   $ticket_ids     The list of ticket IDs to check.
	 *      @type array   $not_ticket_ids The list of ticket IDs to exclude.
	 *      @type boolean $ticketed       Whether the attendee should be ticketed or not yet.
	 *      @type string  $on_date        The date to limit.
	 *      @type string  $type           The type of ticket (ticket | rsvp).
	 *      @type string  $context        The context of the shortcode.
	 *      @type string  $args           The list of all shortcode arguments.
	 * }
	 *
	 * @return boolean Whether someone can see the content.
	 */
	protected function can_see_content_filtered( $can_see_content, $filter_args, $reason ) {
		/**
		 * Allow hooking into whether someone can see the content.
		 *
		 * @since 4.12.1
		 *
		 * @param boolean $can_see_content Whether someone can see the content.
		 * @param string  $reason          The reason why they can see or cannot see the content
		 *                                 (not a user friendly message).
		 * @param array   $filter_args     {
		 *      List of arguments that can be used to check the status.
		 *
		 *      @type int     $post_id        The post ID.
		 *      @type WP_Post $post           The post object.
		 *      @type int     $user_id        The user ID.
		 *      @type array   $ticket_ids     The list of ticket IDs to check.
		 *      @type array   $not_ticket_ids The list of ticket IDs to exclude.
		 *      @type boolean $ticketed       Whether the attendee should be ticketed or not yet.
		 *      @type string  $on_date        The date to limit.
		 *      @type string  $type           The type of ticket (ticket | rsvp).
		 *      @type string  $context        The context of the shortcode.
		 *      @type string  $args           The list of all shortcode arguments.
		 * }
		 */
		$can_see_content = (boolean) apply_filters(
			'tribe_tickets_shortcode_can_see_content',
			$can_see_content,
			$filter_args,
			$reason
		);

		$context = $filter_args['context'];

		/**
		 * Allow hooking into whether someone can see the content based on the context.
		 *
		 * @since 4.12.1
		 *
		 * @param boolean $can_see_content Whether someone can see the content.
		 * @param string  $reason          The reason why they can see or cannot see the content
		 *                                 (not a user friendly message).
		 * @param array   $filter_args     {
		 *      List of arguments that can be used to check the status.
		 *
		 *      @type int     $post_id        The post ID.
		 *      @type WP_Post $post           The post object.
		 *      @type int     $user_id        The user ID.
		 *      @type array   $ticket_ids     The list of ticket IDs to check.
		 *      @type array   $not_ticket_ids The list of ticket IDs to exclude.
		 *      @type boolean $ticketed       Whether the attendee should be ticketed or not yet.
		 *      @type string  $on_date        The date to limit.
		 *      @type string  $type           The type of ticket (ticket | rsvp).
		 *      @type string  $context        The context of the shortcode.
		 *      @type string  $args           The list of all shortcode arguments.
		 * }
		 */
		$can_see_content = (boolean) apply_filters(
			"tribe_tickets_shortcode_{$context}_can_see_content",
			$can_see_content,
			$filter_args,
			$reason
		);

		return $can_see_content;
	}

}
