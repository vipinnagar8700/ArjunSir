Toolset = Toolset || {};

Toolset.head = Toolset.head || head;

Toolset.page = Toolset.page || {};
Toolset.page.codeSnippets = Toolset.page.codeSnippets  || {};

/**
 * Main controller for the "Custom Code" tab on the Toolset Settings page.
 *
 * Basically just overrides of the abstract ListingPage controller's methods.
 *
 * @constructor
 * @since 3.0.8
 */
Toolset.page.codeSnippets.Controller = function() {

    var self = this;

    // Extend the generic listing page controller.
    Toolset.Gui.ListingPage.call(self);

    self.jsPath = '';
    self.initialSnippetModels = [];
    self.initialItemsPerPage = 0;
    self.l10n = {};


    self.beforeInit = function() {
        var modelData = self.getModelData('#toolset_codesnippets_model_data');

        self.l10n = modelData['strings'];

        self.jsPath = modelData['jsIncludePath'];
        self.toolsetCommonVersion = modelData['toolsetCommonVersion'];
        self.initialSnippetModels = modelData['snippets'];
        self.initialItemsPerPage = modelData['itemsPerPage'];
    };


    self.loadDependencies = function(nextStep) {
        var version =  self.toolsetCommonVersion;

        Toolset.head.load(
            self.jsPath + '/Snippet.js?ver=' + version,
            self.jsPath + '/SnippetListing.js?ver=' + version,
            self.jsPath + '/AddNewDialog.js?ver=' + version,
            self.jsPath + '/DeleteDialog.js?ver=' + version,
            nextStep
        );
    };


    self.getPageContent = function() {
        return jQuery('#toolset_codesnippets_listing');
    };


    self.getMainViewModel = function() {
        return new Toolset.page.codeSnippets.SnippetListing(
            self.initialSnippetModels,
            { sortBy: 'displayName', itemsPerPage: self.initialItemsPerPage },
            self.l10n,
            self
        );
    };


    /**
     * Handles all the interaction with the server.
     */
    self.ajax = {

        // Actions recognized by the server.
        action: {
            update: 'update',
            create: 'create',
            delete: 'delete'
        },


        /**
         * Perform an AJAX call.
         *
         * @param {string} actionName One of the names defined in self.ajax.action.
         * @param {Snippet|[Snippet]} snippets One or more snippets
         *     to perform the action on.
         * @param {function} successCallback Callback that gets the AJAX response if the call succeeds.
         * @param {function} failCallback Optional callback for the case of failure. If not provided,
         *     successCallback will be used.
         *
         * @since m2m
         */
        doAjax: function(actionName, snippets, successCallback, failCallback) {

            if (!_.isArray(snippets)) {
                snippets = [snippets];
            }

            var ajaxData = {
                action: 'toolset_code_snippets_action',
                action_name: actionName,
                wpnonce: self.modelData.nonce,
                snippets: snippets
            };

            if (typeof(failCallback) === 'undefined') {
                failCallback = successCallback;
            }

            jQuery.post({
                async: true,
                url: ajaxurl,
                data: ajaxData,

                success: function (originalResponse) {
                    var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                    self.debug('AJAX response', ajaxData, originalResponse);

                    if (response.success) {
                        successCallback(response, response.data || {});
                    } else {
                        failCallback(response, response.data || {});
                    }
                },

                error: function (ajaxContext) {
                    console.log('Error:', ajaxContext.responseText);
                    failCallback({success: false, data: {}}, {});
                }
            });
        }
    };

};


// Make everything happen.
Toolset.page.codeSnippets.main = new Toolset.page.codeSnippets.Controller();
Toolset.head.ready(Toolset.page.codeSnippets.main.init);
