jQuery( document ).ready( function() {
	jQuery( '.unlock-inactive-user-button' ).click(
		function() {
			jQuery( this ).text( inactiveUsersStrings.resettingUser );
			jQuery( this ).attr( 'disabled', true );
			ppmwpResetSingleUser( jQuery( this ).val(), this );
		}
	);
	jQuery( '#ppmwp_inactive_check_now' ).click(
		function() {
			jQuery( this ).attr( 'disabled', true );
			ppmwpInactiveCheck( this );
			setTimeout( function(){ 
				location.reload();
			}, 1000 );
		}
	);
} );

function ppmwpResetSingleUser( id, button ) {
	var targetElm = jQuery( button );
	var is_user_blocked = targetElm.attr( 'data-is-blocked-user' );
	jQuery.ajax(
		{
			type : "post",
			dataType : "json",
			url : ajaxurl,
			data : {
				action: 'ppmwp_unlock_inactive_user',
				user: id,
				unblocking_user: is_user_blocked,
				_wpnonce: inactiveUsersData.nonce,
			},
			success: function(response) {
				if ( response.success ) {
					if ( response.data.reset_time ) {
						jQuery( button ).text( inactiveUsersStrings.resetDone );
						jQuery( button ).attr( 'disabled', true );
						jQuery( button ).parent().parent()
							.fadeOut(
								200,
								function() {
									jQuery( this ).remove();
									// if the list is now empty then show a message.
									if ( 0 === jQuery( '#the-list' ).find( 'td' ).length ) {
										var emptyTableElement = document.createElement( 'tr' );
										jQuery( emptyTableElement ).addClass( 'no-items' );
										var emptyTableInner = document.createElement( 'td' );
										jQuery( emptyTableInner ).addClass( 'colspanchange' );
										// for IE we need to explicitly set this as string.
										emptyTableInner.setAttribute( 'colspan', '' + 5 );
										jQuery( emptyTableInner ).text( inactiveUsersStrings.noUsers );
										jQuery( emptyTableElement ).append( emptyTableInner );
										jQuery( '#the-list' ).append( emptyTableElement );
									}
								}
							);


					}
				}
			}
		}
	);
}

function ppmwpInactiveCheck( button ) {
	jQuery.ajax(
		{
			type : "post",
			dataType : "json",
			url : ajaxurl,
			data : {
				action: 'ppmwp_inactive_users_check',
				_wpnonce: jQuery( button ).data( 'nonce' ),
			},
			success: function(response) {
				if ( response.success ) {
					// if the inactive users were changed then reload the page.
					if ( response.data.changed ) {
						jQuery( button ).text( inactiveUsersStrings.buttonReloading );
						location.reload();
					} else {
						jQuery( button ).attr( 'disabled', false );
					}
				}
			}
		}
	);
}
