jQuery(document).ready( function($) {

    // Preview the changes
    $('#ccj-preview').click( function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Check if there is an URL
        if ( $("#ccj-preview_url").val().length == 0 ) {
            alert("Please provide an URL on which you want to preview the changes.");
            return;
        }

        // Check if there is a FULL url
        if ( $("#ccj-preview_url").val().indexOf('http') === -1 ) {
            alert("Please provide a FULL url on which you want to preview the changes.");
            return;
        }

        // Format the data
        var data = ccj_preview_data();
        var redirect_url = ccj_preview_redirect_url();

        // Send the data through AJAX
        $.post( ajaxurl, data, function( response ) {
            if ( response.indexOf('Error') === 0 ) {
                alert( response );
            } 
            if ( response === 'success' ) {
                window.open( redirect_url, '_blank');
            }
        } );

    });


    // Build the preview url
    function ccj_preview_redirect_url() {
		var delimiter = $("#ccj-preview_url").val().includes('?') ? '&' : '?';
        return $("#ccj-preview_url").val() + delimiter + 'ccj-preview-id=' + $("#ccj_preview-id").val();
    }


    // Build the object for AJAX save
    function ccj_preview_data() {

        var preprocessor = 'none';
        if ( $('input[name=custom_code_preprocessor]').length > 0 ) {
            preprocessor = $('input[name=custom_code_preprocessor]:checked').val();
        }

        var minify = false;
        if ( $('#custom_code_minify').length > 0 ) {
            minify = $('#custom_code_minify').is(':checked');
        }

        var data = {
            'action'      : 'ccj_preview_save',
            'ccj-preview-nonce' : $("#ccj-preview-nonce").val(), 
            'preview_id'  : $("#ccj_preview-id").val(),
            'content'     : $("#ccj_content").val(),
            'post_ID'     : $("#post_ID").val(),
            'preview_url' : $("#ccj-preview_url").val(),
            'linking'     : $('input[name=custom_code_linking]:checked').val(),
            'type'        : $('input[name=custom_code_type]:checked').val(), 
            'side'        : $('input[name=custom_code_side]:checked').val(), 
            'language'    : $('input[name=custom_code_language]').val(),
            'priority'    : $('#custom_code_priority').val(),
            'preprocessor': preprocessor,
            'minify'      : minify,
        };

        return data;
    }
});
