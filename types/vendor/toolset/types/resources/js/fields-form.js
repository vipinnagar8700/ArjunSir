/**
 * Static Data
 */
var fieldFormStaticData;

/**
 * fields edit
 */
jQuery(function($){
    var $modelData = jQuery( '#toolset_model_data' );
    if ($modelData.length) {
        fieldFormStaticData = JSON.parse(
            WPV_Toolset.Utils.editor_decode64(
                $modelData.html()
            )
        );
    }

    var assignedPostTypesCount;

    var wpcfBody = $( 'body' );

    /**
     * Store all current used field slugs
     * @type {Array}
     */
    var allFieldSlugs = [];
    $.ajax({
            url: ajaxurl,
            method: "POST",
            dataType: 'json',
            data: {
                group_id: $( 'input[name="wpcf[group][id]"]' ).val(),
                action: 'wpcf_get_all_field_slugs_except_current_group',
                return: 'ajax-json'
            }
        })
        .done(function( slugs ){
            if( slugs && slugs.length ) {
                $.merge( allFieldSlugs, slugs );
            }
        });

    /**
     * function to update currently selected conditions
     * in the description of "Where to Include These Fields" box
     */
    function update_fields() {
        var msgAll = $( '.wpcf-fields-group-conditions-description' ),
            msgCondNone = $( '.js-wpcf-fields-group-conditions-none' ),
            msgCondAll = $( '.js-wpcf-fields-group-conditions-condition' ),

            conditions = {
                'postTypes' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-post-types' ),
                    'inputsIDs' : 'wpcf-form-groups-support-post-type-',
                    'activeConditionsLabels' : []
                },

                'terms' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-terms' ),
                    'inputsIDs' : 'wpcf-form-groups-support-tax-',
                    'activeConditionsLabels' : []
                },

                'templates' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-templates' ),
                    'inputsIDs' : 'wpcf-form-groups-support-templates-',
                    'activeConditionsLabels' : []
                },

                'data-dependencies' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-data-dependencies' ),
                    'activeConditionsLabels' : []
                },

                taxonomies : {
                    description: $( '.js-wpcf-fields-group-conditions-taxonomies' ),
                    inputsIDs: 'wpcf-form-groups-support-taxonomy-',
                    activeConditionsLabels: []
                }
            },
            conditionsCount = 0,
            uiDialog = $( '.wpcf-filter-dialog' );

        // reset
        msgAll.hide();
        msgCondAll.find( 'span' ).html( '' );

        // update hidden inputs if dialog is open
        if( uiDialog.length ) {
            // reset all hidden inputs
            $( '[id^=wpcf-form-groups-support-]' ).val( '' );
            $( '[id^=wpcf-form-groups-support-tax]' ).remove();

            $( 'input[type=checkbox]:checked', uiDialog ).each( function() {
                // taxonomies are the only not using a prefix ('tax' is inside name)
                if( $( this ).data( 'wpcf-prefix' ) == '' ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'wpcf-form-groups-support-' + $( this ).attr( 'name' ),
                        name: 'wpcf[group][taxonomies][' + $( this ).attr( 'data-wpcf-taxonomy-slug' ) + '][' + $( this ).attr( 'data-wpcf-value' ) + ']',
                        'data-wpcf-label': $( this ).attr( 'data-wpcf-name' ),
                        value: $( this ).attr( 'data-wpcf-value' ),
                    } ).appendTo( '.wpcf-conditions-container' );
                // taxonomies on term fields
                } else if( $( this ).data( 'wpcf-prefix' ) == 'taxonomy-'  ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'wpcf-form-groups-support-taxonomy-' + $( this ).attr( 'name' ),
                        name: 'wpcf[group][taxonomies][' + $( this ).attr( 'data-wpcf-value' ) + ']',
                        'data-wpcf-label': $( this ).attr( 'data-wpcf-name' ),
                        value: $( this ).attr( 'data-wpcf-value' ),
                        class: 'js-wpcf-filter-support-taxonomy wpcf-form-hidden form-hidden hidden',
                    } ).appendTo( '.wpcf-conditions-container' );
                } else {
                    var id = '#wpcf-form-groups-support-' + $( this ).data( 'wpcf-prefix' ) + $( this ).attr( 'name' );
                    var value = $( this ).data( 'wpcf-value' );
                    $( id ).val( value );
                }
            } );
        }

        // get all active conditions
        $.each( conditions, function( id, condition ) {
            if( id === 'data-dependencies' ) {
                $( '.js-wpcf-filter-container .js-wpcf-condition-preview li' ).each( function() {
                    conditionsCount++;
                    conditions[id]['activeConditionsLabels'].push( '<br />' + $( this ).html() );
                } );
            } else {
                var currentConditionTypeCount = 0;
                var assignedPostType = null;
                var selector = 'input[id^=' + condition.inputsIDs + ']';
                $( selector ).filter( function() {
                    return this.value && this.value !== '0';
                } ).each( function() {
                    conditionsCount++;
                    currentConditionTypeCount++;
                    var label = $( this ).data( 'wpcf-label' );
                    assignedPostType = $( this ).val();
                    conditions[id]['activeConditionsLabels'].push( label );
                })
            }

            if( id === 'postTypes' ) {
                assignedPostTypesCount = currentConditionTypeCount;

                if( assignedPostTypesCount === 1 ) {
                    $( '.types-repeatable-group' ).removeClass( 'types-repeatable-group-inactive js-wpcf-tooltip' );
                    $( '.js-wpcf-fields-add-new-repeatable-group' ).removeClass( 'wpcf-fields-btn-inactive js-wpcf-tooltip' );
                } else {
                    $( '.js-wpcf-fields-add-new-repeatable-group' ).addClass( 'wpcf-fields-btn-inactive js-wpcf-tooltip' );
                    $( '.types-repeatable-group' ).addClass( 'types-repeatable-group-inactive js-wpcf-tooltip' );
                }
            }
        });

        // show box description depending of conditions count
        if( conditionsCount > 0 ) {
            $.each( conditions, function( id, condition ) {
                if( condition['activeConditionsLabels'].length ) {
                    condition['description'].show().find( 'span' ).html( condition['activeConditionsLabels'].join( ', ' ) );
                }
            } );
        } else {
            msgCondNone.show();
        }

        // show association option when there is more than one condition added
        if( conditionsCount > 1 ) {
            $( '#wpcf-fields-form-filters-association-form' ).show();
        } else {
            $( '#wpcf-fields-form-filters-association-form' ).hide();
        }

    }

    update_fields();

    /**
     * remove field link
     */
    $(document).on('click', '.js-wpcf-field-remove', function() {
        if ( confirm($(this).data('message-confirm')) ) {
            if( $( this ).data( 'field-type' ) == 'post' ) {
                // handle post reference field
                Types.page.fieldGroupEdit.postReferenceFieldDelete( $( this ) );

                return;
            }

            $(this).closest('.toolset-postbox').slideUp(function(){
                $(this).remove();
                if ( 1 > $('#post-body-content .js-wpcf-fields .toolset-postbox').length ) {
                    $( '.js-wpcf-fields-add-new-last, .js-wpcf-second-submit-container' ).addClass( 'hidden' );
                }
            });
        }
        return false;
    });
    /**
     * change field type
     */
    $(document).on('change', '.js-wpcf-fields-type', function(){
        $('.js-wpcf-fields-type-message').remove();
        $(this).parent().append('<div class="js-wpcf-fields-type-message updated settings-error notice"><p>'+$(this).data('message-after-change')+'</p></div>');
        $('tbody tr', $(this).closest('table')).each(function(){
            if ( !$(this).hasClass('js-wpcf-fields-typeproof') ) {
                $(this).remove();
            }
        });
    });
    /**
     * choose filter
     */
    $( document ).on( 'click', '.js-wpcf-filter-container .js-wpcf-filter-button-edit', function() {
        var thiz = $(this);

        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="wpcf-filter-contant"><span class="spinner"></span>'+thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-filter-dialog wpcf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: thiz.data('wpcf-buttons-apply'),
                click: function() {

                    var currentOpenDialog = $( this ).closest( '.wpcf-filter-dialog' ).length ? $( this ).closest( '.wpcf-filter-dialog' ) : $( this ).closest( '.wpcf-conditions-dialog' ),
                        groupConditions,
                        fieldNonce,
                        fieldName,
                        fieldGroupId,
                        fieldMetaType,
                        extraMetaField = jQuery( '#data-dependant-meta', currentOpenDialog );

                    if( extraMetaField.length ) {
                        groupConditions = ( extraMetaField.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = extraMetaField.data( 'wpcf-id' );
                        fieldGroupId = extraMetaField.data( 'wpcf-group-id' );
                        fieldMetaType = extraMetaField.data( 'wpcf-meta-type' );
                        fieldNonce = extraMetaField.data( 'wpcf-buttons-apply-nonce' );
                    } else {
                        groupConditions = ( thiz.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = thiz.data( 'wpcf-id' );
                        fieldGroupId = thiz.data( 'wpcf-group-id' );
                        fieldMetaType = thiz.data( 'wpcf-meta-type' );
                        fieldNonce = thiz.data('wpcf-buttons-apply-nonce');
                    }
                    /**
                     * show selected values
                     */
                    //$('.js-wpcf-filter-ajax-response', thiz.closest('.js-wpcf-filter-container')).html(affected);

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_save',
                            _wpnonce: fieldNonce,
                            id: fieldName,
                            group_conditions: groupConditions,
                            group_id: fieldGroupId,
                            meta_type: fieldMetaType,
                            conditions: $( 'form', currentOpenDialog ).serialize()
                        }
                    })
                        .done(function(html){
                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-wpcf-filter-container .js-wpcf-condition-preview' );
                                button = $('.js-wpcf-filter-container .js-wpcf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'wpcf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'wpcf-label-edit-condition' ) );
                            }

                            // close dialog
                            update_fields();
                            dialog.dialog( "close" );
                        });
                },
                class: 'button-primary'
            }, {
                text: thiz.data('wpcf-buttons-cancel'),
                click: function() {
                    $( this ).dialog( "close" );
                },
                class: 'wpcf-ui-dialog-cancel'
            }]
        });
        // load remote content
        var $current = [];
        var allFields = $( 'form.wpcf-fields-form input[name^=wpcf\\[group\\]][value!=""]' ).serialize();

        $(thiz.data('wpcf-field-to-clear-class'), thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        var current_page = thiz.data('wpcf-page');
        if( undefined == current_page ) {
            current_page = 'wpcf-edit';
        }

        var assignedPostTypes = [];
        $( 'input[id^="wpcf-form-groups-support-post-type"]' ).filter( function() {
            return this.value && this.value !== '0';
        } ).each( function() {
            assignedPostTypes.push( $( this ).val() );
        });

        dialog.load(
            ajaxurl,
            {
                method: 'post',
                action: 'wpcf_ajax_filter',
                _wpnonce: thiz.data('wpcf-nonce'),
                id: thiz.data('wpcf-id'),
                rfg_prf_count: $( '.types-repeatable-group, .js-wpcf-post-reference-field' ).length,
                assigned_post_types: assignedPostTypes,
                type: thiz.data('wpcf-type'),
                page: current_page,
                current: $current,
                all_fields: allFields
            },
            function (responseText, textStatus, XMLHttpRequest) {
                // tabs
                var menu = $( '.wpcf-tabs-menu' ).detach();

                menu.appendTo( ".wpcf-filter-dialog .ui-widget-header" );

                $(".wpcf-tabs-menu span").on( 'click', function(event) {
                    event.preventDefault();

                    $(this).parent().addClass("wpcf-tabs-menu-current");
                    $(this).parent().siblings().removeClass("wpcf-tabs-menu-current");
                    var tab = $(this).data("open-tab");
                    $(".wpcf-tabs > div").not(tab).css("display", "none");
                    $(tab).fadeIn();
                });

                $(dialog).on('click', 'a[data-wpcf-icon]', function() {
                    var $icon = $(this).data('wpcf-icon');
                    $('#wpcf-types-icon').val($icon);
                    classes = 'wpcf-types-menu-image dashicons-before dashicons-'+$icon;
                    $('div.wpcf-types-menu-image').removeClass().addClass(classes);
                    dialog.dialog( "close" );
                    return false;
                });
                /**
                 * bind search taxonomies
                 */
                $(dialog).on('keyup input cut paste', '.js-wpcf-taxonomy-search', function() {
                    var $parent = $(this).closest('.inside');
                    if ( '' == $(this).val() ) {
                        $('li', $parent).show();
                    } else {
                        var re = new RegExp($(this).val(), "i");
                        $('li input', $parent).each(function(){
                            if (
                                    false
                                    || $(this).data('wpcf-slug').match(re)
                                    || $(this).data('wpcf-name').match(re)
                               ) {
                                $(this).parent().show();
                            } else {
                                $(this).parent().hide();
                            }
                        });
                    }
                });

                /**
                 * Data Dependant
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_get_row',
                            _wpnonce: $( this ).data('wpcf-nonce'),
                            id: $(this).data('wpcf-id'),
                            group_id: $( this ).data( 'wpcf-group-id' ),
                            meta_type: $( this ).data('wpcf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-wpcf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-wpcf-fields-conditions', $(dialog) ).find( '.js-wpcf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $( dialog ).on( 'click', '.js-wpcf-custom-field-remove', function() {
                                    return wpcf_conditional_remove_row( $( this ) );
                                } );
                                wpcf_setup_conditions();
                                $( dialog ).on( 'change', '.js-wpcf-cd-field', function() {
                                    wpcf_setup_conditions();
                                } );
                            }
                        });
                    return false;
                });
                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                    return wpcf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('wpcf-custom-logic') ) {
                        $('.js-wpcf-simple-logic', $container).show();
                        $('.js-wpcf-advance-logic', $container).hide();
                        $(this).data('wpcf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('wpcf-content-advanced'));
                        $('.js-wpcf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-wpcf-simple-logic', $container).hide();
                        $('.js-wpcf-advance-logic', $container).show();
                        $(this).data('wpcf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('wpcf-content-simple'));
                        wpcf_conditional_create_summary(this, $container);
                        $('.js-wpcf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * add new - choose field
     */
    $( document ).on( 'click', '.js-wpcf-fields-add-new', function() {
        var $thiz = $(this);
        var $current;
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="wpcf-choose-field"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window }
        });
        // load remote content
        var $current = [];
        $($thiz.data('wpcf-field-to-clear-class'), $thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });
        $('#post-body-content .toolset-postbox .js-wpcf-slugize').each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        // top or bottom "add new field" clicked
        var position = $thiz.hasClass( 'js-wpcf-fields-add-new-last' )
            ? 'bottom'
            : 'top';

        function add_field_to_fields_list( html ) {
            var newField;

            if( $thiz.data( 'add-field-to' ) ) {
                var container = $( '#' + $thiz.data( 'add-field-to' ) );
                container.append( html );
                newField = container.find( '.toolset-postbox' ).last();
            } else if( position == 'top' ) {
                $( '#post-body-content .js-wpcf-fields' ).append( html );
                newField = $( '#post-body-content .js-wpcf-fields .toolset-postbox' ).last();
            } else {
                $( '#post-body-content .js-wpcf-fields .js-wpcf-fields-add-new-last' ).before( html );
                newField = $( '#post-body-content .js-wpcf-fields .toolset-postbox' ).last();
            }

            // Disable make it Repeatable Field option.
            wpcfDisableRepeatableFieldOption( newField );
            wpcfDisablePostTypeFromPRF( newField );

            $( 'html, body' ).animate( {
                scrollTop: newField.offset().top - 50
            }, 1000 );

            dialog.dialog( 'close' );

            wpcfBindAutoCreateSlugs();
            wpcfFieldsSortable();

            newField.typesFieldOptionsSortable();
            newField.typesMarkExistingField();

            // show bottom "Add new field" and "Save Group Fields" buttons
            $( '.js-wpcf-fields-add-new, .js-wpcf-second-submit-container' ).removeClass( 'hidden' );
            wpcf_setup_conditions();
        }

        // This can be wpcf-postmeta, wpcf-usermeta or wpcf-termmeta.
        var fieldKind = $thiz.data('wpcf-type');

        dialog.load(
            ajaxurl,
            {
                action: 'wpcf_edit_field_choose',
                _wpnonce: $( '.js-wpcf-fields-add-new[data-wpcf-nonce]' ).data( 'wpcf-nonce' ),
                id: $( '.js-wpcf-fields-add-new[data-wpcf-id][id]' ).data( 'wpcf-id' ),
                type: fieldKind,
                current: $current
            },
            function (responseText, textStatus, XMLHttpRequest) {
                var $fields = '';
                var $dialog =  $(this).closest('.ui-dialog-content')

                if( assignedPostTypesCount != 1 ) {
                    // disable post reference field if there is more than one post type assigned to the field group
                    var btnPostReferenceField = $( 'button[data-wpcf-field-type="post"]' );

                    if (btnPostReferenceField.length) {
                        if (typeof fieldFormStaticData !== 'undefined') {
                            btnPostReferenceField.attr( 'data-tooltip',   fieldFormStaticData['strings']['postReferenceFieldOnlyAllowedWithOneAssignedPostType'] );
                            btnPostReferenceField.addClass( 'js-wpcf-tooltip wpcf-form-button-disabled' );
                        }
                        btnPostReferenceField.removeClass( 'js-wpcf-field-button-insert' );
                  	}
                }

                if( $thiz.data( 'add-field-to' ) ) {
                    // disable post reference field for "add new field" of a repeatable field group
                    var btnPostReferenceField = $( 'button[data-wpcf-field-type="post"]' );

                    if (btnPostReferenceField.length) {
                        btnPostReferenceField.attr( 'data-tooltip', fieldFormStaticData['strings']['postReferenceNotAllowedInRFG'] );
                        btnPostReferenceField.addClass( 'js-wpcf-tooltip wpcf-form-button-disabled' );
                        btnPostReferenceField.removeClass( 'js-wpcf-field-button-insert' );
                    }
                }

                /**
                 * choose new field
                 */
                $(dialog).on('click', 'button.js-wpcf-field-button-insert', function() {
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_insert',
                            _wpnonce: $('#wpcf-fields-add-nonce').val(),
                            type: $(this).data('wpcf-field-type'),
                            field_kind: fieldKind
                        }
                    })
                    .done(function(html){
                        add_field_to_fields_list( html );
                    });
                });
                /**
                 * choose from existed fields
                 */
                $(dialog).on('click', '.js-wpcf-switch-to-exists', function() {

                    var current_page = $thiz.data('wpcf-page');
                    if( undefined == current_page ) {
                        current_page = 'wpcf-edit';
                    }

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_select',
                            _wpnonce: $('#wpcf-fields-add-nonce').val(),
                            id: $thiz.data('wpcf-id'),
                            type: $thiz.data('wpcf-type'),
                            current: $current,
                            page: current_page,
                            a:'c'
                        }
                    })
                    .done(function(html){
                        $fields = $dialog.html();
                        $dialog.html(html);
                        $(dialog).on('click', '.js-wpcf-switch-to-new', function() {
                            $dialog.html($fields);
                            return false;
                        });

                        if( assignedPostTypesCount != 1 ) {
                            // disable post reference field if there is more than one post type assigned to the field group
                            var btnPostReferenceField = $( 'button[data-wpcf-field-type="post"]' );

                            if (btnPostReferenceField.length) {
                                if (typeof fieldFormStaticData !== 'undefined') {
                                    btnPostReferenceField.attr( 'data-tooltip',   fieldFormStaticData['strings']['postReferenceFieldOnlyAllowedWithOneAssignedPostType'] );
                                    btnPostReferenceField.addClass( 'js-wpcf-tooltip wpcf-form-button-disabled' );
                                }
                                btnPostReferenceField.removeClass( 'js-wpcf-field-button-use-existed' );
                            }
                        }

                        /**
                         * filter
                         */
                        $(dialog).on('keyup input cut paste', '.js-wpcf-fields-search', function() {
                            if ( '' == $(this).val() ) {
                                $('.js-wpcf-field-button-use-existed', dialog).show();
                            } else {
                                var re = new RegExp($(this).val(), "i");
                                $('.js-wpcf-field-button-use-existed', dialog).each(function(){
                                    if (
                                        false
                                        || $(this).data('wpcf-field-id').match(re)
                                        || $(this).data('wpcf-field-type').match(re)
                                        || $('span', $(this)).html().match(re)
                                    ) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                });
                            }
                        });
                        /**
                         * choose exist field
                         */
                        $(dialog).on('click', 'button.js-wpcf-field-button-use-existed', function() {
                            $.ajax({
                                url: ajaxurl,
                                method: "POST",
                                data: {
                                    action: 'wpcf_edit_field_add_existed',
                                    id: $(this).data('wpcf-field-id'),
                                    type: $(this).data('wpcf-type'),
                                    _wpnonce: $('#wpcf-fields-add-nonce').val()
                                }
                            })
                            .done(function(html){
                                add_field_to_fields_list( html );
                            });
                        });
                    });

                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * update box fifle by field name
     */
    wpcfBody.on( 'keyup', '.wpcf-forms-set-legend', function(){
        var val = $(this).val();
        if ( val ) {
            val = val.replace(/</, '&lt;');
            val = val.replace(/>/, '&gt;');
            val = val.replace(/'/, '&#39;');
            val = val.replace(/"/, '&quot;');
        }
        $(this).parents('.toolset-postbox').first().find('.wpcf-legend-update').first().html(val);
    });

    // Check radio and select if same values
    // Check checkbox has a value to store
    $('.wpcf-fields-form').submit(function(e){
        if( assignedPostTypesCount !== 1 && $( '.types-repeatable-group' ).length ) {
            // abort submit if a RFG is included and we have more than one post type assigned
            e.preventDefault();
            Types.page.fieldGroupEdit.dialogSavingGroupImpossible();
            return;
        }

        wpcfLoadingButton();
        var passed = true;
        var checkedArr = new Array();
        $('.wpcf-compare-unique-value-wrapper').each(function(index){
            var childID = $(this).attr('id');
            checkedArr[childID] = new Array();
            $(this).find('.wpcf-compare-unique-value').each(function(index, value){
                var parentID = $(this).parents('.wpcf-compare-unique-value-wrapper').first().attr('id');
                var currentValue = $(this).val();
                if (currentValue != ''
                    && $.inArray(currentValue, checkedArr[parentID]) > -1) {

                    passed = false;

                    $('#'+parentID).children('.wpcf-form-error-unique-value').remove();

                    // make sure error msg is only applied ounce
                    if( ! $('#'+parentID).find( '.wpcf-form-error' ).length ) {
                        if( document.getElementById( parentID ).tagName == 'TBODY' ) {
                            $('#'+parentID).append('<tr><td colspan="5"><div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueValuesCheckText+'</div><td></tr>');
                        } else {
                            $('#'+parentID).append('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueValuesCheckText+'</div>');
                        }
                    }

                    $(this).parents('fieldset').children('.fieldset-wrapper').slideDown();
                    $(this).trigger( 'focus' );
                }

                checkedArr[parentID].push(currentValue);
            });
        });
        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-compare-unique-value', function(){
                $(this).parents('.wpcf-compare-unique-value-wrapper').find('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }
        // Check field names unique
        passed = true;
        checkedArr = new Array();
        $('.wpcf-forms-field-name').each(function(index){
            var currentValue = $(this).val().toLowerCase();

            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same name
                $( '.wpcf-forms-field-name' ).each( function() {
                    if( $( this ).val().toLowerCase() == currentValue ) {
                        if (!$(this).hasClass('wpcf-name-checked-error')) {
                            $(this).before('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueNamesCheckText+'</div>').addClass('wpcf-name-checked-error');
                        }
                    };

                    // scroll to last expanded postbox with this issue
                    if( $( this ).closest( '.toolset-postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                        $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                        $( this ).first().trigger( 'focus' );
                    }
                } );

            }
            checkedArr.push(currentValue);
        });
        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-forms-field-name', function(){
                $(this).removeClass('wpcf-name-checked-error').prev('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }

        // Check field slugs unique
        passed = true;
        checkedArr = [];
        $.merge( checkedArr, allFieldSlugs );
        /**
         * first fill array with defined, but unused fields
         */
        $('#wpcf-form-groups-user-fields .wpcf-fields-add-ajax-link:visible').each(function(){
            checkedArr.push($(this).data('slug'));
        });
        $('.wpcf-forms-field-slug').each(function(index){

            // skip for "existing fields" if no change in input slug
            if( $( this ).data( 'types-existing-field' ) && $( this ).data( 'types-existing-field' ) == $( this ).val() )
                return true;

            var currentValue = $(this).val().toLowerCase();
            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same slug
                $( '.wpcf-forms-field-slug' ).each( function() {
                   if( $( this ).val() == currentValue ) {
                       if (!$(this).hasClass('wpcf-slug-checked-error')) {
                           $(this).before('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueSlugsCheckText+'</div>').addClass('wpcf-slug-checked-error');
                       }
                   };

                   // scroll to last expanded postbox with this issue
                   if( $( this ).closest( '.toolset-postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                       $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                       $( this ).first().trigger( 'focus' );
                   }
                } );
            }
            checkedArr.push(currentValue);
        });

        // Conditional check
        if (wpcfConditionalFormDateCheck() == false) {
            wpcf_fields_form_submit_failed();
            return false;
        }

        // check to make sure checkboxes have a value to save.
        $('[data-wpcf-type=checkbox],[data-wpcf-type=checkboxes]').each(function () {
            if (wpcf_checkbox_value_zero(this)) {
                passed = false;
            }
        });

        // repeatable field groups check
        if( 'undefined' !== typeof Types && ! Types.page.fieldGroupEdit.validateRepeatableGroupInputs() ) {
            passed = false;
        }

        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-forms-field-slug, .js-types-validate-required', function(){
                $(this).removeClass('wpcf-slug-checked-error').prev('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }

        /**
         * modal advertising dialog is shown on this event
         */
        $( document ).trigger( 'js-wpcf-event-types-show-modal' );
    } );


    var $intermediary_modal = jQuery('#toolset-intermediary-dialog');

    if ( $intermediary_modal.length === 1 ) {
        jQuery('.wpcf-form-submit').attr('disabled', 'disabled');
        $intermediary_modal.dialog({
            closeOnEscape: false,
            modal: true,
            width: 450,
            open: function(event, ui) {
                wpcfIntermediaryPostCreation();
                $intermediary_modal.next('.ui-dialog-buttonpane').hide();
            },
            buttons: [
                {
                    text: $intermediary_modal.data('close'),
                    class: "button-primary",
                    click: function() {
                        $intermediary_modal.remove();
                    }
                }
            ]
        });
    }

    // Disable existing Repeatable Field options.
    jQuery( '.js-wpcf-fields .toolset-postbox' ).each( function() {
        wpcfDisableRepeatableFieldOption( jQuery( this ) );
    } );


    /**
     * Shows WP pointers
     *
     * @since 3.0
     */
    jQuery( document ).on( 'click', '.js-show-tooltip', function() {
        var $this = jQuery(this);

        // default options
        var defaults = {
            edge: "left", // on which edge of the element tooltips should be shown: ( right, top, left, bottom )
            align: "middle", // how the pointer should be aligned on this edge, relative to the target (top, bottom, left, right, middle).
            offset: "15 0 " // pointer offset - relative to the edge
        };

        // custom options passed in HTML "data-" attributes
        var custom = {
            edge: $this.data('edge'),
            align: $this.data('align'),
            offset: $this.data('offset')
        };

        jQuery('.wp-toolset-pointer').hide();
        var content = '<p>' + $this.data('content') + '</p>';
        if ($this.data('header')) {
            content = '<h3>' + $this.data('header') + '</h3>' + content;
        }

        var extraClass = $this.hasClass('types-pointer-tooltip') ? ' types-pointer-tooltip' : '';

        $this.pointer({
            pointerClass: 'wp-toolset-pointer wp-toolset-types-pointer' + extraClass,
            content: content,
            position: jQuery.extend(defaults, custom) // merge defaults and custom attributes
        }).pointer('open');
    } );


});

/**
 * Disables the field option for converting a field into a Repeatable Field
 *
 * @param {Object} $fieldContainer
 * @since 3.0
 */
var wpcfDisableRepeatableFieldOption = function( $fieldContainer ) {
	var insideRFG = $fieldContainer.parents( '.types-repeatable-group' ).first().length > 0;
	var $repetitiveOptions = $fieldContainer.find( 'input:radio[name*="[repetitive]"]' );
	if (insideRFG) {
		$repetitiveOptions.last().attr( 'checked', 'checked' );
		$repetitiveOptions.attr( 'disabled', 'disabled' );
		$fieldContainer.find( '.js-show-tooltip.js-types-repeatable-field-disabled-tooltip' ).removeClass( 'hidden' );
	} else {
		$repetitiveOptions.removeAttr( 'disabled', 'disabled' );
		$fieldContainer.find( '.js-show-tooltip.js-types-repeatable-field-disabled-tooltip' ).addClass( 'hidden' );
	}
};


/**
 * Disables selected post type in PRF
 *
 * @param {Object} $container jQuery container element.
 */
var wpcfDisablePostTypeFromPRF = function( $container ) {
    var supportedPostTypes = [];
    jQuery('[name^="wpcf[group][supports]"][value!=""]').each( function() {
        supportedPostTypes.push( jQuery(this).val() );
    } );
    if ( supportedPostTypes.length > 0 ) {
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
    }
};
/**
 * on form submit fail
 */
function wpcf_fields_form_submit_failed() {
    wpcfLoadingButtonStop();
    wpcf_highlight_first_error();
}

/**
 * scroll to first issue
 */
function wpcf_highlight_first_error() {
    var $ = jQuery,
        firstError = $( '.wpcf-form-error' ).first(),
        postBox = firstError.closest( '.toolset-postbox' );


    if( postBox.hasClass( 'closed' ) ) {
        postBox.removeClass( 'closed' );
        postBox.find( '.handlediv' ).attr( 'aria-expanded', 'true' );
    }

    // Open collapsed divs
    firstError.parents('.toolset-collapsible-closed').find('.toolset-collapsible-handle').click();
    firstError.next( 'input, select' ).trigger( 'focus' );
}

/**
 * remove row
 */
function wpcf_conditional_remove_row(element)
{
    element.closest('tr').remove();
    wpcf_setup_conditions();
    return false;
}
/**
 * Create advance logic
 */
function wpcf_conditional_create_summary(button, parent)
{
    if ( jQuery('.js-wpcf-advance-logic textarea', parent).val() ) {
        return;
    }
    var condition = '';
    var skip = true;
    parent = jQuery(button).closest('form');
    jQuery('.wpcf-cd-entry', parent).each(function(){
        if (!skip) {
            condition += jQuery('.js-wpcf-simple-logic', parent).find('input[type=radio]:checked').val() + ' ';
        }
        skip = false;

        var field = jQuery(this).find('.js-wpcf-cd-field :selected');

        condition += '($(' + jQuery(this).find('.js-wpcf-cd-field').val() + ')';

        // We need to translate from currently supported "simple" to "advanced" syntax. Ironically, the advanced one
        // currently supports only a subset of comparison operators.
        //
        // While we're at it, we translate all operators to their "text-only" equivalents because that's what they're
        // going to be sanitized into anyway.
        var comparisonOperator = jQuery(this).find('.js-wpcf-cd-operation').val();
        switch(comparisonOperator) {
            case '=':
            case '===':
                comparisonOperator = 'eq';
                break;
            case '>':
                comparisonOperator = 'gt';
                break;
            case '>=':
                comparisonOperator = 'gte';
                break;
            case '<':
                comparisonOperator = 'lt';
                break;
            case '<=':
                comparisonOperator = 'lte';
                break;
            case '<>':
            case '!==':
                comparisonOperator = 'ne';
                break;
        }

        condition += ' ' + comparisonOperator;
        // Date
        if (field.hasClass('wpcf-conditional-select-date')) {
            var date = jQuery(this).find('.wpcf-custom-field-date');
            var month = date.children().first();
            var mm = month.val();
            var jj = month.next().val();
            var aa = month.next().next().val();
            condition += ' DATE(' + jj + ',' + mm + ',' + aa + ')) ';
        } else {
            condition += ' ' + jQuery(this).find('.js-wpcf-cd-value').val() + ') ';
        }
    });
    jQuery('.js-wpcf-advance-logic textarea', parent).val(condition);
}

/**
 * check condition methods
 */
function wpcf_setup_conditions()
{
    /**
     * move button "Add another condition" to mid if there is no condition
     */
    var dialog = jQuery( '.wpcf-filter-dialog' ).length ? jQuery( '.wpcf-filter-dialog' ) : jQuery( '.wpcf-conditions-dialog' ),
        btnAddCondition = jQuery('.js-wpcf-condition-button-add-row', dialog );

    if( 0 == jQuery('.js-wpcf-fields-conditions tr', dialog ).length ) {
        btnAddCondition.html( btnAddCondition.data( 'wpcf-label-add-condition' ) );
        btnAddCondition.addClass( 'wpcf-block-center' ).removeClass( 'alignright' );
    } else {
        btnAddCondition.html( btnAddCondition.data( 'wpcf-label-add-another-condition' ) );
        btnAddCondition.addClass( 'alignright' ).removeClass( 'wpcf-block-center' );
    }

    /**
     * checked condition method
     */
    if ( 1 < jQuery('.js-wpcf-fields-conditions tr', dialog ).length ) {
        jQuery('.wpcf-cd-relation.simple-logic').show();
    } else {
        jQuery('.wpcf-cd-relation.simple-logic').hide();
    }
    /**
     * bind select
     */
    jQuery('.js-wpcf-cd-field').on('change', function() {
        if ( jQuery(this).val() ) {
            jQuery('.js-wpcf-cd-operation, .js-wpcf-cd-value', jQuery(this).closest('tr')).removeAttr('disabled');
        } else {
            jQuery('.js-wpcf-cd-operation, .js-wpcf-cd-value', jQuery(this).closest('tr')).attr('disabled', 'disabled');
        }
    });
}

/**
 * @deprecated 2.3 (No longer required)
 */
function wpcfAddPostboxToggles() { return; }

/**
 * Make fields and repeatable groups sortable
 *
 * @since 2.3
 */
var wpcfFieldsSortableRFGWasMoved = 0;
var wpcfFieldsSortableRFGLevelBeforeMove = null;
var wpcfFieldsSortableRFGParentBeforeMove = null;

function wpcfFieldsSortable() {
    Toolset.Gui.AbstractPage.call( this );

    var self = this;
    var draggedObject;

    jQuery('.js-types-fields-sortable').sortable(
        {
            handle: '.js-toolset-sortable-handle',
            opacity: 0.7,
            connectWith: '.js-types-fields-sortable',
            start: function( e, gui ) {
                if( gui.item.find( '.types-repeatable-group-fields' ).length ) {
                    // rfg - let's store the pre-move level
                    wpcfFieldsSortableRFGLevelBeforeMove = gui.item.parents( '.types-repeatable-group-fields' ).length;

                    if( wpcfFieldsSortableRFGLevelBeforeMove > 0 ) {
                        wpcfFieldsSortableRFGParentBeforeMove = gui.item.parents( 'div[data-repeatable-group-id]' ).first().data( 'repeatable-group-id' );
                    }
                }
            },
            stop: function( e, gui ) {
                // don't allow post reference field nested in rfg
                if( gui.item.find('.js-wpcf-post-reference-field').length           // post reference field
                    && gui.item.parents( '.types-repeatable-group-fields' ).length  // dropped into a rfg
                ) {
                    alert( fieldFormStaticData['strings']['postReferenceNotAllowedInRFG'] );
                    jQuery( this ).sortable( 'cancel' );
                }
                wpcfDisableRepeatableFieldOption( jQuery( gui.item ) );
            },
            update: function( e, gui ) {
                if( ! gui.item.find('.types-repeatable-group-fields' ).length ) {
                    // no repeatable group moved
                    return;
                }

                if( this !== gui.item.parent()[0] ) {
                    // update is fired twice, for the target and the source, we only need the event for the target
                    // anyway we need to keep the source stored to be able to 'cancel' the sort on the target event
                    draggedObject = jQuery( this );

                    return;
                }

                var newLevelOfRFG = gui.item.parents( '.types-repeatable-group-fields' ).length;

                if( newLevelOfRFG == wpcfFieldsSortableRFGLevelBeforeMove ) {
                    // the level of the RFG did not changed
                    var newParentOfRFG = jQuery( this ).parents( 'div[data-repeatable-group-id]' ).first().data( 'repeatable-group-id' );

                    if( newParentOfRFG == wpcfFieldsSortableRFGParentBeforeMove ) {
                        // the parent did not changed and the user sorted on the same level - all good
                        return;
                    }
                }

                // at this point we know that the level of RFG has changed OR the parent has changed
                var staticData = jQuery( '#toolset_model_data' ).length
                    ? jQuery.parseJSON(WPV_Toolset.Utils.editor_decode64(jQuery( '#toolset_model_data' ).html()))
                    : false;

                var ajaxNonce = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'nonce' ],
                    ajaxName = staticData[ 'ajaxInfo' ][ 'fieldGroupEditAction' ][ 'name' ];

                jQuery.ajax( {
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxName,
                        wpnonce: ajaxNonce,
                        field_group_action: 'get_rfg_has_items',
                        repeatable_group_id: gui.item.data( 'repeatable-group-id' ),
                    },
                    dataType: 'json',
                    success: function( response ) {
                        if( response.data.error ) {
                            // shouldn't happen
                            alert( response.data.error );
                            return;
                        }

                        if( response.data.rfgHasItems == 1 ) {
                            var dialog = self.createDialog(
                                'types-dialog-move-repeatable-field-group-to-another-parent',
                                staticData[ 'strings' ][ 'moveRepeatableGroup' ],
                                {},
                                [
                                    {
                                        text: staticData[ 'strings' ][ 'button' ][ 'move' ],
                                        click: function() {
                                            // move RFG and delete all items
                                            wpcfFieldsSortableRFGWasMoved = 1;

                                            // delete all items
                                            jQuery.ajax( {
                                                url: ajaxurl,
                                                type: 'POST',
                                                data: {
                                                    action: ajaxName,
                                                    wpnonce: ajaxNonce,
                                                    field_group_action: 'get_rfg_delete_items',
                                                    repeatable_group_id: gui.item.data( 'repeatable-group-id' ),
                                                },
                                                dataType: 'json',
                                                success: function( response ) {

                                                },
                                                error: function( response ) {
                                                    console.log( response );
                                                }
                                            } );

                                            // close dialog
                                            jQuery( dialog.$el ).ddldialog( 'close' );
                                        },
                                        'class': 'button-primary types-delete-button'
                                    },
                                    {
                                        text: staticData[ 'strings' ][ 'button' ][ 'cancel' ],
                                        click: function() {
                                            // abort sort
                                            draggedObject.sortable( "cancel" );
                                            jQuery( dialog.$el ).ddldialog( 'close' );
                                        },
                                        'class': 'wpcf-ui-dialog-cancel'
                                    }
                                ]
                            );
                        } else {
                            wpcfFieldsSortableRFGWasMoved = 1;
                        }
                    },

                    error: function( response ) {
                        console.log( response );
                    }
                } );
            }
        }
    );
}

/**
 * fixes for dialogs
 */
( function( $ ) {
    // on dialogopen
    $( document ).on( 'dialogopen', '.ui-dialog', function( e, ui ) {
        // normalize primary buttons
        $( 'button.button-primary, button.wpcf-ui-dialog-cancel' )
            .trigger( 'blur' )
            .addClass( 'button' )
            .removeClass( 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' );
    } );

    // resize
    var resizeTimeout;
    $( window ).on( 'resize scroll', function() {
        clearTimeout( resizeTimeout );
        resizeTimeout = setTimeout( dialogResize, 200 );
    } );

    function dialogResize() {
        $( '.ui-dialog' ).each( function() {
            $( this ).css( {
                'maxWidth': '100%',
                'top': $( window ).scrollTop() + 50 + 'px',
                'left': ( $( 'body' ).innerWidth() - $( this ).outerWidth() ) / 2 + 'px'
            } );
        } );
    }


    /**
     * choose condition
     */
    $( document ).on( 'click', '.js-wpcf-condition-button-edit', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-conditions-dialog wpcf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: $thiz.data('wpcf-buttons-apply'),
                click: function() {
                    var groupConditions = ( $thiz.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_save',
                            _wpnonce: $thiz.data('wpcf-buttons-apply-nonce'),
                            id: $thiz.data('wpcf-id'),
                            group_conditions: groupConditions,
                            group_id: $thiz.data( 'wpcf-group-id' ),
                            meta_type: $thiz.data('wpcf-meta-type'),
                            conditions: $('form', $(this).closest('.wpcf-conditions-dialog')).serialize()
                        }
                    })
                        .done(function(html){

                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-wpcf-filter-container .js-wpcf-condition-preview' );
                                button = $('.js-wpcf-filter-container .js-wpcf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'wpcf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'wpcf-label-edit-condition' ) );
                            }

                            // close dialog
                            dialog.dialog( "close" );
                        });
                    return false;
                },
                class: 'button-primary'
            }, {
                text: $thiz.data('wpcf-buttons-cancel'),
                click: function() {
                    /**
                     * close dialog
                     */
                    $( this ).dialog( "close" );
                },
                class: 'wpcf-ui-dialog-cancel'
            }]
        });
        /**
         * load dialog content
         */
        dialog.load(
            ajaxurl,
            {
                action: $thiz.data('wpcf-action'),
                _wpnonce: $thiz.data('wpcf-nonce'),
                id: $thiz.data('wpcf-id'),
                group: $thiz.data('wpcf-group'),
                group_id: $thiz.data('wpcf-group-id'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('click', '.js-wpcf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_get_row',
                            _wpnonce: $(this).data('wpcf-nonce'),
                            id: $(this).data('wpcf-id'),
                            group_id: $( this ).data( 'wpcf-group-id' ),
                            meta_type: $(this).data('wpcf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-wpcf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-wpcf-fields-conditions', $(dialog) ).find( '.js-wpcf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                                    return wpcf_conditional_remove_row($(this));
                                });
                                wpcf_setup_conditions();
                                $(dialog).on('change', '.js-wpcf-cd-field', function() {
                                    wpcf_setup_conditions();
                                });
                            }

                        });
                    return false;
                });
                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                    return wpcf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('wpcf-custom-logic') ) {
                        $('.js-wpcf-simple-logic', $container).show();
                        $('.js-wpcf-advance-logic', $container).hide();
                        $(this).data('wpcf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('wpcf-content-advanced'));
                        $('.js-wpcf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-wpcf-simple-logic', $container).hide();
                        $('.js-wpcf-advance-logic', $container).show();
                        $(this).data('wpcf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('wpcf-content-simple'));
                        wpcf_conditional_create_summary(this, $container);
                        $('.js-wpcf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
    });

    $( function() {
        wpcfFieldsSortable();

        /* Post Reference Field
         * On post type change we need to check if the selected post type is valid to be used.
         */

        // holds "before change" value of all post reference fields
        var PRFSelectBeforeChangeValue = {};

        // get the init value of all prf fields
        $( '[data-prf-proof-selected-type]' ).each( function() {
            PRFSelectBeforeChangeValue[ $( this ).attr('id') ] = $( this ).val();
        });

        // event onchange, proof the select post type
        $( 'body' ).on( 'change', '[data-prf-proof-selected-type]', function() {
            if( ! $( this ).attr('id') in PRFSelectBeforeChangeValue ) {
                // new field, the first selected value is 0 ( "Select a post type..." )
                PRFSelectBeforeChangeValue[ $( this ).attr('id') ] = 0;
            }

            if( $( this ).find( ':selected' ).data( 'prf-no-valid-type' ) ) {
                // this post type is not valid, revert selection
                $( this ).val( PRFSelectBeforeChangeValue[ $( this ).attr('id') ] );

                // notice to the user
                Types.page.fieldGroupEdit.postReferenceFieldTypeWrongTranslationMode();
            } else {
                // valid, store new value as "beforeChange" value
                PRFSelectBeforeChangeValue[ $( this ).attr('id') ] = $( this ).val();
            }
        });
    } );

    $( function() {
        // use $_GET['field_group_action'] to set "toolset_field_group_on_load_action"
        // currently there is just one method "add_field", which will automatically open the add field dialog on page load
        if( typeof toolset_field_group_on_load_action !== 'undefined' ) {
            if( toolset_field_group_on_load_action === 'add_field') {
                $( '.js-wpcf-fields-add-new' ).trigger( 'click' );
            }
        }
    });
} )( jQuery );

/**
 * Batch process for creating association intermediary posts
 *
 * @since 2.3
 */
function wpcfIntermediaryPostCreation() {
    var $modal = jQuery('#toolset-intermediary-dialog');
    jQuery.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
            action: 'types_field_group_edit_action',
            field_group_action: 'create_intermediary_posts',
            wpnonce: $modal.data('wpnonce'),
            group_id: jQuery( 'input[name="wpcf[group][id]"]' ).val(),
        }
    }).done(function(data) {
        if ( data.success ) {
            var remaining = data.data.remaining_elements;
            var total = $modal.data('count');
            if ( remaining < total && remaining > 0 ) {
                var percent = parseInt( ( total - remaining ) * 100 / total );
                $modal.find('#intermediary-progress-bar span').css({width: percent + '%'});
                wpcfIntermediaryPostCreation();
                return;
            }
        }
        jQuery('#intermediary-progress-bar').fadeOut(function() {
            jQuery('#toolset-intermediary-dialog-confirmation-content').fadeIn();
            $modal.next('.ui-dialog-buttonpane').show()
        });

        jQuery('.wpcf-form-submit').removeAttr('disabled');
    });

}
