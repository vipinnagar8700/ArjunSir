<?php


/**
 * Class Tribe__Tickets_Plus__CSV_Importer__Tickets_Importer
 */
abstract class Tribe__Tickets_Plus__CSV_Importer__Tickets_Importer extends Tribe__Events__Importer__File_Importer {

	/**
	 * @var array
	 */
	protected $required_fields = array( 'event_name', 'ticket_name' );

	/**
	 * @var array
	 */
	protected static $event_name_cache = array();

	/**
	 * @var array
	 */
	protected static $ticket_name_cache = array();

	/**
	 * @var Tribe__Tickets__Tickets
	 */
	protected $tickets;

	/**
	 * @var bool|string
	 */
	protected $row_message = false;

	/**
	 * The class constructor proxy method to get a WooCommerce based tickets importer.
	 *
	 * @param Tribe__Events__Importer__File_Importer|null $instance The default instance that would be used for the type.
	 * @param Tribe__Events__Importer__File_Reader        $file_reader
	 *
	 * @return Tribe__Tickets_Plus__CSV_Importer__Woo_Tickets_Importer
	 */
	public static function woo_instance( $instance, Tribe__Events__Importer__File_Reader $file_reader ) {
		return new Tribe__Tickets_Plus__CSV_Importer__Woo_Tickets_Importer( $file_reader );
	}

	/**
	 * Resets that class static caches
	 */
	public static function reset_cache() {
		self::$event_name_cache  = array();
		self::$ticket_name_cache = array();
	}

	/**
	 * Tribe__Tickets__CSV_Importer__RSVP_Importer constructor.
	 *
	 * @param Tribe__Events__Importer__File_Reader                  $file_reader
	 * @param Tribe__Events__Importer__Featured_Image_Uploader|null $featured_image_uploader
	 * @param Tribe__Tickets__Tickets|null                          $tickets
	 */
	public function __construct( Tribe__Events__Importer__File_Reader $file_reader, Tribe__Events__Importer__Featured_Image_Uploader $featured_image_uploader = null, Tribe__Tickets__Tickets $tickets = null ) {
		parent::__construct( $file_reader, $featured_image_uploader );
		$this->tickets = ! empty( $tickets ) ? $tickets : $this->get_commerce_engine_instance();
	}

	/**
	 * @param array $record
	 *
	 * @return bool
	 */
	public function match_existing_post( array $record ) {
		$event = $this->get_event_from( $record );

		if ( empty( $event ) ) {
			return false;
		}

		$ticket_name = $this->get_value_by_key( $record, 'ticket_name' );
		$cache_key   = $ticket_name . '-' . $event->ID;

		if ( isset( self::$ticket_name_cache[ $cache_key ] ) ) {
			return self::$ticket_name_cache[ $cache_key ];
		}

		$ticket_post = get_page_by_title( $ticket_name, OBJECT, $this->tickets->ticket_object );
		if ( empty( $ticket_post ) ) {
			return false;
		}

		$ticket = $this->tickets->get_ticket( $event->ID, $ticket_post->ID );

		$match = $ticket->get_event() == $event ? true : false;

		self::$ticket_name_cache[ $cache_key ] = $match;

		return $match;
	}

	/**
	 * @param       $post_id
	 * @param array $record
	 */
	public function update_post( $post_id, array $record ) {
		// nothing is updated in existing tickets
		$engine = $this->get_commerce_engine_instance();

		if ( $this->is_aggregator && ! empty( $this->aggregator_record ) ) {
			$this->aggregator_record->meta['activity']->add( $engine->ticket_object, 'skipped', $post_id );
		}
	}

	/**
	 * @param array $record
	 *
	 * @return int|bool Either the new RSVP ticket post ID or `false` on failure.
	 */
	public function create_post( array $record ) {
		$event = $this->get_event_from( $record );

		/**
		 * Add an opportunity for the user to change the values for the created ticket via the CSV import.
		 *
		 * @since 4.7.3
		 *
		 * @param array $data The data for the new created ticket.
		 */
		$data = (array) apply_filters( 'tribe_tickets_plus_import_ticket_data', $this->get_ticket_data_from( $record ) );

		$ticket_id = $this->tickets->ticket_add( $event->ID, $data );

		$ticket_name = $this->get_value_by_key( $record, 'ticket_name' );
		$cache_key   = $ticket_name . '-' . $event->ID;

		self::$ticket_name_cache[ $cache_key ] = true;

		$engine = $this->get_commerce_engine_instance();
		if ( $this->is_aggregator && ! empty( $this->aggregator_record ) ) {
			$this->aggregator_record->meta['activity']->add( $engine->ticket_object, 'created', $ticket_id );
		}

		return $ticket_id;
	}

