/**
 * API and helper functions for media fields management.
 *
 * @package Toolset
 * @since 3.3
 */

var Toolset = Toolset || {};

Toolset.Common = Toolset.Common || {};

Toolset.Common.MediaField = function( $ ) {

    this.i18n = toolset_media_field_i18n;

    this.mediaInstances = {};

    this.CONST = {
        SINGLE_CONTAINER_SELECTOR: '.js-wpt-field-items',
        REPEATING_CONTAINER_SELECTOR: '.js-wpt-field-item',
        INPUT_SELECTOR: '.js-toolset-media-field-trigger',
        MULTIPLE_GALLERY_ID: 'toolset-gallery',
        MULTIPLE_GALLERY_TOOLBAR_ID: 'toolset-toolbar-gallery',
		TYPES_ADD_NEW_RELATED_CONTENT_FORM_SELECTOR: 'form.types-new-relationship-form'
    };

    this.dialogClassName = 'media-frame toolset-forms-media-frame js-toolset-forms-media-frame';

    this.stylesAdded = false;

};

/**
 * Init constants for selectors.
 * Can be overriden by prototype implementations, for specific selectors.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.initConstants = function() {
    return this;
};

/**
 * Init the dialog classname.
 * Can be overriden by prototype implementations, for specific classnames.
 * Each instance can decine their own classnames, which can help when applying specific styles
 * on the initStyles method.
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.initDialogClassname = function() {
    return this;
};

/**
 * Init validation methods.
 * Can be overriden by prototype implementations, for specific methods.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.initValidationMethods = function() {
    return this;
};

/**
 * Init hooks.
 * Can be overriden by prototype implementations, for specific methods.
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.initHooks = function() {
    return this;
};

/**
 * Init events.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.initEvents = function() {
    var currentInstance = this;

    jQuery( document ).on( 'click', currentInstance.CONST.INPUT_SELECTOR, function( e ) {
        e.preventDefault();
        currentInstance.manageInputSelectorClick( jQuery( this ) );
    });

    return currentInstance;
};

/**
 * Init styles.
 * Can be overriden by prototype implementations, for specific styles.
 * To be used in combination with initDialogClassname to target items on dialogs for
 * each specific implementation.
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.initStyles = function() {
    return this;
};

/**
 * Init the default toolbar.
 *
 * @pram wp.media
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.initDefaultToolbar = function( metaData ) {
    var currentInstance = this,
        mediaInstance = currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ];

    mediaInstance.on( 'toolbar:create:select', function( toolbar, options ) {
        options = {
            text: currentInstance.i18n.dialog.single.button[ metaData.type ]
        };
        options.controller = this;

        toolbar.view = new wp.media.view.Toolbar.Select( options );
    }, mediaInstance );
};

/**
 * Maybe init the image editor if available.
 *
 * @pram wp.media
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.maybeInitImageEdit = function( metaData ) {
    if ( ! window.imageEdit ) {
        return;
    }

    if ( ! _.contains( [ 'image', 'file' ], metaData.type ) ) {
        return;
    }

    var currentInstance = this,
        mediaInstance = currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ];

    mediaInstance.states.add([
        new wp.media.controller.EditImage( { model: mediaInstance.options.editImage } )
    ]);

    mediaInstance.on( 'content:render:edit-image', function() {
        var image = mediaInstance.state().get('image'),
            view = new wp.media.view.EditImage( { model: image, controller: mediaInstance } ).render();

        mediaInstance.content.set( view );

        // after creating the wrapper view, load the actual editor via an ajax call
        view.loadEditor();
    }, mediaInstance );
};

/**
 * Maybe init the repeating fields views.
 *
 * @pram wp.media
 *
 * @since 3.3.6
 */
