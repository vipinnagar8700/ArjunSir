<?php
/**
 * Class Tribe__Tickets_Plus__QR
 */
class Tribe__Tickets_Plus__QR {

	public function __construct() {
		add_filter( 'init', array( $this, 'handle_redirects' ), 10    );
		add_filter( 'admin_notices', array( $this, 'admin_notice' ), 10    );
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'inject_qr' ) );
	}

	/**
	 * Processes the links coming from QR codes and decides what to do:
	 *   - If the user is logged in and has proper permissions, it will redirect
	 *     to the attendees screen for the event, and will automatically check in the user.
	 *
	 *   - If the user is not logged in and/or does not have proper permissions, it will
	 *     redirect to the homepage of the event (front end single event view).
	 */
	public function handle_redirects() {

		// Check if it's our time to shine.
		// Not as fancy as a custom permalink handler, but way less likely to fail depending on setup and settings
		if ( ! isset( $_GET['event_qr_code'] ) ) {
			return;
		}

		// Check all the data we need is there
		if ( empty( $_GET['ticket_id'] ) || empty( $_GET['event_id'] ) ) {
			return;
		}

		// Make sure we don't fail too hard
		if ( ! class_exists( 'Tribe__Tickets__Tickets_Handler' ) ) {
			return;
		}


		$event_id      = (int) $_GET['event_id'];
		$ticket_id     = (int) $_GET['ticket_id'];
		$security_code = (string) isset( $_GET['security_code'] ) ? esc_attr( $_GET['security_code'] ) : '';

		// See if the user had access or not to the checkin process
		$checkin_arr = $this->authorized_checkin( $event_id, $ticket_id, $security_code );

		/**
		 * Filters the redirect URL if the user can access the QR checkin
		 *
		 * @param string $url             URL to redirect to, gets escaped upstream
		 * @param int    $event_id        Event Post ID
		 * @param int    $ticket_id       Ticket Post ID
		 * @param bool   $user_had_access Whether or not the logged-in user has permission to perform check ins
		 */
		$url = apply_filters( 'tribe_tickets_plus_qr_handle_redirects', $checkin_arr['url'], $event_id, $ticket_id, $checkin_arr['user_had_access'] );

		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Check if user is authorized to Checkin Ticket
	 *
	 * @since 4.8.1
	 *
	 * @param $event_id      int event post ID
	 * @param $ticket_id     int ticket tost ID
	 * @param $security_code string ticket security code
	 *
	 * @return array
	 */
	public function authorized_checkin( $event_id, $ticket_id, $security_code ) {

		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			$checkin_arr = array(
				'url'             => get_permalink( $event_id ),
				'user_had_access' => false,
			);

			return $checkin_arr;
		}

		$post = get_post( $event_id );

		if ( empty( $post ) ) {
			return array( 'url' => '', 'user_had_access' => true );
		}

		/**
		 * Filters the check for security code when checking in a ticket
		 *
		 * @since 4.11.2 Change the default to true.
		 *
		 * @param bool true The default is to check the security code.
		 */
		$check_security_code = apply_filters( 'tribe_tickets_plus_qr_check_security_code', true );

		$service_provider = tribe( 'tickets.data_api' )->get_ticket_provider( $ticket_id );

		//if check_security_code and security key does not match do not checkin and redirect with message
		if ( $check_security_code &&
			(
				empty( $service_provider->security_code ) ||
				$security_code !== get_post_meta( $ticket_id, $service_provider->security_code, true )
			)
		) {

			$url = add_query_arg(
				array(
					'post_type'              => $post->post_type,
					'page'                   => tribe( 'tickets.attendees' )->slug(),
					'event_id'               => $event_id,
					'qr_checked_in'          => $ticket_id,
					'no_security_code_match' => true,
				), admin_url( 'edit.php' )
			);

			$checkin_arr = array(
				'url'             => $url,
				'user_had_access' => true,
			);

			return $checkin_arr;

		}

		// If the user is the site owner (or similar), Check in the user to the event
		$this->_check_in( $ticket_id );

		$url = add_query_arg(
			array(
				'post_type'     => $post->post_type,
				'page'          => tribe( 'tickets.attendees' )->slug(),
				'event_id'      => $event_id,
				'qr_checked_in' => $ticket_id,
			), admin_url( 'edit.php' )
		);

		$checkin_arr = array(
			'url'             => $url,
			'user_had_access' => true,
		);

		return $checkin_arr;
	}


	/**
	 * Show a notice so the user knows the ticket was checked in.
	 */
	public function admin_notice() {

		if ( empty( $_GET['qr_checked_in'] ) ) {
			return;
		}

		// Use Human-readable ID Where Available for QR Check in Message.
		$ticket_id        = absint( $_GET['qr_checked_in'] );
		$no_match         = isset( $_GET['no_security_code_match'] ) ? absint( $_GET['no_security_code_match'] ) : false;
		$ticket_status    = get_post_status( $ticket_id );
		$checked_status   = get_post_meta( $ticket_id, '_tribe_qr_status', true );
		$ticket_unique_id = get_post_meta( $ticket_id, '_unique_id', true );
		$ticket_id        = $ticket_unique_id === '' ? $ticket_id : $ticket_unique_id;

		// If the attendee was deleted.
		if ( false === $ticket_status || 'trash' === $ticket_status ) {

			echo '<div class="error"><p>';
				printf( esc_html__( 'The ticket with ID %s was deleted and cannot be checked-in.', 'event-tickets-plus' ), esc_html( $ticket_id ) );
			echo '</p></div>';

		// If Security Code does not match
		} elseif ( $no_match ) {
			echo '<div class="error"><p>';
				printf( esc_html__( 'The security code for ticket with ID %s does not match.', 'event-tickets-plus' ), esc_html( $ticket_id ) );
			echo '</p></div>';

		// If status is QR then display already checked-in warning.
		} elseif ( $checked_status ) {

			echo '<div class="error"><p>';
				printf( esc_html__( 'The ticket with ID %s has already been checked in.', 'event-tickets-plus' ), esc_html( $ticket_id ) );
			echo '</p></div>';

		// Otherwise, just check-in like normal.
		} else {

			echo '<div class="updated"><p>';
				printf( esc_html__( 'The ticket with ID %s was checked in.', 'event-tickets-plus' ), esc_html( $ticket_id ) );
			echo '</p></div>';

			// Update the checked-in status when using the QR code here.
			update_post_meta( absint( $_GET['qr_checked_in'] ), '_tribe_qr_status', 1 );
		}
	}

	/**
	 * Generates the QR image, stores is locally and injects it into the tickets email
	 *
	 * @param $ticket array
	 *
	 * @return string
	 */
	public function inject_qr( $ticket ) {
		// if gzuncompress doesn't exist, we can't render QR codes
		if ( ! function_exists( 'gzuncompress' ) ) {
			tribe( 'logger' )->log_warning( __( 'Could not render QR code because gzuncompress() is not available', 'event-tickets-plus' ), __CLASS__ );
			return;
		}

		$enabled = tribe_get_option( 'tickets-enable-qr-codes', true );

		/**
		 * Filters the QR enabled value
		 *
		 * @since 4.8.2
		 *
		 * @param bool   $enabled       The bool that comes from the options
		 * @param array  $ticket        The ticket
		 */
		$enabled = apply_filters( 'tribe_tickets_plus_qr_enabled', $enabled, $ticket );

		if ( empty( $enabled ) ) {
			return;
		}

		$link = $this->_get_link( $ticket['qr_ticket_id'], $ticket['event_id'], $ticket['security_code'] );
		$qr   = $this->_get_image( $link );

		if ( ! $qr ) {
			return;
		}

		// echo QR template for email
		tribe_tickets_get_template_part( 'tickets-plus/email-qr', null, array( 'qr' => $qr ), true );
	}


	/**
	 * Generates the link for the QR image
	 *
	 * @param $ticket_id
	 * @param $event_id
	 *
	 * @return string
	 */
	private function _get_link( $ticket_id, $event_id, $security_code ) {

		/**
		 * Allows filtering the base URL which QR code query args are appended to. Defaults to
		 * the site's home_url() with a trailing slash.
		 *
		 * @since 4.7.3
		 *
		 * @param string $url
		 * @param int $ticket_id
		 * @param int $event_id
		 */
		$base_url = apply_filters( 'tribe_tickets_qr_code_base_url', home_url( '/' ), $ticket_id, $event_id );

		$url = add_query_arg( 'event_qr_code', 1, $base_url );
		$url = add_query_arg( 'ticket_id', $ticket_id, $url );
		$url = add_query_arg( 'event_id', $event_id, $url );
		$url = add_query_arg( 'security_code', $security_code, $url );

		// add REST API QR Endpoint Path
		if ( function_exists( 'tribe_tickets_rest_url_prefix' ) ) {
			$url = add_query_arg( 'path', urlencode( tribe_tickets_rest_url_prefix() . '/qr' ), $url );
		}

		return $url;
	}

	/**
	 * Generates the QR image for a given link and stores it in /wp-content/uploads.
	 * Returns the link to the new image.
	 *
	 * @param $link
	 *
	 * @return string
	 */
	private function _get_image( $link ) {
		if ( ! function_exists( 'ImageCreate' ) ) {
			// The phpqrcode library requires GD but doesn't actually check if it is available
			return null;
		}
		if ( ! class_exists( 'QRencode' ) ) {
			include_once( EVENT_TICKETS_PLUS_DIR . '/vendor/phpqrcode/qrlib.php' );
		}

		$uploads   = wp_upload_dir();
		$file_name = 'qr_' . md5( $link ) . '.png';
		$path      = trailingslashit( $uploads['path'] ) . $file_name;
		$url       = trailingslashit( $uploads['url'] ) . $file_name;

		if ( ! file_exists( $path ) ) {
			QRcode::png( $link, $path, QR_ECLEVEL_L, 3 );
		}

		return $url;
	}

	/**
	 * Checks the user in, for all the *Tickets modules running.
	 *
	 * @param $ticket_id
	 */
	private function _check_in( $ticket_id ) {
		$modules = Tribe__Tickets__Tickets::modules();

		foreach ( $modules as $class => $module ) {
			if ( ! is_callable( array( $class, 'get_instance' ) ) ) {
				continue;
			}
			$obj = call_user_func( array( $class, 'get_instance' ) );
			$obj->checkin( $ticket_id, false );
		}
	}
}
