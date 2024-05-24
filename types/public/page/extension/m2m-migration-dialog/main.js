var Types = Types || {};
Types.page = Types.page || {};
Types.page.extension = Types.page.extension || {};

/**
 * m2m activation/migration wizard wrapper.
 *
 * Do not use directly, instead see the instructions in Types_Page_Extension_M2m_Migration_Dialog.
 *
 * @param $ jQuery
 * @returns {Types.page.extension.M2mMigrationDialog}
 * @constructor
 * @since 2.3-b4
 */
Types.page.extension.M2mMigrationDialog = function($) {

    var self = this;

    const MIGRATION_PHASE_COUNT = 4;

    // Apply mixins.
    Toolset.Gui.Mixins.CreateDialog.call(self);
    Toolset.Gui.Mixins.KnockoutExtensions.call(self);


    /** @var {} types_page_extension_m2m_migration_dialog */
    self.l10n = types_page_extension_m2m_migration_dialog;


    /**
     * Initialize the controlller.
     */
    self.init = function() {
        self.initKnockout();

        // Discourage the user from leaving the page while an action is in progress.
        WPV_Toolset.Utils.setConfirmUnload(
            function () {
                return self.vm.isInProgress();
            },
            null,
            // Just a fallback, modern browsers don't show any message.
            'There is an operation in progress. Please do not leave the page until it finishes.'
        );
    };


    /**
     * Display the dialog.
     */
    self.display = function() {

        var dialog = self.createDialog(
            'types-m2m-activation-dialog-confirmation',
            self.l10n.confirmationDialog.title,
            {},
            [
                {
                    text: self.l10n.confirmationDialog.activateButton,
                    click: function () {
                    },
                    'class': 'toolset-button-in-dialog button button-m2m-start'
                },
                {
                    text: self.l10n.confirmationDialog.finishButton,
                    click: function () {
                    },
                    'class': 'toolset-button-in-dialog button button-primary button-m2m-finish'
                },
                {
                    text: self.l10n.confirmationDialog.cancelButton,
                    click: function () {
                        jQuery(dialog.$el).ddldialog('close');
                    },
                    'class': 'toolset-button-in-dialog wpcf-ui-dialog-cancel button-m2m-cancel'
                }
            ],
            {
                dialogClass: 'types-m2m-activation-dialog'
            }
        );

        ko.applyBindings(self.vm, dialog.el);

        // Dialog buttons need to be handled separately because
        // they're not part of the dialog content template.

        var $startButton = $('.button-m2m-start').first();
        $startButton.attr(
            'data-bind',
            'click: startMigration, '
            + 'disablePrimary: ! step.confirmation.buttonStatus(), '
            + 'visible: ( 1 === step.current.number() )'
        );
        ko.applyBindings(self.vm, $startButton.get(0));

        var $finishButton = $('.button-m2m-finish').first();
        $finishButton.attr(
            'data-bind',
            'click: step.migration.finishButton.onClick, '
            + 'visible: step.migration.finishButton.isVisible, '
            + 'disablePrimary: ! isCompleted() || isInProgress(), '
            + 'text: step.migration.finishButton.label'
        );
        ko.applyBindings(self.vm, $finishButton.get(0));

        var $cancelButton = $('.button-m2m-cancel').first();
        $cancelButton.attr(
            'data-bind',
            'visible: isCancelActionAvailable'
        );
        ko.applyBindings(self.vm, $cancelButton.get(0));

        dialog.$el.on('ddldialogclose', function () {
            // Putting this on the Close button action is not enough, there are other ways to close the dialog.
            ko.cleanNode(dialog.el);
        });

        // Populate the table of relationships as soon as the dialog is open.
        self.vm.step.confirmation.preview.populate();

        // Scan for legacy custom code.
        self.vm.step.confirmation.legacyCodeScan.populate();

        // force some space to the top
        $( '.ui-dialog.types-m2m-activation-dialog' ).css( 'marginTop', '40px' );

    };


    // Viewmodel for the dialog, where most of the stuff happens:
    //
    //
    var vm = {};
    self.vm = vm;

    /** Is the action in progress at the moment? */
    vm.isInProgress = ko.observable(false);

    const DIALOG_STEP_CONFIRMATION = 1;
    const DIALOG_STEP_MIGRATION = 2;

    vm.isCancelActionAvailable = ko.observable(true);
    vm.conflictWithWpmlTranslationMode = ko.observable(false);
    vm.hasTranslatablePostTypesInRelationships = ko.observable(self.l10n.hasTranslatablePostTypesInRelationships || false);
    vm.canSetCptTranslationStatus = ko.pureComputed(function() {
        return !!self.l10n['confirmationDialog']['canSetCptTranslationStatus'];
    });
    vm.translationSettingsURL = self.l10n['confirmationDialog']['translationSettingsURL'];

    //noinspection JSUnusedGlobalSymbols
    vm.step = {

        /**
         * Information about the current step.
         */
        current: {
            number: ko.observable(DIALOG_STEP_CONFIRMATION),

            image: ko.pureComputed(function () {
                if (vm.step.current.number() === DIALOG_STEP_CONFIRMATION) {
                    return '<i class="fa fa-exclamation-triangle fa-5x" style="margin: 40px 0;"></i>';
                } else {
                    //noinspection JSUnresolvedVariable
                    return '<img src="' + self.l10n.confirmationDialog.inProgressImageUrl + '">';
                }
            })
        },

        /**
         * Things related to the first step (confirmation of the m2m activation).
         */
        confirmation: {

            /** True if the user has clicked on the "I have created a database backup recently" checkbox */
            userConfirmed: ko.observable(false),

            /** True if the user has clicked on the "Ok, change the translation mode for the affected post types" */
            userConfirmedChangingTranslationMode: ko.observable(false),

            /** 'create'|'skip'|'' */
            postWithoutDefaultTranslationHandling: ko.observable(''),

            /** An actual value set by the user. */
            copyWhenCreatingPostTranslation: ko.observable(false),

            useMaintenanceMode: ko.observable(true),

            /** Value of the checkbox as it will be displayed. */
            copyWhenCreatingPostTranslationCheckbox: ko.pureComputed({
                read: function() {
                    return (
                        'create' === vm.step.confirmation.postWithoutDefaultTranslationHandling()
                        && vm.step.confirmation.copyWhenCreatingPostTranslation()
                    );
                },
                write: function(newValue) {
                    vm.step.confirmation.copyWhenCreatingPostTranslation(newValue);
                }
            }),

            /**
             * Determine whether the action can be started.
             */
            canProceed: ko.pureComputed(function () {
                return (
                    vm.step.confirmation.userConfirmed()
                    && vm.step.current.number() === DIALOG_STEP_CONFIRMATION
                    && ( ! vm.hasTranslatablePostTypesInRelationships() || '' !== vm.step.confirmation.postWithoutDefaultTranslationHandling() )
                    && vm.step.confirmation.legacyCodeScan.userConfirmation()
                );
            }),

            /**
             * Compute the CSS selector for the Start button.
             */
            buttonClass: ko.pureComputed(function () {
                if (vm.step.confirmation.canProceed()) {
                    return 'button-primary';
                } else {
                    return 'button-secondary';
                }
            }),


            /**
             * Compute enabled/disabled status for the Start button
             */
            buttonStatus: ko.pureComputed(function () {
                return vm.step.confirmation.canProceed()
            }),


            /**
             * Manage the preview of legacy post relationships that will be migrated.
             */
            preview: {

                // Properties related to the table of relationships.
                //
                //

                populate: function() {
                    if (
                        vm.step.confirmation.preview.isRelationshipTablePopulated()
                        || vm.step.confirmation.preview.isRelationshipTableBeingPopulated()
                    ) {
                        return;
                    }

                    vm.step.confirmation.preview.isRelationshipTableBeingPopulated(true);

                    var failCallback = function() {
                        vm.step.confirmation.preview.isRelationshipTableBeingPopulated(false);
                    };

                    jQuery.post({
                        url: ajaxurl,
                        data: {
                            action: self.l10n.confirmationDialog.previewRelationships.actionName,
                            wpnonce: self.l10n.confirmationDialog.previewRelationships.nonce
                        },
                        success: function (originalResponse) {
                            var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                            if (response.success) {
                                vm.step.confirmation.preview.relationships(_.map(response.data['results'], function(relationshipInfo) {
                                    var parentHasWrongTranslationMode = _.has( relationshipInfo, 'parent_has_show_only_translated_mode' )
                                        ? relationshipInfo['parent_has_show_only_translated_mode']
                                        : 0;

                                    var childHasWrongTranslationMode = _.has( relationshipInfo, 'child_has_show_only_translated_mode' )
                                        ? relationshipInfo['child_has_show_only_translated_mode']
                                        : 0;

                                    if( parentHasWrongTranslationMode || childHasWrongTranslationMode ) {
                                        vm.conflictWithWpmlTranslationMode(true);
                                    }

                                    return {
                                        parentPostType: ( _.has(relationshipInfo, 'parent') ? relationshipInfo['parent'] : '?' ),
                                        childPostType: ( _.has(relationshipInfo, 'child') ? relationshipInfo['child'] : '?' ),
                                        relationshipSlug: ( _.has(relationshipInfo, 'slug') ? relationshipInfo['slug'] : '?' ),
                                        parentCanBeUsedInRelationship: ! parentHasWrongTranslationMode,
                                        childCanBeUsedInRelationship: ! childHasWrongTranslationMode
                                    };
                                }));

                                vm.step.confirmation.preview.isRelationshipTablePopulated(true);
                                vm.step.confirmation.preview.isRelationshipTableBeingPopulated(false);
                            } else {
                                failCallback();
                            }
                        },
                        error: function (ajaxContext) {
                            console.log('Error:', ajaxContext.responseText);
                            failCallback({success: false, data: {}});
                        }
                    });

                },

                relationships: ko.observableArray(),

                isRelationshipTablePopulated: ko.observable(false),

                isRelationshipTableBeingPopulated: ko.observable(false),

                // Properties related to the table of post associations
                //
                //

                associations: ko.observableArray(),

                nextAssociationLoadStep: 0,

                hasMoreAssociations: ko.observable(true),

                isAssociationTableVisible: ko.observable(false),

                toggleAssociationTable: function() {
                    var preview = vm.step.confirmation.preview;

                    preview.isAssociationTableVisible(!preview.isAssociationTableVisible());

                    if(0 === preview.nextAssociationLoadStep) {
                        preview.loadNextAssociations();
                    }
                },

                loadNextAssociations: function() {

                    var preview = vm.step.confirmation.preview;

                    if(preview.isLoadingAssociations()) {
                        return;
                    }

                    preview.isLoadingAssociations(true);

                    var failCallback = function() {
                        preview.isLoadingAssociations(false);
                    };

                    jQuery.post({
                        url: ajaxurl,
                        data: {
                            action: self.l10n.associationPreviewDialog.loadAssociationPreview.actionName,
                            wpnonce: self.l10n.associationPreviewDialog.loadAssociationPreview.nonce,
                            step: preview.nextAssociationLoadStep
                        },
                        success: function (originalResponse) {
                            var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                            if (response.success) {
                                preview.associations.push.apply(preview.associations, response.data['results']);
                                preview.isLoadingAssociations(false);
                                preview.nextAssociationLoadStep++;
                                if(0 === response.data['results'].length) {
                                    preview.hasMoreAssociations(false);
                                }
                            } else {
                                failCallback(response);
                            }
                        },
                        error: function (ajaxContext) {
                            console.log('Error:', ajaxContext.responseText);
                            failCallback({success: false, data: {}}, {});
                        }
                    })
                },

                isLoadingAssociations: ko.observable(false),

                associationsScrolled: function(data, event) {
                    var preview = vm.step.confirmation.preview;

                    if(preview.isLoadingAssociations() || ! preview.hasMoreAssociations()) {
                        return;
                    }

                    var element = event.target;
                    if (element.scrollTop > (element.scrollHeight - element.offsetHeight - 150)) {
                        preview.loadNextAssociations();
                    }
                }
            },

            /**
             * @since 2.3-b5
             */
            legacyCodeScan: {

                /**
                 * Perform the scan for the legacy code in batches.
                 *
                 * On error, we fail quietly - in any case, the user is warned that the results may not be
                 * complete.
                 */
                populate: function() {
                    var lcs = vm.step.confirmation.legacyCodeScan;

                    lcs.isBeingPopulated(true);

                    var failCallback = function() {
                        lcs.isBeingPopulated(false);
                    };

                    (function oneStep(scanStep) {
                        // noinspection JSUnresolvedVariable
                        jQuery.post({
                            url: ajaxurl,
                            data: {
                                action: self.l10n.confirmationDialog.scanLegacyCodeUsage.actionName,
                                wpnonce: self.l10n.confirmationDialog.scanLegacyCodeUsage.nonce,
                                scan_step: scanStep
                            },
                            success: function (originalResponse) {
                                var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                                if (response.success) {
                                    _.each(response.data['results'], function(result) {
                                        lcs.results.push(result);
                                    });

                                    var nextScanStep = response.data['next_step'];
                                    if(0 === nextScanStep) {
                                        // There is no need for the next batch, finish.
                                        lcs.isBeingPopulated(false);
                                    } else {
                                        // This will be called immediately after the stack unwinds,
                                        // preventing a stack overflow even for very high number of steps.
                                        setTimeout(_.partial(oneStep, nextScanStep), 0);
                                    }
                                } else {
                                    failCallback();
                                }
                            },
                            error: function (ajaxContext) {
                                console.log('Error:', ajaxContext.responseText);
                                failCallback({success: false, data: {}});
                            }
                        });
                    })(0 /* start with the first batch */);
                },

                isBeingPopulated: ko.observable(false),

                isPopulated: ko.observable(false),

                results: ko.observableArray(),

                hasResults: ko.pureComputed(function() {
                    return vm.step.confirmation.legacyCodeScan.results().length > 0;
                }),

                userConfirmation: ko.observable(false)
            }

        },


        /**
         * Things related to the second step (the actual migration).
         */
        migration: {

            /**
             * This is for showing progress in the second step on the dialog.
             */
            currentMigrationPhase: ko.observable(0),

            /**
             * The finish button changes it's appearance and behaviour depending
             * on the result of the migration.
             *
             * - success (or warning): "Finish" and reload the page
             * - first failure: Try again
             * - second failure: Go to support forum (open a link in a new tab)
             */
            finishButton: {

                isVisible: ko.pureComputed(function () {
                    return vm.step.current.number() === DIALOG_STEP_MIGRATION;
                }),


                /**
                 * @var ko.observable 'close'|'retry'|'goToSupport'
                 */
                action: ko.observable('close'),

                onClick: function () {
                    switch(vm.step.migration.finishButton.action()) {
                        case 'close':
                            window.location.replace(self.l10n.confirmationDialog.redirectAfterFinish);
                            break;
                        case 'retry':
                            vm.step.migration.message.text('');
                            vm.step.migration.phase.clear();
                            vm.step.migration.currentMigrationPhase(0);
                            vm.startMigration();
                            break;
                        case 'goToSupport':
                            window.open(self.l10n['confirmationDialog']['supportForumURL'], '_blank');
                            break;
                    }
                },

                label: ko.pureComputed(function() {
                    switch(vm.step.migration.finishButton.action()) {
                        case 'close':
                            return self.l10n['confirmationDialog']['finishButton'];
                        case 'retry':
                            return self.l10n['confirmationDialog']['retryButton'];
                        case 'goToSupport':
                            return self.l10n['confirmationDialog']['goToSupportButton'];
                    }
                }),

                /**
                 * Indicate a failure, move the button to the next state.
                 */
                onFailure: function() {
                    var action = vm.step.migration.finishButton.action;

                    switch(action()) {
                        case 'close':
                            action('retry');
                            break;
                        case 'retry':
                        default:
                            action('goToSupport');
                            break;
                    }
                },

                onSuccess: function() {
                    vm.step.migration.finishButton.action('close');
                }
            },

            updateMigrationProgress: function (data) {
                if (_.has(data, 'ajax_arguments')) {
                    var phase = data.ajax_arguments['phase'] || 0;
                    vm.step.migration.currentMigrationPhase(phase);
                }

                if (_.has(data, 'previous_phase')) {
                    var previousPhase = data['previous_phase'];
                    vm.step.migration.phase.update(
                        previousPhase,
                        _.has(data, 'status') ? data['status'] : 'error'
                    );
                }

                if (_.has(data, 'message')) {
                    vm.step.migration.log.append(data['message']);
                }
            },

            /**
             * Textual output from the AJAX calls to be displayed to the user.
             */
            log: {
                output: ko.observable(''),
                isEmpty: ko.pureComputed(function () {
                    return (0 === vm.step.migration.log.output().length);
                }),

                /** Append a new line to the output. */
                append: function (text) {
                    var currentContent = (vm.step.migration.log.isEmpty() ? '' : vm.step.migration.log.output() + "\n");
                    vm.step.migration.log.output(currentContent + '> ' + text);
                },

                isVisible: ko.observable(false),

                toggle: function () {
                    vm.step.migration.log.isVisible(!vm.step.migration.log.isVisible());
                }
            },

            /**
             * Manage the result message after the activation process finishes.
             *
             * @type {{show: show, text: *, isVisible, classes: *}}
             */
            message: {
                show: function (text, type) {
                    var classes = 'notice ';
                    switch (type) {
                        case 'warning':
                            classes += 'notice-warning';
                            break;
                        case 'error':
                            classes += 'notice-error';
                            break;
                        case 'success':
                            classes += 'notice-success';
                            break;
                    }
                    vm.step.migration.message.classes(classes);

                    vm.step.migration.message.text(text);
                },
                text: ko.observable(''),
                isVisible: ko.pureComputed(function () {
                    return (0 !== vm.step.migration.message.text().length);
                }),
                classes: ko.observable('')
            },


            //noinspection JSUnusedGlobalSymbols
            /**
             * Manage results of individual migration phases as well as the way the results will be
             * displayed.
             *
             * @type {{results, getMigrationPhaseResult: function, update: update, getDisplayInfo: getDisplayInfo}}
             */
            phase: {
                results: ko.observableArray(),
                getMigrationPhaseResult: function (phaseNumber) {
                    phaseNumber = Number(phaseNumber);
                    return _.find(vm.step.migration.phase.results(), function (result) {
                        return (_.has(result, 'phaseNumber') && phaseNumber === result['phaseNumber']);
                    });
                },
                update: function (phaseNumber, status) {
                    phaseNumber = Number(phaseNumber);
                    var migrationPhaseResult = vm.step.migration.phase.getMigrationPhaseResult(phaseNumber) || {phaseNumber: phaseNumber};

                    migrationPhaseResult.status = (
                        (_.has(migrationPhaseResult, 'status') && 'warning' === migrationPhaseResult['status'] )
                            ? 'warning'
                            : status
                    );

                    vm.step.migration.phase.results.remove(migrationPhaseResult);
                    vm.step.migration.phase.results.push(migrationPhaseResult);
                },
                clear: function() {
                    vm.step.migration.phase.results.removeAll();
                },

                getDisplayInfo: function (phaseNumber) {
                    var migrationPhaseResult = vm.step.migration.phase.getMigrationPhaseResult(phaseNumber);

                    var statusToDisplayInfo = {
                        error: {
                            icon: 'fa-times',
                            color: 'red'
                        },
                        warning: {
                            icon: 'fa-exclamation-triangle',
                            color: 'orange'
                        },
                        success: {
                            icon: 'fa-check',
                            color: '#289d40'
                        },
                        none: {
                            icon: '',
                            color: '',
                            visible: false
                        }
                    };

                    var status = (_.has(migrationPhaseResult, 'status') ? migrationPhaseResult['status'] : 'none');

                    return {
                        status: status,
                        icon: 'fa fa-lg ' + statusToDisplayInfo[status].icon,
                        color: statusToDisplayInfo[status].color,
                        visible: _.has(statusToDisplayInfo[status].visible) ? statusToDisplayInfo[status].visible : true
                    };
                }
            }
        }
    };


    /** True once the action has been completed. */
    vm.isCompleted = ko.observable(false);


    vm.startMigration = function () {

        vm.step.migration.log.append('Post relationships migration started.');

        var initialActionData = {
            action: self.l10n.actionName,
            wpnonce: self.l10n.nonce,
            options: {
                posts_without_default_translation: vm.step.confirmation.postWithoutDefaultTranslationHandling(),
                copy_content_when_creating_posts: vm.step.confirmation.copyWhenCreatingPostTranslation() ? 1 : 0,
                use_maintenance_mode: vm.step.confirmation.useMaintenanceMode() ? 1 : 0,
                adjust_translation_mode: vm.step.confirmation.userConfirmedChangingTranslationMode() ? 1 : 0
            }
        };

        var element = $('.button-m2m-start');

        ko.removeNode(element);

        vm.isCancelActionAvailable(false);

        vm.step.current.number(DIALOG_STEP_MIGRATION);

        /**
         * Perform the (possibly multi-step) action.
         *
         * Starts with an AJAX call as defined by the section VM. Depending on what the AJAX response is,
         * further calls may be performed until the "continue" flag is no longer true.
         *
         * @param {{action:string, wpnonce:string}} initialActionData Base for the "data" property for the AJAX call arguments.
         * @param {{ajax_arguments:object}} initialArgumentSource Object holding the "argument source" whose properties will be appended to
         *    the "data" mentioned above.
         * @since m2m
         */
        var doAction = function (initialActionData, initialArgumentSource) {

            var migration = vm.step.migration;

            // Indicate the activity
            vm.isInProgress(true);

            // Prepare helper functions
            var indicateActivityEnd = function () {

                vm.isCompleted(true);
                vm.isInProgress(false);

                var anyPhaseHas = function (what) {
                    return _.some(migration.phase.results(), function (result) {
                        var hasWarning = (_.has(result, 'status') && what === result['status']);
                        return hasWarning;
                    });
                };

                if (anyPhaseHas('error')) {
                    migration.log.isVisible(true);
                    migration.message.show(self.l10n.confirmationDialog.resultMessage.error, 'error');

                    // The behaviour of the finish button changes each time a failure occurrs.
                    migration.finishButton.onFailure();
                    vm.isCancelActionAvailable(true);

                } else if (anyPhaseHas('warning')) {
                    migration.log.isVisible(true);
                    migration.message.show(self.l10n.confirmationDialog.resultMessage.warning, 'warning');
                } else {
                    migration.finishButton.onSuccess();
                }
            };

            // From the "argument source" and initialActionData, build a new actionData object.
            var maybeAppendAjaxArguments = function (source) {
                var actionData = _.extend(initialActionData);
                if (_.has(source, 'ajax_arguments')) {
                    actionData = _.extendOwn(actionData, source['ajax_arguments']);
                }
                return actionData;
            };

            // Callback on failed AJAX call. Print the error and finish.
            //noinspection JSUnusedLocalSymbols
            var failCallback = function (response, responseData) {
                migration.phase.update(migration.currentMigrationPhase(), 'error');
                indicateActivityEnd();
            };

            var actionData = maybeAppendAjaxArguments(initialArgumentSource);

            // Process one step at a time until the AJAX call doesn't return the "continue" flag in the response.
            //
            // For explanation about how we're processing asynchronous AJAX calls in a loop but without risking
            // a stack overflow, look here: http://metaduck.com/01-asynchronous-iteration-patterns.html
            (function oneStep(actionData) {

                // Perform the async call
                $.post({
                    url: ajaxurl,
                    data: actionData,
                    success: function (originalResponse) {
                        var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                        if (response.success) {

                            // Inform the user
                            var responseData = response.data;

                            migration.updateMigrationProgress(responseData);

                            // Does the response contain instructions about next step?
                            var shouldContinue = _.has(responseData, 'continue') && responseData.continue;

                            if (shouldContinue) {
                                // Build actionData for next step and execute it
                                var actionData = maybeAppendAjaxArguments(responseData);

                                // This will be called immediately after the stack unwinds,
                                // preventing a stack overflow even for very high number of steps.
                                setTimeout(_.partial(oneStep, actionData), 0);

                            } else {
                                // No next steps, we're successfully done here.
                                indicateActivityEnd();

                                migration.currentMigrationPhase(MIGRATION_PHASE_COUNT + 1);
                            }

                        } else {
                            failCallback(response, response.data || {});
                        }
                    },

                    error: function (ajaxContext) {
                        console.log('Error:', ajaxContext.responseText);
                        failCallback({success: false, data: {}}, {});
                    }
                });

            })(actionData);
        };


        doAction(initialActionData, self.l10n);
    };


    self.init();


    return self;
};


Toolset.hooks.addAction('types-open-m2m-migration-dialog', function() {
    var dialog = new Types.page.extension.M2mMigrationDialog(jQuery);
    dialog.display();
});