	/**
	 * @param array $record
	 *
	 * @return bool|WP_Post
	 */
	protected function get_event_from( array $record ) {
		$event_name = $this->get_value_by_key( $record, 'event_name' );

		if ( empty( $event_name ) ) {
			return false;
		}

		if ( isset( self::$event_name_cache[ $event_name ] ) ) {
			return self::$event_name_cache[ $event_name ];
		}

		// by title
		$event = get_page_by_title( $event_name, OBJECT, Tribe__Events__Main::POSTTYPE );
		if ( empty( $event ) ) {
			// by slug
			$event = get_page_by_path( $event_name, OBJECT, Tribe__Events__Main::POSTTYPE );
		}
		if ( empty( $event ) ) {
			// by ID
			$event = get_post( $event_name );
		}

		$event = ! empty( $event ) ? $event : false;

		self::$event_name_cache[ $event_name ] = $event;

		return $event;
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function get_ticket_data_from( array $record ) {
		$data                       = array();
		$data['ticket_name']        = $this->get_value_by_key( $record, 'ticket_name' );
		$data['ticket_description'] = $this->get_value_by_key( $record, 'ticket_description' );
		$data['ticket_start_date']  = $this->get_value_by_key( $record, 'ticket_start_sale_date' );
		$data['ticket_end_date']    = $this->get_value_by_key( $record, 'ticket_end_sale_date' );

		$show_description = trim( (string) $this->get_value_by_key( $record, 'ticket_show_description' ) );

		if ( tribe_is_truthy( $show_description ) ) {
			$data['ticket_show_description'] = $show_description;
		}

		$ticket_start_sale_time = $this->get_value_by_key( $record, 'ticket_start_sale_time' );

		if ( ! empty( $data['ticket_start_date'] ) && ! empty( $ticket_start_sale_time ) ) {
			$start_date = new DateTime( $data['ticket_start_date'] . ' ' . $ticket_start_sale_time );

			$data['ticket_start_meridian'] = $start_date->format( 'A' );
			$data['ticket_start_time']     = $start_date->format( 'H:i:00' );
		}

		$ticket_end_sale_time = $this->get_value_by_key( $record, 'ticket_end_sale_time' );

		if ( ! empty( $data['ticket_end_date'] ) && ! empty( $ticket_end_sale_time ) ) {
			$end_date = new DateTime( $data['ticket_end_date'] . ' ' . $ticket_end_sale_time );

			$data['ticket_end_meridian'] = $end_date->format( 'A' );
			$data['ticket_end_time']     = $end_date->format( 'H:i:00' );
		}

		$price = $this->get_value_by_key( $record, 'ticket_price' );

		if ( '' !== $price ) {
			$data['ticket_price'] = $price;
		}

		$data = $this->modify_data( $record, $data );

		return $data;
	}

	/**
	 * @param array $record
	 *
	 * @return bool
	 */
	public function is_valid_record( array $record ) {
		$valid = parent::is_valid_record( $record );
		if ( empty( $valid ) ) {
			return false;
		}

		$event = $this->get_event_from( $record );

		if ( empty( $event ) ) {
			return false;
		}

		if ( function_exists( 'tribe_is_recurring_event' ) ) {
			$is_recurring = tribe_is_recurring_event( $event->ID );

			if ( $is_recurring ) {
				$this->row_message = sprintf( esc_html__( 'Recurring event tickets are not supported, event %d.', 'event-tickets-plus' ), $event->post_title );
			}

			return ! $is_recurring;
		}
		$this->row_message = false;

		return true;
	}

	/**
	 * @param $row
	 *
	 * @return string
	 */
	protected function get_skipped_row_message( $row ) {
		return $this->row_message === false ? parent::get_skipped_row_message( $row ) : $this->row_message;
	}

	/**
	 * Returns an instance of the commerce provider.
	 *
	 * This method should be overridden in extending classes.
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Main
	 */
	abstract protected function get_commerce_engine_instance();

	/**
	 * Modify the data for the specific commerce engine ticket implementation.
	 *
	 * This method should be overridden in extending classes.
	 *
	 * @param array  $record
	 * @param  array $data
	 *
	 * @return array
	 */
	abstract protected function modify_data( array $record, array $data );
}
