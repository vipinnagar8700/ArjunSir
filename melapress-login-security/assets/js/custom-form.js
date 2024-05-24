/* global ajaxurl, pws_l10n, user_profile_l10n */
( function( $ ) {
	var $pass1,
	inputEvent;

	/*
	 * Use feature detection to determine whether password inputs should use
	 * the `keyup` or `input` event. Input is preferred but lacks support
	 * in legacy browsers.
	 */
	if ( 'oninput' in document.createElement( 'input' ) ) {
		inputEvent = 'input';
	} else {
		inputEvent = 'keyup';
	}

	// Memebrpress reg.
	if ( jQuery( '.mls_pw_errors' ).length ) {
		setTimeout(() => {
			var errorString = jQuery( '.mls_pw_errors' ).attr( 'data-error-keys' );
			var errorArray = errorString.split(',');
			jQuery( '.mls_pw_errors' ).html( '' )
			jQuery.each( errorArray, function ( index, value ) {
				var errText = jQuery( '.pass-strength-result .' + jQuery.trim( value ) ).text();
				if ( 'undefined' !== typeof errText ) {
					jQuery( '.mls_pw_errors' ).append( errText + '<br>' );					
				}
			});		
		}, 50);
	}

	jQuery( document ).ready( function() {

		if ( PPM_Custom_Form.element.length > 0 ) {
			jQuery( PPM_Custom_Form.policy ).insertAfter( PPM_Custom_Form.element );
			jQuery( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', function (e) {
				check_pass_strength( PPM_Custom_Form );
			} );
			jQuery( '.pass-strength-result' ).show();
	
			// Hide any elements by the classes/IDs supplied.
			var elementsToHide = PPM_Custom_Form.form_selector + ' ' + PPM_Custom_Form.elements_to_hide;
	
			if ( elementsToHide !== '' ) {
				jQuery( elementsToHide ).css( 'display', 'none' );
			}
		}	

		var basePpmForm = PPM_Custom_Form;
		
		if ( PPM_Custom_Form.custom_forms_arr.length > 0 ) {
			jQuery.each( PPM_Custom_Form.custom_forms_arr, function (e, customForm ) {
				setup_custom_forms_arr( customForm, basePpmForm );
			});
		}
	} );
} )( jQuery );

function setup_custom_forms_arr( customForm, PPM_Custom_Form ) {

	var policy = PPM_Custom_Form.policy;
	var PPM_Custom_Form = customForm;
	PPM_Custom_Form.element = customForm.form_selector + ' ' + customForm.pw_field_selector;
	PPM_Custom_Form.button_class = customForm.form_selector  + ' ' + customForm.form_submit_selector;
	var elementsToHide = customForm.form_selector + ' ' + customForm.elements_to_hide;

	if ( 'oninput' in document.createElement( 'input' ) ) {
		inputEvent = 'input';
	} else {
		inputEvent = 'keyup';
	}

	jQuery( policy ).insertAfter( PPM_Custom_Form.element );

	jQuery( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', function (e) {
		check_pass_strength( PPM_Custom_Form, true );
	} );

	jQuery( '.pass-strength-result' ).show();

	if ( elementsToHide !== '' ) {
		jQuery( elementsToHide ).css( 'display', 'none' );
		// Backup, as some forms may re-add hints etc via JS.
		jQuery('head').append('<style type="text/css">'+ elementsToHide +' { display: none !important; visibility: hidden !important; }</style>');
	}
}

function check_pass_strength( form, is_known_single = false ) {

	// Empty vars we will fill later.
	var strength;
	var pass1;

	if ( typeof form.form_selector !== 'undefined' && form.form_selector.length ) {
		var selectPrefix = form.form_selector + ' ';
	} else {
		var selectPrefix = '';
	}

	jQuery( selectPrefix + '.pass-strength-result' ).removeClass( 'short bad good strong' );

	// Try to seperate the list of items.
	var possibleInputsToCheck = form.element.split(',');

	if ( ! is_known_single ) {
		possibleInputsToCheck = jQuery.map(possibleInputsToCheck, function(){
			return possibleInputsToCheck.toString().replace(/ /g, '');
		});
	}
	// Not possible to split, so treat as if only 1 class/id is provided.
	if ( ! possibleInputsToCheck ) {
		// pass1 is a single class/id.
		pass1 = jQuery( form.element ).val();
	} else {
		// pass1 is an array of classes/ids to check.
		jQuery.each( possibleInputsToCheck, function( index, input ) {
			// If we have something, lets pass it to pass1.
			pass1 = jQuery( input ).val();
		});
	}

	// By this point, we should have a value (password) to check.
	if ( !pass1 ) {			
		jQuery( selectPrefix + '.pass-strength-result' ).html( form.policy );
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).prop( "disabled", false ).removeClass( 'button-disabled' );
		return;
	}

	strength = wp.passwordStrength.policyCheck( pass1, wp.passwordStrength.userInputDisallowedList(), pass1 );

	var errors = '';
	var err_pfx = '';
	var err_sfx = '';
	var ErrorData = [];

	if ( !jQuery.isEmptyObject( wp.passwordStrength.policyFails ) ) {
		err_pfx = "<ul>";
		err_sfx = "</ul>";
	}
	jQuery.each( wp.passwordStrength.policyFails, function( $namespace, $value ) {
		errors = errors + '<li>' + ppmJSErrors[$namespace] + '</li>';
		ErrorData.push( $value );
	} );
	errors = err_pfx + errors + err_sfx;
	if ( ErrorData.length == 0 ) {
		jQuery( selectPrefix +'.pass-strength-result li' ).css('color', '#21760c');
	} else {
		jQuery.each( ErrorData, function( i, val ) {
			if ( jQuery( selectPrefix +'.pass-strength-result li' ).hasClass( val ) ) {
				jQuery( selectPrefix +'.pass-strength-result li.' + val ).css('color', '#F00');
			} else {
				jQuery( selectPrefix +'.pass-strength-result li' ).css('color', '#21760c');
			}
		} );
	}
	if ( ErrorData.length <= 1 ) {
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).not( '.mp-hide-pw' ).prop( "disabled", false ).removeClass( 'button-disabled' );
		jQuery( selectPrefix + form.button_class ).prop( "disabled", false ).removeClass( 'button-disabled' );
	} else {
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).not( '.mp-hide-pw' ).prop( "disabled", true ).addClass( 'button-disabled' );
		jQuery( selectPrefix + form.button_class ).prop( "disabled", true ).addClass( 'button-disabled' );
	}
}

function get_ppm_custom_form_base() {
	return PPM_Custom_Form;
}

// Memberpress reg fix.
jQuery( document ).on( 'click', '.button.mp-hide-pw', function ( event ) {
	jQuery( '#mepr_user_password1' ).attr( 'type', jQuery( '.pass-strength-result' ).attr( 'type' ) );
} );