Toolset.Common.MediaField.prototype.maybeInitRepeating = function( metaData ) {
    if ( ! metaData.multiple ) {
        return;
    }

    var currentInstance = this,
        mediaInstance = currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ];

    // Add a state for custom gallery creation
    mediaInstance.states.add([
        new wp.media.controller.Library({
            id: currentInstance.CONST.MULTIPLE_GALLERY_ID,
            title: currentInstance.i18n.dialog.multiple.title[ metaData.type ],
            button: {
                text: currentInstance.i18n.dialog.multiple.button[ metaData.type ]
            },
            priority: 20,
            toolbar: currentInstance.CONST.MULTIPLE_GALLERY_TOOLBAR_ID,
            filterable: 'uploaded',
            library: wp.media.query( mediaInstance.options.library ),
            multiple: 'add'
        })
    ]);

    // Custom gallery toolbar
    mediaInstance.on( 'toolbar:create:' + currentInstance.CONST.MULTIPLE_GALLERY_TOOLBAR_ID, function( toolbar, options ) {
        options = {
            text: currentInstance.i18n.dialog.multiple.button[ metaData.type ]
        };
        options.controller = this;

        toolbar.view = new wp.media.view.Toolbar.Select( options );
    }, mediaInstance );

    // Make sure the query panel is updated when uploading a file.
    // See: https://core.trac.wordpress.org/ticket/34465
    mediaInstance.states.get( currentInstance.CONST.MULTIPLE_GALLERY_ID ).get( 'library' ).observe( wp.Uploader.queue );
};

