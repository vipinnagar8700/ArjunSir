/**
 * Viewmodel of the dialog adding new Custom Field: posts, users, terms
 *
 * Requires the 'types-add-new-custom-field-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change. If yes, the second argument is an array of
 *     newly assigned group slugs.
 *
 * @returns {Types.page.fieldControl.viewmodels.AddNewDialogViewModel}
 * @since 2.3
 */
Types.page.customFields.viewmodels.AddNewDialogViewModel = function(closeCallback) {

    var self = this;

		/**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };


        var dialog = Types.page.customFields.main.createDialog(
            'types-add-new-custom-field-dialog',
            Types.page.customFields.strings.misc.addNewTitle,
            {}
        );

        ko.applyBindings(self, dialog.el);
    };

    return self;
};
