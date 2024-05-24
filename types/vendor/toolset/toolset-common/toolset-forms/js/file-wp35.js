/**
 * Media manager for backend file-related fields.
 *
 * @since 3.3
 * @package Toolset
 * @extends Toolset.Common.MediaField
 */

var Toolset = Toolset || {};

Toolset.Forms = Toolset.Forms || {};

Toolset.Forms.MediaField = function( $ ) {
    Toolset.Common.MediaField.call( this );

    var self = this;

    /**
     * Initialize the backend-specific constants for selectors.
     *
     * @since 3.3
     */
    self.initConstants = function() {
        self.CONST = _.extend( {}, self.CONST, {
            INPUT_VALUE_SELECTOR: '.wpt-form-textfield',
            REPEATING_CONTAINER_SELECTOR: '.js-wpt-field-item',
            PREVIEW_CONTAINER_SELECTOR: '.js-wpt-file-preview',
        } );

        return self;
    };

    /**
     * Set the field value.
     *
     * @param object $instance DOM element matching the field instance structure.
     * @param object Media item selected in the media dialog.
     *
     * @since 3.3
     */
    self.setFieldValue = function( $instance, mediaItem ) {
        var value = self.getItemUrl( mediaItem );
        $instance
            .find( self.CONST.INPUT_VALUE_SELECTOR )
                .val( value )
                .trigger( 'change' );
    };

    /**
     * Update the field preview, if any.
     *
     * @param object $instance DOM element matching the field instance structure.
     * @param object Media item selected in the media dialog.
     *
     * @since 3.3
     */
    self.manageFieldPreview = function( $instance, mediaItem ) {
        var $previewContainer = $instance.find( self.CONST.PREVIEW_CONTAINER_SELECTOR ),
            $mediaSelector = $instance.find( self.CONST.INPUT_SELECTOR ),
            metaData = $mediaSelector.data( 'meta' );

        metaData = _.defaults( metaData, {
            metakey: '',
            parent: 0,
            type: '',
            preview: '',
            multiple: false,
            select_label: '',
            edit_label: ''
        });

        if ( '' == metaData.preview ) {
            if ( _.contains( [ 'audio', 'file', 'video' ], metaData.type ) ) {
                metaData.preview = 'url';
            }
            if ( _.contains( [ 'image' ], metaData.type ) ) {
                metaData.preview = 'img';
            }
        }

        switch( metaData.preview ) {
            case 'img':
                if ( 0 == $( 'img', $previewContainer ).length) {
                    $previewContainer.append('<img src="">');
                }

                var $img = $previewContainer.find( 'img' );

                var has_size_full = false;
                if (
                    'undefined' != typeof mediaItem.sizes
                    && 'undefined' != typeof mediaItem.sizes.full
                    && 'undefined' != typeof mediaItem.sizes.full.url
                ) {
                    has_size_full = true;
                }

                if (
                    'undefined' != typeof mediaItem.sizes
                    && 'undefined' != typeof mediaItem.sizes.thumbnail
                    && 'undefined' != typeof mediaItem.sizes.thumbnail.url
                   ) {
                    $img.attr(
                        {
                            'src': mediaItem.sizes.thumbnail.url,
                            'srcset': mediaItem.sizes.thumbnail.url,
                        }
                    );
                }
                else if ( has_size_full ) {
                    $img.attr(
                        {
                            'src': mediaItem.sizes.full.url,
                            'srcset': mediaItem.sizes.full.url,
                        }
                    );
                }
                else if ( 'undefined' != typeof(mediaItem.url) ) {
                    $img.attr(
                        {
                            'src': mediaItem.url,
                            'srcset': mediaItem.url,
                        }
                    );
                }
                /**
                 * add full
                 */
                if ( has_size_full ) {
                    $img.data('full-src', mediaItem.sizes.full.url);
                } else if ( 'undefined' != typeof(mediaItem.url) ) {
                    $img.data('full-src', mediaItem.url);
                }

                // add "title" and "alt" attribute to preview image
                $img.attr('alt', mediaItem.alt );
                $img.attr('title', mediaItem.title );

                /**
                 * bind preview popup
                 */
                if ( 'function' == typeof bind_colorbox_to_thumbnail_preview) {
                    bind_colorbox_to_thumbnail_preview();
                }
                break;
            case 'url':
            case 'filename':
            default:
                $previewContainer.hide();
                break;
        }

        $mediaSelector.text( metaData[ 'edit_label' ] );

    };

    /**
     * Set the post to attach media to, if needed.
     *
     * Note that we only force a post ID when dealing with quick edit for related posts:
     * - it works properly on add new/connect to existing relationships creation.
     * - it works properly on RFGs instances.
     * - but it only attaches properly to the right post in quick edit :-(
     *
     * @param int parentId
     * @param object $mediaSelector DOM element matching the button to add field values.
     *
     * @since 3.3
     */
    self.setParentId = function( parentId, $mediaSelector ) {
        if ( $mediaSelector.closest( '.types-quick-edit-fields' ).length > 0 ) {
            parentId = $mediaSelector
                .closest( '.types-quick-edit-fields' )
                    .find('[name=post_id]')
                        .val();
            wp.media.model.settings.post.id = parentId;
        }
        return parentId;
    };

    self.init();
};

Toolset.Forms.MediaField.prototype = Object.create( Toolset.Common.MediaField.prototype );

jQuery( function( $ ) {
    new Toolset.Forms.MediaField( $ );
});
