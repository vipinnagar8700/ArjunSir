/* eslint-disable */
/**
 Repeatable Field Group Scripts
 Functionality required for the creation of Repeatable Field Group on the field group edit page

 @since m2m
 */

// Base
var Types = Types || {};
Types.page = Types.page || {};

// Field Group Edit page
Types.page.fieldGroupEdit = {};

Types.page.fieldGroupEdit.View = function() {
    Toolset.Gui.AbstractPage.call( this );

    var $ = jQuery,
        self = this,
        staticData = self.getModelData(),
        ajaxNonce = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'nonce' ],
        ajaxName = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'name' ],
        fieldGroupId = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'fieldGroupId' ];

    // add new repeatable group
    self.addRepeatableGroup = function( data, e ) {
        if( $( e.target ).hasClass( 'wpcf-fields-btn-inactive' ) ) {
            // button is not active (this is controlled by fields-form.js (legacy))
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: ajaxName,
                wpnonce: ajaxNonce,
                field_group_action: 'add_repeatable_field_group',
                field_group_id: fieldGroupId
            },
            dataType: 'json',
            success: function( response ) {
                if( response.success ) {
                    var $button = jQuery( e.target );
                    var $target = jQuery( '#' + $button.data( 'add-field-to' ) );
                    // add repeatable group creation template
                    if ( $target.length ) {
                        $target.append( response.data.html_group );
                    } else  {
                        $( '#post-body-content .js-wpcf-fields' ).append( response.data.html_group );
                    }
                    // we need to add bindings for the new add group
                    ko.applyBindings( self, document.getElementById( response.data.html_group_id ) );
                    // make it sortable
                    wpcfFieldsSortable();
                } else {
                    // would only happen if no post could be created
                    console.log( response.data.error );
                }
            },

            error: function( response ) {
                console.log( response );
            }
        } );
    };

    self.deleteRepeatableGroup = function( data, e ) {
        var trigger = $( e.target ),
            group_id = trigger.data( 'types-repeatable-group-id' );

        if( wpcfFieldsSortableRFGWasMoved == 1 ) {
            var dialog = self.createDialog(
                'types-dialog-save-before-rfg-can-be-deleted',
                staticData[ 'strings' ][ 'unsavedChanges' ],
                {},
                [
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'close' ],
                        click: function() {
                            jQuery( dialog.$el ).ddldialog( 'close' );
                        },
                        'class': 'button'
                    }
                ]
            );
            return;
        }

        var dialog = self.createDialog(
            'types-dialog-delete-repeatable-field-group',
            staticData[ 'strings' ][ 'deleteRepeatableGroup' ],
            {},
            [
                {
                    text: staticData[ 'strings' ][ 'button' ][ 'delete' ],
                    click: function() {
                        $.ajax( {
                            async: false,
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: ajaxName,
                                wpnonce: ajaxNonce,
                                field_group_action: 'delete_group',
                                group_id: group_id
                            },
                            dataType: 'json',
                            success: function( response ) {
                                jQuery( dialog.$el ).ddldialog( 'close' );

                                if( response.success ) {
                                    trigger.closest( '.toolset-postbox' ).slideUp( function() {
                                        $( this ).remove();
                                    } );
                                } else {
                                    alert( response.data.error );
                                }
                            },
                            error: function( response ) {
                                console.log( response );
                            }
                        } );
                    },
                    'class': 'button-primary types-delete-button'
                },
                {
                    text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                    click: function() {
                        jQuery( dialog.$el ).ddldialog( 'close' );
                    },
                    'class': 'wpcf-ui-dialog-cancel'
                }
            ]
        );
    };


    /**
     * Captures legacy delete action if there are RFG
     *
     * @param {object} data Knockout object.
     * @param {Event} event Event.
     * @since m2m
     */
    self.deleteFieldGroup = function( data, event ) {
        var deleteURL = event.target.getAttribute( 'href' );
        if ( deleteURL.match( /wpcf_continueBubbling/ ) ) {
            return;
        }
        if ( jQuery('.types-repeatable-group:not([data-repeatable-group-slug=""])').length ) {
            event.stopPropagation();
            // Removes wpcf_warning to avoid confirm calls
            deleteURL = deleteURL.replace( /wpcf_warning=[^&]+/, '' );
            var dialog = self.createDialog(
                'types-dialog-delete-field-group',
                staticData[ 'strings' ][ 'deleteFieldGroupTitle' ],
                {},
                [
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'convertAndDelete' ],
                        click: function() {
                            event.target.setAttribute( 'href', deleteURL + '&wpcf_convert_rfg=true&wpcf_continueBubbling=true' );
                            jQuery( event.target ).click();
                        },
                        'class': 'button-primary types-delete-button'
                    },
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'delete' ],
                        click: function() {
                            event.target.setAttribute( 'href', deleteURL + '&wpcf_continueBubbling=true' );
                            jQuery( event.target ).click();
                        },
                        'class': 'button-primary types-delete-button toolset-danger-button'
                    },
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                        click: function() {
                            event.stopPropagation();
                            jQuery( dialog.$el ).ddldialog( 'close' );
                        },
                        'class': 'wpcf-ui-dialog-cancel'
                    }
                ]
            );
            ko.applyBindings(self, dialog.el);
        } else {
        }
    };


    /**
     * Returns the list of repeatable groups
     */
    self.listRepeatableGroups = function() {
        var html = '<ul>';
        jQuery('.types-repeatable-group:not([data-repeatable-group-slug=""]) input:text[name^=\'wpcf[fields][_repeatable_\'][name$=\'_name]\']').each( function() {
            html += '<li><strong>' + jQuery( this ).val() + '</strong></li>';
        } );
        html += '</ul>';
        return html;
    };

    /**
     * Returns the delete field group dialog content text
     */
    self.deleteFieldGroupWarning = function() {
        return staticData.strings.deleteFieldGroupWarning[ jQuery('.types-repeatable-group:not([data-repeatable-group-slug=""])').length === 1 ? 'singular' : 'plural' ];
    }

	/**
	 * Actions executed after init event
	 *
	 * - Required Field Groups can be editable
	 */
	self.afterInit = () => {
		if ( staticData.extra.isRequired ) {
			document
				.querySelectorAll( '#post-body-content' )[0]
				.querySelectorAll( 'input, textarea, select, button' )
				.forEach( input => {
					input.disabled = 'disabled';
					// Some radio inputs are modified elsewhere, removing disabled attribute. This avoids that.
					if ( input.type === 'radio' ) {
						input.class = '';
						input.name = '';
					}
				} );
			document.querySelectorAll( '.js-wpcf-field-remove, .js-wpcf-toggle-description' ).forEach( item => item.remove() );
		}
	}

	this.init();

};


