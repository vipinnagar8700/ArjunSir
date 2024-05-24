<?php

/**
 * Class Tribe__Tickets_Plus__Meta__Field__Email
 *
 * Adds an Email field to RSVP and Tickets.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Meta__Field__Email extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'email';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Email', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'email';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
