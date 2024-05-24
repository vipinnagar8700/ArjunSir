/* global ajaxurl, pws_l10n, user_profile_l10n */

/**
 * A polyfill to add array.prototype.find since it's unavailable on IE.
 *
 * @link https://github.com/jsPolyfill/Array.prototype.find/blob/master/find.js
 * @licence Used under MIT licence.
 */
Array.prototype.find = Array.prototype.find || function(callback) {
	// NOTE: these are error strings directly from origin, moved to a function
	// that can translate them but otherwise left as-is.
	if ( this === null ) {
		throw new TypeError( user_profile_l10n.polyfill.calledOnNull );
	} else if ( typeof callback !== 'function' ) {
		throw new TypeError( user_profile_l10n.polyfill.callbackNotFunction );
	}

	// Setup the interim object.
	var list = Object( this );
	// Makes sures is always has an positive integer as length.
	var length  = list.length >>> 0;
	var thisArg = arguments[1];
	for ( var i = 0; i < length; i++ ) {
		var element = list[i];
		if ( callback.call( thisArg, element, i, list ) ) {
			return element;
		}
	}
};

/* Taken from WP 5.7 wp-admin/js/user-profile.js */
(function($) {
	var updateLock = false,
		$pass1Row,
		$pass1,
		$pass2,
		$weakRow,
		$weakCheckbox,
		$toggleButton,
		$submitButtons,
		$submitButton,
		currentPass,
		$passwordWrapper,
		$pass1Label,
		$pass1Text,
		inputEvent;

	function generatePassword() {
		if ( typeof zxcvbn !== 'function' ) {
			setTimeout( generatePassword, 50 );
			return;
		} else if ( ! $pass1.val() || $passwordWrapper.hasClass( 'is-open' ) ) {
			// zxcvbn loaded before user entered password, or generating new password.
			$pass1.val( $pass1.data( 'pw' ) );
			$pass1.trigger( 'pwupdate' );
			showOrHideWeakPasswordCheckbox();
		} else {
			// zxcvbn loaded after the user entered password, check strength.
			check_pass_strength();
			showOrHideWeakPasswordCheckbox();
		}

		// Install screen.
		if ( 1 !== parseInt( $toggleButton.data( 'start-masked' ), 10 ) ) {
			// Show the password not masked if admin_password hasn't been posted yet.
			$pass1.attr( 'type', 'text' );
		} else {
			// Otherwise, mask the password.
			$toggleButton.trigger( 'click' );
		}

		// Once zxcvbn loads, passwords strength is known.
		$( '#pw-weak-text-label' ).html( user_profile_l10n.warnWeak );
	}

	function bindPass1() {
		currentPass = $pass1.val();

		if ( 1 === parseInt( $pass1.data( 'reveal' ), 10 ) ) {
			generatePassword();
		}

		$pass1.on( 'input' + ' pwupdate', function () {
			if ( $pass1.val() === currentPass ) {
				return;
			}

			currentPass = $pass1.val();

			// Refresh password strength area.
			$pass1.removeClass( 'short bad good strong' );
			showOrHideWeakPasswordCheckbox();
		} );
	}

	function resetToggle( show ) {
		$toggleButton
			.attr({
				'aria-label': show ? user_profile_l10n.show : user_profile_l10n.hide
			})
			.find( '.text' )
				.text( show ? user_profile_l10n.show : user_profile_l10n.hide )
			.end()
			.find( '.dashicons' )
				.removeClass( show ? 'dashicons-hidden' : 'dashicons-visibility' )
				.addClass( show ? 'dashicons-visibility' : 'dashicons-hidden' );
	}

	function bindToggleButton() {
		$toggleButton = $pass1Row.find('.wp-hide-pw');
		$toggleButton.show().on( 'click', function () {
			if ( 'password' === $pass1.attr( 'type' ) ) {
				$pass1.attr( 'type', 'text' );
				resetToggle( false );
			} else {
				$pass1.attr( 'type', 'password' );
				resetToggle( true );
			}
		});
	}

	/**
	 * Handle the password reset button. Sets up an ajax callback to trigger sending
	 * a password reset email.
	 */
	function bindPasswordRestLink() {
		$( '#generate-reset-link' ).on( 'click', function() {
			var $this  = $(this),
				data = {
					'user_id': user_profile_l10n.user_id, // The user to send a reset to.
					'nonce':   user_profile_l10n.nonce    // Nonce to validate the action.
				};

				// Remove any previous error messages.
				$this.parent().find( '.notice-error' ).remove();

				// Send the reset request.
				var resetAction =  wp.ajax.post( 'send-password-reset', data );

				// Handle reset success.
				resetAction.done( function( response ) {
					addInlineNotice( $this, true, response );
				} );

				// Handle reset failure.
				resetAction.fail( function( response ) {
					addInlineNotice( $this, false, response );
				} );

		});

	}

	/**
	 * Helper function to insert an inline notice of success or failure.
	 *
	 * @param {jQuery Object} $this   The button element: the message will be inserted
	 *                                above this button
	 * @param {bool}          success Whether the message is a success message.
	 * @param {string}        message The message to insert.
	 */
	function addInlineNotice( $this, success, message ) {
		var resultDiv = $( '<div />' );

		// Set up the notice div.
		resultDiv.addClass( 'notice inline' );

		// Add a class indicating success or failure.
		resultDiv.addClass( 'notice-' + ( success ? 'success' : 'error' ) );

		// Add the message, wrapping in a p tag, with a fadein to highlight each message.
		resultDiv.text( $( $.parseHTML( message ) ).text() ).wrapInner( '<p />');

		// Disable the button when the callback has succeeded.
		$this.prop( 'disabled', success );

		// Remove any previous notices.
		$this.siblings( '.notice' ).remove();

		// Insert the notice.
		$this.before( resultDiv );
	}

	function bindPasswordForm() {
		var $generateButton,
			$cancelButton;

		$pass1Row = $( '.user-pass1-wrap, .user-pass-wrap, .reset-pass-submit' );
		$pass1Label = $pass1Row.find( 'th label' ).attr( 'for', 'pass1-text' );

		// Hide the confirm password field when JavaScript support is enabled.
		$('.user-pass2-wrap').hide();

		$submitButton = $( '#submit, #wp-submit' ).on( 'click', function () {
			updateLock = false;
		});

		$submitButtons = $submitButton.add( ' #createusersub' );

		$weakRow = $( '.pw-weak' );
		$weakCheckbox = $weakRow.find( '.pw-checkbox' );
		$weakCheckbox.on( 'change', function() {
			$submitButtons.prop( 'disabled', ! $weakCheckbox.prop( 'checked' ) );
		} );

		$pass1 = $('#pass1');
		if ( $pass1.length ) {
			bindPass1();
		} else {
			// Password field for the login form.
			$pass1 = $( '#user_pass' );
		}

		/*
		 * Fix a LastPass mismatch issue, LastPass only changes pass2.
		 *
		 * This fixes the issue by copying any changes from the hidden
		 * pass2 field to the pass1 field, then running check_pass_strength.
		 */
		$pass2 = $( '#pass2' ).on( 'input', function () {
			if ( $pass2.val().length > 0 ) {
				$pass1.val( $pass2.val() );
				$pass2.val('');
				currentPass = '';
				$pass1.trigger( 'pwupdate' );
			}
		} );

		// Disable hidden inputs to prevent autofill and submission.
		if ( $pass1.is( ':hidden' ) ) {
			$pass1.prop( 'disabled', true );
			$pass2.prop( 'disabled', true );
		}

		$passwordWrapper = $pass1Row.find( '.wp-pwd' );
		$generateButton  = $pass1Row.find( 'button.wp-generate-pw' );

		bindToggleButton();

		$generateButton.show();
		$generateButton.on( 'click', function () {
			updateLock = true;

			// Make sure the password fields are shown.
			$generateButton.attr( 'aria-expanded', 'true' );
			$passwordWrapper
				.show()
				.addClass( 'is-open' );

			// Enable the inputs when showing.
			$pass1.attr( 'disabled', false );
			$pass2.attr( 'disabled', false );

			// Set the password to the generated value.
			generatePassword();

			// Show generated password in plaintext by default.
			resetToggle ( false );

			// Generate the next password and cache.
			wp.ajax.post('generate-password', {
				'ppm_usr': jQuery('#user_login').val(),
				'ppm_nonce': jQuery('#_wpnonce_create-user').val()
				} )
				.done( function( data ) {
					$pass1.data( 'pw', data );
				} );
		} );

		$cancelButton = $pass1Row.find( 'button.wp-cancel-pw' );
		$cancelButton.on( 'click', function () {
			updateLock = false;

			// Disable the inputs when hiding to prevent autofill and submission.
			$pass1.prop( 'disabled', true );
			$pass2.prop( 'disabled', true );

			// Clear password field and update the UI.
			$pass1.val( '' ).trigger( 'pwupdate' );
			resetToggle( false );

			// Hide password controls.
			$passwordWrapper
				.hide()
				.removeClass( 'is-open' );

			// Stop an empty password from being submitted as a change.
			$submitButtons.prop( 'disabled', false ).removeClass( 'button-disabled' );
		} );

		$pass1Row.closest( 'form' ).on( 'submit', function () {
			updateLock = false;

			$pass1.prop( 'disabled', false );
			$pass2.prop( 'disabled', false );
			$pass2.val( $pass1.val() );
		});
	}

	function check_pass_strength() {
		var pass1 = $( '#pass1' ).val(), strength;

		$( '#pass-strength-result' ).removeClass( 'short bad good strong' );
		if ( !pass1 && ppmPolicyRules.length > 1 ) {
			$( '#pass-strength-result' ).html( '&nbsp;' );
			return;
		}

		strength = wp.passwordStrength.policyCheck( pass1, wp.passwordStrength.userInputDisallowedList(), pass1 );

		var errors = '';
		var err_pfx = '';
		var err_sfx = '';
		var ErrorData = [];
		if ( !$.isEmptyObject( wp.passwordStrength.policyFails ) ) {
			err_pfx = "<ul>";
			err_sfx = "</ul>";
		}

		/*
		 * Store the error (policy fails) in an array. Used on the reset screen.
		 */
		$.each( wp.passwordStrength.policyFails, function( $namespace, $value ) {
			ErrorData.push( $value );
		} );

		errors = err_pfx + errors + err_sfx;

		/*
		 * The password reset form shows each policy in the 'hints' area as a
		 * list. This handles colorizing them based on the password input.
		 *
		 * Green (#21760c) = pass, Red (#F00) = fail.
		 */
		if ( ErrorData.length == 0 ) {
			$( '#resetpassform li' ).css('color', '#21760c');
		} else {
			// val === a 1:1 match for a classname of a policy type.
			$.each( ErrorData, function( i, val ) {
				if ( $( '#resetpassform li' ).hasClass( val ) ) {
					// failed policy.
					$( '#resetpassform li.' + val ).css('color', '#F00');
				} else {
					// passed policy.
					$( '#resetpassform li' ).css('color', '#21760c');
				}
			} );
		}
		$( "input[id$='submit'], input#createusersub" ).prop( "disabled", true ).addClass( 'button-disabled' );

		/*
		 *  If we have any invalid data then use invalid string or else
		 *  fallthrough to standard strength checks. Currently only one policy
		 *  makes the password fully invalid. More may be added later.
		 */
		var invalidCheck = ErrorData.find(
			function( el ) {
				return el === 'exclude_special_chars';
			}
		);

		if ( invalidCheck ) {
			$( '#pass-strength-result' ).addClass( 'bad' ).html( '<span class="policy-strength-string">' + pws_l10n.invalid + '</span>' );
			$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
		} else {
			switch ( strength ) {
				case -1:
					$( '#pass-strength-result' ).addClass( 'bad' ).html( '<span class="policy-strength-string">' + pws_l10n.unknown + '</span>' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
					break;
				case 0:
				case 1:
				case 2:
					$( '#pass-strength-result' ).addClass( 'bad' ).html( '<span class="policy-strength-string">' + pws_l10n.bad + '</span>' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
					break;
				case 3:
					$( '#pass-strength-result' ).addClass( 'good' ).html( '<span class="policy-strength-string">' + pws_l10n.good + '</span>' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
					break;
				case 4:
					$( '#pass-strength-result' ).addClass( 'strong' ).html( '<span class="policy-strength-string">' + pws_l10n.strong + '</span>' );
					$( "input[id$='submit'], input#createusersub" ).prop( "disabled", false ).removeClass( 'button-disabled' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
					break;
				case 5:
					$( '#pass-strength-result' ).addClass( 'short' ).html( '<span class="policy-strength-string">' + pws_l10n.mismatch + '</span>' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
					break;
				default:
					//$( '#pass-strength-result' ).addClass( 'short' ).html( pws_l10n['short'] );
					$( "input[id$='submit'], input#createusersub" ).prop( "disabled", false ).removeClass( 'button-disabled' );
					$( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).append( ppmwpbuildPolicyListForDisplay( ppmPolicyRules, wp.passwordStrength.policyFails ) );
			}
		}
		if ( ppmPolicyRules.length === 0 ) {
		 $( "input[id$='submit'], input#createusersub" ).prop( "disabled", false ).removeClass( 'button-disabled' );
		 $( '#pass-strength-result:not(#resetpassform #pass-strength-result)' ).html( '<p class="hint-msg">' + user_profile_l10n.hintMsgUserNew + '</p><ul style="list-style: none;"><li>' + user_profile_l10n.hintBefore + ' <a target="_blank" href="https://www.melapress.com/guide-wordpress-password-security/">' + user_profile_l10n.hintLink + '</a> ' + user_profile_l10n.hintAfter + '</li></ul>' );
		 jQuery( '#pass-strength-result' ).css( 'opacity', 1 ).removeClass('strong');
	 }
	}

	function showOrHideWeakPasswordCheckbox() {
		var passStrength = $('#pass-strength-result')[0];

		if ( passStrength.className ) {
			$pass1.addClass( passStrength.className );
			if ( $( passStrength ).is( '.short, .bad' ) ) {
				if ( ! $weakCheckbox.prop( 'checked' ) ) {
					$submitButtons.prop( 'disabled', true );
				}
				$weakRow.show();
			} else {
				if ( $( passStrength ).is( '.empty' ) ) {
					$submitButtons.prop( 'disabled', true );
					$weakCheckbox.prop( 'checked', false );
				} else {
					$submitButtons.prop( 'disabled', false );
				}
				$weakRow.hide();
			}
		}
	}

	$(document).ready( function() {
		var $colorpicker, $stylesheet, user_id, current_user_id,
			select       = $( '#display_name' ),
			current_name = select.val(),
			greeting     = $( '#wp-admin-bar-my-account' ).find( '.display-name' );

		$( '#pass1' ).val( '' ).on( 'input' + ' pwupdate', check_pass_strength );
		$('#pass-strength-result').show();
		$('.color-palette').on( 'click', function() {
			$(this).siblings('input[name="admin_color"]').prop('checked', true);
		});

		if ( select.length ) {
			$('#first_name, #last_name, #nickname').on( 'blur.user_profile', function() {
				var dub = [],
					inputs = {
						display_nickname  : $('#nickname').val() || '',
						display_username  : $('#user_login').val() || '',
						display_firstname : $('#first_name').val() || '',
						display_lastname  : $('#last_name').val() || ''
					};

				if ( inputs.display_firstname && inputs.display_lastname ) {
					inputs.display_firstlast = inputs.display_firstname + ' ' + inputs.display_lastname;
					inputs.display_lastfirst = inputs.display_lastname + ' ' + inputs.display_firstname;
				}

				$.each( $('option', select), function( i, el ){
					dub.push( el.value );
				});

				$.each(inputs, function( id, value ) {
					if ( ! value ) {
						return;
					}

					var val = value.replace(/<\/?[a-z][^>]*>/gi, '');

					if ( inputs[id].length && $.inArray( val, dub ) === -1 ) {
						dub.push(val);
						$('<option />', {
							'text': val
						}).appendTo( select );
					}
				});
			});

			/**
			 * Replaces "Howdy, *" in the admin toolbar whenever the display name dropdown is updated for one's own profile.
			 */
			select.on( 'change', function() {
				if ( user_id !== current_user_id ) {
					return;
				}

				var display_name = $.trim( this.value ) || current_name;

				greeting.text( display_name );
			} );
		}

		$colorpicker = $( '#color-picker' );
		$stylesheet = $( '#colors-css' );
		user_id = $( 'input#user_id' ).val();
		current_user_id = $( 'input[name="checkuser_id"]' ).val();

		$colorpicker.on( 'click.colorpicker', '.color-option', function() {
			var colors,
				$this = $(this);

			if ( $this.hasClass( 'selected' ) ) {
				return;
			}

			$this.siblings( '.selected' ).removeClass( 'selected' );
			$this.addClass( 'selected' ).find( 'input[type="radio"]' ).prop( 'checked', true );

			// Set color scheme.
			if ( user_id === current_user_id ) {
				// Load the colors stylesheet.
				// The default color scheme won't have one, so we'll need to create an element.
				if ( 0 === $stylesheet.length ) {
					$stylesheet = $( '<link rel="stylesheet" />' ).appendTo( 'head' );
				}
				$stylesheet.attr( 'href', $this.children( '.css_url' ).val() );

				// Repaint icons.
				if ( typeof wp !== 'undefined' && wp.svgPainter ) {
					try {
						colors = JSON.parse( $this.children( '.icon_colors' ).val() );
					} catch ( error ) {}

					if ( colors ) {
						wp.svgPainter.setColors( colors );
						wp.svgPainter.paint();
					}
				}

				// Update user option.
				$.post( ajaxurl, {
					action:       'save-user-color-scheme',
					color_scheme: $this.children( 'input[name="admin_color"]' ).val(),
					nonce:        $('#color-nonce').val()
				}).done( function( response ) {
					if ( response.success ) {
						$( 'body' ).removeClass( response.data.previousScheme ).addClass( response.data.currentScheme );
					}
				});
			}
		});

		bindPasswordForm();
		bindPasswordRestLink();
	});

	$( '#destroy-sessions' ).on( 'click', function( e ) {
		var $this = $(this);

		wp.ajax.post( 'destroy-sessions', {
			nonce: $( '#_wpnonce' ).val(),
			user_id: $( '#user_id' ).val()
		}).done( function( response ) {
			$this.prop( 'disabled', true );
			$this.siblings( '.notice' ).remove();
			$this.before( '<div class="notice notice-success inline"><p>' + response.message + '</p></div>' );
		}).fail( function( response ) {
			$this.siblings( '.notice' ).remove();
			$this.before( '<div class="notice notice-error inline"><p>' + response.message + '</p></div>' );
		});

		e.preventDefault();
	});

	window.generatePassword = generatePassword;

	// Warn the user if password was generated but not saved.
	$( window ).on( 'beforeunload', function () {
		if ( true === updateLock ) {
			return user_profile_l10n.warn;
		}
	} );

	/*
	 * We need to generate a password as soon as the Reset Password page is loaded,
	 * to avoid double clicking the button to retrieve the first generated password.
	 * See ticket #39638.
	 */
	$( document ).ready( function() {
		if ( $( '.reset-pass-submit' ).length ) {
			$( '.reset-pass-submit button.wp-generate-pw' ).trigger( 'click' );
		}
	});

})(jQuery);


/**
 * Generate some markup to use as a list of valid/invalid policies to display
 * in the password details popup.
 *
 * @method ppmwpbuildPolicyListForDisplay
 * @since  2.1.0
 */
function ppmwpbuildPolicyListForDisplay( policies, fails ) {
	var htmlString  = '';
	// clone policies before modifying an object maybe used later.
	var policiesCopy = JSON.parse( JSON.stringify( policies ) );
	// Check if we are enforcing upper/lowercase letters.
	if ( typeof policiesCopy.upper_case !== 'undefined' || typeof policiesCopy.lower_case !== 'undefined' ) {
		// mix_case is when either upper or lower case is missing. create key for it.
		delete policiesCopy.upper_case;
		delete policiesCopy.lower_case;
		policiesCopy.mix_case = '';
	}

	// Remove this line if we have no chars to show.
	if ( policiesCopy.special_chars == '' ) {
		delete policiesCopy.special_chars;
	}

	// form a string of html with the error message and either a pass/fail flag.
	jQuery.each(
		policiesCopy,
		function ( namespace, regex ) {
			if ( namespace in fails ) {
				htmlString = htmlString + '<li class="policy-fail">' + ppmJSErrors[namespace] + '</li>';
			} else {
				htmlString = htmlString + '<li class="policy-pass">' + ppmJSErrors[namespace] + '</li>';
			}
		}
	);

	return ( '<p class="hint-msg">' + user_profile_l10n.hintMsg + '</p><ul>' + htmlString + '</ul>' );
}