/**
 * Validate inputs for repeatable field group.
 *
 * @returns {boolean}
 */
Types.page.fieldGroupEdit.validateRepeatableGroupInputs = function() {
    Toolset.Gui.AbstractPage.call( this );

    var $ = jQuery,
        self = this,
        staticData = self.getModelData(),
        ajaxNonce = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'nonce' ],
        ajaxName = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'name' ],
        validationPassed = true;

    // VALIDATION: REQUIRED
    $( '[data-types-validate-required]' ).each( function() {
        var userInput = $( this );

        if( userInput.val() ) {
            // input is not empty
            return true;
        }

        // input empty
        validationPassed = false;

        if( self.hasErrorMessage( userInput ) ) {
            // input already has a validation error
            return true;
        }

        // add error message (legacy style)
        self.addLegacyErrorMessage( userInput, staticData[ 'strings' ][ 'fieldIsRequired' ] );
    } );

    // VALIDATION: POST TYPE SLUG
    $( '[data-types-validate-post-type-slug]' ).each( function() {
        var userInput = $( this );

        if( self.hasErrorMessage( userInput ) ) {
            // input already has a validation error
            return validationPassed = false;
        }

        $.ajax( {
            async: false,
            url: ajaxurl,
            type: 'POST',
            data: {
                action: ajaxName,
                wpnonce: ajaxNonce,
                field_group_action: 'validate_and_save_post_type_slug_and_label',
                post_type_slug: userInput.val(),
                post_type_label: $( '[name=wpcf\\[fields\\]\\[_repeatable_group_' + userInput.data( 'types-repeatable-group-id' ) + '_name\\]]' ).val(),
                group_id: userInput.data( 'types-repeatable-group-id' )
            },
            dataType: 'json',
            success: function( response ) {
                if( !response.success ) {
                    validationPassed = false;
                    Types.page.fieldGroupEdit.addLegacyErrorMessage( userInput, response.data.error );
                }
            },
            error: function( response ) {
                console.log( response );
            }
        } );
    } );

    return validationPassed;
};


