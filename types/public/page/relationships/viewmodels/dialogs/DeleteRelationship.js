
Types.page.relationships.viewmodels.dialogs = Types.page.relationships.viewmodels.dialogs || {};

/**
 * A dialog that asks for confirmation before deleting a relationship.
 *
 * It also allows the user to deactivate the relationship instead.
 * Possible results are 'delete' or 'deactivate'. Anything else means the dialog was canceled.
 *
 * @param {Types.page.relationships.viewmodels.RelationshipViewModel} relationshipDefinition
 * @param {function} closeCallback
 * @returns {Types.page.relationships.viewmodels.dialogs.DeleteRelationship}
 * @constructor
 * @since m2m
 */
Types.page.relationships.viewmodels.dialogs.DeleteRelationship = function(relationshipDefinition, closeCallback) {

    var self = this;

    var strings = Types.page.relationships.strings['deleteRelationshipDialog'];

    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };

        var dialogButtons = [];
        if (relationshipDefinition.isActive()) {
            dialogButtons.push({
                text: strings['deactivate'],
                click: function() {
                    cleanup(dialog);
                    closeCallback('deactivate');
                },
                'class': 'button button-secondary toolset-deactivate-relationship'
            });
        }
        dialogButtons.push({
            text: strings['delete'],
            click: function() {
                cleanup(dialog);
                closeCallback('delete');
            },
            'class': 'button toolset-danger-button'
        });
        dialogButtons.push({
            text: strings['cancel'],
            click: function() {
                cleanup(dialog);
                closeCallback('cancel');
            },
            'class': 'button wpcf-ui-dialog-cancel'
        });

        var dialog = Types.page.relationships.main.createDialog(
            'types-confirm-relationship-deleting',
            strings.title + ' "' + relationshipDefinition.displayName() + '"',
            {},
            dialogButtons
        );

        ko.applyBindings(self, dialog.el);
    };


    self.relationshipDisplayName = ko.pureComputed(function() { return relationshipDefinition.displayName(); });

    return self;

};
