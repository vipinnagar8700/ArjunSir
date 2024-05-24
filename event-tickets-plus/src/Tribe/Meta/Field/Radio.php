<?php

class Tribe__Tickets_Plus__Meta__Field__Radio extends Tribe__Tickets_Plus__Meta__Field__Abstract_Options_Field {
	public $type = 'radio';

	/**
	 * @inheritDoc
	 */
	public static function get_name() {
		return _x( 'Radio', 'Attendee Information Field Name', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_identifier() {
		return 'radio';
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
