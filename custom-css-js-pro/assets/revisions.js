jQuery(document).ready( function($) {

    // Toggle the `delete` checkboxes
    $("#ccj-delete-checkbox").click( function() {
        $("table.revisions .revisions-delete input:not([disabled])").attr('checked', $("#ccj-delete-checkbox").is(":checked"));
    });


    // Show the `compare` Thickbox
    $("#revisions-compare-button").click( function() {
       var left = $("table.revisions input[name=compare_left]:checked").val(); 
       var right = $("table.revisions input[name=compare_right]:checked").val(); 
       var post_id = $("#post_ID").val();

       if ( !left || !right ) {
            alert( 'Please select two revisions to compare' );
            return;
       }

       if ( left === right ) {
            alert( 'Please select two different revisions to compare' );
            return;
       }

       var url = 'admin-post.php?action=ccj-revisions-compare&left='+left+'&right='+right+'&post_id='+post_id+'&TB_iframe=true';
       var title = $("input#title").val();

       tb_show( 'Compare revisions for "'+title+'"', url ); 

       this.blur();
    }); 

    // Delete the revisions
    $("#revisions-delete-button").click( function() {
        
        if ( $(".revisions-delete input:checked").length === 0 ) {
            alert( 'You did not select any revision to delete' );    
            return;
        }

        if ( ! confirm( 'Are you sure you want to delete the revisions?' ) ) {
            return;
        }

        var ids = [];

        $(".revisions-delete input:checked").each( function() {
            var id = $(this).val();
            ids.push( id );
            $("#revision-row-" + id).css('background-color', '#f2dede');
        });

        $.post('admin-post.php', {
                action: 'ccj-revisions-delete',
                revision_ids: ids.join(','),
                _wpnonce: $("#revisions-nonce").val() 
            }, function(xml) {
                var response = wpAjax.parseAjaxResponse(xml);
                response = response.responses[0];

                if ( response.data == -1 ) {
                    alert( 'The request to delete the revisions failed. Please try again.' );
                } else {
                    var revisions = response.supplemental.ids.split(',');
                    revisions.forEach(function( id ){
                        $("#revision-row-" + id).fadeOut( 300, function() {
                            $(this).remove();
                        });
                    });
                } 
            }
        );

    });
});
