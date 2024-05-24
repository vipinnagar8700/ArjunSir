/**
 * Listing for the "Custom Code" tab on the Toolset Settings page.
 *
 * @param snippetModels
 * @param defaults Default listing configuration.
 * @param l10n
 * @param pageController
 * @constructor
 */
Toolset.page.codeSnippets.SnippetListing = function(snippetModels, defaults, l10n, pageController) {

    var self = this;

	/**
	 * @param {Toolset.page.codeSnippets.Snippet} snippetModel
	 * @param {string} searchString
	 */
	var itemSearchFunction = function( snippetModel, searchString ) {
		return _.some(
			[ snippetModel.slug(), snippetModel.displayName(), snippetModel.description(), snippetModel.code() ],
			function( value ) {
				return ( typeof ( value ) !== 'undefined' && value.toLowerCase().indexOf( searchString.toLowerCase() ) > -1 );
			},
		);
	};

    Toolset.Gui.ListingViewModel.call(self, snippetModels, defaults, itemSearchFunction, pageController);

    // Overrides
    //
    //
    self.getRootNodeForKnockout = function() {
        // This includes the whole settings tab, we need it for the Add New button.
        return document.getElementById('toolset-code-snippets-main');
    };


    self.createItemViewModels = function(itemModels) {
        self.items(_.map(itemModels, function(itemModel) {
            return new Toolset.page.codeSnippets.Snippet(itemModel, self.itemActions, self, l10n, pageController);
        }));
    };



    // New methods
    //
    //

    /**
     * Perform an update action on one or more snippets.
     *
     * @param {string} ajaxAction Action name
     * @param {Toolset.page.codeSnippets.Snippet|[Toolset.page.codeSnippets.Snippet]} snippets One or more snippets
     * @param {function|null} [updateCallback] Function that accepts a snippet viewmodel and performs a change on it.
     *     It's applied to all snippets before sending them via AJAX.
     * @param {function|null} [finalizeCallback] Will be called after the AJAX call finishes.
     */
    self.updateSnippets = function(ajaxAction, snippets, updateCallback, finalizeCallback) {
        if(!_.isArray(snippets)){
            snippets = [snippets];
        }

        self.beginAction(snippets);

        if(_.isFunction(updateCallback)) {
            _.each(snippets, updateCallback);
        }

        var finalize = function() {
            self.finishAction(snippets);
            if(_.isFunction(finalizeCallback)) {
                finalizeCallback();
            }
        };

        // Process the response from the AJAX call and update snippets accordingly (also remove viewmodels
        // for snippets that have been deleted on the server).
        var handleUpdatedSnippets = function(responseData) {
            if(_.has(responseData, 'updated_snippets')) {
                var updatedSnippets = responseData['updated_snippets'];
                var deletedSnippets = responseData['deleted_snippets'];
                if(!_.isArray(updatedSnippets) || ! _.isArray(deletedSnippets)) {
                    handleFailure(response);
                    return;
                }

                var getSnippetViewmodel = function(updatedSnippetModel) {
                    var previousSlug = (_.has(updatedSnippetModel, 'previousSlug') ? updatedSnippetModel['previousSlug'] : updatedSnippetModel['slug']);
                    var snippetViewModel = _.find(self.items(), function(snippet) {
                        return snippet.slug() === previousSlug;
                    });

                    return snippetViewModel;
                };

                _.each(updatedSnippets, function(updatedSnippetModel) {
                    var snippetViewModel = getSnippetViewmodel(updatedSnippetModel);

                    // todo check that it exists
                    snippetViewModel.updateViewModelFromModel(updatedSnippetModel, snippetViewModel.getModel());
                    snippetViewModel.changedProperties([]);
                });

                _.each(deletedSnippets, function(deletedSnippetModel) {
                    var snippetViewModel = getSnippetViewmodel(deletedSnippetModel);
                    self.items.remove(snippetViewModel);
                });
            }
        };

        var handleFailure = function(response) {
            handleUpdatedSnippets(response.data || {});
            self.displayMessagesFromAjax(response.data || {}, 'error', l10n['updateResults']['error']);
            finalize();
        };

        var handleSuccess = function(response, responseData) {
            handleUpdatedSnippets(responseData);
            self.displayMessagesFromAjax(responseData, 'info', l10n['updateResults']['success']);

            finalize();
        };

        pageController.ajax.doAjax(ajaxAction, _.map(snippets, function(snippet) { return snippet.getModel(); } ), handleSuccess, handleFailure);
    };


    self.activateSnippets = function(snippets) {
        self.updateSnippets(pageController.ajax.action.update, snippets, function(snippet) {
            snippet.isActive(true);
        });
    };

    self.deactivateSnippets = function(snippets) {
        self.updateSnippets(pageController.ajax.action.update, snippets, function(snippet) {
            snippet.isActive(false);
        })
    };


    self.deleteSnippets = function(snippets) {
        self.updateSnippets(pageController.ajax.action.delete, snippets);
    };


    /**
     * Create a new snippet on the server.
     */
    self.onAddNew = function() {
        var dialog = new Toolset.page.codeSnippets.AddNewDialog(pageController, function(snippetSlug) {

            self.beginAction();

			// We only need to pass the slug, basically.
			var snippet = new Toolset.page.codeSnippets.Snippet({
				slug: snippetSlug,
				isActive: false,
				isEditable: false,
				displayName: '',
				description: '',
				code: '',
				filePath: '',
				runMode: '',
				runContexts: [],
				lastError: '',
				hasSecurityCheck: true,
			}, self.itemActions, self, l10n, pageController);

            var handleFailure = function(response) {
                self.displayMessagesFromAjax(response.data || {}, 'error', l10n['updateResults']['error']);
                self.finishAction();
            };

            var handleSuccess = function(response, responseData) {
                var updatedSnippets = responseData['updated_snippets'];
                if(!_.isArray(updatedSnippets) || updatedSnippets.length !== 1) {
                    handleFailure(response);
                    return;
                }
                var updatedSnippetModel = _.first(updatedSnippets);
                snippet.updateViewModelFromModel(updatedSnippetModel, snippet.getModel());
                self.items.push(snippet);

                self.displayMessagesFromAjax(responseData, 'info', l10n['updateResults']['success']);
                self.finishAction();
            };

            pageController.ajax.doAjax(
                pageController.ajax.action.create,
                snippet.getModel(),
                handleSuccess,
                handleFailure
            );
        }, l10n);
        dialog.display();
    };


    /**
     * Offer bulk actions for the listing.
     *
     * Note: This is an override but it needs to be placed below handler callbacks in this file.
     *
     * @since 3.0.5
     */
    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: l10n['bulkAction']['select']
        },
        {
            value: 'activate',
            displayName: l10n['bulkAction']['activate'],
            handler: self.activateSnippets
        },
        {
            value: 'deactivate',
            displayName: l10n['bulkAction']['deactivate'],
            handler: self.deactivateSnippets
        }
    ]);


    // Help sections on the left side of the listing.
    var sections = {
        disabling: ko.observable(false),
        testMode: ko.observable(false),
        troubleshooting: ko.observable(false)
    };

    self.toggleSection = function(sectionName) {
        var currentValue = sections[sectionName]();
        sections[sectionName](!currentValue);
    };


    self.isSectionVisible = function(sectionName) {
        var isVisible = sections[sectionName]();
        return isVisible;
    };

    self.init();
};
