/**
 * Tribe QR Generate
 *
 * @since 4.7.5
 *
 * @type {{}}
 */
var tribe_ticket_plus_qr = tribe_ticket_plus_qr || {};
( function ( $, obj ) {
	'use strict';

	obj.init = function() {
		obj.init_generate();
	};

	/**
	 * Initialize QR Generate
	 *
	 * @since 4.7.5
	 *
	 */
	obj.init_generate = function() {
		this.$generate_key = $( '.tribe-generate-qr-api-key' );
		this.$generate_key_msg = $( '.tribe-enerate-qr-api-key-msg' );
		this.$generate_key_input = $( '[name="tickets-plus-qr-options-api-key"]' );

		this.$generate_key.on( 'click', function ( e ) {
			e.preventDefault();
			obj.qr_ajax();

		} );

	};


	/**
	 * AJAX to Generate and Save QR Key
	 *
	 * @since 4.7.5
	 *
	 */
	obj.qr_ajax = function() {

		var request = {
			'action': 'tribe_tickets_plus_generate_api_key',
			'confirm': tribe_qr.generate_qr_nonce
		};

		// Send our request
		$.post(
			ajaxurl,
			request,
			function( results ) {
				if ( results.success ) {
					obj.$generate_key_msg.html( "<p class=\'optin-success\'>" + results.data.msg + "</p>" );
					obj.$generate_key_input.val( results.data.key );
				} else {
					obj.$generate_key_msg.html( "<p class=\'optin-fail\'>" + results.data + "</p>" );
				}
			}
		);

	};

	$( document ).ready( obj.init );

})( jQuery, tribe_ticket_plus_qr );