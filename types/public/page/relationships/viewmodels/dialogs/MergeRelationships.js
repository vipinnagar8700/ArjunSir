/**
 * Dialog for merging two one-to-many relationship into a many-to-many one, in a batch process.
 *
 * See the BatchProcessDialog mixin and Types_Ajax_Handler_Merge_Relationships for further details.
 *
 * @param {Types.page.relationships.viewmodels.RelationshipViewModel[]} relationships
 * @constructor
 */
Types.page.relationships.viewmodels.dialogs.MergeRelationships = function(relationships) {

    var self = this;

    var strings = Types.page.relationships.strings['mergeRelationshipsDialog'];


    Toolset.Gui.Mixins.BatchProcessDialog.call(self);


    self.relationships = ko.observableArray(relationships);


    /**
     * Retrieve available post type information (slug and labels)
     *
     * @param {string} postTypeSlug
     * @returns {[{slug: string, plural: string, singular: string}]}
     */
    var getPostTypeInfo = function(postTypeSlug) {
        return Types.page.relationships.main.modelData.postTypes[postTypeSlug];
    };


    /**
     * Build a default name (or label) of the new relationship.
     *
     * @param {string} label slug|plural|singular
     * @returns {string}
     */
    var getNewDefaultRelationshipName = function(label) {
        var separator = ('slug' === label ? '-' : ' ');
        return getPostTypeInfo(relationships[0].display.postType.parent())[label]
            + separator
            + getPostTypeInfo(relationships[1].display.postType.parent())[label];
    };


    self.newSlug = ko.observable(getNewDefaultRelationshipName('slug'));
    self.newDisplayNamePlural = ko.observable(getNewDefaultRelationshipName('plural'));
    self.newDisplayNameSingular = ko.observable(getNewDefaultRelationshipName('singular'));


    /**
     * User acknowledges that we can continue with the merging.
     *
     * @type {*|observable}
     */
    self.isConfirmedByUser = ko.observable(false);


    /**
     * Show the dialog.
     */
    self.display = function() {

        self.dialog = self.createDialog(
            'types-merge-relationships',
            strings['title'],
            {},
            [
                {
                    text: strings['cancel'],
                    'class': 'button wpcf-ui-dialog-cancel types-merge-relationship__cancel-button',
                    click: self.cleanup
                },
                {
                    text: strings['merge'],
                    'class': 'button toolset-danger-button types-merge-relationship__merge-button',
                    click: self.startProcess
                },
                {
                    text: strings['close'],
                    'class': 'button types-merge-relationship__close-button',
                    click: function() {
                        window.location.reload(true);
                        self.cleanup();
                    }
                }
            ],
            {'maxHeight': window.innerHeight * .8 }
        );

        self.addBindingToDialogButton(
            self.dialog.el, 'types-merge-relationship__merge-button',
            'disablePrimary: ! isConfirmedByUser(), '
            + 'visible: ( 1 === currentDialogStepNumber() )'
        );

        self.addBindingToDialogButton(
            self.dialog.el, 'types-merge-relationship__cancel-button',
            'visible: isCancelActionAvailable'
        );

        self.addBindingToDialogButton(
            self.dialog.el, 'types-merge-relationship__close-button',
            'visible: isCompleted'
        );

        ko.applyBindings(self, self.dialog.el);
    };


    // Overrides for the BatchProcessDialog.js mixin.
    //
    //
    self.getAjaxActionName = function() {
        return strings['actionName'];
    };

    self.getNonce = function() {
        return strings['nonce'];
    };


    /**
     * Options that will be passed to the first AJAX call (and then passed around)
     *
     * @returns {{relationshipLeft: string, relationshipRight: string, newRelationship: {slug: string, plural: string, singular: string}}}
     */
    self.getAjaxOptions = function() {
        return {
            relationshipLeft: self.relationships()[0].slug(),
            relationshipRight: self.relationships()[1].slug(),
            newRelationship: {
                slug: self.newSlug(),
                plural: self.newDisplayNamePlural(),
                singular: self.newDisplayNameSingular()
            }
        }
    };


    self.onProcessStart = function() {
        // Prevent the process from being started again.
        ko.removeNode(jQuery('.types-merge-relationship__merge-button'));
    };


    self.getl10n = function() {
        return strings;
    };


    self.getProcessPhases = function() {
        return _.map(strings['phaseLabels'], function(label) {
            return { label: label };
        })
    };


    self.getMessageOnProcessEnd = function(state) {
        return (_.has(strings['resultMessage'], state) ? strings['resultMessage'][state] : state );
    };

};