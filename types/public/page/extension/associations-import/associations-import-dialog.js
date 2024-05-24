/* eslint-disable */

var Types = Types || {};
Types.AssociationsImport = Types.AssociationsImport || {};


/**
 * Associations Import
 *
 * @since 3.0
 */
Types.AssociationsImport = function( $ ) {
    Toolset.Gui.AbstractPage.call( this );

    var self = this,
        predefined = Types.AssociationsImport.predefined;

    self.viewModel = {
        pagination: function( associations ) {
            var pagination = this;

            pagination.allAssociations = associations;
            pagination.currentPage = ko.observable( 1 );
            pagination.associationsPerPage = 15;

            // count of all pages
            pagination.countPages = ko.computed( function() {
                var pages = Math.floor( pagination.allAssociations().length / pagination.associationsPerPage );
                pages += pagination.allAssociations().length % pagination.associationsPerPage > 0 ? 1 : 0;
                return pages;
            } );

            // current visible associations
            pagination.visibleAssociations = ko.computed( function() {
                var pointer = ( pagination.currentPage() - 1 ) * pagination.associationsPerPage;
                return pagination.allAssociations.slice( pointer, pointer + pagination.associationsPerPage );
            } );

            // first page
            pagination.firstPage = function() {
                pagination.currentPage( 1 );
            }

            // last page
            pagination.lastPage = function() {
                pagination.currentPage( pagination.countPages() );
            }

            // previous page
            pagination.hasPreviousPage = ko.computed( function() {
                return pagination.currentPage() !== 1;
            } );

            pagination.previousPage = function() {
                if( pagination.hasPreviousPage() ) {
                    pagination.currentPage( pagination.currentPage() - 1 );
                }
            }

            // next page
            pagination.hasNextPage = ko.computed( function() {
                return pagination.currentPage() < pagination.countPages();
            });

            pagination.nextPage = function() {
                var current = pagination.currentPage();

                if( pagination.hasNextPage() ) {
                    pagination.currentPage( pagination.currentPage() + 1 );

                    // some strange behaviour on the first click
                    // (which does not update currentPage probably without this)
                    if( pagination.currentPage() == current ) {
                        pagination.currentPage( current + 1 );
                    }
                }
            }
        },
        loadingAssociation: ko.observable( true ),
        importingAssociations: ko.observable( false ),
        importStarted: ko.observable( false ),
        associations: {
            count: ko.observable( 0 ),

            import: ko.observableArray(),
            imported: ko.observable( 0 ),
            importedError: ko.observable( 0 ),

            readyToImport: ko.observableArray(),
            readyToImportExpanded: ko.observable( false ),
            areAlreadyImported: ko.observableArray(),
            areAlreadyImportedExpanded: ko.observable( false ),
            haveMissingData: ko.observableArray(),
            haveMissingDataExpanded: ko.observable( true )
        },

        isAnyAssociationVisible: ko.computed( function() {
            return true;
        } ),

        toggle: function( expanded ) {
            expanded( ! expanded() );
        },

        importStart: function() {
            var associations = ko.toJS( self.viewModel.associations.import );
            self.viewModel.importStarted( true );
            self.viewModel.importingAssociations( true );

            self.importAssociations( associations, 100, 0 );
        },

        loadAssociations: function() {
            self.loadAssociations( 100, 0 );
        }
    }

    // readyToImport pagination
    self.viewModel.associations.readyToImportPagination
        = new self.viewModel.pagination( self.viewModel.associations.readyToImport );

    // areAlreadyImported pagination
    self.viewModel.associations.areAlreadyImportedPagination
        = new self.viewModel.pagination( self.viewModel.associations.areAlreadyImported );

    // haveMissingData pagination
    self.viewModel.associations.haveMissingDataPagination
        = new self.viewModel.pagination( self.viewModel.associations.haveMissingData );

    // Replace TabIcon by an spinning icon (can't do that on the template as the tab is out of scope)
    Types.AssociationsImport.TabIcon.attr( 'class', 'fa fa-refresh fa-spin' );

    // load associations
    self.loadAssociations = function( loadLimit, loadOffset ) {
        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: predefined.ajax.action,
                wpnonce: predefined.ajax.nonce,
                associations_import_action: 'getAssociations',
                associations_import_limit : loadLimit,
                associations_import_offset: loadOffset
            },
            dataType: 'json',
            success: function( response ) {
                if( response.data.associations.length > 0 ) {
                    self.loadAssociations( loadLimit, loadOffset + loadLimit );

                    ko.utils.arrayMap( response.data.associations, function( data ) {

                        var association = new Types.AssociationsImport.Association( data );
                        self.viewModel.associations.count( self.viewModel.associations.count() + 1 );

                        if( association.hasMissingData ) {
                            self.viewModel.associations.haveMissingData.push( association );
                        } else if( association.isAlreadyImported ) {
                            self.viewModel.associations.areAlreadyImported.push( association );
                        } else {
                            self.viewModel.associations.readyToImport.push( association );
                            self.viewModel.associations.import.push( association );
                        }

                    } );
                } else {
                    // all data loaded
                    self.viewModel.loadingAssociation( false );

                    // delete already existing associations
                    self.deleteAlreadyExistingAssociationsFromImportList();

                    // remove the spinning icon from the tab
                    Types.AssociationsImport.TabIcon.attr( 'class', Types.AssociationsImport.TabIcon.OriginalClass );
                }
            },
            error: function( response ) {
                console.log( response );
            }
        } );
    }

    /**
     * Import Associations
     *
     * @param associations
     * @param importLimit
     * @param importOffset
     */
    self.importAssociations = function( associations, importLimit, importOffset ) {
        var associationsChunk = associations.slice( importOffset, importOffset + importLimit );

        if( associationsChunk.length == 0 ) {
            // all items imported
            self.viewModel.importingAssociations( false );

            // delete broken associations (which weren't fixed by the user before)
            self.deleteBrokenAssociationsFromImportList();

            return;
        }

        $.ajax(
            {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: predefined.ajax.action,
                    wpnonce: predefined.ajax.nonce,
                    associations_import_action: 'importAssociations',
                    associations_import: JSON.stringify( associationsChunk ),
                },
                dataType: 'json',
                success: function( response ) {
                    // success associations
                    self.viewModel.associations.imported(
                        self.viewModel.associations.imported() + response.data.success );

                    // associations with error on import
                    // (very edge case as we proofed them before in the listing - but can happen if in the meantime
                    // someone deletes an association element)
                    self.viewModel.associations.importedError(
                        self.viewModel.associations.importedError() + response.data.error );

                    // next chunk
                    setTimeout( function() {
                        self.importAssociations( associations, importLimit, importOffset + importLimit )
                    }, 0);
                },
                error: function( response ) {
                    console.log( response );
                }
            }
        );
    }

    /**
     * Delete already existing associations from the import list
     */
    self.deleteAlreadyExistingAssociationsFromImportList = function() {
        $.ajax(
            {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: predefined.ajax.action,
                    wpnonce: predefined.ajax.nonce,
                    associations_import_action: 'deleteExistingAssociationsFromImportList',
                },
                dataType: 'json',
                success: function( response ) {
                    // nothing to do on success of delet
                    // "response.data.deleted" holds the count of deleted associations
                },
                error: function( response ) {
                    console.log( response );
                }
            }
        );
    }

    /**
     * Delete broken associations from the import list
     */
    self.deleteBrokenAssociationsFromImportList = function() {
        var brokenAssociations = ko.toJS( self.viewModel.associations.haveMissingData );

        console.log( brokenAssociations );
        $.ajax(
            {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: predefined.ajax.action,
                    wpnonce: predefined.ajax.nonce,
                    associations_import_action: 'deleteBrokenAssociationsFromImportList',
                    associations_to_delete: brokenAssociations,
                },
                dataType: 'json',
                success: function( response ) {
                    // nothing to do on success of delet
                    // "response.data.deleted" holds the count of deleted associations
                },
                error: function( response ) {
                    console.log( response );
                }
            }
        );
    }

    // associations import container
    self.associationsImportContainer = $( '#toolset-associations-import' );

    if( self.associationsImportContainer.length ) {
        ko.applyBindings( self.viewModel, self.associationsImportContainer[ 0 ] );

        // start loading associations
        self.viewModel.loadAssociations();

        // show after knockout applyBindings to make sure there is no GUI flickering
        self.associationsImportContainer.show();
    }
}