/**
 * Check if element already has an active error message
 *
 * @param el
 * @returns {boolean}
 */
Types.page.fieldGroupEdit.hasErrorMessage = function( el ) {
    return el.data('error-msg-active') === true;
};


/**
 * Dialog for condition change not allowed
 */
Types.page.fieldGroupEdit.dialogNoConditionsChangeAllowed = function() {
    Toolset.Gui.AbstractPage.call( this );

    var self = this,
        staticData = self.getModelData(),
        dialog = self.createDialog(
            'types-dialog-condition-change-impossible',
            staticData[ 'strings' ][ 'conditionChangeNotAllowed' ],
            {},
            [
                {
                    text: staticData[ 'strings' ][ 'button' ][ 'close' ],
                    click: function() {
                        jQuery( dialog.$el ).ddldialog( 'close' );
                    },
                    'class': 'button-primary types-delete-button'
                }
            ]
        );
};


/**
 * Adds legacy error message and takes care of removing it on field change
 *
 * @param el
 * @param msg
 */
Types.page.fieldGroupEdit.addLegacyErrorMessage = function( el, msg ) {
    el.before(
        '<div class="wpcf-form-error wpcf-form-error-unique-value" style="margin-top: 0;">'
        + msg
        + '</div>' )

        // add msg active identifier
        .data( 'error-msg-active', true )

        // on keyup remove error msg
        .on( 'keyup', function() {

            if( el.val() ) {
                el.off( 'keyup' )
                    .data( 'error-msg-active', false )
                    .prev( '.wpcf-form-error-unique-value' )
                    .fadeOut( function() {
                        jQuery( this ).remove();
                    } );
            }
        } );
};


/**
 * Keep original input value
 *
 * @type {{init: ko.bindingHandlers.typesPrefilledInput.init}}
 */
ko.bindingHandlers.typesPrefilledInput = {
    init: function( el, input ) {
        var inputValue = jQuery( el ).val();
        input()( inputValue );

        ko.applyBindingsToNode( el, {
            textInput: input()
        } )
    }
};


ko.applyBindings( new Types.page.fieldGroupEdit.View() );


/**
 * Deletetion of a Post Reference Field
 * The $el must have the attribute "data-field-slug" with the slug of the field to delete
 *
 * @param $el jQuery Object
 */
