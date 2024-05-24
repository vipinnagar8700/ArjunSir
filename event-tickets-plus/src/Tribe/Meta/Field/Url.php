<?php

/**
 * Class Tribe__Tickets_Plus__Meta__Field__Url
 *
 * Adds a Url field to RSVP and Tickets.
 *
 * @since 4.12.1
 */
class Tribe__Tickets_Plus__Meta__Field__Url extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'url';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'URL', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'url';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