/**
 * Association
 * @param data
 *
 * @constructor
 */
Types.AssociationsImport.Association = function( data ) {
    var self = this;

    self.meta = {
        postId: data.meta.postId,
        key: data.meta.key,
        associationString: data.meta.associationString
    }

    /** @var bool */
    self.isAlreadyImported = data.isAlreadyImported;

    /** @var bool */
    self.hasMissingData = data.hasMissingData;

    /** @var Types.AssociationsImport.Relationship|null */
    self.relationship = new Types.AssociationsImport.Relationship( data.relationship );

    /** @var Types.AssociationsImport.Post|null */
    self.parent = new Types.AssociationsImport.Post( data.parent );

    /** @var Types.AssociationsImport.Post|null */
    self.child = new Types.AssociationsImport.Post( data.child );

    /** @var Types.AssociationsImport.Post|null */
    self.intermediary = new Types.AssociationsImport.Post( data.intermediary );
}

/*
 * @param data
 * @constructor
 */
Types.AssociationsImport.Post = function( data ) {
    var self = this;

    /** @var false|string
     * If the post couldn't be found it will hold the ident string (for now the GUID of the post) */
    // self.couldNotBeFound = 'couldNotBeFound' in data ? data.couldNotBeFound : false;
    // if( self.couldNotBeFound ) return;

    /** @var bool */
    self.isAvailable = data.isAvailable;

    /** @var bool Can be false for intermediary */
    self.isRequired = data.isRequired;

    /** @var string */
    self.postTitle = data.postTitle;

    /** @var int */
    self.id = data.id;

    /** @var string */
    self.guid = data.guid;
}