Types.page.fieldGroupEdit.postReferenceFieldDelete = function( $el ) {
    if( ! $el.data( 'field-slug' ) ) {
        // no valid field
        return;
    }

    // extend Toolset.Gui.AbstractPage
    Toolset.Gui.AbstractPage.call( this );

    var $ = jQuery,
        self = this,
        staticData = self.getModelData(),
        ajaxNonce = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'nonce' ],
        ajaxName = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'name' ],
        deleteField = function( deleteMode, onSuccess ) {
            $.ajax( {
                async: false,
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: ajaxName,
                    wpnonce: ajaxNonce,
                    field_group_action: 'delete_post_reference_field',
                    field_slug:  $el.data( 'field-slug' ),
                    delete_mode: deleteMode
                },
                dataType: 'json',
                success: function( response ) {
                    onSuccess( response );
                },
                error: function( response ) {
                    console.log( response );
                }
            } );
        };

    deleteField( 'empty_or_request', function( response ) {
        if( response.data.request ) {
            // DOM: remove field
            var dialog = self.createDialog(
                'types-dialog-post-reference-field-delete',
                staticData[ 'strings' ][ 'postReferenceHasAssociations' ],
                {},
                [
                    // Button: Cancel
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                        click: function() {
                            jQuery( dialog.$el ).ddldialog( 'close' );
                        },
                        'class': 'button wpcf-ui-dialog-cancel'
                    },

                    // Button: Keep Associations
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'prf_keep_associations' ],
                        click: function() {
                            // keep associations - also means keep relationship, all we do is
                            // removing the field from the current group
                            $el.closest('.toolset-postbox').slideUp( function(){
                                $( this ).remove();
                            });

                            jQuery( dialog.$el ).ddldialog( 'close' );
                        },
                        'class': 'button button-primary toolset-button-in-row'
                    },

                    // Button: Delete Associations
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'prf_delete_associations' ],
                        click: function() {
                            jQuery( dialog.$el ).ddldialog( 'close' );

                            // ajax: delete prf with associations
                            deleteField( 'delete_associations', function( response ) {
                                if( response.data.error ) {
                                    // an error
                                    var errorDialog = self.createDialog(
                                        'types-dialog-field-group-error',
                                        staticData[ 'strings' ][ 'fieldGroupError' ],
                                        {
                                            'error': response.data.error
                                        },
                                        [
                                            // Button: Close
                                            {
                                                text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                                                click: function() {
                                                    jQuery( errorDialog.$el ).ddldialog( 'close' );
                                                },
                                                'class': 'button button-primary'
                                            }
                                        ]
                                    );

                                    return;
                                }

                                // associations deleted - remove the field from the group
                                $el.closest( '.toolset-postbox' ).slideUp( function(){
                                    $( this ).remove();
                                } );
                            } );
                        },
                        'class': 'button button-primary toolset-button-in-row'
                    }
                ]
            );

            return;
        }

        // field had no associations and was directly deleted - remove from DOM
        $el.closest( '.toolset-postbox' ).slideUp( function(){
            $( this ).remove();
        } );
    } );
};


Types.page.fieldGroupEdit.postReferenceFieldTypeWrongTranslationMode = function() {
    // extend Toolset.Gui.AbstractPage
    Toolset.Gui.AbstractPage.call( this );

    var self = this,
        staticData = self.getModelData();

    var dialog = self.createDialog(
        'types-dialog-prf-type-wrong-translation-mode',
        staticData[ 'strings' ][ 'postReferenceTypeNotSupportedTranslationMode' ],
        {},
        [
            // Button: Cancel
            {
                text: staticData[ 'strings' ][ 'button' ][ 'close' ],
                click: function() {
                    jQuery( dialog.$el ).ddldialog( 'close' );
                },
                'class': 'button'
            }

        ]
    );
};


/**
 * Making a Post Reference Field Repatable
 * Means nothing else than transforming it to an one-to-many relationship
 *
 * @param $el jQuery Object
 */