/**
 * Input selector click: open the right media dialog.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.manageInputSelectorClick = function( $mediaSelector ) {
    var currentInstance = this;

	var typesRelationshipForm = $mediaSelector.closest( currentInstance.CONST.TYPES_ADD_NEW_RELATED_CONTENT_FORM_SELECTOR );
    var metaData = $mediaSelector.data( 'meta' );

    metaData = _.defaults( metaData, {
        metakey: '',
        title: '',
        parent: 0,
        type: '',
        multiple: false
    });

    // Make sure the post parent ID is an integer, force zero otherwise
    metaData.parent = parseInt( metaData.parent) || 0;

    // Maybe set the parent post to attach media;
    // backend does not need it as WP manages it by itself.
    metaData.parent = currentInstance.setParentId( metaData.parent, $mediaSelector );

    // Destroy media instances binded to an unknown parent:
    // needed for specific cases where this could lead to wrong fields caching
    // as containers might be wrongly set, when using templates for fields groups,
    // like in the Types dialogs to add a new related post,
    // or in frontend user forms.
    if (
        0 == metaData.parent
        && _.has( currentInstance.mediaInstances, metaData.parent )
    ) {
        currentInstance.mediaInstances = _.omit( currentInstance.mediaInstances, metaData.parent );
    }

    if ( ! _.has( currentInstance.mediaInstances, metaData.parent ) ) {
        currentInstance.mediaInstances[ metaData.parent ] = {};
    }

    // If the frame already exists and isn't inside a Types relationship related content metabox, re-open it.
    if ( 0 === typesRelationshipForm.length && _.has( currentInstance.mediaInstances[ metaData.parent ], metaData.metakey ) ) {
        currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].open();
        return;
    }

    var $innerContainer = $mediaSelector.closest( currentInstance.CONST.REPEATING_CONTAINER_SELECTOR ),
        $outerContainer = $mediaSelector.closest( currentInstance.CONST.SINGLE_CONTAINER_SELECTOR );

    if ( $innerContainer.length < 1 ) {
        $innerContainer = $outerContainer;
    }

    var libraryQueryParameters = Toolset.hooks.applyFilters( 'toolset_media_field_library_query_arguments', {
		'toolset_media_management_nonce': currentInstance.i18n.dialog.nonce,
		'toolset_media_management_origin': 'toolsetCommon',
        'toolset_media_management_filter': {
            // TODO support filtering by current author only
            //author: true
        },
        // Include an unique query arg so each instance does trigger an individual library query
        'toolset_media_management_unique_query_arg': metaData.metakey
    }, {
		selector: $mediaSelector,
		metaData: metaData
	});

    // Generic settings for the media modal
    var mediaSettings = {
        title: currentInstance.i18n.dialog.single.title[ metaData.type ],
        button: {
            text: currentInstance.i18n.dialog.single.button[ metaData.type ]
        },
        className: currentInstance.dialogClassName,
        frame: 'select',
        multiple: false,
        library: libraryQueryParameters
    };

    if ( metaData.multiple ) {
        // Open the iframe in the gallery view
        mediaSettings.state = currentInstance.CONST.MULTIPLE_GALLERY_ID;
    }

    // Enforce a file type if needed
    if ( _.contains( [ 'audio', 'image', 'video' ], metaData.type ) ) {
        mediaSettings.library.type = metaData.type;
    }

    // Initialize the media modal
    currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ] = wp.media( mediaSettings );

    // Native select toolbar
    currentInstance.initDefaultToolbar( metaData );

    // Support image editing if available
    currentInstance.maybeInitImageEdit( metaData );

    // Support repeating fields
    currentInstance.maybeInitRepeating( metaData );

    // Make sure the query panel is updated when uploading a file.
    // See: https://core.trac.wordpress.org/ticket/34465
    currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].states.get( 'library' ).get( 'library' ).observe( wp.Uploader.queue );

    // Set the upload custom nonce value, on dialog open, to ensure that
    // the uploder has been defined.
    // Note that there is no way of limiting upload per file type.
    currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].on( 'open', function() {
		currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].uploader.uploader.param( 'toolset_media_management_nonce', currentInstance.i18n.dialog.nonce );
		currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].uploader.uploader.param( 'toolset_media_management_origin', 'toolsetCommon' );
        Toolset.hooks.doAction( 'toolset_media_field_wp_media_onOpen', {
            wpMedia: currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ],
			selector: $mediaSelector,
			metaData: metaData
        });
        currentInstance.initStyles();
    });

    currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].on( 'select', function() {
        // Watch changes in wp-includes/js/media-editor.js
        var selectedMedia = currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ]
            .state()
            .get( 'selection' )
            .toJSON();

        // Set the value of the relevant input after getting at least one selected media item.
        var firstMediaItem = _.first( selectedMedia );

        /*
        // Repeat this for the repeating field below...
        // Set field value and update preview
        */
        currentInstance.setFieldValue( $innerContainer, firstMediaItem );
        currentInstance.manageFieldPreview( $innerContainer, firstMediaItem );

        // If more than one item is selected, create instances for all but first,
        // append them one after the other, and populate their values.
        if ( _.size( selectedMedia ) > 1 ) {
            var selectedMediaRest = _.rest( selectedMedia ),
                newInstancesNumber = _.size( selectedMediaRest ),
                $newInstancesTrigger = $outerContainer.find( '.js-wpt-repadd' ),
                $insertAfter = $innerContainer;
            _.times( newInstancesNumber, function( instanceIndex ) {
                var currentMediaItem = _.first( selectedMediaRest );

                $newInstancesTrigger.trigger( 'click', [ $insertAfter ] );
                var $currentInstance = $insertAfter.next( currentInstance.CONST.REPEATING_CONTAINER_SELECTOR );

                currentInstance.setFieldValue( $currentInstance, currentMediaItem );
                currentInstance.manageFieldPreview( $currentInstance, currentMediaItem );

                selectedMediaRest = _.rest( selectedMediaRest );
                $insertAfter = $currentInstance
            });
        }

    });

    currentInstance.mediaInstances[ metaData.parent ][ metaData.metakey ].open();

};

/**
 * Set the post ID where media will be attached, if needed.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.setParentId = function( parentId ) {
    return parentId;
};

/**
 * Set the field value.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.setFieldValue = function( $instance, mediaItem ) {
    return;
};

/**
 * Get the URL value from the selected mediaItem.
 *
 * Note that for images after WP 5.3 we should get the original size URL.
 *
 * @param object mediaItem
 * @return string
 * @since 3.4.9
 */
Toolset.Common.MediaField.prototype.getItemUrl = function( mediaItem ) {
    var url = mediaItem.url;
	if (
		_.has( mediaItem, 'sizes' )
		&& _.has( mediaItem.sizes, 'toolsetOriginal' )
		&& _.has( mediaItem.sizes.toolsetOriginal, 'url' )
	) {
		url = mediaItem.sizes.toolsetOriginal.url
	}

	return url;
};

/**
 * Update the field preview.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.manageFieldPreview = function( $instance, mediaItem ) {
    return;
};

/**
 * Initialize this prototype.
 *
 * @since 3.3
 */
Toolset.Common.MediaField.prototype.init = function() {
    this.initConstants()
        .initDialogClassname()
        .initValidationMethods()
        .initHooks()
        .initEvents();
};
