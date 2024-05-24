// used to hold data in a higher scope for popup notices.
var ppmwpNoticeData = {};

jQuery( 'document' ).ready( function( $ ) {
	function display( value, id ) {
		var $li_item = jQuery( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		if ( parseInt( id ) > 0 ) {
			$existing_val = jQuery( "#ppm-exempted-users" ).val();
			if ( $existing_val.indexOf( id ) === -1 ) {
				$li_item.prepend( value ).prependTo( "ul#ppm-exempted-list" )
			}
			add_exemption( $li_item, id, 'users' );
		} else {
			$existing_val = jQuery( "#ppm-exempted-roles" ).val();
			if ( $existing_val.indexOf( id ) === -1 ) {
				$li_item.prepend( value ).prependTo( "ul#ppm-exempted-list" )
			}
			add_exemption( $li_item, id, 'roles' );
		}
		jQuery( "#ppm-exempted-list" ).scrollTop( 0 );
	}

	function add_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-" + $type );
		$existing_val = jQuery( "#ppm-exempted-" + $type ).val();

		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}

		$existing_val.indexOf( $id ) === -1 ? $existing_val.push( $id ) : alert( 'Item already exmpt' );

		jQuery( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );

	}

	function remove_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = jQuery( "#ppm-exempted-" + $type ).val();

		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		jQuery( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( "#ppm-exempted" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					user_role: jQuery( '#ppm-exempted-role' ).val(),
					exclude_users: JSON.stringify( jQuery( "#ppm-exempted-users" ).val() ),
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display( ui.item.value, ui.item.id );
			jQuery( this ).val( "" );
			return false;

		}
	} );

	jQuery( '#ppm-exempted' ).on( 'keypress', function( e ) {
		var code = ( e.keyCode ? e.keyCode : e.which );
		if ( code == 13 ) { //Enter keycode
			return false;
		}
	} );

	jQuery( '#ppm-custom_login_url, #ppm-custom_login_redirect' ).on( 'keypress', function( e ) {
		var code = ( e.keyCode ? e.keyCode : e.which );
		if (e.keyCode >= 48 && e.keyCode <= 57 || e.keyCode == 189 || e.keyCode == 45 || (e.charCode >= 65 && e.charCode <= 90) || (e.charCode >= 97 && e.charCode <= 122) || (e.charCode == 32)) {
			return true;
		} else {
			return false;
		}
	} );

	jQuery( "#ppm-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = jQuery( this ).closest( 'li.ppm-exempted-list-item' );

		var $id = $list_item.data( 'id' ).toString();

		if ( $list_item.hasClass( 'ppm-exempted-users' ) ) {
			remove_exemption( $id, 'users' );
		} else {
			remove_exemption( $id, 'roles' );
		}

		$list_item.remove();

	} );

	// Inactive exempted.
	function display_inactive_exempted( value, id ) {
		var $li_item = jQuery( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		$li_item.prepend( value ).prependTo( "ul#ppm-inactive-exempted-list" );

		if ( parseInt( id ) > 0 ) {
			add_inactive_exemption( $li_item, id, 'users' );
		} else {
			add_inactive_exemption( $li_item, id, 'roles' );
		}
		jQuery( "#ppm-inactive-exempted-list" ).scrollTop( 0 );
	}

	function add_inactive_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-user" );
		$existing_val = jQuery( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}
		$existing_val.indexOf( $id ) === -1 ? $existing_val.push( $id ) : alert( 'Item already exempt' );
		jQuery( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( "#ppm-inactive-exempted-search" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display_inactive_exempted( ui.item.value, ui.item.value );
			jQuery( this ).val( "" );
			return false;
		}
	} );

	jQuery( "#ppm-inactive-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = jQuery( this ).closest( 'li.ppm-exempted-list-item' );
		var $id = $list_item.text().trim().toString();
		remove_inactive_exemption( $id, 'users' );
		$list_item.remove();
	} );

	function remove_inactive_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = jQuery( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		jQuery( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( '#ppm-wp-test-email' ).on( 'click', function ( event ) {
		jQuery( this ).prop( 'disabled', true );
		jQuery( '#ppm-wp-test-email-loading' ).css( 'visibility', 'visible' );
		$.get( {
			url: ppm_ajax.ajax_url,
			dataType: 'json',
			data: {
				action: 'ppm_wp_send_test_email',
				_wpnonce: ppm_ajax.test_email_nonce
			},
			success: function ( data ) {
				jQuery( '.ppm-email-notice' ).remove();
				jQuery( '#ppm-wp-test-email-loading' ).css( 'visibility', 'hidden' );
				jQuery( "html, body" ).animate( { scrollTop: 0 } );
				if ( data.success ) {
					jQuery( '.wrap .page-head h2' ).after( '<div class="notice notice-success ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				} else {
					jQuery( '.wrap .page-head h2' ).after( '<div class="notice notice-error ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				}
				jQuery( '#ppm-wp-test-email' ).prop( 'disabled', false );
			}
		} );
	} );

	jQuery('#ppm_master_switch').change(function() {
		if ( jQuery( this ).parents( 'table' ).data( 'id' ) !='' ) {
			if( jQuery(this).is(':checked') ) {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				jQuery('.ppm-settings').slideUp( 300 ).addClass('disabled');
				jQuery(this).val( 1 );
				jQuery( '#inherit_policies' ).val( 1 );
			}
			else {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				jQuery('.ppm-settings').slideDown( 300 ).removeClass('disabled');
				jQuery(this).val( 0 );
				jQuery( '#inherit_policies' ).val( 0 );
			}
		} else {
			if( jQuery(this).is(':checked') ) {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				jQuery(' .nav-tab-wrapper').fadeIn( 300 ).removeClass('disabled');
				jQuery('.ppm-settings').slideDown( 300 ).removeClass('disabled');
				jQuery(this).val( 1 );
			}
			else {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				jQuery('.nav-tab-wrapper').fadeOut( 300 ).addClass('disabled');
				jQuery('.ppm-settings').slideUp( 300 ).addClass('disabled');
				jQuery(this).val( 0 );
			}
		}
		jQuery(this).removeAttr('disabled');
		jQuery('#ppm-wp-settings input[type="hidden"]').removeAttr('disabled');
		// trigger change so it's disabled state is not broken by the code above.
		jQuery( '#ppm-exclude-special' ).change();
		// trigger a change to ensure initial state of inactive users is correct.
		jQuery( '#ppm-expiry-value' ).change();

		// Check status of failed login options.
		disable_enabled_failed_login_options();
	}).change();

	// enforce password
	jQuery( '#ppm_enforce_password' ).change( function() {
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( this ).parents( 'form' ).find( 'input, select, button' ).not('input[name=_ppm_save],input[type="hidden"], input#_ppm_reset').not( this ).attr( 'disabled', 'disabled' );
			jQuery('.ppm-settings, .master-switch').addClass('disabled');
			jQuery( '#inherit_policies' ).val( 0 );
		} else {
			if ( jQuery( '#inherit_policies' ).val() == 0 ) {
				// Set value
				if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) ) {
					jQuery( '#inherit_policies' ).val( 1 );
					jQuery( this ).parents( 'form' ).find( 'button, #ppm_master_switch' ).removeAttr( 'disabled' );
					jQuery('.master-switch').removeClass('disabled');
				} else {
					jQuery( '#inherit_policies' ).val( 0 );
					jQuery('input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button','#ppm-wp-settings').removeAttr('disabled');
					jQuery('.ppm-settings, .master-switch').removeClass('disabled');
				}
			}
		}
	} ).change();

	// Exclude Special Characters Input.
	jQuery( '#ppm-exclude-special' ).change(
		function() {
			if ( jQuery( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) && jQuery( this ).is( ':checked' ) ) {
				jQuery( '#ppm-excluded-special-chars' ).prop( 'disabled', false );
			} else if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) ) {
				jQuery( '#ppm-excluded-special-chars' ).prop( 'disabled', true );
			}
		}
	).change();

	jQuery( '#ppm-inactive-users-reset-on-unlock' ).change(
		function() {
			if ( jQuery( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-deactivated-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-deactivated-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	jQuery( '#ppm-inactive-users-disable-reset' ).change(
		function() {
			if ( jQuery( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-self-reset-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-self-reset-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	jQuery( '#disable-self-reset' ).change(
		function() {
			if ( jQuery( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-reset-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-reset-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	// trigger change so it's initial state is set.
	jQuery( '#ppm-exclude-special' ).change();

	// trigger a change to ensure initial state of inactive users is correct.
	jQuery( '#ppm-expiry-value' ).change();

	hideShowResetSettings();
	jQuery( '[name="reset_type"]' ).change(function() {
		hideShowResetSettings();
	});

	function hideShowResetSettings() {
		if ( jQuery( '[name="reset_type"]' ).length > 0 ) {
			jQuery( '[data-active-shows-setting]' ).each( function () {
				jQuery( jQuery( this ).attr( 'data-active-shows-setting' ) ).addClass( 'hidden' );
			});
			
			var currVal = jQuery( '[name="reset_type"]:checked' ).val();
			jQuery( jQuery( '[name="reset_type"]:checked' ).attr( 'data-active-shows-setting' ) ).removeClass( 'hidden' );
		
		}
	}

	// Mass reset.
	jQuery( 'input#_ppm_reset' ).on( 'click', function( event ) {
		event.preventDefault;
		hideShowResetSettings();

		// If check class exists OR not
		if ( jQuery( '#ppm-wp-settings' ).hasClass( 'ppm_reset_all' ) ) return true;

		jQuery( '#reset-all-modal' ).addClass( 'show' );

		// Remove current user field
		jQuery( '#ppm-wp-settings' ).find( '.current_user' ).remove();
		return false;
	} );

	jQuery( 'a[href="#modal-cancel"]' ).on( 'click', function( event ) {
		jQuery( jQuery( this ).attr( 'data-modal-close-target' ) ).removeClass( 'show' );
		var attr = jQuery(this).attr('data-reload-on-close');
		if ( typeof attr !== 'undefined' && attr !== false ) {
			setTimeout( function() {
				window.location.reload();
			}, 300 );
		}
	
	});

	// Proceed with PW reset.
	jQuery( 'a[href="#modal-proceed"]' ).on( 'click', function( event ) {
		var currVal        = jQuery( '[name="reset_type"]:checked' ).val();
		var sendResetEmail = jQuery( '#send_reset_email' ).is( ':checked' );
		var includeSelf    = jQuery( '#include_reset_self' ).is( ':checked' );
		var killSessions   = jQuery( '#terminate_sessions_on_reset' ).is( ':checked' );
		var nonce = jQuery( this ).attr( 'data-reset-nonce' );
		
		var role     = false;
		var users    = [];
		var fileText = false;

		if ( currVal == 'reset-role' ) {
			var role = jQuery( '#reset-role-select option:selected' ).text();
		} else if ( currVal == 'reset-users' ) {
			var users = [];
			jQuery( '#ppm-exempted-list li' ).each(function () {
				users.push( jQuery(this).attr( 'data-id' ) );
			});
		} else if ( currVal == 'reset-csv' ) {
			var fileInput = document.getElementById( "users-reset-file" );
			var reader    = new FileReader();
			reader.readAsText( fileInput.files[0] );

			reader.onload = function () {
				var fileText = reader.result;
				var isValid = /^[0-9,]*$/.test(fileText);
				var lengthError = false;

				if ( ! isValid ) {
					var trySplit = fileText.split(/\r?\n/);
					var idArr    = trySplit.join();
					isValid = ( idArr.length > 0 );
					if ( ! isValid ) {
						lengthError = true;
					}
				}

				if ( isValid ) {
					jQuery.ajax({
						type: 'POST',
						url: ajaxurl,
						async: true,
						data: {
							action: 'mls_process_reset',
							nonce : nonce,
							reset_type: currVal,
							role : role,
							users : users,
							send_reset : sendResetEmail,
							kill_sessions : killSessions,
							include_self : includeSelf,
							file_text : fileText,
						},
						success: function ( result ) {		
							jQuery( 'a[href="#modal-proceed"]' ).remove();
							jQuery( 'a[href="#modal-cancel"]' ).text( 'Close' );
							jQuery( '.mls-modal-content-wrapper' ).slideUp( 300 );
							setTimeout( function() {
								jQuery( '.mls-modal-content-wrapper' ).html( '<h3>' +  ppm_ajax.reset_done_title + '</h3><p class="description">' +  ppm_ajax.reset_done_title + '</p><br>' );
							}, 300 );
							setTimeout( function() {
								jQuery( '.mls-modal-content-wrapper' ).slideDown( 300 );
							}, 350 );
						}
					});
				} else {
					jQuery( '.reset-users-file' ).after('<div id="csvWarning" style="color: red;">' +  ppm_ajax.csv_error + '</div>');
					setTimeout( function() {
						jQuery( '#csvWarning' ).slideUp( 300 ).delay( 400 ).remove();
					}, 3000 );
					if ( lengthError ) {
						jQuery( '.reset-users-file' ).after('<div id="csvLengthWarning" style="color: red;">' +  ppm_ajax.csv_error_length + '</div>');
						setTimeout( function() {
							jQuery( '#csvLengthWarning' ).slideUp( 300 ).delay( 400 ).remove();
						}, 3000 );
					}
				}

			}
			
			return true;
		}

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'mls_process_reset',
				nonce : nonce,
				reset_type: currVal,
				role : role,
				users : users,
				send_reset : sendResetEmail,
				kill_sessions : killSessions,
				include_self : includeSelf,
				file_text : fileText,
			},
			success: function ( result ) {		
				jQuery( 'a[href="#modal-proceed"]' ).remove();
				jQuery( 'a[href="#modal-cancel"]' ).text( 'Close' ).attr( 'data-reload-on-close', true );
				jQuery( '.mls-modal-content-wrapper' ).slideUp( 300 );
				setTimeout( function() {
					jQuery( '.mls-modal-content-wrapper' ).html( '<h3>' +  ppm_ajax.reset_done_title + '</h3><p class="description">' +  ppm_ajax.reset_done_title + '</p><br>' );
				}, 300 );
				setTimeout( function() {
					jQuery( '.mls-modal-content-wrapper' ).slideDown( 300 );
				}, 350 );
			}
		});
	});

	disable_enabled_failed_login_options();
	jQuery( '#ppm-failed-login-policies-enabled' ).change(function() {
		disable_enabled_failed_login_options();
	});

	
	disable_enabled_inactive_users_options();
	jQuery( '#ppm-inactive-users-enabled' ).change(function() {
		disable_enabled_inactive_users_options();
	});

	disable_enabled_timed_login_options();
	jQuery( '#ppm-timed-logins' ).change(function() {
		disable_enabled_timed_login_options();
	});

	disable_enabled_restrict_login_ip_options();
	jQuery( '#mls-restrict-login-ip' ).change(function() {
		disable_enabled_restrict_login_ip_options();
	});

	// Handle multiple role setting.
	check_multiple_roles_status();
	jQuery( '#ppm-users-have-multiple-roles' ).change( check_multiple_roles_status ).change();

	jQuery( "#roles_sortable" ).sortable({
		update: function(event, ui) {       
			var roles = [];
			jQuery( '#roles_sortable [data-role-key]' ).each(function () {
				roles.push( '"' + jQuery(this).attr( 'data-role-key') + '"' );
			});
			jQuery( '#multiple-role-order' ).val( jQuery.parseJSON( '[' + roles + ']' ) );
		},
	});
	jQuery( "#roles_sortable" ).disableSelection();

	// Correct times if something bad is entered.
	jQuery( '.timed-logins-tr [type="number"]:not([name="_ppm_options[restrict_login_ip_count]"])' ).change(function( e ) {		
		var val = parseInt( jQuery( this )[0]['value'] );
		var minval = parseInt( jQuery( this )[0]['min'] );
		var maxval = parseInt( jQuery( this )[0]['max'] );

		if ( val >= minval && val <= maxval  ) {
			if ( val < 10  ) {
				jQuery( this ).val( '0' + val );
			}
			return;
		} else {
			if ( val < minval ) {
				jQuery( this ).val( minval );
			} else if ( val > maxval ) {				
				jQuery( this ).val( maxval );
			}
		}
		
	});

	jQuery( '.timed-logins-tr [type="number"]:not([name="_ppm_options[restrict_login_ip_count]"])' ).each(function () {
		var val = parseInt( jQuery( this )[0]['value'] );
		if ( val < 10  ) {
			jQuery( this ).val( '0' + val );
		}
	});

	jQuery( '.timed-logins-tr select' ).change(function( e ) {	
		var ourName = jQuery( this ).attr( 'name' ).toString();
		var isFromSelect = false

		if( ourName.toLowerCase().includes( 'from_am_or_pm' ) ) {
			isFromSelect = true;
		}

		var ourCurrentVal = jQuery( this ).val();
		var theOtherCurrentVal = jQuery( this ).parent().find( 'select' ).not( this ).val();

		if ( isFromSelect ) {
			if ( ourCurrentVal == 'pm' && theOtherCurrentVal == 'am' ) {
				jQuery( this ).val( 'am' );
			}
		} else {
			if ( ourCurrentVal == 'am' && theOtherCurrentVal == 'pm' ) {
				jQuery( this ).val( 'pm' );
			}
		}
	});

	jQuery( '.timed-login-option input[type="checkbox"]' ).each(function () {
		if ( jQuery( this ).prop('checked') ) {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).removeClass( 'disabled' );
		} else {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).addClass( 'disabled' );
		}
	});

	jQuery( '.timed-login-option input[type="checkbox"]' ).change(function() {
		if ( jQuery( this ).prop('checked') ) {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).removeClass( 'disabled' );
		} else {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).addClass( 'disabled' );
		}
	});
	
	jQuery('body').on('click', 'a#add-login_denied-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#login_geo_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#login_geo_countries').val();

		if ( currentVal.indexOf(newIP) != -1 ) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-comment_denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if ( ! jQuery('#login_geo_countries').val() ) {
			jQuery('#login_geo_countries').val( newIP ).trigger("change");
		} else {
			var newVal = jQuery('#login_geo_countries').val() + ',' +  newIP;
			jQuery('#login_geo_countries').val( newVal ).trigger("change");
		}
		jQuery('#login_geo_countries_input').val('');
	});

	jQuery('body').on('click', 'span#remove-denied-country', function(e) {
		var removingIP = jQuery(this).attr('data-value');
		var textareaValue = jQuery('#login_geo_countries').val();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');
		
		jQuery('#login_geo_countries').val( newValue ).trigger("change");
		jQuery(this).parent().remove();
	});

	jQuery('body').on("change", '#login_geo_countries', function(e) {
		buildDeniedCountries();
	});
	buildDeniedCountries();
} );

function check_multiple_roles_status() {
	if ( jQuery( '#ppm-users-have-multiple-roles' ).prop('checked') ) {
		jQuery( '#sortable_roles_holder' ).removeClass( 'disabled' ).slideDown( 300 );
	} else {
		jQuery( '#sortable_roles_holder' ).slideUp( 300 );
	}
}

function disable_enabled_failed_login_options() {
	jQuery( '.ppmwp-login-block-options' ).addClass( 'disabled' );
	jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-failed-login-policies-enabled' ).prop('checked') ) {
		jQuery( '.ppmwp-login-block-options' ).removeClass( 'disabled' );
		jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_inactive_users_options() {
	jQuery( '#ppmwp-inactive-setting-reset-pw-row, #ppmwp-inactive-setting-row' ).addClass( 'disabled' );
	jQuery( '#ppmwp-inactive-setting-reset-pw-row :input,  #ppmwp-inactive-setting-row :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-inactive-users-enabled' ).prop('checked') ) {
		jQuery( '#ppmwp-inactive-setting-reset-pw-row, #ppmwp-inactive-setting-row' ).removeClass( 'disabled' );
		jQuery( '#ppmwp-inactive-setting-reset-pw-row :input,  #ppmwp-inactive-setting-row :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_timed_login_options() {
	jQuery( '.timed-login-option' ).addClass( 'disabled' );
	jQuery( '.timed-login-option :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-timed-logins' ).prop('checked') ) {
		jQuery( '.timed-login-option' ).removeClass( 'disabled' );
		jQuery( '.timed-login-option :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_restrict_login_ip_options() {
	jQuery( '.restrict-login-option' ).addClass( 'disabled' );
	jQuery( '.restrict-login-option :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#mls-restrict-login-ip' ).prop('checked') ) {
		jQuery( '.restrict-login-option' ).removeClass( 'disabled' );
		jQuery( '.restrict-login-option :input' ).prop( 'disabled', false );
	}
}

/**
 * Shows confirm dialog after click on checkbox with two types of messages: one for checked stated and one for unchecked state.
 *
 * @param obj 		Should be the html input tag
 * @param message_disable		Message to show if checkbox is in checked state and user trying to uncheck it
 * @param message_enable 		Message to show if checkbox is in unchecked state and user trying to check it
 * @returns {boolean}
 */
function confirm_custom_messages(obj, message_disable, message_enable){
	var message;
	if( jQuery(obj).is(':checked') ){
		message = message_enable;
	}
	else{
		message = message_disable;
	}
	return confirm(message);
}

/**
 * Allow only a set of predefined characters to be typed into the input.
 */
function accept_only_special_chars_input( event ) {
	var ch     = String.fromCharCode( event.charCode );
	var filter = new RegExp( ppm_ajax.special_chars_regex );
	if ( ! filter.test( ch ) || event.target.value.indexOf( ch ) > -1 ) {
		event.preventDefault();
	}
}

/**
 * Warn admin to exclude themselves if needed.
 */
function admin_lockout_check( event ) {
	var expiryVal = document.getElementById('ppm-expiry-value').value;	
	if ( expiryVal > 0 && event.target.checked ) {
		tb_show( '' , '#TB_inline?height=110&width=500&inlineId=mls_admin_lockout_notice_modal' );
	}
}

/**
 * Closes the thickbox or redirects users depending on what type of notice is
 * currently on display.
 *
 * @method ppmwp_close_thickbox
 * @since  2.1.0
 * @param  {string} redirect a url to redirect users to on clicking ok.
 */
function ppmwp_close_thickbox( redirect ) {
	if ( 'undefined' !== typeof redirect && redirect.length > 0 ) {
		window.location = redirect;
	} else {
		tb_remove();
	}
}

function getCodesList(justReturnCodes = false) {
	var availableCodes = {
		'Afghanistan': 'AF',
		'Åland Islands': 'AX',
		'Albania': 'AL',
		'Algeria': 'DZ',
		'American Samoa': 'AS',
		'Andorra': 'AD',
		'Angola': 'AO',
		'Anguilla': 'AI',
		'Antarctica': 'AQ',
		'Antigua and Barbuda': 'AG',
		'Argentina': 'AR',
		'Armenia': 'AM',
		'Aruba': 'AW',
		'Australia': 'AU',
		'Austria': 'AT',
		'Azerbaijan': 'AZ',
		'Bahamas': 'BS',
		'Bahrain': 'BH',
		'Bangladesh': 'BD',
		'Barbados': 'BB',
		'Belarus': 'BY',
		'Belgium': 'BE',
		'Belize': 'BZ',
		'Benin': 'BJ',
		'Bermuda': 'BM',
		'Bhutan': 'BT',
		'Bolivia, Plurinational State of': 'BO',
		'Bonaire, Sint Eustatius and Saba': 'BQ',
		'Bosnia and Herzegovina': 'BA',
		'Botswana': 'BW',
		'Bouvet Island': 'BV',
		'Brazil': 'BR',
		'British Indian Ocean Territory': 'IO',
		'Brunei Darussalam': 'BN',
		'Bulgaria': 'BG',
		'Burkina Faso': 'BF',
		'Burundi': 'BI',
		'Cambodia': 'KH',
		'Cameroon': 'CM',
		'Canada': 'CA',
		'Cape Verde': 'CV',
		'Cayman Islands': 'KY',
		'Central African Republic': 'CF',
		'Chad': 'TD',
		'Chile': 'CL',
		'China': 'CN',
		'Christmas Island': 'CX',
		'Cocos (Keeling) Islands': 'CC',
		'Colombia': 'CO',
		'Comoros': 'KM',
		'Congo': 'CG',
		'Congo, the Democratic Republic of the': 'CD',
		'Cook Islands': 'CK',
		'Costa Rica': 'CR',
		'Côte d Ivoire': 'CI',
		'Croatia': 'HR',
		'Cuba': 'CU',
		'Curaçao': 'CW',
		'Cyprus': 'CY',
		'Czech Republic': 'CZ',
		'Denmark': 'DK',
		'Djibouti': 'DJ',
		'Dominica': 'DM',
		'Dominican Republic': 'DO',
		'Ecuador': 'EC',
		'Egypt': 'EG',
		'El Salvador': 'SV',
		'Equatorial Guinea': 'GQ',
		'Eritrea': 'ER',
		'Estonia': 'EE',
		'Ethiopia': 'ET',
		'Falkland Islands (Malvinas)': 'FK',
		'Faroe Islands': 'FO',
		'Fiji': 'FJ',
		'Finland': 'FI',
		'France': 'FR',
		'French Guiana': 'GF',
		'French Polynesia': 'PF',
		'French Southern Territories': 'TF',
		'Gabon': 'GA',
		'Gambia': 'GM',
		'Georgia': 'GE',
		'Germany': 'DE',
		'Ghana': 'GH',
		'Gibraltar': 'GI',
		'Greece': 'GR',
		'Greenland': 'GL',
		'Grenada': 'GD',
		'Guadeloupe': 'GP',
		'Guam': 'GU',
		'Guatemala': 'GT',
		'Guernsey': 'GG',
		'Guinea': 'GN',
		'Guinea-Bissau': 'GW',
		'Guyana': 'GY',
		'Haiti': 'HT',
		'Heard Island and McDonald Islands': 'HM',
		'Holy See (Vatican City State)': 'VA',
		'Honduras': 'HN',
		'Hong Kong': 'HK',
		'Hungary': 'HU',
		'Iceland': 'IS',
		'India': 'IN',
		'Indonesia': 'ID',
		'Iran, Islamic Republic of': 'IR',
		'Iraq': 'IQ',
		'Ireland': 'IE',
		'Isle of Man': 'IM',
		'Israel': 'IL',
		'Italy': 'IT',
		'Jamaica': 'JM',
		'Japan': 'JP',
		'Jersey': 'JE',
		'Jordan': 'JO',
		'Kazakhstan': 'KZ',
		'Kenya': 'KE',
		'Kiribati': 'KI',
		'Korea, Democratic Peoples Republic of': 'KP',
		'Korea, Republic of': 'KR',
		'Kuwait': 'KW',
		'Kyrgyzstan': 'KG',
		'Lao Peoples Democratic Republic': 'LA',
		'Latvia': 'LV',
		'Lebanon': 'LB',
		'Lesotho': 'LS',
		'Liberia': 'LR',
		'Libya': 'LY',
		'Liechtenstein': 'LI',
		'Lithuania': 'LT',
		'Luxembourg': 'LU',
		'Macao': 'MO',
		'Macedonia, the Former Yugoslav Republic of': 'MK',
		'Madagascar': 'MG',
		'Malawi': 'MW',
		'Malaysia': 'MY',
		'Maldives': 'MV',
		'Mali': 'ML',
		'Malta': 'MT',
		'Marshall Islands': 'MH',
		'Martinique': 'MQ',
		'Mauritania': 'MR',
		'Mauritius': 'MU',
		'Mayotte': 'YT',
		'Mexico': 'MX',
		'Micronesia, Federated States of': 'FM',
		'Moldova, Republic of': 'MD',
		'Monaco': 'MC',
		'Mongolia': 'MN',
		'Montenegro': 'ME',
		'Montserrat': 'MS',
		'Morocco': 'MA',
		'Mozambique': 'MZ',
		'Myanmar': 'MM',
		'Namibia': 'NA',
		'Nauru': 'NR',
		'Nepal': 'NP',
		'Netherlands': 'NL',
		'New Caledonia': 'NC',
		'New Zealand': 'NZ',
		'Nicaragua': 'NI',
		'Niger': 'NE',
		'Nigeria': 'NG',
		'Niue': 'NU',
		'Norfolk Island': 'NF',
		'Northern Mariana Islands': 'MP',
		'Norway': 'NO',
		'Oman': 'OM',
		'Pakistan': 'PK',
		'Palau': 'PW',
		'Palestine, State of': 'PS',
		'Panama': 'PA',
		'Papua New Guinea': 'PG',
		'Paraguay': 'PY',
		'Peru': 'PE',
		'Philippines': 'PH',
		'Pitcairn': 'PN',
		'Poland': 'PL',
		'Portugal': 'PT',
		'Puerto Rico': 'PR',
		'Qatar': 'QA',
		'Réunion': 'RE',
		'Romania': 'RO',
		'Russian Federation': 'RU',
		'Rwanda': 'RW',
		'Saint Barthélemy': 'BL',
		'Saint Helena, Ascension and Tristan da Cunha': 'SH',
		'Saint Kitts and Nevis': 'KN',
		'Saint Lucia': 'LC',
		'Saint Martin (French part)': 'MF',
		'Saint Pierre and Miquelon': 'PM',
		'Saint Vincent and the Grenadines': 'VC',
		'Samoa': 'WS',
		'San Marino': 'SM',
		'Sao Tome and Principe': 'ST',
		'Saudi Arabia': 'SA',
		'Senegal': 'SN',
		'Serbia': 'RS',
		'Seychelles': 'SC',
		'Sierra Leone': 'SL',
		'Singapore': 'SG',
		'Sint Maarten (Dutch part)': 'SX',
		'Slovakia': 'SK',
		'Slovenia': 'SI',
		'Solomon Islands': 'SB',
		'Somalia': 'SO',
		'South Africa': 'ZA',
		'South Georgia and the South Sandwich Islands': 'GS',
		'South Sudan': 'SS',
		'Spain': 'ES',
		'Sri Lanka': 'LK',
		'Sudan': 'SD',
		'Suriname': 'SR',
		'Svalbard and Jan Mayen': 'SJ',
		'Swaziland': 'SZ',
		'Sweden': 'SE',
		'Switzerland': 'CH',
		'Syrian Arab Republic': 'SY',
		'Taiwan, Province of China': 'TW',
		'Tajikistan': 'TJ',
		'Tanzania, United Republic of': 'TZ',
		'Thailand': 'TH',
		'Timor-Leste': 'TL',
		'Togo': 'TG',
		'Tokelau': 'TK',
		'Tonga': 'TO',
		'Trinidad and Tobago': 'TT',
		'Tunisia': 'TN',
		'Turkey': 'TR',
		'Turkmenistan': 'TM',
		'Turks and Caicos Islands': 'TC',
		'Tuvalu': 'TV',
		'Uganda': 'UG',
		'Ukraine': 'UA',
		'United Arab Emirates': 'AE',
		'United Kingdom': 'GB',
		'United States': 'US',
		'United States Minor Outlying Islands': 'UM',
		'Uruguay': 'UY',
		'Uzbekistan': 'UZ',
		'Vanuatu': 'VU',
		'Venezuela, Bolivarian Republic of': 'VE',
		'Viet Nam': 'VN',
		'Virgin Islands, British': 'VG',
		'Virgin Islands, U.S.': 'VI',
		'Wallis and Futuna': 'WF',
		'Western Sahara': 'EH',
		'Yemen': 'YE',
		'Zambia': 'ZM',
		'Zimbabwe': 'ZW',
	};

	if (justReturnCodes) {
		var list = getCodesList();
		var justCodes = [];

		jQuery.each(list, function(key, value) {
			justCodes.push(value);
		});

		availableCodes = justCodes;
	}

	return availableCodes;
};

function buildDeniedCountries() {
	if ( jQuery('#login_geo_countries').val() ) {
		var text = jQuery('#login_geo_countries').val();
		var output = text.split(',');
		jQuery( '#login_geo_countries-countries-userfacing').html('<ul>' + jQuery.map(output, function(v) {
			return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-denied-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
		}).join('') + '</ul>');
	}
}