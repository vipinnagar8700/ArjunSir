<?php

class Tribe__Tickets_Plus__Meta__Field__Select extends Tribe__Tickets_Plus__Meta__Field__Abstract_Options_Field {
	public $type = 'select';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Dropdown', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'select';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
