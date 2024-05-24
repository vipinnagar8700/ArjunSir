var Types = Types || {};

Types.page = Types.page || {};

Types.page.editPostType = {};

/**
 * Edit Post Type page controller.
 *
 * Works together with the legacy script, however completely new code should be added here.
 *
 * @param $ jQuery
 * @constructor
 * @since 2.1
 */
Types.page.editPostType.Class = function($) {

    var self = this;
    let $editorInput;
    let $showInRestControl;
    let $showInRestInput;
    let $showInRestDescriptionElement;
    let showInRestOriginalValue;

    self.init = function() {
        self.initRewriteSlugChecker();

        $editorInput = $( 'input[name="ct[editor]"]' );
        $editorInput.on( 'click', self.adjustShowInRestRelatedToEditor );
        $showInRestControl = $( 'input[name="ct[show_in_rest_control]"]' );
        $showInRestInput = $( 'input[name="ct[show_in_rest]"]' );
        $showInRestDescriptionElement = $( '#attr_show_in_rest > .description' );
        showInRestOriginalValue = $showInRestControl.is( ':checked' );

    };


    /**
     * Start checking for rewrite slug conflicts within post types and taxonomies.
     *
     * Displays a warning (not error) message after the input field as long as there is a conflict, but doesn't block the
     * form submitting.
     *
     * @since 2.1
     */
    self.initRewriteSlugChecker = function() {
        var rewriteSlugInput = $('input[name="ct[rewrite][slug]"]');

        if(rewriteSlugInput.length == 0) {
            return;
        }

        var checker = Types.slugConflictChecker.build(
            rewriteSlugInput,
            ['post_type_rewrite_slugs', 'taxonomy_rewrite_slugs'],
            'post_type_rewrite_slugs',
            $('input[name="ct[wpcf-post-type]"]').val(),
            $('input[name="types_check_slug_conflicts_nonce"]').val(),
            function(isConflict, displayMessage) {

                // Hide previous error label
                var errorLabel = rewriteSlugInput.parent().find('label.wpcf-form-error.types-slug-conflict');
                if(0 !== errorLabel.length) {
                    errorLabel.remove();
                }

                if(isConflict) {
                    rewriteSlugInput.after(
                        '<label class="wpcf-form-error types-slug-conflict">' + displayMessage  + '</label>'
                    );
                }
            }
        );

        checker.bind();

        // Check even if rewrite is not enabled at the moment. When enabled later, the warning will be already in place.
        if(rewriteSlugInput.val().length > 0) {
            checker.check();
        }
    };

    /**
     * Adjusts the "show_in_rest"
     */
    self.adjustShowInRestRelatedToEditor = function(){
        let $currentSelectedOption = $( 'input[name="ct[editor]"]:checked' );

        if( $currentSelectedOption.val() == 'block' ) {
            $showInRestControl.prop( 'checked', true );
            $showInRestControl.attr( 'disabled', 'disabled' );
            $showInRestDescriptionElement.html( $showInRestControl.data( 'description-block' ) );
        } else {
            $showInRestControl.prop( 'checked', showInRestOriginalValue );
            $showInRestControl.removeAttr( 'disabled' );
            $showInRestDescriptionElement.html( $showInRestControl.data( 'description-classic' ) );
        }
    }

    $( self.init );
    $( self.adjustShowInRestRelatedToEditor );

    $( document ).on( 'submit','form.wpcf-types-form',function(){
        if( $showInRestControl.is( ':checked' ) ) {
            $showInRestInput.val( 1 );
        } else {
            $showInRestInput.remove();
        }
    });
};


Types.page.editPostType.main = new Types.page.editPostType.Class(jQuery);
