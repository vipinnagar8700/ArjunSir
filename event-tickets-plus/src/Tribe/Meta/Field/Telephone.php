<?php

/**
 * Class Tribe__Tickets_Plus__Meta__Field__Telephone
 *
 * Adds a Telephone field to RSVP and Tickets.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Meta__Field__Telephone extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'telephone';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Telephone', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'telephone';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
