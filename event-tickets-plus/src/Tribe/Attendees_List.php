<?php

use Tribe\Tickets\Events\Attendees_List;

/**
 * Class Tribe__Tickets_Plus__Attendees_List
 */
class Tribe__Tickets_Plus__Attendees_List extends Attendees_List {

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Hook the necessary filters and Actions!
	 *
	 * @return void
	 */
	public static function hook() {
		$myself = self::instance();

		// This will include before the RSVP
		add_action( 'tribe_tickets_before_front_end_ticket_form', array( $myself, 'render' ), 4 );

		// Unhook Event Ticket's "View your RSVP's" rendering logic so that we can re-render with ET+'s "Who's attending?" list.
		add_action( 'init', array( $myself, 'unhook_event_tickets_order_link_logic' ) );

		// Add the Admin Option for removing the Attendees List
		add_action( 'tribe_events_tickets_metabox_pre', array( $myself, 'render_admin_options' ) );

		// Create the ShortCode
		add_shortcode( 'tribe_attendees_list', array( $myself, 'shortcode' ) );

		// Purging the attendees cache on all the modules
		// @todo: make this a little bit more clean
		add_action( 'event_tickets_rsvp_ticket_created', array( $myself, 'purge_transient' ), 10, 3 );
		add_action( 'event_ticket_woo_attendee_created', array( $myself, 'purge_transient' ), 10, 3 );
		add_action( 'event_tickets_woocommerce_tickets_generated_for_product', array( $myself, 'purge_transient_post_ticket' ), 10, 4 );

		add_action( 'event_tickets_edd_ticket_created', array( $myself, 'edd_purge_transient' ), 10, 2 );
	}

	/**
	 * Determine if we need to hide the attendees list.
	 *
	 * @param int|WP_Post $post   The post object or ID.
	 * @param boolean     $strict Whether to strictly check the meta value.
	 *
	 * @return bool Whether the attendees list is hidden.
	 */
	public static function is_hidden_on( $post, $strict = true ) {
		/**
		 * Use this to filter and hide the Attendees List for a specific post or all of them.
		 *
		 * @param bool    $is_hidden Whether the attendees list is hidden.
		 * @param WP_Post $post      The post object.
		 */
		return apply_filters( 'tribe_tickets_plus_hide_attendees_list', parent::is_hidden_on( $post ), $post );
	}

