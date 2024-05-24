jQuery( document ).ready( function( $ ) {

	$( document ).on( 'click', 'a.ppm-pointer-close', function ( event ) {
		$( this ).parents( '.wp-pointer-content' ).find( '.close' ).trigger( 'click' );
		window.location = $( this ).attr( 'href' );
		event.preventDefault();
	} );

	$( '#ppm-wp-dialog' ).dialog( {
		title: 'Your session expired',
		dialogClass: 'wp-dialog',
		autoOpen: false,
		draggable: false,
		width: 'auto',
		modal: true,
		resizable: false,
		closeOnEscape: false,
		position: {
			my: "center",
			at: "center",
			of: window
		},
		open: function( event, ui ) {
		$(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
	},
		create: function() {
			// style fix for WordPress admin
			$( '.ui-dialog-titlebar-close' ).addClass( 'ui-button' );
		},
	} );
	// bind a button or a link to open the dialog
	if ( options.terminate_session_password == 1 && options.should_password_expire == 1 ) {
		$( '#ppm-wp-dialog' ).dialog( 'open' );
		// Show title
		$(".ui-dialog-titlebar").show();
	}

	$( '#ppm-wp-dialog' ).on( 'click', 'a.reset', function( e ) {
		e.preventDefault();
		$( '#ppm-wp-dialog' ).dialog( 'close' );
		if ( $( this ).hasClass( 'process-end' ) ) {
			return;
		}
		$.post( options.global_ajax_url, { action: 'ppm_ajax_session_expired' }, function( data, textStatus, xhr ) {} );

		$( '#ppm-wp-dialog' ).html( '<p>' + ppmwpGlobalStrings.emailResetInstructions + '</p><a href="javascript:;" class="button-primary process-end reset">' + ppmwpGlobalStrings.submitOK + '</a>' );
		$( '#ppm-wp-dialog' ).dialog( {
			closeOnEscape: false,
			open: function(event, ui) {
      	$(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
    	}
		} );
		$( '#ppm-wp-dialog' ).dialog( 'open' );
		// Show title
		$(".ui-dialog-titlebar").show();
	} );

	// Admin Show this warning:
	var PassWordLengthPolicy = function( $pass1Text ) {
		$( '#ppm-wp-dialog' ).dialog( 'close' );
		$( '#ppm-wp-dialog' ).html( '<p>' + ppmwpGlobalStrings.shortPasswordMessage + '</p><a href="javascript:;" class="button-primary ok">' + ppmwpGlobalStrings.submitOK + '</a>&nbsp;&nbsp;<a href="javascript:;" class="button-primary no">' + ppmwpGlobalStrings.submitNo + '</a>' );
		$( '#ppm-wp-dialog' ).dialog( {
			open: function( event, ui ) {
				$(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
			}
		} );
		// Open modal
		$( '#ppm-wp-dialog' ).dialog( 'open' );
		// Remove modal title
		$(".ui-dialog-titlebar").hide();
	};

	// Click button OK/NO
	$( '#ppm-wp-dialog' ).on( 'click', 'a.ok, a.no', function() {
		var button_event = false;
		button_event = $( this ).hasClass( 'ok' ) ? true : false;
		if ( button_event ) {
			// Force submit
			$( '#ppm-min-length' ).addClass( 'force-submit' );
			// Uncheck checkbox
			$( '#ppm-mix-case, #ppm-numeric, #ppm-special' ).prop( 'checked', false );
			// Submit form
			$( 'input[name="_ppm_save"]' ).trigger( 'click' );
		} else {
			// Close modal
			$( '#ppm-wp-dialog' ).dialog( 'close' );
		}

	} );
	// save ppm-wp setting form
	$( '#ppm-wp-settings' ).submit( function( event ) {
		// Reset all user process.
		if ( $( this ).hasClass( 'ppm_reset_all' ) ) {
			return true;
		}
		// If check enforce password is checked.
		if ( $( this ).find( '#ppm_enforce_password' ).is( ':checked' ) ) {
			return true;
		}
		// If check table data.id
		if ( $( this ).find( 'table' ).data( 'id' ) == '' ) {
			if ( $( 'body' ).find( '#ppm_master_switch' ).is( ':checked' ) ) {
				// If check password length ( if less than 6 )
				if ( $( '#ppm-min-length' ).val() < 6 && ! $( '#ppm-min-length' ).hasClass( 'force-submit' ) ) {
				// Open dialog popup
				PassWordLengthPolicy();
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	} else {
		if ( ! $( 'body' ).find( '#ppm_master_switch' ).is( ':checked' ) ) {
				// If check password length ( if less than 6 )
				if ( $( '#ppm-min-length' ).val() < 6 && ! $( '#ppm-min-length' ).hasClass( 'force-submit' ) ) {
				// Open dialog popup
				PassWordLengthPolicy();
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	return false;
	} );

	function download(filename, text) {
		// Create temporary element.
		var element = document.createElement('a');
		element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
		element.setAttribute('download', filename);

		// Set the element to not display.
		element.style.display = 'none';
		document.body.appendChild(element);

		// Simlate click on the element.
		element.click();

		// Remove temporary element.
		document.body.removeChild(element);
	}

	jQuery( document ).ready( function() {
		var download_btn = jQuery( '#ppmwp-download-sysinfo' );
		download_btn.click( function( event ) {
			event.preventDefault();
			download( 'mls-system-info.txt', jQuery( '#system-info-textarea' ).val() );
		} );
	} );

} );
