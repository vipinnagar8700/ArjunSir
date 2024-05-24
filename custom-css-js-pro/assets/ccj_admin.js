jQuery(document).ready( function($) { 

    $('.page-title-action').hide();

    var postID = document.getElementById('post_ID') != null ? document.getElementById('post_ID').value : 0;

    var getCookie = function (name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
    };

    // Initialize the CodeMirror editor
    if ( $('#ccj_content').length > 0 ) {
        var content_mode = $("#ccj_content").attr('mode');
        if ( content_mode == 'text/css' ) {
            var preprocessor = $('input[name=custom_code_preprocessor]:checked').val();
            if ( preprocessor == 'sass' ) content_mode = 'text/x-scss'; 
            if ( preprocessor == 'less' ) content_mode = 'text/x-less'; 
        }

		CCJ.codemirror.theme = $("#editor_theme").val();
		CCJ.codemirror.mode = content_mode;
		CCJ.codemirror.extraKeys.F11 = function(cm) {
        	cm.setOption("fullScreen", !cm.getOption("fullScreen"));
			fullscreen_buttons( true );
			var cookies = (getCookie('ccj-' + postID) || '0,0,0,0').split(',');
			document.cookie = 'ccj-' + postID + '=' + [cookies[0], cookies[1], cookies[2], 1].join(',') + '; SameSite=Lax';
		};
        CCJ.codemirror.extraKeys.Esc = function(cm) {
			if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
			fullscreen_buttons( false );
			var cookies = (getCookie('ccj-' + postID) || '0,0,0,0').split(',');
			document.cookie = 'ccj-' + postID + '=' + [cookies[0], cookies[1], cookies[2], 0].join(',') + '; SameSite=Lax';
		};


		// Linting
		if ( CCJ.codemirror.lint ) {
			if ( CCJ.codemirror.mode == 'text/javascript' ) {
				CCJ.codemirror.lint = CCJ.jshint;
			}

			if ( CCJ.codemirror.mode == 'text/css' ) {
				CCJ.codemirror.lint = CCJ.csslint;
			}

			if ( preprocessor == 'sass' ) {
				CCJ.codemirror.lint = {options:{rules:CCJ.sasslint}};
			}
		}

		// Switch the linting when switching the CSS preprocessor
        $('input[name=custom_code_preprocessor]:radio').change(function(){
			if ( ! CCJ.codemirror.lint ) return;
			editor.setOption('lint', false);
			switch ( $(this).val() ) {
				case 'sass' : 
            		editor.setOption('mode', 'text/x-scss' ); 
					editor.setOption('lint', {options:{rules:CCJ.sasslint}});
					break;
				case 'less' :
            		editor.setOption('mode', 'text/x-less' ); 
					editor.setOption('lint', CCJ.csslint);
					break;
				default :
            		editor.setOption('mode', 'text/css' ); 
					editor.setOption('lint', CCJ.csslint);
					break;

			}
        });

        var editor = CodeMirror.fromTextArea(document.getElementById("ccj_content"), CCJ.codemirror);

		// Code folding
		editor.setOption("lineNumbers", true);
		editor.setOption("lineWrapping", true);
		editor.setOption("foldGutter", true);
		editor.setOption("gutters", ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]);
		CCJ.codemirror.extraKeys["Ctrl-Q"] = function(cm){ cm.foldCode(cm.getCursor()); };

		// Note: ccj-postID cookie will save cursor line, cursor character, editor height and fullscreen values
		var cookies = (getCookie('ccj-' + postID) || '0,0,0,0').split(',');

        // Make the editor resizable
        var cm_width = $('#title').width() + 16;
		var cm_height = (parseFloat(cookies[2]) >= 200) ? parseFloat(cookies[2]) : 500;
        editor.setSize(cm_width, cm_height);

        $('.CodeMirror').resizable({
            resize: function() {
                editor.setSize($(this).width(), $(this).height());
            } ,
            maxWidth: cm_width,
            minWidth: cm_width,
            minHeight: 200
            
        });

        $(window).resize(function () { 
            var cm_width = $('#title').width() + 16;
            var cm_height = $('.CodeMirror').height();
            editor.setSize(cm_width, cm_height);
        });


		// Autocomplete
		if ( CCJ.autocomplete === '1' ) {
			editor.on( "keyup", function ( cm, event ) {
				if ( ! cm.state.completionActive && event.keyCode > 64 && event.keyCode < 91 ) {
					CodeMirror.commands.autocomplete( cm, null, { completeSingle: false } );
				}
			});
		}

        // Code Beautifier
        $("#ccj-beautifier").click(function(e){
            CodeMirror.commands["selectAll"](editor);
            editor.autoFormatRange(editor.getCursor(true), editor.getCursor(false));
            editor.setCursor(0);
            e.preventDefault();
        });


        // Adjust the gutter size to the editor
        var gutter_size = parseInt( $(".code-mirror-after div").css('margin-left') );
        var current_gutter_size = parseInt( $(".CodeMirror-gutter-wrapper").css('left') );
        if ( gutter_size != current_gutter_size ) {
            gutter_size = -current_gutter_size -1 ;  
            gutter_size += 'px';
            $(".code-mirror-after div").css('margin-left', gutter_size );
            $(".code-mirror-before div").css('margin-left', gutter_size );
        }

        // Keep the adjustments to the gutter size after browser resize
        editor.on( 'update', function() {
            var current_gutter_size = parseInt( $(".CodeMirror-gutter-wrapper").css('left') );
            if ( gutter_size != current_gutter_size ) {
                gutter_size = -current_gutter_size -1 ;  
                gutter_size += 'px';
                $(".code-mirror-after div").css('margin-left', gutter_size );
                $(".code-mirror-before div").css('margin-left', gutter_size );
            }
        });


        // Saving cursor state
        editor.on('cursorActivity', function () {
            var curPos = editor.getCursor();
            document.cookie = 'ccj-' + postID + '=' + [curPos.line, curPos.ch, cookies[2], cookies[3]].join(',') + '; SameSite=Lax';
        });

        // Restoring cursor state
        editor.setCursor(parseFloat(cookies[0]), parseFloat(cookies[1]));

		// Save the editor's height
		editor.on('refresh', function() {
			var height = ( !editor.getOption('fullScreen') ) ? $('.CodeMirror').height() : cookies[2];
			var curPos = editor.getCursor();
			document.cookie = 'ccj-' + postID + '=' + [curPos.line, curPos.ch, height, Number(editor.getOption('fullScreen'))].join(',') + '; SameSite=Lax';
		});

		// Save the custom code when hitting "Ctrl-S"
		editor.on('keydown', function(cm, event) {
			if ( ! event.ctrlKey && ! event.metaKey || event.which !== 83 ) return;

			var height = ( !editor.getOption('fullScreen') ) ? $('.CodeMirror').height() : cookies[2];
			var curPos = editor.getCursor();
			document.cookie = 'ccj-' + postID + '=' + [curPos.line, curPos.ch, height, Number(editor.getOption('fullScreen'))].join(',') + '; SameSite=Lax';

			$("form#post").submit();
            event.preventDefault();
            return false;
		});

		// Restoring fullscreen
		editor.setOption("fullScreen", parseFloat(cookies[3]));
        fullscreen_buttons( Boolean(parseFloat(cookies[3])) );

		// Pour the CodeMirror editor's content into <textarea id="ccj_content> before generating the preview
		if ( $('#ccj-preview').length > 0 ) {
			$('#ccj-preview').click( function(e) {
				$("#ccj_content").val( editor.getValue() );
			});
		}

		// Action for the `fullscreen` button
		$("#ccj-fullscreen-button").click( function() {
			editor.triggerOnKeyDown({type: 'keydown', keyCode: 122});
		});

		$("#publish").click(function(e){
			var cookies = (getCookie('ccj-' + postID) || '0,0,0,0').split(',');
			var curPos = editor.getCursor();
			document.cookie = 'ccj-' + postID + '=' + [curPos.line, curPos.ch, cookies[2], Number(editor.getOption('fullScreen'))].join(',') + '; SameSite=Lax';
		});
	}

    // Enable the tipsy 
    $('span[rel=tipsy].tipsy-no-html').tipsy({fade: true, gravity: 's'});
    $('span[rel=tipsy]').tipsy({fade: true, gravity: 's', html: true});

    // Toggle the buttons when in fullscreen mode
    function fullscreen_buttons( mode ) {
        editor.focus();
        if ( mode === true ) {
            $("#publish").css({
                'position'  : 'fixed',
                'right'     : '40px',
                'bottom'    : '40px',
                'z-index'   : 100005,
            });
        } else {
            $("#publish").css({
                'position'  : 'static',
                'right'     : 'initial',
                'bottom'    : 'initial',
                'z-index'   : 10,
            });
        }
    }


    // Ask for name when "As shortcode" is chosen
    var old_code_name = '';
    $('input[type=radio][name=custom_code_type]').change(function() {
        if ( this.value == 'shortcode' ) {
            if ( $('#custom_code_name').length === 0 ) {
                var name_input = '<div id="custom_code_name_div"><label for="custom_code_name">Shortcode id: </label> <input type="text" name="custom_code_name" id="custom_code_name" value="'+old_code_name+'" /></div>';
                $(this.closest('div.radio-group')).append(name_input);
            }
            $('#custom_code_name').focus();
            toggle_fields_for_shortcode( false, 500 );
        } else {
            if ( $('#custom_code_name_div').length != 0 ) {
                old_code_name = $('#custom_code_name').val();
                $('#custom_code_name_div').remove();
            }
            toggle_fields_for_shortcode( true, 500 );
        }
    });

    // Toggle some fields when `shortcode` is enabled
    function toggle_fields_for_shortcode( toggle, time ) {
        var ids = ".options_meta_box h3:nth-child(3)" +
            ", .options_meta_box .radio-group:nth-child(4)" +
            ", .options_meta_box h3:nth-child(5)" +
            ", .options_meta_box .radio-group:nth-child(6)" +
            ", .options_meta_box h3:nth-child(7)" +
            ", .options_meta_box .radio-group:nth-child(8)" +
            ", #previewdiv" +
            ", #url-rules";

        if ( toggle === true ) {
            $(ids).show( time );
        } else {
            $(ids).hide( time );
        }
    }
    if ( $('input[name=custom_code_type]').length > 0 && $('input[name=custom_code_type]:checked').val() == 'shortcode' ) {
        toggle_fields_for_shortcode( false, 0 );
    }


    // Check for shortcode name before saving
    $('#post').submit(function(e) {
        if ( $('input[name=custom_code_type]:checked').val() === 'shortcode' && $('#custom_code_name').val().length === 0 ) {
            alert('You forgot to give an ID to this shortcode');
            $('#custom_code_name').focus();
            e.preventDefault();
        }
    });


    // Ask for id for Less and SASS proprocessors 
    var old_preprocessor_id = '';
    $('input[type=radio][name=custom_code_preprocessor]').change(function() {
        if ( this.value == 'less' || this.value === 'sass' ) {
            if ( $('#custom_code_preid').length === 0 ) {

                var preid_info_text = 'This ID can be used in a Less/SASS import statement from another custom CSS code. Example: if this ID is \'vars.scss\', then you can write <b>@import \'vars.scss\';</b> in another custom Less/SASS code in order to import it. Leave the ID empty if it will not be imported from another custom code.';
                var preid_info = '<span class="dashicons dashicons-editor-help" rel="tipsy" original-title="'+preid_info_text+'"></span>';
                var preid_input = '<div id="custom_code_preid_div">'+preid_info+' <label for="custom_code_preid">Id: </label> <input type="text" name="custom_code_preid" id="custom_code_preid" value="'+old_preprocessor_id+'" /></div>';
                $(this.closest('div.radio-group')).append(preid_input);
                $('.dashicons-editor-help').tipsy({fade: true, gravity: 's', html: true});
            }
            $('#custom_code_preid').focus();
            $('#custom_code_preid').on('input', function() {
                if ( this.value.length > 0 ) {
                    toggle_fields_for_preprocessor( false, 500 );
                } else {
                    toggle_fields_for_preprocessor( true, 500 );
                }
            });
        } else {
            if ( $('#custom_code_preid_div').length != 0 ) {
                old_preprocessor_id = $('#custom_code_preid').val();
                $('#custom_code_preid_div').remove();
            }
            toggle_fields_for_preprocessor( true, 500 );
        }
    });


    // Toggle some fields when `CSS preprocessor` is Less or SASS
    function toggle_fields_for_preprocessor(toggle, time) {
        var ids = 
            ".options_meta_box h3:nth-child(1)" +
            ", .options_meta_box .radio-group:nth-child(2)" +
            ", .options_meta_box h3:nth-child(2)" +
            ", .options_meta_box .radio-group:nth-child(3)" +
            ", .options_meta_box h3:nth-child(3)" +
            ", .options_meta_box .radio-group:nth-child(4)" +
            ", .options_meta_box h3:nth-child(5)" +
            ", .options_meta_box .radio-group:nth-child(6)" +
            ", .options_meta_box h3:nth-child(9)" +
            ", .options_meta_box .radio-group:nth-child(10)" +
            ", .options_meta_box h3:nth-child(11)" +
            ", .options_meta_box .radio-group:nth-child(12)" +
            ", #previewdiv" +
            ", #url-rules";

        if ( toggle === true ) {
            $(ids).show( time );
        } else {
            $(ids).hide( time );
        }
    } 
    if( $('input[name=custom_code_preprocessor]:checked').val() == 'sass'
        || $('input[name=custom_code_preprocessor]:checked').val() == 'less') {
        if( $('#custom_code_preid').length && $('#custom_code_preid').val().length > 0 ) {
            toggle_fields_for_preprocessor( false, 0 );
        }
        $('#custom_code_preid').on('input', function() {
            if ( this.value.length > 0 ) {
                toggle_fields_for_preprocessor( false, 500 );
            } else {
                toggle_fields_for_preprocessor( true, 500 );
            }
        });
    }

    // Filter the admin Settings page
    if ( $('#ccj_settings').length > 0 ) {
        $('#ccj_settings').change( function() {
            
            // "Duration of Preview" > 0
            if ( $("#ccj_duration_preview").length > 0 ) {
                var value = $("#ccj_duration_preview").val();
                value = parseInt( value );
                if ( isNaN(value) || value <= 0 ) {
                    alert('"Duration of Preview" field accepts only integers > 0');
                }
            }

            if ( $("#ccj_role-current_user").length > 0 ) {
                var username = $("#ccj_role-current_user").val();
                var the_rights = null;

                var partial = $("#ccj_role-user_ids_partial").val();
                if ( partial.indexOf( username ) >= 0 ) {
                    the_rights = 'partial';
                }

                var none = $("#ccj_role-user_ids_none").val();
                if ( none.indexOf( username ) >= 0 ) {
                    the_rights = 'none';
                }

                if ( the_rights !== null ) {
                    alert('Your username is in the list of users with '+ the_rights +' rights. You are locking yourself out of the Settings page. Please remove your username, unless you know what you\'re doing.');
                }
            }
        }); 
    }

    // For post.php or post-new.php pages show the code's title in the page title
    if ( $('#titlediv #title').length > 0 ) {
        var new_title = $("input[name=custom_code_language]").val().toUpperCase() + ' - ' + $('#titlediv #title').val();
        if( $('#titlediv #title').val().length > 0 ) {
            $(document).prop('title', new_title );
        }
        $('#titlediv #title').change(function() {
            if ( $(this).val().length > 0 ) {
                $(document).prop('title', new_title);
            } 
        });
    }


    // Make the inactive rows opaque
    if ( $('.dashicons-star-empty.ccj_row').length > 0 ) {
        $('.dashicons-star-empty.ccj_row').each(function(){
            $(this).parent().parent().parent().css('opacity', '0.4');
        });
    }

    // Activate/deactivate codes with AJAX
    $(".ccj_activate_deactivate").click( function(e) {
        var url = $(this).attr('href');
        var code_id = $(this).attr('data-code-id');
        e.preventDefault(); 
        $.ajax({
            url: url, 
            success: function(data){
                if (data === 'yes') {
                    ccj_activate_deactivate(code_id, false);
                }
                if (data === 'no') {
                    ccj_activate_deactivate(code_id, true);
                }
            }
        });
    });


	// The "After <body> tag" option cannot go together with the "In Admin" option
	custom_code_type_change();
	$( 'input[name=custom_code_type]' ).on( 'change', custom_code_type_change );
	function custom_code_type_change() {
		if ( $( 'input[name=custom_code_type]:checked' ).val() === 'body_open' ) {
			$( '#custom_code_side-admin' ).prop( 'disabled', true );
			if ( $( 'input[name=custom_code_side]:checked' ).val() === 'admin' ) {
				$( '#custom_code_side-admin' ).prop( 'checked', 'checked' );
			}
		} else {
			$( '#custom_code_side-admin' ).prop( 'disabled', false );
		}
	}
	custom_code_side_change();
	$( 'input[name=custom_code_side]' ).on( 'change', custom_code_side_change );
	function custom_code_side_change() {
		if ( $( 'input[name=custom_code_side]:checked' ).val() === 'admin' ) {
			$( '#custom_code_type-body_open' ).prop( 'disabled', true );
		} else {
			$( '#custom_code_type-body_open' ).prop( 'disabled', false );
			if ( $( 'input[name=custom_code_type]:checked' ).val() === 'body_open' ) {
				$( '#custom_code_type-body_open' ).prop( 'checked', true );
			}
		}
	}

    // Toggle the signs for activating/deactivating codes
    function ccj_activate_deactivate(code_id, action) {
        var row = $('tr#post-'+code_id);
        if ( action === true ) {
            row.css('opacity', '1');
            row.find('.row-actions .ccj_activate_deactivate')
                .text(CCJ.deactivate)
                .attr('title', CCJ.active_title);
            row.find('td.active .dashicons')
                .removeClass('dashicons-star-empty')
                .addClass('dashicons-star-filled');
            row.find('td.active .ccj_activate_deactivate')
                .attr('title', CCJ.active_title);
            $('#activate-action span').text(CCJ.active);
            $('#activate-action .ccj_activate_deactivate').text(CCJ.deactivate);
        } else {
            row.css('opacity', '0.4');
            row.find('.row-actions .ccj_activate_deactivate')
                .text(CCJ.activate)
                .attr('title', CCJ.deactive_title);
            row.find('td.active .dashicons')
                .removeClass('dashicons-star-filled')
                .addClass('dashicons-star-empty');
            row.find('td.active .ccj_activate_deactivate')
                .attr('title', CCJ.deactive_title);
            $('#activate-action span').text(CCJ.inactive);
            $('#activate-action .ccj_activate_deactivate').text(CCJ.activate);
        }
    }



    // Permalink slug
    $( '#titlediv' ).on( 'click', '.ccj-edit-slug', function() {
		var i, 
			$el, revert_e,
			c = 0,
            slug_value = $('#editable-post-name').html(),
			real_slug = $('#post_name'),
			revert_slug = real_slug.val(),
			permalink = $( '#sample-permalink' ),
			permalinkOrig = permalink.html(),
			permalinkInner = $( '#sample-permalink a' ).html(),
            permalinkHref = $('#sample-permalink a').attr('href'),
			buttons = $('#ccj-edit-slug-buttons'),
			buttonsOrig = buttons.html(),
			full = $('#editable-post-name-full');

		// Deal with Twemoji in the post-name.
		full.find( 'img' ).replaceWith( function() { return this.alt; } );
		full = full.html();

		permalink.html( permalinkInner );

		// Save current content to revert to when cancelling.
		$el = $( '#editable-post-name' );
		revert_e = $el.html();

		if ( typeof postL10n === 'undefined' || postL10n.cancel === '' || postL10n.ok === '' ) {
			postL10n = {
				ok     : wp.i18n.__( 'OK' ),
				cancel : wp.i18n.__( 'Cancel' ),
			}
		}

        buttons.html( '<button type="button" class="save button button-small">' + postL10n.ok + '</button> <button type="button" class="cancel button-link">' + postL10n.cancel + '</button>' );


        // Save permalink changes.
		buttons.children( '.save' ).click( function() {
			var new_slug = $el.children( 'input' ).val();

			if ( new_slug == $('#editable-post-name-full').text() ) {
				buttons.children('.cancel').click();
				return;
			}

			$.post(
				ajaxurl,
				{
					action: 'ccj_permalink',
					code_id: $('#post_ID').val(),
					new_slug: new_slug,
                    permalink: permalinkHref, 
					filetype: $('#editable-post-name-full').data('filetype'), 
					ccj_permalink_nonce: $('#ccj-permalink-nonce').val()
				},
				function(data) {
					var box = $('#edit-slug-box');
					box.html(data);
					if (box.hasClass('hidden')) {
						box.fadeIn('fast', function () {
							box.removeClass('hidden');
						});
					}
				}
			);
		});

		// Cancel editing of permalink.
		buttons.children( '.cancel' ).click( function() {
			$('#view-post-btn').show();
			$el.html(revert_e);
			buttons.html(buttonsOrig);
			permalink.html(permalinkOrig);
			real_slug.val(revert_slug);
			$( '.ccj-edit-slug' ).focus();
		});

		$el.html( '<input type="text" name="new_slug" id="new-post-slug" value="' + slug_value + '" autocomplete="off" />' ).children( 'input' ).keydown( function( e ) {
			var key = e.which;
			// On [enter], just save the new slug, don't save the post.
			if ( 13 === key ) {
				e.preventDefault();
				buttons.children( '.save' ).click();
			}
			// On [esc] cancel the editing.
			if ( 27 === key ) {
				buttons.children( '.cancel' ).click();
			}
		} ).keyup( function() {
			real_slug.val( this.value );
		}).focus();


    });



});