	/**
	 * Renders the Administration option to hide Attendees List
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function render_admin_options( $post_id = null ) {
		$is_attendees_list_hidden = self::is_hidden_on( $post_id );

		include_once Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/attendees-list.php';
	}

	/**
	 * Wrapper to create the Shortcode with the Attendees List
	 *
	 * @todo Move to using Common-extended \Tribe\Tickets\Plus\Service_Providers\Shortcode.
	 *
	 * @param  array $atts
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = (object) shortcode_atts( array(
			'event' => null,
			'limit' => 20,
		), $atts, 'tribe_attendees_list' );

		ob_start();
		$this->render( $atts->event, $atts->limit );
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Remove the Post Transients when a EDD Ticket is bought
	 *
	 * @param  int $attendee_id
	 * @param  int $order_id
	 * @return void
	 */
	public function edd_purge_transient( $attendee_id, $order_id ) {
		$event_id = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance()->get_event_id_from_order_id( $order_id );
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Remove the Post Transients for the Tickets Attendees during attendee generation
	 *
	 * @param  int $unused_attendee_id
	 * @param  int $event_id
	 * @param  int $unused_product_id
	 * @return void
	 */
	public function purge_transient( $unused_attendee_id, $event_id, $unused_product_id ) {
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Remove the Post Transients for the Tickets Attendees during late ticket generation
	 *
	 * @since 4.10.1.1
	 *
	 * @param  int $unused_attendee_id
	 * @param  int $unused_event_id
	 * @param  int $unused_product_id
	 * @param  int $event_id
	 * @return void
	 */
	public function purge_transient_post_ticket( $unused_product_id, $unused_order_id, $unused_quantity, $event_id ) {
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Unhook Event Ticket's "View your RSVPs" rendering logic. Better enables re-rendering of that link
	 * with ET+'s "Who's attending?" list across all tickets-enabled post types.
	 *
	 * @since 4.5.4
	 */
	public function unhook_event_tickets_order_link_logic() {
		$tickets_view = Tribe__Tickets__Tickets_View::instance();

		remove_action( 'tribe_events_single_event_after_the_meta', [ $tickets_view, 'inject_link_template' ], 4 );
		remove_filter( 'the_content', [ $tickets_view, 'inject_link_template_the_content' ], 9 );
	}

	/**
	 * Includes the Attendees List HTML
	 *
	 * @param  int|WP_Post $event
	 * @return void
	 */
	public function render( $event = null, $limit = 20 ) {
		$event = get_post( $event );
		if ( ! $event instanceof WP_Post ) {
			$event = get_post();
		}

		if (
			'tribe_tickets_before_front_end_ticket_form' === current_filter()
			&& self::is_hidden_on( $event )
		) {
			return;
		}

		$attendees_list = $this->get_attendees( $event->ID, $limit );

		if ( ! $attendees_list ) {
			return;
		}

		$attendees_total = count( $attendees_list );

		if ( empty( $attendees_total ) ) {
			return;
		}

		include_once Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( 'attendees-list' );

		$tickets_view = Tribe__Tickets__Tickets_View::instance();

		$tickets_view->inject_link_template();
	}

	/**
	 * Returns an Array ready for printing of the Attendees List
	 *
	 * @param WP_Post|int $post_id Post object or ID.
	 * @param int         $limit   Limit of attendees to be retrieved.
	 *
	 * @return array
	 */
	public function get_attendees( $post_id, $limit = 20 ) {
		/**
		 * Allow for adjusting the limit of attendees fetched from the database for the front-end "Who's Attending?" list.
		 *
		 * @since 4.10.5
		 *
		 * @param int $limit_attendees Number of attendees to retrieve. Default is no limit -1.
		 */
		$limit_attendees = (int) apply_filters( 'tribe_tickets_plus_attendees_list_limit_attendees', - 1 );

		$attendees_to_display = $this->get_attendees_for_post( $post_id, $limit_attendees );

		if ( empty( $attendees_to_display ) ) {
			return [];
		}

		$output = [];

		/**
		 * Allow for adjusting the limit of attendees retrieved for the front-end "Who's Attending?" list.
		 *
		 * @since 4.5.5
		 *
		 * @param int $limit Number of attendees to retrieve.
		 */
		$limit = (int) apply_filters( 'tribe_tickets_plus_attendees_list_limit', $limit );

		$has_broken = false;

		foreach ( $attendees_to_display as $key => $attendee ) {
			if ( ! $has_broken && is_numeric( $limit ) && $limit < $key + 1 ) {
				$has_broken = true;
			}

			$class = $has_broken ? 'hidden' : 'shown';

			$output[ $attendee['attendee_id'] ] = sprintf(
				'<span class="tribe-attendees-list-%1$s">%2$s</span>',
				$class,
				get_avatar( $attendee['purchaser_email'], 40, '', $attendee['purchaser_name'] )
			);
		}

		if ( $has_broken ) {
			$output['show-more'] = sprintf(
				'<a href="#show-all-attendees"
					data-offset="%1$s"
					title="%2$s"
					class="tribe-attendees-list-showall avatar">
					%3$s
				</a>',
				esc_attr( $limit ),
				esc_attr__( 'Load all attendees', 'event-tickets-plus' ),
				get_avatar( '', 40, '', esc_attr__( 'Load all attendees', 'event-tickets-plus' ) )
			);
		}

		return $output;
	}

}
