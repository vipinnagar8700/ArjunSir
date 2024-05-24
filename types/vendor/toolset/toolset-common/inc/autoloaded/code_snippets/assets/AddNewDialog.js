/**
 * Dialog for adding a new code snippet for the "Custom Code" tab on the Toolset Settings page.
 *
 * @param pageController
 * @param addNewSnippetCallback
 * @param l10n
 * @constructor
 *
 * @since 3.0.8
 */
Toolset.page.codeSnippets.AddNewDialog = function (pageController, addNewSnippetCallback, l10n) {

    var self = this;

    Toolset.Gui.Mixins.CreateDialog.call(self);

    var dialogL10n = l10n['dialogs']['addNewDialog'];


    self.snippetSlug = ko.observable('');

    self.snippetSlugCandidate = ko.observable('');

    var isSlugValueValid = function (value) {
        if (value.length === 0) {
            return false;
        }

        return /^[a-z0-9_.-]+$/.test(value);
    };

    self.isSlugValid = ko.pureComputed(function() {
        return isSlugValueValid(self.snippetSlugCandidate());
    });

    self.snippetSlugCandidate.subscribe(function (newValue) {
        var $slugInput = document.getElementById('toolset_code_snippets__new_snippet_slug');
        if (isSlugValueValid(newValue)) {
            self.snippetSlug(newValue);
            $slugInput.setCustomValidity('');
        } else {
            $slugInput.setCustomValidity('Invalid input')
        }
    });


    self.cleanup = function () {
        self.dialog.$el.ddldialog('close');
        ko.cleanNode(self.dialog.el);
    };


    self.display = function () {
        self.dialog = self.createDialog(
            dialogL10n['handle'], dialogL10n['title'], {},
            [
                {
                    text: dialogL10n['cancel'],
                    'class': 'button wpcf-ui-dialog-cancel',
                    click: self.cleanup
                },
                {
                    text: dialogL10n['create'],
                    'class': 'button button-primary toolset_code_snippets__create_button',
                    click: self.createSnippet
                }
            ],
            {'maxHeight': window.innerHeight * .8}
        );

        ko.applyBindings(self, self.dialog.el);

        var $createButton = jQuery('.toolset_code_snippets__create_button').first();
        $createButton.attr('data-bind', 'disablePrimary: ! isSlugValid()');
        var buttonForBinding = $createButton.get(0);
        ko.applyBindings(self, buttonForBinding);

        self.onDialogClose = function() {
            ko.cleanNode(buttonForBinding);
        }
    };


    self.createSnippet = function () {
        addNewSnippetCallback(self.snippetSlug());
        self.cleanup();
    };

};