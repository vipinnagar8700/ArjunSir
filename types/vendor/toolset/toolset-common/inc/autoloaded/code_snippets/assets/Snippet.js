/**
 * Viewmodel of a snippet for the "Custom Code" tab on the Toolset Settings page.
 *
 * @param modelSource
 * @param fieldActions
 * @param listingViewModel
 * @param l10n
 * @param pageController
 * @constructor
 *
 * @since 3.0.8
 */
Toolset.page.codeSnippets.Snippet = function(modelSource, fieldActions, listingViewModel, l10n, pageController) {

    var self = this;

    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call(self, modelSource, fieldActions);

    Toolset.Gui.Mixins.AdvancedItemViewModel.call(self);
    Toolset.Gui.Mixins.CodeMirror.call(self);

    var model = modelSource;

    // Data relevant when passing them back to server, not actual model properties.
    model.originalSlug = model.slug;
    model.runNow = false;

    self.slug = self.createModelProperty(ko.observable, model, 'slug');
    self.isActive = self.createModelProperty(ko.observable, model, 'isActive');
    self.isCodeEditable = self.createModelProperty(ko.observable, model, 'isEditable');
    self.displayName = self.createModelProperty(ko.observable, model, 'displayName');
    self.description = self.createModelProperty(ko.observable, model, 'description');
    self.code = self.createModelProperty(ko.observable, model, 'code');
    self.filePath = self.createModelProperty(ko.observable, model, 'filePath');
    self.runMode = self.createModelProperty(ko.observable, model, 'runMode');
    self.runContexts = self.createModelProperty(ko.observableArray, model, 'runContexts');
    self.lastError = self.createModelProperty(ko.observable, model, 'lastError');
    self.hasSecurityCheck = self.createModelProperty(ko.observable, model, 'hasSecurityCheck');


    self.getModel = function() { return model; };


    self.isEditing = ko.observable(false);


    var getEditorTextareaId = function() {
        return 'toolset_code_snippets__editor-' + self.slug();
    };


    self.onEdit = function() {
        self.isEditing(true);
        if(self.isCodeEditable()) {
            self.codeMirrorInitialize(getEditorTextareaId(), 'application/x-httpd-php', function(value) {
                self.code(value);
            });
            self.enableCodeMirrorAutoresize();
            self.refreshCodeMirror(true);
        }
    };


    self.onDisplayNameClick = function() {
        self.onEdit();
    };


    self.onDelete = function() {
        var dialog = new Toolset.page.codeSnippets.DeleteDialog(pageController, self, function() {
            listingViewModel.deleteSnippets(self);
        }, l10n);
        dialog.display();
    };


    self.onActivate = function() {
        listingViewModel.activateSnippets(self);
    };


    self.onDeactivate = function() {
        listingViewModel.deactivateSnippets(self);
    };


    self.onCancelEdit = function() {
        self.isEditing(false);
        self.resetChanges();
    };


    self.onSave = function() {
        listingViewModel.updateSnippets(pageController.ajax.action.update, self);
    };


    self.onSaveAndClose = function() {
        listingViewModel.updateSnippets(pageController.ajax.action.update, self, function() {
            self.isEditing(false);
        });
    };


    self.onSetActive = function() {
        self.isActive(true);
    };


    self.onTryAgain = function() {
        model.runNow = true;
        listingViewModel.updateSnippets(pageController.ajax.action.update, self, null, function() {
            model.runNow = false;
        });
    };


    self.onRunOnce = function() {
        self.onTryAgain();
    };


    self.display = {
        activityStatus: ko.pureComputed(function() {
            return self.isActive() ? l10n['active'] : l10n['inactive'];
        }),

        runMode: ko.pureComputed(function() {
            return l10n['runMode'][ self.runMode() ];
        }),

        runContexts: ko.pureComputed(function() {
            if(self.runContexts().length === 0) {
                return l10n['runContexts']['nowhere'];
            }

            if(self.runContexts().length === 3) {
                return l10n['runContexts']['everywhere'];
            }
            return _.map(self.runContexts(), function(contextName) {
                return l10n['runContexts'][ contextName ];
            }).join(', ');
        }),

        hasLastError: ko.pureComputed(function() {
            return self.lastError().length > 0;
        }),

        hasWarning: ko.pureComputed(function() {
            return ( ! self.display.hasLastError() && ! self.hasSecurityCheck() );
        })
    };

};