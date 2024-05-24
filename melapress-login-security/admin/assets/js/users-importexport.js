
jQuery( document ).ready( function() {

	jQuery(function() {

		// Handle exporting of settings to JSON.
		jQuery( 'body' ).on( 'click', '[data-export-wpws-users]', function ( e ) {
			e.preventDefault();
			var ourButton = jQuery( this );
			var nonce     = ourButton.attr( 'data-nonce' );

			var key = jQuery( this ).closest( '.logs-management-settings').data( 'key' );
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				async: true,
				data: {
					action: 'mls_export_users',
					nonce: nonce,
				},
				success: function ( result ) {
					var blob = new Blob( [result.data.join('\n')] );
					var link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob, {
						type: 'text/csv;charset=utf-8'
					});
					link.download = "mls_users.csv";
					link.click();
				}
			});
		});

		// Check and import users.
		jQuery( 'body' ).on( 'click', '[data-import-wpws-users]', function ( e ) {
			e.preventDefault();
			jQuery( '#wpws-users-file-output li, #wpws-import-read' ).remove();

			// Check extension.
			var jsonFile = jQuery( '#wpws-users-file' );
			var ext = jsonFile.val().split(".").pop().toLowerCase();
			
			// Alert if wrong file type.
			if( jQuery.inArray( ext, ["txt","csv"] ) === -1 ){
				alert( wpws_import_data.wrongFormat );
				return false;
			}

			build_file_info( false );
		});

		// Proceed with import after checks.
		jQuery( 'body' ).on( 'click', '#proceed-user-import', function ( e ) {
			build_file_info( true );
		});

		jQuery( 'body' ).on( 'click', '.import-users-modal-close', function ( e ) {
			var modal = document.getElementById( "import-users-modal" );
			modal.style.display = "none";
			location.reload();
		});


		// Turn JSON into string and process it.
		function build_file_info( do_import = false ) {
			var fileInput = document.getElementById( "wpws-users-file" );
			var reader = new FileReader();
			reader.readAsText( fileInput.files[0] );

			if ( do_import  ) {
				reader.onload = function () {
					jQuery( '#wpws-import-read' ).remove();
					var csvArray = parseResult( reader.result );	
					var modal = document.getElementById( "import-users-modal" );
					modal.style.display = "block";
	
					// Append list to modal.
					jQuery( '#wpws-users-file-output li' ).each( function( index, value ) {	
						if ( index == 0 ) {
							return;
						}
						
						var thisElem   = jQuery( this );
						var nonce      = jQuery( '[data-import-wpws-users]' ).attr( 'data-nonce' );
						var username   = jQuery( this ).find( '[data-username]' ).attr( 'data-username' ).trim();
						var email      = jQuery( this ).find( '[data-email-address]' ).attr( 'data-email-address' );
						var forceReset = jQuery( '#force-reset' ).is(':checked');
						var importRole = jQuery( '#import-role' ).val();

						if ( ! username || username === '' ) {
							return;
						}
						
						jQuery.ajax({
							type: 'POST',
							url: ajaxurl,
							async: true,
							data: {
								action: 'mls_process_user_import',
								username : username,
								email : email,
								nonce : nonce,
								force_reset : forceReset,
								role : importRole,
							},
							success: function ( result ) {
								if ( result.success ) {
									jQuery( thisElem ).append( '<span class="user-import-result" data-new-user-id="'+ result.data.user_created +'"> User ID ' + result.data.user_created + ' Created. <a target="_blank" href="' + result.data.user_link + '">View Profile</a></span>' );
								} else if ( jQuery( '[data-new-user-id="'+ result.data.user_exists +'"]' ).length == 0 ) {
									jQuery( thisElem ).append( '<span class="user-import-result error" data-new-user-id="'+ result.data.user_exists +'">Username exists</span>' );
								}
							}
						});

						
					});
	
					jQuery( '#wpws-users-actions' ).append( '<br><div id="wpws-import-read" style="display: inline-block;"><input type="button" id="cancel" class="button-secondary import-users-modal-close" value="'+ wpws_import_data.cancelMessage +'"> <input style="margin-left: 10px;" type="button" id="finish" class="button-primary import-users-modal-close" value="OK"></div>' );	
				};
			} else {				
				reader.onload = function () {
					var csvArray = parseResult( reader.result );	
					var modal = document.getElementById( "import-users-modal" );
					modal.style.display = "block";
	
					// Append list to modal.
					jQuery( csvArray ).each(function(index, value) {					
						jQuery( '#wpws-users-file-output' ).append( '<li></li>' );
						jQuery( value ).each( function( index, value ) {
							var dataAttr = index > 0 ? "data-email-address='"+ value.replace(/\"/g,'') +"'" : "data-username='"+ value.replace(/\"/g,'') +"'" ;
							jQuery( '#wpws-users-file-output li:last-of-type').append( '<span class="label" ' + dataAttr.replace(/\"/g,'') + '>' + value.replace(/\"/g,'') + '</span>' );
						});
					});
	
					jQuery( '#wpws-users-actions' ).append( '<br><div id="wpws-import-read" style="display: inline-block;"><input type="button" id="cancel" class="button-secondary import-users-modal-close" value="'+ wpws_import_data.cancelMessage +'"> <input style="margin-left: 10px;" type="button" id="proceed-user-import" class="button-primary" value="'+ wpws_import_data.proceed +'"></div>' );	
				};
			}
		}

		function parseResult(result) {
			var resultArray = [];
			result.split("\n").forEach(function(row) {
				var rowArray = [];
				row.split(",").forEach(function(cell) {
					rowArray.push(cell);
				});
				resultArray.push(rowArray);
			});
			return resultArray;
		}

		jQuery( 'body' ).on( 'mouseenter', '[data-help]', function ( e ) {
			var message = jQuery( this ).data( 'help-text' );
			jQuery( this ).append( '<div class="tooltip help-msg">'+ message +'</div>' );
		});

		jQuery( 'body' ).on( 'mouseout', '[data-help]', function ( e ) {
			if ( jQuery( '.help-msg:hover' ).length != 0 ) {
				setTimeout( function() {
					jQuery( '.help-msg' ).fadeOut( 800 );
				}, 1000 );
			}
		});
	});
 });
