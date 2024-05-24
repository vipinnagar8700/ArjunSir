/* eslint-disable */
/*
    Post Reference Field Script
    Handles the select2 functionality for the post reference field

    @since m2m
 */
;(function( $ ) {
    $( function() {
        var self = this,
            staticData = JSON.parse( WPV_Toolset.Utils.editor_decode64( $( '#types_post_reference_model_data' ).html() ) );

		/**
		 * If the current page is a new one, with a post translation being translated, extract the
		 * new post's future language and TRID, to be used in WpmlTridAutodraftOverride.
		 */
		var getNewPostTranslationOverride = function() {
			var matchNewPostPage = new RegExp( /.*\/wp-admin\/post-new.php\?.*/gm, 'i' );
			var isNewPostPage = ( null !== matchNewPostPage.exec( window.location.href ) );

			if ( ! isNewPostPage ) {
				return null;
			}

			return {
				trid: WPV_Toolset.Utils.getParameterByName( 'trid', window.location.href ),
				lang_code: WPV_Toolset.Utils.getParameterByName( 'lang', window.location.href ),
			};
		};

        $( '[data-types-post-reference]' ).each( function() {
            var selectField = $( this );
            selectField.toolset_select2({
                allowClear: true,
                triggerChange: true,
                width: '100%',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    type: 'post',
	                data: function (params) {
		                return {
			                action: staticData['action']['name'],
			                skip_capability_check: true,
			                wpnonce: staticData['action']['nonce'],
							post_reference_field_action: 'json_post_reference_field_posts',
							post_id: staticData['post_id'],
							search: params.term,
							page: params.page,
							post_type: selectField.data( 'types-post-reference' ),
							field_slug: selectField.data( 'wpt-id' ),
							relationship_slug: selectField.data( 'types-relationship' ),
							parent_post_translation_override: getNewPostTranslationOverride(),
						};
					},
                    processResults: function (data, params) {
                        // console.log( data.items );
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: {
                                more: ( params.page * staticData['select2']['posts_per_load'] ) < data.total_count
                            }
                        };
                    },
                    cache: false
                },
                templateSelection: function( selected ) {
                    // add the url to the <option> tag as 'data-url' attribute
                    // (required for PRF Yoast compatiblity)
                    jQuery( selected.element ).attr("data-url", selected.url );

                    return selected.text;
                }
            });
        } );

        // Pointers.
        jQuery( '.toolset-post-reference-field .js-show-tooltip').click( function() {
            ToolsetTypes.Utils.Pointer.show( this );
        } );
    });

})( jQuery );
