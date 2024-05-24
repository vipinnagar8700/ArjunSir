var tribe_event_tickets_plus = tribe_event_tickets_plus || {};
tribe_event_tickets_plus.meta = tribe_event_tickets_plus.meta || {};
tribe_event_tickets_plus.meta.event = tribe_event_tickets_plus.meta.event || {};

(function ( window, document, $, my ) {
	'use strict';

	my.selectors = {
		field: {
			text: '.tribe-tickets-meta-text',
			checkbox: '.tribe-tickets-meta-checkbox',
			select: '.tribe-tickets-meta-select',
			radio: '.tribe-tickets-meta-radio',
			number: '.ticket-meta-number-field',
		},
		quantity: 'input.tribe-tickets-quantity',
		name: 'input.tribe-tickets-full-name',
		email: 'input.tribe-tickets-email',
		horizontal_datepicker: {
			container: '.tribe_horizontal_datepicker__container',
			select: '.tribe_horizontal_datepicker__container select',
			day: '.tribe_horizontal_datepicker__day',
			month: '.tribe_horizontal_datepicker__month',
			year: '.tribe_horizontal_datepicker__year',
			value: '.tribe_horizontal_datepicker__value',
		},
	};

	my.horizontal_datepicker = {};

	/**
	 * Initializes the meta functionality
	 */
	my.init = function() {
		$( '.tribe-list' ).on( 'click', '.attendee-meta.toggle', function() {
			$( this )
				.toggleClass( 'on' )
				.siblings( '.attendee-meta-row' )
				.slideToggle();
		});

		this.$ticket_form = $( '.tribe-events-tickets' ).closest( '.cart' );

		this.$ticket_form
			.on( 'change', '.tribe-tickets-order_status-row select', this.event.status_changed )
			.on( 'change', '.quantity input, .quantity select', this.event.quantity_changed )
			.on( 'keyup input', '.quantity input', this.event.quantity_changed );

		this.$ticket_form.find( '.quantity input:not([type="hidden"]), .quantity select' ).each( function() {
			my.set_quantity( $( this ) );
		} );

		$( '.tribe-event-tickets-plus-meta-fields' ).on( 'keydown', '.tribe-tickets-meta-number input', this.event.limit_number_field_typing );

		// Block editor FE event handlers
		this.$rsvp_block = $( '.tribe-block__rsvp' );

		this.$rsvp_block
			.on( 'change', '.tribe-block__rsvp__number-input input', this.event.block_quantity_changed )
			.on( 'keyup', '.tribe-block__rsvp__number-input input', this.event.block_quantity_changed );

		this.horizontal_datepicker.populateSelects();
	};

	my.render_fields = function( ticket_id, num ) {
		var $row = $( '.tribe-event-tickets-plus-meta' ).filter( '[data-ticket-id="' + ticket_id + '"]' );
		var $template = $row.find( '.tribe-event-tickets-plus-meta-fields-tpl' );
		var $fields = $row.find( '.tribe-event-tickets-plus-meta-fields' );
		var template_html = $template.html();

		if ( ! my.has_meta_fields( ticket_id ) ) {
			return;
		}

		var current_count = $fields.find( '.tribe-event-tickets-plus-meta-attendee' ).length;
		var diff = 0;

		if ( current_count > num ) {
			diff = current_count - num;

			$fields.find( '.tribe-event-tickets-plus-meta-attendee:nth-last-child(-n+' + diff + ')' ).remove();
			return;
		}

		diff = num - current_count;

		var i = 0;
		for ( ; i < diff; i++ ) {
			var tweaked_template_html = template_html;
			tweaked_template_html = template_html.replace( /tribe-tickets-meta\[\]/g, 'tribe-tickets-meta[' + ticket_id + '][' + ( current_count + i ) + ']' );
			tweaked_template_html = tweaked_template_html.replace( /tribe-tickets-meta_([a-z0-9\-]+)_/g, 'tribe-tickets-meta_$1_' + ( current_count + i ) + '_' );
			$fields.append( tweaked_template_html );
		}
	};

	my.set_quantity = function( $field ) {
		var going = $field.closest( 'form' ).find( '.tribe-tickets-order_status-row select' ).val() === 'yes';

		if ( going ) {
			var quantity = parseInt( $field.val(), 10 );
			var ticket_id = parseInt( $field.closest( 'td' ).data( 'product-id' ), 10 );

			my.render_fields( ticket_id, quantity );
		}
	};

	/**
	 * Set quantity for RSVP block
	 */
	my.block_set_quantity = function( $field, going ) {
		var quantity = going ? parseInt( $field.val(), 10 ) : 0;
		var ticket_id = parseInt( $field.closest( 'form' ).data( 'product-id' ), 10 );

		my.render_fields( ticket_id, quantity );
	};

	my.has_meta_fields = function( ticket_id ) {
		var template_html = $( document.getElementById( 'tribe-event-tickets-plus-meta-fields-tpl-' + ticket_id ) ).html();
		return !! $( template_html ).find( '.tribe-tickets-meta' ).length;
	};

	/**
	 * Validates the required fields for attendee registration in RSVP block
	 */
	my.validate_meta = function( $form ) {
		var is_valid = true;
		var $fields = $form.find( '.tribe-tickets-meta-required' );

		$fields.each( function() {
			var $field = $( this );
			var val = '';

			if (
				$field.is( my.selectors.field.radio )
				|| $field.is( my.selectors.field.checkbox )
			) {
				val = $field.find( 'input:checked' ).length ? 'checked' : '';
			} else if ( $field.is( my.selectors.field.select ) ) {
				val = $field.find('select').val();
			} else if (
				$field.is( my.selectors.horizontal_datepicker.day ) ||
				$field.is( my.selectors.horizontal_datepicker.month ) ||
				$field.is( my.selectors.horizontal_datepicker.year )
			) {
				val = $field.val();
			} else {
				val = $field.find( 'input, textarea' ).val().trim();
			}

			if ( null === val || 0 === val.length ) {
				is_valid = false;
			}
		} );

		return is_valid;
	};

	my.event.quantity_changed = function() {
		my.set_quantity( $( this ) );
	};

	/**
	 * Event handler for RSVP when status has changed
	 */
	my.event.status_changed = function() {
		var $quantity = my.$ticket_form.find( '.quantity' );
		var going = $( this ).val() === 'yes';
		var ticket_id = parseInt( $quantity.data( 'product-id' ), 10 );
		var quantity = going ? parseInt( $quantity.find( '.tribe-tickets-quantity' ).val(), 10 ) : 0;

		my.render_fields( ticket_id, quantity );
	};

	/**
	 * Event handler for RSVP block when quantity has changed
	 */
	my.event.block_quantity_changed = function() {
		var going = my.$rsvp_block
			.find( '.tribe-block__rsvp__status .tribe-active' )
			.hasClass( 'tribe-block__rsvp__status-button--going' );

		my.block_set_quantity( $( this ), going );
	};

	/**
	 * Ensure that only whole numbers can be entered into the number field
	 */
	my.event.limit_number_field_typing = function( e ) {
		if (
			// Allow: backspace, delete, tab, escape, and enter
			$.inArray( e.keyCode, [ 46, 8, 9, 27, 13, 110 ] ) !== -1 ||
			// Allow: Ctrl+A, Command+A
			( e.keyCode === 65 && ( e.ctrlKey === true || e.metaKey === true ) ) ||
			// Allow: Ctrl+C
			( e.keyCode === 67 && e.ctrlKey === true ) ||
			// Allow: Ctrl+V
			( e.keyCode === 86 && e.ctrlKey === true ) ||
			// Allow: Ctrl+X
			( e.keyCode === 88 && e.ctrlKey === true ) ||
			// Allow: home, end, left, right, down, up
			( e.keyCode >= 35 && e.keyCode <= 40 )
		) {
			// let it happen, don't do anything
			return;
		}
		// Ensure that it is a number and stop the keypress
		if ( ( e.shiftKey || ( e.keyCode < 48 || e.keyCode > 57 ) ) && ( e.keyCode < 96 || e.keyCode > 105 ) ) {
			e.preventDefault();
		}
	};

	/**
	 * Parses the yyyy-mm-dd date from the hidden field that holds
	 * the value, and updates the visible month-day-year select with
	 * the according values.
	 *
	 * @since TBD
	 */
	my.horizontal_datepicker.populateSelects = function() {
		$( my.selectors.horizontal_datepicker.container ).each( function( index, value ) {
			const wrapper = $( value );

			const day = wrapper.find( my.selectors.horizontal_datepicker.day );
			const month = wrapper.find( my.selectors.horizontal_datepicker.month );
			const year = wrapper.find( my.selectors.horizontal_datepicker.year );
			const realValue = wrapper.find( my.selectors.horizontal_datepicker.value );

			const savedValues = realValue.val().split( '-' );

			if ( savedValues.length === 3 ) {
				year.val( savedValues[ 0 ] );
				month.val( savedValues[ 1 ] );
				day.val( savedValues[ 2 ] );
			}
		} );
	};

	/**
	 * Parses the visible month-day-year selects and builds a date in the
	 * yyyy-mm-dd format, which is saved in a hidden field as the actual
	 * value to be sent.
	 *
	 * @since TBD
	 *
	 * @param e
	 */
	my.horizontal_datepicker.populateHiddenValue = function( e ) {
		const wrapper = $( e.target ).closest( my.selectors.horizontal_datepicker.container );
		const day = wrapper.find( my.selectors.horizontal_datepicker.day );
		const month = wrapper.find( my.selectors.horizontal_datepicker.month );
		const year = wrapper.find( my.selectors.horizontal_datepicker.year );
		const realValue = wrapper.find( my.selectors.horizontal_datepicker.value );

		// Data is stored in format: yyyy-mm-dd
		realValue.val( year.val() + '-' + month.val() + '-' + day.val() );
		realValue.trigger( 'change' );
	};

	my.horizontal_datepicker.makeDayMonthYearRequired = function( e ) {
		const hiddenValue = $( e.target );
		const wrapper = hiddenValue.closest( my.selectors.horizontal_datepicker.container );

		let isValueEmpty = hiddenValue.val() === '' || hiddenValue.val() === 'null-null-null';
		let isRequired = ! isValueEmpty;
		
		wrapper.find( my.selectors.horizontal_datepicker.day ).prop( 'required', isRequired );
		wrapper.find( my.selectors.horizontal_datepicker.month ).prop( 'required', isRequired );
		wrapper.find( my.selectors.horizontal_datepicker.year ).prop( 'required', isRequired );

		if ( isRequired ) {
			wrapper.find( my.selectors.horizontal_datepicker.day ).addClass( 'tribe-tickets-meta-required' );
			wrapper.find( my.selectors.horizontal_datepicker.month ).addClass( 'tribe-tickets-meta-required' );
			wrapper.find( my.selectors.horizontal_datepicker.year ).addClass( 'tribe-tickets-meta-required' );
		} else {
			wrapper.find( my.selectors.horizontal_datepicker.day ).removeClass( 'tribe-tickets-meta-required' );
			wrapper.find( my.selectors.horizontal_datepicker.month ).removeClass( 'tribe-tickets-meta-required' );
			wrapper.find( my.selectors.horizontal_datepicker.year ).removeClass( 'tribe-tickets-meta-required' );
		}


	};

	/**
	 * Converts the number to positive if a negative provided.
	 *
	 * @since TBD
	 *
	 * @param e
	 */
	my.horizontal_datepicker.ensurePositiveNumber = function( e ) {
		const input       = $( e.target );
		const inputValue  = parseInt( input.val(), 10 );
		const numberValue = Math.abs( inputValue );

		// Only change the value if it needs to be set.
		if ( inputValue !== numberValue ) {
			input.val( numberValue );
		}
	};

	/**
	 * Add Listeners to the Meta functionality.
	 *
	 * @since TBD
	 */
	my.addListeners = function() {
		$( document ).on( 'change', my.selectors.horizontal_datepicker.select, this.horizontal_datepicker.populateHiddenValue );
		$( document ).on( 'change', my.selectors.field.number, this.horizontal_datepicker.ensurePositiveNumber );
		$( document ).on( 'change', my.selectors.horizontal_datepicker.value, this.horizontal_datepicker.makeDayMonthYearRequired );

		/**
		 * Fires after the meta fields that were loaded through AJAX have been populated with data.
		 *
		 * @see Window.tribe.tickets.registration.initFormPrefills
		 */
		$( window ).on( 'tribe_et_after_form_prefills', this.horizontal_datepicker.populateSelects );
	};

	$( function() {
		my.init();
		my.addListeners();
	} );
} )( window, document, jQuery, tribe_event_tickets_plus.meta );
