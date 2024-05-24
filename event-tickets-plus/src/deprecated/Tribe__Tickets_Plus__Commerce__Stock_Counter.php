<?php
_deprecated_file( __FILE__, '4.6', 'Tribe__Tickets_Plus__Commerce__Stock_Counter' );

/**
 * @deprecated 4.6
 */
class Tribe__Tickets_Plus__Commerce__Stock_Counter {
	public function __get( $unused ) {}
	public function __set( $unused_a, $unused_b ) {}
	public function __call( $unused_a, $unused_b ) {}

	public function get_total_for( $event ) {
		return tribe( 'tickets-plus.main' )->apm_filters()->stock_filter()->get_total_value( $event );
	}
}