/*
 * @param data
 * @constructor
 */
Types.AssociationsImport.Relationship = function( data ) {
    var self = this;

    /** @var bool */
    self.isAvailable = data.isAvailable;

    /** @var string */
    self.slug = data.slug;

    /** @var string */
    self.pluralName = data.pluralName;

    /** @var string */
    self.singularName = data.singularName;
}

// Tab Icon
Types.AssociationsImport.TabIcon = jQuery( '.js-toolset-nav-tab[data-target="associations"]' ).find( 'i' );
Types.AssociationsImport.TabIcon.OriginalClass = Types.AssociationsImport.TabIcon.attr( 'class' );

/** @var {} types_page_extension_associations_import_dialog */
Types.AssociationsImport.predefined = types_page_extension_associations_import_dialog;

/* Trigger to load associations */
if( Types.AssociationsImport.predefined.toolsetImportExport.activeTab == 'associations' ) {
    // associations tab is active
    Types.AssociationsImport( jQuery );
} else {
    // associations tab is not active
    jQuery( document ).on( 'click.toolset-associations', '.js-toolset-nav-tab', function() {
        if( jQuery( this ).data( 'target' ) == 'associations' ) {
            // disable trigger
            jQuery( document ).off( 'click.toolset-associations', '.js-toolset-nav-tab' );
            Types.AssociationsImport( jQuery );
        }
    });
}

/**
 * tooltip (move this to toolset-common)
 * http://www.mkyong.com/jquery/how-to-create-a-tooltips-with-jquery/
 */
Types.AssociationsImport.changeTooltipPosition = function(event) {
    var tooltipX = event.pageX + 2;
    var tooltipY = event.pageY + 8;
    jQuery('div.association-tooltip').css({top: tooltipY, left: tooltipX});
};

Types.AssociationsImport.showTooltip = function(event) {
    jQuery('div.association-tooltip').remove();
    jQuery('<div class="association-tooltip">'+jQuery(this).data('tooltip')+'</div>').appendTo('body');
    Types.AssociationsImport.changeTooltipPosition(event);
};

Types.AssociationsImport.hideTooltip = function() {
    jQuery('div.association-tooltip').remove();
};

jQuery(document).on( 'mousemove', '.js-wpcf-tooltip', Types.AssociationsImport.changeTooltipPosition );
jQuery(document).on( 'mouseenter', '.js-wpcf-tooltip', Types.AssociationsImport.showTooltip );
jQuery(document).on( 'mouseleave', '.js-wpcf-tooltip', Types.AssociationsImport.hideTooltip );
