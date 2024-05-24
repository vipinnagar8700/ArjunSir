/**
 * Confirmation dialog for deleting a code snippet on the "Custom Code" tab on the Toolset Settings page.
 *
 * @param pageController
 * @param snippet
 * @param deleteSnippetCallback
 * @param l10n
 * @constructor
 * @since 3.0.8
 */
Toolset.page.codeSnippets.DeleteDialog = function(pageController, snippet, deleteSnippetCallback, l10n) {

    var self = this;

    var dialogL10n = l10n['dialogs']['deleteDialog'];

    self.snippet = snippet;


    self.cleanup = function () {
        self.dialog.$el.ddldialog('close');
        ko.cleanNode(self.dialog.el);
    };


    self.display = function () {
        self.dialog = pageController.createDialog(
            dialogL10n['handle'], dialogL10n['title'], {},
            [
                {
                    text: dialogL10n['cancel'],
                    'class': 'button wpcf-ui-dialog-cancel',
                    click: self.cleanup
                },
                {
                    text: dialogL10n['delete'],
                    'class': 'button toolset-danger-button',
                    click: self.deleteSnippet
                }
            ],
            {'maxHeight': window.innerHeight * .8}
        );

        ko.applyBindings(self, self.dialog.el);
    };


    self.deleteSnippet = function () {
        deleteSnippetCallback(snippet);
        self.cleanup();
    };
};