Types.page.fieldGroupEdit.postReferenceFieldMakeRepeatable = function( $el ) {
    // extend Toolset.Gui.AbstractPage
    Toolset.Gui.AbstractPage.call( this );

    var $ = jQuery,
        self = this,
        staticData = self.getModelData(),
        ajaxNonce = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'nonce' ],
        ajaxName = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'name' ],
        containerOfField = $el.closest( '.toolset-postbox' ),
        slug = containerOfField.find( 'input[name$=\\[slug\\]]' ).val(),
        slugPreSave = containerOfField.find( 'input[name$=\\[slug-pre-save\\]]' ).val(),
        postReferenceType = containerOfField.find( 'select[name$=\\[post_reference_type\\]]' ).val(),
        postReferenceTypePreSave = containerOfField.find( 'input[name$=\\[post_reference_type_pre_save\\]]' ).val();

    if( slugPreSave === "" || postReferenceTypePreSave === "" || slug !== slugPreSave || postReferenceType !== postReferenceTypePreSave ) {
        // changes are done - making the field "repeatable" requires to save first
        var dialog = self.createDialog(
            'types-dialog-post-reference-field-save-required',
            staticData[ 'strings' ][ 'fieldGroupError' ],
            {},
            [
                // Button: Close
                {
                    text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                    click: function() {
                        jQuery( dialog.$el ).ddldialog( 'close' );
                    },
                    'class': 'button button-primary'
                }
            ]
        );
        return;
    }

    self.parentSingular = $( this ).find(':selected').data('name-singular');
    self.childSingular  = '';
    self.childPlural    = '';

    // "Preview change" button
    $( 'body' ).on( 'click', '.js-prf-dialog-show-step-2', function() {
        $( '.js-prf-dialog-step-1' ).hide();
        $( '.js-prf-dialog-step-2' ).show();
    } );

    // Load PRF data
    $.ajax( {
        async: false,
        url: ajaxurl,
        type: 'POST',
        data: {
            action: ajaxName,
            wpnonce: ajaxNonce,
            field_group_action: 'get_post_reference_field_infos',
            field_slug: slug
        },
        dataType: 'json',
        success: function( response ) {
            if( response.error ) {
                alert( response.error );
                return;
            }

            // Dialog to make Post Reference Field repeatable
            var dialog = self.createDialog(
                'types-dialog-post-reference-field-make-repeatable',
                staticData[ 'strings' ][ 'makeFieldRepeatable' ],
                {
                    'parentSingular': response.data.parentSingular,
                    'parentPlural': response.data.parentPlural,
                    'childSingular': response.data.childSingular,
                    'childPlural': response.data.childPlural
                },
                [
                    // Button: Placebo for proper styling
                    {
                        text: '',
                        'style': 'padding:0;width:1px;opacity:0;'
                    },

                    // Button: Make this change
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'make_this_change' ],
                        click: function() {
                            $.ajax( {
                                    async: false,
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: ajaxName,
                                        wpnonce: ajaxNonce,
                                        field_group_action: 'convert_prf_to_relationship',
                                        field_slug: slug
                                    },
                                    dataType: 'json',
                                    success: function( response ) {
                                        if( response.error ) {
                                            alert( response.error );
                                            return;
                                        }

                                        // remove field from DOM
                                        $el.closest( '.toolset-postbox' ).slideUp( function(){
                                            $( this ).remove();
                                        } );

                                        // show step 3
                                        $( '.js-prf-dialog-step-2' ).hide();
                                        $( '.js-prf-dialog-step-3' ).show();

                                        // enable "close" button
                                        $( '.js-prf-dialog-close' ).on( 'click', function () {
                                            jQuery( dialog.$el ).ddldialog( 'close' );
                                        });

                                        return;
                                    },
                                error: function( response ) {
                                    console.log( response );
                                }
                            } );
                        },
                        'class': 'button button-primary js-prf-dialog-step-2',
                        'style': 'display: none;'
                    },

                    // Button: Close
                    {
                        text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                        click: function() {
                            jQuery( dialog.$el ).ddldialog( 'close' );
                        },
                        'class': 'button wpcf-ui-dialog-cancel js-prf-dialog-step-2'
                    }
                ]
            );
        },
        error: function( response ) {
            console.log( response );
        }
    } );
};


