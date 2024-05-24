<?php

/**
 * Class Tribe__Tickets_Plus__Meta__Field__Number
 *
 * Adds a Number field to RSVP and Tickets.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Meta__Field__Number extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'number';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Number', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'number';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
