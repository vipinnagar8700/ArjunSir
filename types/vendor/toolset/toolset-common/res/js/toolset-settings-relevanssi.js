var ToolsetCommon = ToolsetCommon || {};

/**
 * Relevanssi integration - Control the behavior of the Text Search tab in the Toolset Settings page.
 *
 * @since 2.2
 */
ToolsetCommon.ToolsetSettingsRelevanssi = function( $ ) {

    var self = this;

    /**
     * Get the array of fields checked for Relevanssi indexation.
     *
     * @param checked	array	List of checkboxes for fields that are checked, hence should be included in the index.
     * @return array		Valus of the fields checked for indexing.
     * @since 2.2
     */
    self.get_list_array = function( checked ) {
        var fields_array = checked.map( function() {
            return $( this ).val();
        }).get();
        return fields_array;
    };

    /**
     * Update the comma separated list of fields that need to be indexed.
     *
     * Replace the readonly input value that holds the comma separated list of fields to index
     * and glow it for half a second.
     *
     * @param fields_list	string	Comma separated list of fields to be included in the index.
     * @since 2.2
     */
    self.update_list_summary_fields = function( fields_list ) {
        $( '.js-toolset-relevanssi-list-summary-fields' )
            .val( fields_list )
            .css(
                {
                    'box-shadow':		'0 0 5px 1px #f6921e',
                    'background-color':	'#f6921e',
                    'color':			'#fff'
                }
            );
        setTimeout( function() {
            $( '.js-toolset-relevanssi-list-summary-fields' ).css(
                {
                    'box-shadow':		'none',
                    'background-color':	'#ededed',
                    'color':			'#444'
                }
            );
        }, 500 );
    };

    /**
     * Save Relevanssi settings.
     *
     * On eqch field checkbox change, debounce the saving and update the comma separated fields list.
     * Note that we track the fields status and only act when there is an actual change.
     *
     * @since 2.2
     */
    self.save_relevanssi_settings = function() {
        if ( self.revelanssi_state != $( '.js-toolset-relevanssi-list :input' ).serialize() ) {
            var checked = $( '.js-toolset-relevanssi-list-item:checked' ),
                fields_array = self.get_list_array( checked ),
                data = {
                    action:		'toolset_update_toolset_relevanssi_settings',
                    fields:		fields_array,
                    wpnonce:	$('#toolset_relevanssi_settings_nonce').val()
                };
            $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
            $.ajax({
                type:		"POST",
                dataType:	"json",
                url:		ajaxurl,
                data:		data,
                success: function( response ) {
                    if ( response.success ) {
                        self.revelanssi_state = $( '.js-toolset-relevanssi-list :input' ).serialize();
                        if ( checked.length > 0 ) {
                            self.update_list_summary_fields( fields_array.join( ', ' ) );
                            $( '.js-toolset-relevanssi-list-summary' ).fadeIn( 'fast' );
                        } else {
                            $( '.js-toolset-relevanssi-list-summary' ).hide();
                        }
                        $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
                    } else {
                        $( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
                    }
                },
                error: function( ajaxContext ) {
                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
                },
                complete: function() {

                }
            });
        }
    };

    self.relevanssi_debounce_update = _.debounce( self.save_relevanssi_settings, 1000 );

    $( document ).on( 'change', '.js-toolset-relevanssi-list-item', function() {
        self.relevanssi_debounce_update();
    });

    self.revelanssi_state = '';

    /**
     * Get the list of fields that can be included in the text search, on demand.
     *
     * @since 3.4
     */
    self.getListOfCompatibleFields = function() {
        var data = {
            action: 'toolset_list_toolset_relevanssi_compatible_fields',
            wpnonce: $( '#toolset_relevanssi_settings_nonce' ).val()
        };
        $.ajax({
            type: "GET",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
                    $( '.js-toolset-relevanssi-container' ).html( response.data.content );
                    // Track the fields that are checked for indexation.
                    self.revelanssi_state = $( '.js-toolset-relevanssi-list :input' ).serialize();
                }
            },
            error: function( ajaxContext ) {

            },
            complete: function() {
                $( '.js-toolset-relevanssi-summary-list-compatible-fields' ).removeClass( 'disabled' ).prop( 'disabled', false );
                $( '.js-toolset-relevanssi-container .fa.fa-refresh' ).remove();
            }
        });
    }

    $( document ).on( 'click', '.js-toolset-relevanssi-summary-list-compatible-fields', function( event ) {
        event.preventDefault();
        $( this ).addClass( 'disabled' ).prop( 'disabled', true );
        $( '<i class="fa fa-refresh fa-spin"></i>' ).insertAfter( $( this ) );
        self.getListOfCompatibleFields();
    });

    self.init = function() {

    };

    self.init();

};

jQuery( function( $ ) {
    ToolsetCommon.settings_relevanssi = new ToolsetCommon.ToolsetSettingsRelevanssi( $ );
});
