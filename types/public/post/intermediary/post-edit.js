/*
    Handles the select of parent and child post on intermediary post edit page.

    @since 3.0
 */
;(function( $ ) {
    var viewModel = {
        // parent
        parentId: ko.observable(),
        parentUrl: ko.observable(),
        // child
        childId: ko.observable(),
        childUrl: ko.observable(),
        // conflicting intermediary
        // - will be set, when the user selects a parent/child combination, which already has an intermediary post
        conflictId: ko.observable(),
        conflictUrl: ko.observable(),
        // saving status
        isSaving: ko.observable( false ),
        // initialized status
        isInitialized: ko.observable( false )
    };

    var staticData,
        parentChildValues = [];

    function associationUpdate() {
        var parentId = parentChildValues[ 0 ],
            childId = parentChildValues[ 1 ];

        viewModel.isSaving( true );

        $.ajax( {
                url: ajaxurl,
                dataType: 'json',
                type: 'post',
                data: {
                    action: staticData[ 'action' ][ 'name' ],
                    wpnonce: staticData[ 'action' ][ 'nonce' ],
                    intermediary_action: 'json_save_association',
                    intermediary_id: staticData[ 'intermediaryId' ],
                    parent_id: parentId,
                    child_id: childId,
                },
                success: function( response ) {
                    viewModel.isSaving( false );
                    viewModel.conflictId( false );
                    viewModel.conflictUrl( false );

                    // Error Handling
                    if( response.result == staticData[ 'action' ][ 'responseStatus' ][ 'systemError' ]
                        || response.result == staticData[ 'action' ][ 'responseStatus' ][ 'domError' ]  ) {
                        alert( response.message );
                        return;
                    }

                    // Conflict Handling
                    if( response.result == staticData[ 'action' ][ 'responseStatus' ][ 'conflict' ]  ) {
                        viewModel.conflictId( response.conflict_id );
                        viewModel.conflictUrl( response.conflict_url );
                        return;
                    }
                },
                error: function( response ) {
                    console.log( response );
                }
            }
        )
    }

    /**
     * Call to set parent / child observables
     *
     * @param index     0 for parent, 1 for child
     * @param id        id of the post
     * @param editUrl   edit url of the post
     */
    function setParentChild( index, id, editUrl ) {
        if( index === 0 ) {
            viewModel.parentId( id );
            viewModel.parentUrl( editUrl );
        } else if( index === 1 ) {
            viewModel.childId( id );
            viewModel.childUrl( editUrl );
        }
    }

    $( function() {
        staticData = JSON.parse(
            WPV_Toolset.Utils.editor_decode64(
                $( '#toolset-types-intermediary-post-parent-child-data' ).html()
            )
        );

        $( '[data-types-intermediary-parent-child-select]' ).each( function( index ) {
            var selectField = $( this ),
                postType = selectField.data( 'types-intermediary-parent-child-select' );

            parentChildValues[ index ] = selectField.val();

            // update observables
            setParentChild( index, parentChildValues[ index ], selectField.find( ':selected' ).data( 'edit-url' ) );

            selectField.toolset_select2( {
                allowClear: true,
                triggerChange: true,
                width: '100%',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    type: 'post',
                    data: function( params ) {
                        return {
                            action: staticData[ 'action' ][ 'name' ],
                            wpnonce: staticData[ 'action' ][ 'nonce' ],
                            intermediary_action: 'json_intermediary_parent_child_posts',
                            post_id: staticData[ 'intermediaryId' ],
                            search: params.term,
                            page: params.page,
                            post_type: postType
                        };
                    },
                    processResults: function( data, params ) {
                        params.page = params.page || 1;
                        return {
                            results: $.map( data.items, function ( item ) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    editUrl: item.editUrl
                                };
                            }),
                            pagination: {
                                more: (params.page * staticData[ 'select2' ][ 'posts_per_load' ]) < data.total_count
                            }
                        };
                    },
                    cache: false
                },
            } );

            selectField.on( 'toolset_select2:unselect', function() {
                var preChange = parentChildValues.slice();

                // user change
                parentChildValues[ index ] = 0;
                setParentChild( index, false, false );

                console.log( preChange );
                if( preChange[0] != 0 && preChange[1] != 0 ) {
                    // parent && child had values before the user unset one -> delete the association
                    associationUpdate();
                }
            } );

            selectField.on( 'toolset_select2:select', function( e ) {
                parentChildValues[ index ] = e.params.data[ 'id' ];
                setParentChild( index, e.params.data[ 'id' ], e.params.data[ 'editUrl' ] );

                if( parentChildValues[0] != 0 && parentChildValues[1] != 0 ) {
                    // parent and child set -> save association
                    associationUpdate();
                }
            } );
        } );

        ko.applyBindings( viewModel );
        viewModel.isInitialized( true );
    } );

})( jQuery );
