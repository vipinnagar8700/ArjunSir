/* eslint-disable */
var Types = Types || {};

Types.page = Types.page || {};

Types.page.editTaxonomy = {};

/**
 * Edit Taxonomy page controller.
 *
 * Works together with the legacy script, however completely new code should be added here.
 *
 * @param $ jQuery
 * @constructor
 * @since 2.1
 */
Types.page.editTaxonomy.Class = function($) {

    var self = this;

    self.init = function() {
    	self.initTaxonomySlugChecker();
        self.initRewriteSlugChecker();
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

        if ( rewriteSlugInput.length === 0 ) {
            return;
        }

        var checker = Types.slugConflictChecker.build(
            rewriteSlugInput,
            ['post_type_rewrite_slugs', 'taxonomy_rewrite_slugs'],
            'taxonomy_rewrite_slugs',
            $('input[name="ct[wpcf-tax]"]').val(),
            $('input[name="types_check_slug_conflicts_nonce"]').val(),
            function (isConflict, displayMessage) {

                var errorLabel = rewriteSlugInput.parent().find('label.wpcf-form-error.types-slug-conflict');
                if (0 !== errorLabel.length) {
                    errorLabel.remove();
                }

                if (isConflict) {
                    rewriteSlugInput.after(
                        '<label class="wpcf-form-error types-slug-conflict">' + displayMessage + '</label>'
                    );
                }
            }
        );

        checker.bind();

        // Check even if rewrite is not enabled at the moment. When enabled later, the warning will be already in place.
        if (rewriteSlugInput.val().length > 0) {
            checker.check();
        }
    };

	/**
	 * Check an already used taxonomy slug for conflicts.
	 *
	 * Note that checking new values before saving is handled in legacy code (look for 'ajax_wpcf_is_reserved_name').
	 *
	 * @since 3.3.8
	 */
	self.initTaxonomySlugChecker = function() {
		var taxonomySlugInput = $('input[name="ct[slug]"]');

		if ( taxonomySlugInput.length === 0 ) {
			return;
		}

		// We're only checking against the static, read-only value.
		// That means the warning will only display when the value is already in use.
		//
		// Validation before saving is already implemented, so no new taxonomy should be saved this way.
		var taxonomySlugHiddenInput = $('input[name="ct[wpcf-tax]"]');
		if ( taxonomySlugHiddenInput.length === 0 ) {
			return;
		}

		var getErrorLabel = function() {
			return taxonomySlugInput.parent().find('label.wpcf-form-error.types-slug-conflict');
		};

		var removeExistingErrorLabel = function() {
			var errorLabel = getErrorLabel();
			if (0 !== errorLabel.length) {
				errorLabel.remove();
			}
		};

		var checker = Types.slugConflictChecker.build(
			taxonomySlugHiddenInput,
			['taxonomy_slugs'],
			'taxonomy_slugs',
			taxonomySlugHiddenInput.val(),
			$('input[name="types_check_slug_conflicts_nonce"]').val(),
			function (isConflict, displayMessage) {

				removeExistingErrorLabel();

				if (isConflict) {
					taxonomySlugInput.after(
						'<label class="wpcf-form-error types-slug-conflict">' + displayMessage + '</label>'
					);
				}
			}
		);

		// Checking only on initialization.
		if (taxonomySlugHiddenInput.val().length > 0) {
			checker.check();
		}

		taxonomySlugInput.on( 'change', _.debounce( removeExistingErrorLabel, 1000 ) );
	};

    $(self.init);
};


Types.page.editTaxonomy.main = new Types.page.editTaxonomy.Class(jQuery);
