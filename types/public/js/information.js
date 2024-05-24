//noinspection JSUnusedAssignment
var Types = Types || {};

Types.information = Types.information || {};

( function( $ ) {

	var $body = $('body');

    Types.information.openDialog = function( id ) {
        // dialog
        var dialog = $( '#' + id );

        if( dialog.length ) {
            dialog.dialog( {
                dialogClass : 'wp-dialog types-information-dialog',
                modal : true,
                autoOpen : false,
                closeOnEscape : true,
                minWidth: 800,
                open: function() {
                    dialog.find( 'a' ).blur();
                },
            } ).dialog( 'open' );
        }
    };

	$body.on( 'click', '[data-types-open-dialog]', function() {
        Types.information.openDialog( $( this ).data( 'types-open-dialog' ) );
    } );

    Types.information.openPointer = function( trigger ) {
        var content = $( '#' + trigger.data( 'types-open-pointer' ) );
        $( '.types-information-active-pointer' ).pointer( 'close' );

        if( trigger.length ) {
            trigger.addClass( 'types-information-active-pointer' );
            trigger.pointer( {
                pointerClass : 'types-information-pointer',
                content: content.html(),
                position: {
                    edge: 'bottom',
                    align: 'right'
                },
                buttons: function( event, t ) {
                    var button_close = $( '<a href="javascript:void(0);" class="notice-dismiss alignright"></a>' );
                    button_close.on( 'click.pointer', function( e ) {
                        e.preventDefault();
                        t.element.pointer( 'close' );
                    });
                    return button_close;
                },
                show: function( event, t ){
                    t.pointer.css( 'marginLeft', '54px' );
                },
                close: function( event, t ){
                    t.pointer.css( 'marginLeft', '0' );
                },
            } ).pointer( 'open' );
        }
    };

	$body.on( 'click', '[data-types-open-pointer]', function() {
        Types.information.openPointer( $( this ) );
    });

	$( '.data-types-open-pointer-hover' ).mouseover(function() {
		Types.information.openPointer( $( this ) );
	}).mouseout(function() {
		$( '.types-information-active-pointer' ).pointer( 'close' );
	});

	/**
	 * Show or hide the select GUI for changing the post type's editor mode.
	 *
	 * @param {string} postType Post type slug.
	 * @param {bool} show Show the select GUI?
	 * @since 3.2.2
	 */
	var showEditorModeSelect = function(postType, show) {
		if(show) {
			$('.toolset-dashboard__editor-mode-selection[data-post-type="' + postType + '"]').show();
			$('.toolset-dashboard__editor-mode-label[data-post-type="' + postType + '"]').hide();
		} else {
			$('.toolset-dashboard__editor-mode-selection[data-post-type="' + postType + '"]').hide();
			$('.toolset-dashboard__editor-mode-label[data-post-type="' + postType + '"]').show();
		}
	};


	/**
	 * Edit the post type editor mode.
	 *
	 * @since 3.2.2
	 */
	$body.on('click', '.toolset-dashboard__editor-mode-change', function(e) {
		var postType = $(e.target).data('post-type');
    	showEditorModeSelect(postType, true);
    });


	/**
	 * Update the post type editor mode via AJAX after the user confirms their choice.
	 *
	 * @since 3.2.2
	 */
	$body.on('click', '.toolset-dashboard__editor-mode-selection > button', function(e) {
		var postType = $(e.target).data('post-type');
		var nonce = $('.toolset-dashboard__editor_mode_update_nonce').text();
		var $selectedOption = $('.toolset-dashboard__editor-mode-selection[data-post-type="' + postType + '"] > select').children('option:selected');
		var editorMode = $selectedOption.val();

		$.post({
			url: ajaxurl,
			async: true,
			data: {
				action: 'types_set_editor_mode',
				wpnonce: nonce,
				post_type: postType,
				editor_mode: editorMode
			},
			success: function(originalResponse) {
				if(originalResponse.success) {
					$('.toolset-dashboard__editor-mode-label-value[data-post-type="' + postType + '"]').text($selectedOption.text());
				}
				showEditorModeSelect(postType, false)
			},
			failure: function(ajaxContext) {
				console.log(ajaxContext);
				showEditorModeSelect(postType, false)
			}
		});
	});

} )( jQuery );
