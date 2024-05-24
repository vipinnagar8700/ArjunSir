var tribe_event_tickets_plus = tribe_event_tickets_plus || {};
tribe_event_tickets_plus.meta = tribe_event_tickets_plus.meta || {};
tribe_event_tickets_plus.meta.admin = tribe_event_tickets_plus.meta.admin || {};
tribe_event_tickets_plus.meta.admin.event = tribe_event_tickets_plus.meta.admin.event || {};

(function ( window, document, $, my ) {
	'use strict';

	/**
	 * Initializes the meta functionality
	 */
	my.init = function() {
		my.$tribe_tickets = $( document.getElementById( 'tribetickets' ) );
		my.$event_tickets = $( document.getElementById( 'event_tickets' ) );

		my.$event_tickets
			.on( 'change', 'input.show_attendee_info', my.event.toggle_linked_form )
			.on( 'change', '.save_attendee_fieldset', my.event.toggle_linked_form );

		my.$tribe_tickets
			.on( 'change', '.ticket-attendee-info-dropdown', my.event.select_saved_fieldset )
			.on( 'change', '.save_attendee_fieldset', my.event.toggle_linked_form )
			.on( 'click', '.meta-postbox .hndle, .meta-postbox .handlediv', my.event.click_postbox )
			.on( 'click', 'a.add-attendee-field', my.event.add_field )
			.on( 'click', 'a.delete-attendee-field', my.event.remove_field )
			.on( 'edit-ticket.tribe', my.event.edit_ticket )
			.on( 'saved-ticket.tribe', my.event.saved_ticket );

		my.init_ticket_fields();

		my.$event_tickets.trigger( 'event-tickets-plus-meta-initialized.tribe' );
	};

	/**
	 * Sets up the custom meta field area for the ticket form
	 */
	my.init_ticket_fields = function() {
		if ( ! my.$event_tickets ) {
			my.$event_tickets = $( document.getElementById( 'event_tickets' ) );
		}

		my.init_custom_field_sorting();
		my.maybe_hide_saved_fields_select();
		my.$event_tickets.trigger( 'event-tickets-plus-ticket-meta-initialized.tribe', {
			ticket_id: my.$event_tickets.find( '#ticket_id' ).val()
		} );
	};

	/**
	 * Initializes the sortable area for custom fields
	 */
	my.init_custom_field_sorting = function() {
		$( document.getElementById( 'tribe-tickets-attendee-sortables' ) ).sortable( {
			containment: 'parent',
			items: '> div',
			tolerance: 'pointer',
			connectWith: '#tribe-tickets-attendee-sortables'
		} );
	};

	/**
	 * Toggles a form linked to a checkbox
	 *
	 * Forms (or containers with form fields) are linked to a checkbox via an
	 * ID/data-tribe-toggle relationship. The checkbox has the data-tribe-toggle
	 * attribute that corresponds to the HTML element that will be toggled open or
	 * closed based on the checkbox state.
	 *
	 * Checked == open
	 * Unchecked == closed
	 *
	 * @param jQuery $checkbox Checkbox input field
	 */
	my.toggle_linked_form = function( $checkbox ) {
		var $form = $();
		var form_id = $checkbox.data( 'tribe-toggle' );

		if ( form_id ) {
			$form = $( document.getElementById( form_id ) );
		}

		if ( $checkbox.is( ':checked' ) ) {
			$form.show();
		} else {
			$form.hide();
		}
	};

	/**
	 * hide the saved fields selection if there are active fields
	 */
	my.maybe_hide_saved_fields_select = function() {
		if ( $( '.tribe-tickets-attendee-info-active-field' ).length ) {
			$('.tribe-tickets-attendee-saved-fields' ).hide();
		} else {
			$('.tribe-tickets-attendee-saved-fields' ).show();
		}
	};

	/**
	 * Fetches saved fields via AJAX
	 *
	 * @param int saved_fieldset_id Fieldset ID to fetch via AJAX
	 * @return jqXHR
	 */
	my.fetch_saved_fields = function( saved_fieldset_id ) {
		//load the saved fieldset
		var args = {
			action: 'tribe-tickets-load-saved-fields',
			fieldset: saved_fieldset_id
		};

		return $.post(
			ajaxurl,
			args,
			'json'
		);
	};

	/**
	 * Injects a saved fieldset into the custom meta field area
	 *
	 * @param int saved_fieldset_id Fieldset ID to inject
	 */
	my.inject_saved_fields = function( saved_fieldset_id ) {
		var field_jqxhr = my.fetch_saved_fields( saved_fieldset_id );

		field_jqxhr.done( function( response ) {
			if ( ! response.success ) {
				my.$event_tickets.trigger( 'event-tickets-plus-fieldset-load-failure.tribe', { fieldset_id: saved_fieldset_id } );
				return;
			}

			$( document.getElementById( 'tribe-tickets-attendee-sortables' ) ).append( response.data );
			my.maybe_hide_saved_fields_select();

			my.$event_tickets.trigger( 'event-tickets-plus-fieldset-loaded.tribe', { fieldset_id: saved_fieldset_id } );
		} );

		field_jqxhr.fail( function() {
			my.$event_tickets.trigger( 'event-tickets-plus-fieldset-load-failure.tribe', { fieldset_id: saved_fieldset_id } );
		} );
	};

	/**
	 * Adds a custom meta field to the custom meta field area
	 *
	 * @param string type Type of field to add
	 */
	my.add_field = function( type ) {
		var args = {
			action: 'tribe-tickets-info-render-field',
			type: type
		};

		var jqxhr = $.post(
			ajaxurl,
			args,
			'json'
		);

		jqxhr.done( function( response ) {
			if ( ! response.success ) {
				my.$event_tickets.trigger( 'event-tickets-plus-field-add-failure.tribe', { type: type } );
				return;
			}

			$( document.getElementById( 'tribe-tickets-attendee-sortables' ) ).append( response.data );
			my.maybe_hide_saved_fields_select();
			my.$event_tickets.trigger( 'event-tickets-plus-field-added.tribe', { type: type } );
		} );

		jqxhr.fail( function() {
			my.$event_tickets.trigger( 'event-tickets-plus-field-add-failure.tribe', { type: type } );
		} );
	};

	/**
	 * Removes a custom meta field from the custom meta field area
	 *
	 * @param jQuery $field Field to remove
	 */
	my.remove_field = function( $field ) {
		var field_html = '';

		if ( 'undefined' !== $field[0].outerHTML ) {
			field_html = $field[0].outerHTML;
		} else {
			field_html = $( '<div>' ).append( $field.eq( 0 ).clone() ).html();
		}

		$field.remove();

		my.maybe_hide_saved_fields_select();
		my.$event_tickets.trigger( 'event-tickets-plus-field-removed.tribe', { field: field_html } );
	};

	/**
	 * Toggles the visibility of an element with WordPress postbox behaviors attached to it
	 *
	 * @param jQuery $postbox Element with the .postbox class to toggle visibility on
	 */
	my.toggle_postbox = function( $postbox ) {
		$postbox.toggleClass( 'closed' );
	};

	/**
	 * event to handle the toggling of a linked form
	 */
	my.event.toggle_linked_form = function() {
		my.toggle_linked_form( $( this ) );
	};

	/**
	 * event to handle injecting a saved fieldset into the custom meta field form
	 */
	my.event.select_saved_fieldset = function() {
		var saved_fieldset_id = $( this ).val();

		if ( ! saved_fieldset_id || 0 === parseInt( saved_fieldset_id, 10 ) ) {
			return;
		}

		my.inject_saved_fields( saved_fieldset_id );
	};

	/**
	 * event to handle the clicking of an add-field option
	 */
	my.event.add_field = function( e ) {
		e.preventDefault();

		my.add_field( $( this ).data( 'type' ) );
	};

	/**
	 * event to handle the clicking of the remove field link on a custom field
	 */
	my.event.remove_field = function( e ) {
		e.preventDefault();
		my.remove_field( $( this ).closest( '.meta-postbox' ) );
	};

	/**
	 * event to handle initializing the ticket area when editing an event ticket
	 */
	my.event.edit_ticket = function() {
		my.init_ticket_fields();
	};

	/**
	 * Toggles an element with the WordPress postbox behaviors tied to it via the .postbox and
	 * associated classes
	 */
	my.event.click_postbox = function() {
		my.toggle_postbox( $( this ).closest( '.meta-postbox' ) );
	};

	my.event.saved_ticket = function( e, response ) {
		if ( 'undefined' === typeof response.fieldsets || ! response.success ) {
			return;
		}

		var $fieldsets = $( document.getElementById( 'saved_ticket-attendee-info' ) );
		$fieldsets.find( 'option:not([value="0"])' ).remove();

		for ( var i = 0; i < response.fieldsets.length; i++ ) {
			$fieldsets.append( '<option value="' + response.fieldsets[ i ].ID + '" data-attendee-group="' + response.fieldsets[ i ].post_name.replace( '"', '\\"' ) + '">' + response.fieldsets[ i ].post_name + '</option>' );
		}
	};

	$( function() {
		my.init();
	} );
} )( window, document, jQuery, tribe_event_tickets_plus.meta.admin );
