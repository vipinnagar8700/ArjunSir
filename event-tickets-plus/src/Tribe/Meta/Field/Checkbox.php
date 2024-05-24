<?php

class Tribe__Tickets_Plus__Meta__Field__Checkbox extends Tribe__Tickets_Plus__Meta__Field__Abstract_Options_Field {
	public $type = 'checkbox';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Checkbox', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'checkbox';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
