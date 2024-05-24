<?php


class Tribe__Tickets_Plus__Meta__Unique_ID {

	/**
	 * @var string
	 */
	protected $progressive_ticket_number_event_meta_key = '_tribe_progressive_ticket_current_number';

	/**
	 * @var string
	 */
	protected $unique_id_meta_key = '_unique_id';

	/**
	 * @var string
	 */
	protected $root_meta_key = '_tribe_post_root';

	/**
	 * @var Tribe__Utils__Post_Root_Pool
	 */
	private $pool;

	/**
	 * @var static
	 */
	protected static $instance;

	/**
	 * @return Tribe__Tickets_Plus__Meta__Unique_ID
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self( new Tribe__Utils__Post_Root_Pool() );
		}

		return self::$instance;
	}

	/**
	 * Tribe__Tickets_Plus__Meta__Unique_ID constructor.
	 *
	 * @param Tribe__Utils__Post_Root_Pool $pool
	 */
	public function __construct( Tribe__Utils__Post_Root_Pool $pool ) {
		$this->pool = $pool;
	}

	/**
	 * Calculates and appends a unique ID to a ticket.
	 *
	 * @param  int $attendee_id
	 * @param  int $event_id
	 */
	public function assign_unique_id( $attendee_id, $event_id ) {
		$this->maybe_prime_pool();

		$event_root         = $this->get_event_root( $event_id );
		$next_ticket_number = $this->get_next_ticket_number( $event_id );
		$progressive_number = $event_root . $next_ticket_number . '-' . $this->generate_alphanumeric_id();

		update_post_meta( $attendee_id, $this->unique_id_meta_key, $progressive_number );

		return $progressive_number;
	}

	/**
	 * Generates an ID with alphanumeric characters on it.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	protected function generate_alphanumeric_id( $length = 6 ) {

		$batch = array_merge( range( '0', '9' ), range( 'A', 'Z' ) );

		shuffle( $batch );
		$id = '';
		$keys = array_rand( $batch, $length );
		foreach ( $keys as $index ) {
			$id .= $batch[ $index ];
		}
		return $id;
	}

	protected function maybe_prime_pool() {
		if ( ! $this->pool->is_primed() ) {
			/** @var \wpdb $wpdb */
			global $wpdb;
			$query = $wpdb->prepare( "SELECT pm.meta_value as 'root', p.ID as 'ID'
				FROM $wpdb->posts p 
				JOIN $wpdb->postmeta pm 
				ON p.ID = pm.post_id 
				WHERE pm.meta_key = %s",
				$this->root_meta_key
			);

			// Fetch from DB and array_filter to remove any empty results
			$all_roots = array_filter( $wpdb->get_results( $query ) );

			// If there are No Roots just leave
			if ( empty( $all_roots ) ) {
				return false;
			}

			// Creates an Array with Root values as keys and Post IDs as Values
			$all_roots = array_combine( wp_list_pluck( $all_roots, 'root' ), wp_list_pluck( $all_roots, 'ID' ) );

			// Sets the Pool based on the array above
			$this->pool->set_pool( $all_roots, true );
		}
	}

	/**
	 * @param int $event_id
	 *
	 * @return string
	 */
	protected function get_event_root( $event_id ) {
		$event_root = get_post_meta( $event_id, $this->root_meta_key, true );
		if ( empty( $event_root ) ) {
			$event_post = get_post( $event_id );
			$event_root = $this->pool->generate_unique_root( $event_post );
			update_post_meta( $event_id, $this->root_meta_key, $event_root );
		}

		return $event_root;
	}

	/**
	 * @param $event_id
	 *
	 * @return int
	 */
	protected function get_next_ticket_number( $event_id ) {
		global $wpdb;

		// We wrap this in a transaction to avoid race conditions leading to different
		// tickets being issued the same ticket number
		$wpdb->query( 'BEGIN WORK' );

		if ( '' === ( $number = get_post_meta( $event_id, $this->progressive_ticket_number_event_meta_key, true ) ) ) {
			/**
			 * Sets the initial value used to start a sequence of ticket numbers.
			 *
			 * By default this is zero and so the first number in the sequence will be 1, however
			 * it could be changed to 999 if it was desirable to have 1000 as the first generated
			 * number.
			 *
			 * The number must be an absolute integer and it will be passed through absint() to
			 * enforce this.
			 *
			 * @param integer $initial_ticket_number
			 */
			$number = absint( apply_filters( 'tribe_tickets_plus_inital_ticket_number', 0 ) );
		}
		$number += 1;
		update_post_meta( $event_id, $this->progressive_ticket_number_event_meta_key, $number );

		$wpdb->query( 'COMMIT' );

		return $number;
	}

	/**
	 * @return string
	 */
	public function get_root_meta_key() {
		return $this->root_meta_key;
	}
}