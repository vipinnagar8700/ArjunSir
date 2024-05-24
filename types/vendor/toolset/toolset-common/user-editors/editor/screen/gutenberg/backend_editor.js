/**
 * Backend script to be used when editing Content Templates using Gutenberg.
 *
 * @summary Content Template editor manager for Gutenberg.
 *
 * @since 2.6.9
 * @since 2.8 Require codemirror
 * @requires jquery.js
 * @requires underscore.js
 * @requires codemirror
 */

/* global toolset_user_editors_native */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.GutenbergEditor = function( $ ) {

    var self = this;

    // Some initial data
    self.i18n = window.toolset_user_editors_gutenberg_script_i18n;
    self.id = self.i18n.id;
    self.selectedUsagesLength = $( '.js-wpv-content-template-usage-selector:checked' ).length;
    self.$selectedFirstUsage = $( '.js-wpv-content-template-usage-selector:checked:first' );

    /**
     * Get the button to apply a single pages usage to all existing posts.
     *
     * @since Views 2.8
     */
    self.getApplyToDissidentsButton = function() {
        return $( '<a href="#" class="button button-small js-wpv-content-template-usage-selector-kill-dissident">' + self.i18n.killDissidentPosts.buttonLabel + '</a>' );
    };

    /**
     * Get a native Gutenberg spinner.
     *
     * @since Views 2.8
     */
    self.getSpinner = function() {
        return $( '<span class="components-spinner"></span>' );
    };

    self.init = function() {
        if ( ToolsetCommon.UserEditor.hasOwnProperty( 'NativeEditorInstance' ) ) {
            // Deactivate the alternative syntax for shortcodes in the Gutenberg editor
            Toolset.hooks.removeFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', ToolsetCommon.UserEditor.NativeEditorInstance.secureShortcodeFromSanitization );
            Toolset.hooks.removeFilter( 'wpv-filter-wpv-shortcodes-transform-format', ToolsetCommon.UserEditor.NativeEditorInstance.secureShortcodeFromSanitization );
            Toolset.hooks.removeFilter( 'toolset-filter-get-crafted-shortcode', ToolsetCommon.UserEditor.NativeEditorInstance.secureShortcodeFromSanitization, 11 );
        }

        return self;
    };

    /**
     * Initialize the Codemirror CSS editor.
     *
     * @since Views 2.8
     */
    self.initCssEditor = function() {
        var head = document.head || document.getElementsByTagName('head')[0];

        self.cssStyle = document.createElement( 'style' );
        head.appendChild( self.cssStyle );
        self.cssStyle.type = 'text/css';

        self.cssEditor = CodeMirror.fromTextArea( document.getElementById( 'wpv_template_extra_css' ), {
            lineNumbers: true,
            lineWrapping: true
        });

        // Automatic height based on content length
        _.defer( function() {
            $( '.CodeMirror' ).css( 'height', 'auto' );
            $( '.CodeMirror-scroll' ).css( {'overflow-y':'hidden', 'overflow-x':'auto', 'min-height':'15em'} );
            self.cssEditor.refresh();
        });

        // Refresh when opening the container metabox
        $( document ).on( 'click', '#wpv-content-template-css-metabox .hndle', function() {
            self.cssEditor.refresh();
        });

        // Save the Codemirror content to the live inline CSS added to the head of the document
        self.cssEditorApply = function() {
            self.cssStyle.innerHTML = self.cssEditor.getValue();
        }

        // Debounce-save the Codemirror editor content to its underlying textarea, and live apply it
        self.cssEditorSave = function() {
            self.cssEditor.save();
            self.cssEditorApply();
        };
        self.cssEditorSaveDebounced = _.debounce( self.cssEditorSave, 2000 );
        self.cssEditor.on( 'change', function() {
            self.cssEditorSaveDebounced();
        });

        self.cssEditorApply();

        return self;
    }

	/**
	 * Initialize the Codemirror JS editor.
	 *
	 * Note that while changes happen in the Codemirror JS editor, these change won't be reflected on the editor preview,
	 * but only on the frontend.
	 *
	 * @since Views 3.2
	 */
	self.initJsEditor = function() {
		self.jsEditor = CodeMirror.fromTextArea( document.getElementById( 'wpv_template_extra_js' ), {
			lineNumbers: true,
			lineWrapping: true
		});

		// Automatic height based on content length
		_.defer( function() {
			$( '.CodeMirror' ).css( 'height', 'auto' );
			$( '.CodeMirror-scroll' ).css( {'overflow-y':'hidden', 'overflow-x':'auto', 'min-height':'15em'} );
			self.jsEditor.refresh();
		});

		// Refresh when opening the container metabox
		$( document ).on( 'click', '#wpv-content-template-js-metabox .hndle', function() {
			self.jsEditor.refresh();
		});

		// Debounce-save the Codemirror editor content to its underlying textarea, and live apply it
		self.jsEditorSave = function() {
			self.jsEditor.save();
		};
		self.jsEditorSaveDebounced = _.debounce( self.jsEditorSave, 2000 );
		self.jsEditor.on( 'change', function() {
			self.jsEditorSaveDebounced();
		});

		return self;
	};

    /**
     * Check whether any single page usages holds dissident posts.
     *
     * @since Views 2.8
     */
    self.checkDissidentPosts = function() {
        $( '.js-wpv-content-template-usage-selector' ).each( function() {
            var $input = $( this ),
                $item = $input.closest( 'li' );

            if ( $item.find( '.js-wpv-content-template-usage-selector-kill-dissident' ).length ) {
                return;
            }

            if (
                $input.prop( 'checked' )
                && $input.hasClass( 'js-wpv-content-template-usage-selector-has-dissident' )
            ) {
                var $button = self.getApplyToDissidentsButton();
                $item.append( $button );
            }
        });
    };

    /**
     * Kill dissident posts from a given post type.
     *
     * @param string postType
     * @since Views 2.8
     */
    self.killDissidentPosts = function( postType ) {
        var $input = $( 'input.js-wpv-content-template-usage-selector-has-dissident[value="' + postType + '"]' ),
            $item = $input.closest( 'li' ),
            $button = $item.find( '.js-wpv-content-template-usage-selector-kill-dissident' );
        $spinner = self.getSpinner();

        $button.addClass( 'button-disabled' ).prop( 'disabled', true );
        $( '.edit-post-meta-boxes-area.is-side' )
            .addClass( 'is-loading' )
            .prepend( $spinner );;

        var data = {
            action: self.i18n.killDissidentPosts.action,
            postType: postType,
            ctId: self.id,
            wpnonce: self.i18n.killDissidentPosts.nonce
        };

        $.ajax({
            type: "POST",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
                    $input.removeClass( 'js-wpv-content-template-usage-selector-has-dissident' );
                    $button.remove();
                } else {
                    $button.removeClass( 'button-disabled' ).prop( 'disabled', false );
                }
            },
            error: function( ajaxContext ) {
                $button.removeClass( 'button-disabled' ).prop( 'disabled', false );
            },
            complete: function() {
                $( '.edit-post-meta-boxes-area.is-side' ).removeClass( 'is-loading' );
                $spinner.remove();
            }
        });
    };

    /**
     * Initialize the interaction with the usages metaboxes.
     *
     * @since Views 2.8
     */
    self.initUsage = function() {
        self.checkDissidentPosts();

        var editPost = window.wp.data.select( 'core/edit-post' ),
            lastIsSaving = false;

        // Display notices when the selected usages require some manual action
        window.wp.data.subscribe( function() {
            var isSaving = editPost.isSavingMetaBoxes();
            if ( isSaving !== lastIsSaving && !isSaving ) {
                lastIsSaving = isSaving;

                // The first originally selected usage is not checked anymore
                if (
                    self.selectedUsagesLength > 0
                    && ! self.$selectedFirstUsage.prop( 'checked' )
                ) {
                    window.wp.data.dispatch( 'core/notices' ).createInfoNotice (
                        self.i18n.suggestReload,
                        {
                            isDismissible: false
                        }
                    );
                }

                // A first usage was saved
                if (
                    0 == self.selectedUsagesLength
                    && $( '.js-wpv-content-template-usage-selector:checked' ).length > 0
                ) {
                    window.wp.data.dispatch( 'core/notices' ).createInfoNotice (
                        self.i18n.suggestReload,
                        {
                            isDismissible: false
                        }
                    );
                }

                self.checkDissidentPosts();
            }

            lastIsSaving = isSaving;
        } );

        // Bind the dissident killing click with its action
        $( document ).on( 'click', '.js-wpv-content-template-usage-selector-kill-dissident', function( e ) {
            e.preventDefault();

            var $item = $( this ).closest( 'li' ),
                postType = $item.find( '.js-wpv-content-template-usage-selector' ).val();

            self.killDissidentPosts( postType );
        });

        return self;
	}

	/**
	 * Initialize the interaction with the General metaboxes.
	 *
	 * @returns {Object}
	 * @since Views 2.8
	 */
	self.initGeneral = function() {
		let isLocked = false;
		const postName = document.getElementById( 'wpv-content-template-general-post-name' );
		if ( postName ) {
			postName.addEventListener( 'input', ev => {
				const value = ev.target.value.trim();
				if ( ! value ) {
					wp.data.dispatch('core/editor').lockPostSaving( 'toolset_content_template_post_name' );
					isLocked = true;
				} else if ( isLocked ) {
					wp.data.dispatch('core/editor').unlockPostSaving( 'toolset_content_template_post_name' );
					isLocked = false;
				}
				wp.data.dispatch( 'core/editor' ).editPost( { title: value } );
			} );
			postName.addEventListener( 'blur', ev => {
				ev.target.value = ev.target.value.trim();
			} );
		}
		return self;
	};

	self.init()
		.initCssEditor()
		.initJsEditor()
		.initUsage()
		.initGeneral();
};

jQuery( function( $ ) {
    ToolsetCommon.UserEditor.GutenbergEditorInstance = new ToolsetCommon.UserEditor.GutenbergEditor( $ );
});
