<?php
class Tribe__Tickets_Plus__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since 4.6
	 * @since 4.11.1 Only load if in a tickets-enabled post context.
	 *
	 * @see   \tribe_tickets_is_enabled_post_context()
	 */
	public function enqueue_scripts() {
		// Set up our base list of enqueues
		$enqueue_array = [
			[ 'event-tickets-plus-tickets-css', 'tickets.css', [ 'dashicons' ] ],
			[ 'jquery-deparam', 'vendor/jquery.deparam/jquery.deparam.js', [ 'jquery' ] ],
			[ 'jquery-cookie', 'vendor/jquery.cookie/jquery.cookie.js', [ 'jquery' ] ],
			[ 'event-tickets-plus-attendees-list-js', 'attendees-list.js', [ 'event-tickets-attendees-list-js' ] ],
			[ 'event-tickets-plus-meta-js', 'meta.js', [ 'jquery-cookie', 'jquery-deparam' ] ],
		];

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'wp_enqueue_scripts',
			[
				'localize'     => [
					'name' => 'TribeTicketsPlus',
					'data' => [
						'ajaxurl'                  => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
						'save_attendee_info_nonce' => wp_create_nonce( 'save_attendee_info' ),
					],
				],
				'conditionals' => 'tribe_tickets_is_enabled_post_context',
			]
		);

	}

	/**
	 * Enqueue scripts for admin views
	 *
	 * @since 4.6
	 */
	public function admin_enqueue_scripts() {
		// Set up our base list of enqueues
		$enqueue_array = array(
			array( 'event-tickets-plus-meta-admin-css', 'meta.css', array() ),
			array( 'event-tickets-plus-meta-report-js', 'meta-report.js', array() ),
			array( 'event-tickets-plus-attendees-list-js', 'attendees-list.js', array( 'event-tickets-attendees-list-js' ) ),
			array( 'event-tickets-plus-meta-admin-js', 'meta-admin.js', array( 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' ) ),
			array( 'event-tickets-plus-admin-css', 'admin.css', array( 'event-tickets-admin-css' ) ),
			array( 'event-tickets-plus-admin-tables-js', 'tickets-tables.js', array( 'underscore', 'jquery', 'tribe-common' ) ),
			array( 'event-tickets-plus-admin-qr', 'qr.js', array( 'jquery' ) ),
		);

		/**
		 * Filter the array of module names.
		 *
		 * @since 4.6
		 *
		 * @param array the array of modules
		 */
		$modules = Tribe__Tickets__Tickets::modules();
		$modules = array_values( $modules );

		if ( in_array( 'WooCommerce', $modules )  ) {
			$enqueue_array[] = array( 'event-tickets-plus-wootickets-css', 'wootickets.css', array( 'event-tickets-plus-meta-admin-css' ) );
		}

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'admin_enqueue_scripts',
			array(
				'priority' => 0,
				'groups'       => 'event-tickets-plus-admin',
				'localize' => (object) array(
					'name' => 'tribe_qr',
					'data' => array(
						'generate_qr_nonce'   => wp_create_nonce( 'generate_qr_nonce' ),
					),
				),
			)
		);
	}
}
