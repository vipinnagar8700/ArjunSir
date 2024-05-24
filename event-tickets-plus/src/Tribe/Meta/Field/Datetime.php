<?php

/**
 * Class Tribe__Tickets_Plus__Meta__Field__Datetime
 *
 * Adds a Date field to RSVP and Tickets.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Meta__Field__Datetime extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'datetime';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Date', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'datetime';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
