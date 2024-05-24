var tribe_event_tickets_plus = tribe_event_tickets_plus || {};
tribe_event_tickets_plus.meta = tribe_event_tickets_plus.meta || {};
tribe_event_tickets_plus.meta.report = tribe_event_tickets_plus.meta.report || {};
tribe_event_tickets_plus.meta.report.event = tribe_event_tickets_plus.meta.report.event || {};

(function ( window, document, $, my ) {
	'use strict';

	/**
	 * Initializes the meta functionality
	 */
	my.init = function() {
		$( '.wp-list-table.attendees' ).on( 'click', '.event-tickets-meta-toggle', this.event.toggle_meta_view );
		$( window ).on( 'attendees-report-before-print.tribe-tickets', this.toggle_meta_rows );
		$( window ).on( 'attendees-report-before-print.tribe-tickets', this.toggle_meta_rows );
	};

	/**
	 * Toggles the visibility of the view/hide meta data links and the meta data
	 * row itself.
	 */
	my.toggle_meta_view = function( $row ) {
		$row.toggleClass( 'event-tickets-meta-toggle-open' );
	};

	/**
	 * Toggles visibility of the meta data row and sets its colspan
	 * attribute to the correct value.
	 *
	 * @param $parent_row
	 * @param $meta_row
	 */
	my.toggle_meta_row = function( $parent_row, $meta_row ) {
		my.toggle_meta_view( $meta_row );

		var column_count = tribe_event_tickets_attendees.count_columns( $parent_row );
		var $meta_cell   = $meta_row.find( 'td' );

		// We reduce the column count by one because we expect a <th> to be present
		$meta_cell.attr( 'colspan', column_count - 1 );
	};

	/**
	 * Event to handle the toggling of an attendee's meta data open/closed
	 */
	my.event.toggle_meta_view = function( e ) {
		e.preventDefault();

		var $this = $( this );
		var $closest_row = $this.closest( 'tr' );
		var $next_meta_rows = $this.parents( 'tr' ).nextAll( 'tr.event-tickets-meta-row' );

		if ( ! $next_meta_rows.length ) {
			return;
		}

		my.toggle_meta_view( $closest_row );
		my.toggle_meta_row( $closest_row, $next_meta_rows.first() );
	};

	my.toggle_meta_rows = function () {
		var $rows = $( 'table.wp-list-table.attendees' ).find( 'tr' ).has( 'a.event-tickets-meta-toggle' );
		// show all details
		$rows.each( function () {
			var $this = $( this );
			my.toggle_meta_view( $this );
			my.toggle_meta_row( $this, $this.next( 'tr.event-tickets-meta-row' ).first() );
		} );
	};

	$( function() {
		my.init();
	} );
} )( window, document, jQuery, tribe_event_tickets_plus.meta.report );
