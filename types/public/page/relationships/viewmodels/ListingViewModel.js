/**
 * Represents the listing of relationships.
 *
 * @param {{[object]}} relationshipModels Relationship model data coming from PHP.
 * @constructor
 */
Types.page.relationships.viewmodels.ListingViewModel = function(relationshipModels) {

    var self = this;


    /**
     * Hooks saved
     *
     * EventManager doesn't have a method to check if a hook is added.
     * In case of reseting the wizard model, the hooks will add again and again
     * and repeat each action several times.
     */
    self.hooksAdded = [];


    // Apply the generic listing viewmodel.
    Toolset.Gui.ListingViewModel.call(
        self,
        relationshipModels,
        {
            sortBy: 'displayName',
            itemsPerPage: Types.page.relationships.itemsPerPage
        },
        function (relationship, searchString) {
            return _.some([relationship.slug(), relationship.displayName()], function (value) {
                return (typeof(value) !== 'undefined' && value.toLowerCase().indexOf(searchString.toLowerCase()) > -1);
            });
        }
    );


    /**
     * Fill field definitions with data from PHP.
     *
     * Result will be stored in the self.items() observable array.
     *
     * @param itemModels
     * @since m2m
     */
    self.createItemViewModels = function(itemModels) {
        self.items(_.map(itemModels, function(itemModel) {
            return new Types.page.relationships.viewmodels.RelationshipViewModel(itemModel, self.itemActions, self);
        }));
    };


    /**
     * The relationship definition that is being edited at the moment, or null.
     *
     * @since m2m
     */
    self.currentRelationshipDefinition = ko.observable(null);


    /**
     * Returns true if any of the relationship definitions indicates that it has unsaved changes.
     *
     * @since m2m
     */
    self.hasUnsavedItems = ko.pureComputed(function() {
        // Note that we can't use _.some with a predicate that checks for hasChanged() directly, because _.some
        // stops on first match. But we actually need to call every hasChanged() observable so that knockout
        // knows this result depends on it.
        return _.some(_.map(self.items(), function(relationshipDefitinion) {
            return relationshipDefitinion.hasChanged();
        }));
    });


    /**
     * Shows Add New Relationship Wizard
     *
     * @since m2m
     */
    self.onAddNew = function() {
        Types.page.relationships.main.showAddNewRelationshipWizard();
    };


    /**
     * Resets the wizardModel
     */
    self.resetWizardModel = function() {
      self.wizardModel(new Types.page.relationships.viewmodels.WizardViewModel(self));
    };


    /**
     * Closes the wizard dialog
     */
    self.onExitWizard = function() {
      Toolset.hooks.doAction( 'types-relationships-wizard-close' );
    };


    /**
     * Some actions have to be run after template rendering
     */
    self.initWizard = function() {
      Toolset.hooks.doAction( 'types-relationships-wizard-init' );
    };


    var previousScreenState = 'listing';


    /**
     * Handles history navigation
     *
     * @since m2m
     */
    window.addEventListener('popstate', function(event) {
        var state = event.state? event.state : {screen: 'listing', previous: ''};

        if ('addNew' === previousScreenState) {
            Toolset.hooks.doAction( 'types-relationships-wizard-exit' );
        }
        switch(state.screen) {
            case 'addNew':
                Types.page.relationships.main.showAddNewRelationshipWizard();
                break;
            default:
                break;
        }
        previousScreenState = state.screen;
    });


    self.wizardModel = ko.observable(new Types.page.relationships.viewmodels.WizardViewModel(self));


    /**
     * Delete a relationship via AJAX, update the collection of viewmodels and show the result.
     *
     * @since m2m
     * @param relationshipDefinition
     */
    self.deleteRelationship = function(relationshipDefinition) {
        self.beginAction([relationshipDefinition]);

        var finalize = function() {
            self.finishAction([relationshipDefinition]);
        };

        var ajax = Types.page.relationships.main.ajax;

        var handleSuccess = function(response, responseData) {

            var hasNoUpdatedDefinitions = (
                _.has(responseData, 'updated_definitions')
                && _.isArray(responseData['updated_definitions'] )
                && 0 !== responseData['updated_definitions'].length
            );

            var hasOneDeletedDefinition = (
                _.has(responseData, 'deleted_definitions')
                && _.isArray(responseData['deleted_definitions'] )
                && 1 !== responseData['deleted_definitions'].length
            );

            // This is what we always expect from the "delete" AJAX call. A bit cumbersome
            // but it leaves open door for easy bulk action implementation.
            if(!hasNoUpdatedDefinitions || !hasOneDeletedDefinition) {
                handleFailure(response)
            }

            var removedDefinitionSlug = _.first(responseData['deleted_definitions']),
                removedDefinition = _.find(self.items(), function(relationshipDefinition) {
                    return (relationshipDefinition.slug() === removedDefinitionSlug);
                });

            self.items.remove(removedDefinition);

            self.displayMessagesFromAjax(responseData, 'info', 'Relationship has been deleted.' );

            finalize();
        };

        var handleFailure = function(response) {
            self.displayMessagesFromAjax(response.data || {}, 'error', 'There was an error when deleting the relationship.');
            console.log(response);
            finalize();
            // todo force page reload before saving anything?
        };

        ajax.doAjax(ajax.action.delete, relationshipDefinition.getModel(), handleSuccess, handleFailure);
    };


    /**
     * Offer bulk actions for the listing.
     *
     * @since 3.0.5
     */
    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: Types.page.relationships.strings['bulkAction']['select']
        },
        {
            value: 'merge',
            displayName: Types.page.relationships.strings['bulkAction']['merge'],
            handler: function(relationships) {
                var dialog = new Types.page.relationships.viewmodels.dialogs.MergeRelationships(relationships);
                dialog.display();
            }
        }
    ]);


    /**
     * Determine whether a particular bulk action is ready to be executed.
     *
     * @since 3.0.5
     */
    self.isBulkActionAllowed = ko.pureComputed(function () {
        switch(self.selectedBulkAction()) {
            case '-1':
                return false;
            case 'merge':
                return (self.selectedItems().length === 2);
            default:
                return (self.selectedItems().length > 0);
        }
    });

    var parentAllowSelectAllVisibleItems = self.allowSelectAllVisibleItems;
        
    self.allowSelectAllVisibleItems = ko.pureComputed(function() {
        if(self.selectedBulkAction() === 'merge') {
            return false;
        }

        return parentAllowSelectAllVisibleItems();
    });


    self.init();

};