jQuery(function() {
    /**
     * Returns the list of supported post types
     *
     * @return {Array}
     * @since m2m
     */
    var getSupportedPostTypes = function() {
        // Checks if PRF points to same post type as group supported post types.
        var supportedPostTypes = [];
        jQuery('[name^="wpcf[group][supports]"][value!=""]').each( function() {
            supportedPostTypes.push( jQuery(this).val() );
        } );
        return supportedPostTypes;
    };


    /**
     * Toggle 'make repeatable' link
     *
     * @param {Object} $postbox jQuery .postbox element
     */
    var toggleMakeRepeatableLink = function( $postbox ) {
        var $link = $postbox.find( '.js-types-post-reference-field-make-repeatable' );
        var $linkTrContainer = $link.parents( 'tr' ).first();
        var postType = $linkTrContainer.prevAll( 'tr' ).first().find( 'select' ).val();
        var supportedPostTypes = getSupportedPostTypes();
        var $tooltip = $linkTrContainer.find('.wpcf-tooltip');
        var isSupported = supportedPostTypes.includes( postType );
        $tooltip.toggle( isSupported );
        $link.toggleClass( 'prf-link-disabled', isSupported );
        disablePostTypeFromPRF( $postbox );
    };

    /**
     * Disables selected post type in PRF
     *
     * @param {Object} $container jQuery container element.
     */
    var disablePostTypeFromPRF = function( $container ) {
        var supportedPostTypes = getSupportedPostTypes();
        $container.find('[id^=post-select]:not(.js-wpcf-fields-type) option').each( function() {
            var $this = jQuery(this);
            if ( supportedPostTypes.includes( $this.val() ) ) {
                $this.attr( 'disabled', 'disabled' );
                if ( $this.attr( 'selected' ) === 'selected' ) {
                    $this.removeAttr( 'selected' );
                    $this.parent().children()[0].setAttribute( 'selected', 'selected' );
                }
            } else {
                $this.removeAttr( 'disabled' );
            }
        } );
    };


    // link to start making a PRF repeatable
    var $body = jQuery( 'body' );
    $body.on( 'click', '.js-types-post-reference-field-make-repeatable', function( el ) {
        el.preventDefault();
        var $this = jQuery(this);
        // Checks if PRF points to same post type as group supported post types, and make sure it doesn't work, in case user "reveal" hide element.
        var supportedPostTypes = getSupportedPostTypes();
        var postType = $this.parents('tr').first().prevAll('tr').first().find('select').val();
        if ( supportedPostTypes.includes( postType ) ) {
            return;
        }
        Types.page.fieldGroupEdit.postReferenceFieldMakeRepeatable( $this );
    } );

    jQuery( '[id^=types-custom-field].toolset-postbox' ).each( function() {
        toggleMakeRepeatableLink( jQuery( this ) );
    } );

    $body.on( 'change', 'select[id^=post-select-]', function() {
        toggleMakeRepeatableLink( jQuery( this ).parents( '.toolset-postbox' ).first() );
    } );

});

/**
 * Dialog when saving the group is not possible, because of rfg and multiple cpt assigned
 */
Types.page.fieldGroupEdit.dialogSavingGroupImpossible = function() {
    Toolset.Gui.AbstractPage.call( this );

    var self = this,
        staticData = self.getModelData(),
        dialog = self.createDialog(
            'types-dialog-saving-group-impossible',
            staticData[ 'strings' ][ 'savingGroupImpossible' ],
            {},
            [
                {
                    text: staticData[ 'strings' ][ 'button' ][ 'close' ],
                    click: function() {
                        jQuery( dialog.$el ).ddldialog( 'close' );
                    },
                    'class': 'button-primary types-delete-button'
                }
            ]
        );
};


// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
        value: function(searchElement, fromIndex) {

            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            // 1. Let O be ? ToObject(this value).
            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If len is 0, return false.
            if (len === 0) {
                return false;
            }

            // 4. Let n be ? ToInteger(fromIndex).
            //    (If fromIndex is undefined, this step produces the value 0.)
            var n = fromIndex | 0;

            // 5. If n ≥ 0, then
            //  a. Let k be n.
            // 6. Else n < 0,
            //  a. Let k be len + n.
            //  b. If k < 0, let k be 0.
            var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

            function sameValueZero(x, y) {
                return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
            }

            // 7. Repeat, while k < len
            while (k < len) {
                // a. Let elementK be the result of ? Get(O, ! ToString(k)).
                // b. If SameValueZero(searchElement, elementK) is true, return true.
                if (sameValueZero(o[k], searchElement)) {
                    return true;
                }
                // c. Increase k by 1.
                k++;
            }

            // 8. Return false
            return false;
        }
    });
}
