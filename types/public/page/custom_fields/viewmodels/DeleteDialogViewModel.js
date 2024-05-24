/**
 * Viewmodel of the dialog for deleting Custom Fields.
 *
 * Requires the 'types-delete-custom-field-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change.
 * @since 2.3
 */
Types.page.customFields.viewmodels.DeleteDialogViewModel = function(subject, closeCallback) {

    var self = this;

    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };

        var isSingleFieldAction = !_.isArray(subject);

        var title = (
            isSingleFieldAction
                ? Types.page.customFields.strings.misc['deleteFieldGroup'] + ' ' + jQuery(subject.displayName()).text()
                : Types.page.customFields.strings.misc['deleteFieldGroups']
        );

        var dialog = Types.page.customFields.main.createDialog(
            'types-delete-custom-field-dialog',
            title,
            {},
            [
                {
                    text: Types.page.customFields.strings.button['delete'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(true);
                    },
                    'class': 'button toolset-danger-button'
                },
                {
                    text: Types.page.customFields.strings.button['cancel'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(false);
                    },
                    'class': 'button wpcf-ui-dialog-cancel'
                }
            ]
        );

        ko.applyBindings(self, dialog.el);
    };


    return self;
};
