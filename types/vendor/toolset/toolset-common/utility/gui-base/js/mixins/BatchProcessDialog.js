var Toolset = Toolset || {};
Toolset.Gui = Toolset.Gui || {};
Toolset.Gui.Mixins = Toolset.Gui.Mixins || {};

/**
 * Mixin for displaying a standardized batch process dialog.
 *
 * To be used in conjunction with Toolset_Ajax_Handler_Batch_Process, where you will find the documentation
 * for the server-side implementation.
 *
 * Dialogs created by this mixin resemble the m2m migration dialog, and you can find one example
 * usage in Types, in MergeRelationships.js (for merging two one-to-many relationships into one many-to-many).
 *
 * When this mixin is applied, you just need to override several methods to make your dialog work. These are
 * grouped together in an "To be overridden" section.
 *
 * @constructor
 */
Toolset.Gui.Mixins.BatchProcessDialog = function () {

    var self = this;

    Toolset.Gui.Mixins.CreateDialog.call(self);


    // Current status
    //
    //

    self.currentDialogStepNumber = ko.observable(1);
    self.isInProgress = ko.observable(false);
    self.isCompleted = ko.observable(false);
    self.currentProcessPhase = ko.observable(0);

    /**
     * This can be used for a binding with dialog buttons.
     */
    self.isCancelActionAvailable = ko.observable(true);

    /**
     * Close the dialog and clean up after it.
     */
    self.cleanup = function () {
        self.dialog.$el.ddldialog('close');
    };


    // To be overridden.
    //
    //

    var notImplemented = function () {
        console.error('Function not implemented.')
    };


    /**
     * @return string Name of the AJAX action of the batch process.
     * @type {function}
     */
    self.getAjaxActionName = notImplemented;


    /**
     * @return string Value of the nonce for the AJAX action.
     * @type {function}
     */
    self.getNonce = notImplemented;


    /**
     * @return string Initial value of the "options" parameter that will be passed to the first AJAX call.
     * @type {function(): object}
     */
    self.getAjaxOptions = self.getAjaxOptions = function () {
        return {};
    };


    /**
     * The dialog can have multiple steps (the current one being stored in self.currentDialogStepNumber).
     * This function should return the (1-based) number of the step where the batch process takes place.
     *
     * @return {int}
     */
    self.getDialogStepNumberWithProcess = function () {
        return 2
    };


    /**
     * This will be called when the batch process begins.
     *
     * @type function
     */
    self.onProcessStart = function () {
    };


    /**
     * Get an array of process phases, in the correct order, with display labels.
     *
     * @type {function: [{label: string}]}
     */
    self.getProcessPhases = notImplemented;


    /**
     * This will be called when the process finishes. As a parameter, it gets a boolean value indicating
     * success (total success or finished with warnings) or failure (fatal error).
     *
     * @type {function(bool): void}
     */
    self.onProcessEnd = function (ignored) {};


    /**
     * Return a result message after the whole process completes, depending on the result.
     *
     * @param {string} result success|warning|error
     * @returns {string}
     */
    self.getMessageOnProcessEnd = function (result) {
        notImplemented();
        return result;
    };


    /**
     * Return the l10n object.
     *
     * If it has an ajax_arguments property, it should be an object whose properties will be used to construct the AJAX call "data".
     * See maybeAppendAjaxArguments() and oneStep() functions for details.
     *
     * @type {function() : {ajax_arguments: object}}
     */
    self.getl10n = notImplemented;


    // End of the "to be overridden" section.
    //
    //


    /**
     * Handle the final result message that's being displayed at the end of the process.
     *
     * @type {{show: Toolset.Gui.Mixins.BatchProcessDialog.processMessage.show, text: (function|ko.observable), isVisible: ko.observable, classes: ko.observable}}
     */
    self.processMessage = {
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
            self.processMessage.classes(classes);
            self.processMessage.text(text);
            self.processMessage.isVisible(text.length > 0);
        },
        text: ko.observable(''),
        isVisible: ko.observable(false),
        classes: ko.observable('')
    };


    /**
     * Log output ("technical details") where the messages coming from the batch steps are appended.
     *
     * @type {{output: (*|observable), isEmpty: *, append: Toolset.Gui.Mixins.BatchProcessDialog.processLog.append, isVisible: (*|observable), toggle: Toolset.Gui.Mixins.BatchProcessDialog.processLog.toggle}}
     */
    self.processLog = {
        output: ko.observable(''),
        isEmpty: ko.pureComputed(function () {
            return (0 === self.processLog.output().length);
        }),

        /** Append a new line to the output. */
        append: function (text) {
            var currentContent = (self.processLog.isEmpty() ? '' : self.processLog.output() + "\n");
            self.processLog.output(currentContent + '> ' + text);
        },

        isVisible: ko.observable(false),

        toggle: function () {
            self.processLog.isVisible(!self.processLog.isVisible());
        }
    };


    /**
     * Find a dialog button by its class and apply a particular knockout binding on it.
     *
     * @param $dialog jQuery element of the dialog.
     * @param {string} buttonClass Class that uniquely identifies the button. Only a first match will be used.
     * @param {string} binding Value for the data-bind attribute.
     */
    self.addBindingToDialogButton = function ($dialog, buttonClass, binding) {
        var $button = jQuery('.' + buttonClass).first();
        $button.attr('data-bind', binding);
        ko.applyBindings(self, $button.get(0));
    };


    /**
     * @returns {number}
     */
    self.getProcessPhaseCount = function () {
        return self.getProcessPhases().length;
    };


    // noinspection JSUnusedGlobalSymbols
    /**
     * Manage results of individual process phases as well as the way the results will be
     * displayed.
     */
    self.phase = {
        definition: ko.observableArray(),
        results: ko.observableArray(),
        getPhaseResult: function (phaseNumber) {
            phaseNumber = Number(phaseNumber);
            // noinspection JSValidateTypes
            return _.find(self.phase.results(), function (result) {
                return (_.has(result, 'phaseNumber') && phaseNumber === result['phaseNumber']);
            });
        },
        update: function (phaseNumber, status) {
            phaseNumber = Number(phaseNumber);
            var phaseResult = self.phase.getPhaseResult(phaseNumber) || {phaseNumber: phaseNumber};

            phaseResult.status = (
                (_.has(phaseResult, 'status') && 'warning' === phaseResult['status'])
                    ? 'warning'
                    : status
            );

            self.phase.results.remove(phaseResult);
            self.phase.results.push(phaseResult);
        },
        clear: function () {
            self.phase.results.removeAll();
        },

        getDisplayInfo: function (phaseNumber) {
            var phaseResult = self.phase.getPhaseResult(phaseNumber);

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

            var status = (_.has(phaseResult, 'status') ? phaseResult['status'] : 'none');

            return {
                status: status,
                icon: 'fa fa-lg ' + statusToDisplayInfo[status].icon,
                color: statusToDisplayInfo[status].color,
                visible: _.has(statusToDisplayInfo[status].visible) ? statusToDisplayInfo[status].visible : true
            };
        }
    };


    /**
     * Parse the AJAX call response and update the current progress.
     *
     * That means setting the result of a previous phase (if applicable), setting a current phase and
     * appending the log output messages from the last step.
     *
     * @param data AJAX response data.
     */
    self.updateProgress = function (data) {
        if (_.has(data, 'ajax_arguments')) {
            var phase = data.ajax_arguments['phase'] || 0;
            self.currentProcessPhase(phase);
        }

        if (_.has(data, 'previous_phase')) {
            var previousPhase = data['previous_phase'];
            self.phase.update(
                previousPhase,
                _.has(data, 'status') ? data['status'] : 'error'
            );
        }

        if (_.has(data, 'message')) {
            self.processLog.append(data['message']);
        }
    };


    /**
     * To be called when the whole process finishes.
     *
     * Set the state observables accordingly and show a result message and a process log accordingly.
     */
    var indicateActivityEnd = function () {

        self.isCompleted(true);
        self.isInProgress(false);

        var anyPhaseHas = function (what) {
            return _.some(self.phase.results(), function (result) {
                var hasWarning = (_.has(result, 'status') && what === result['status']);
                return hasWarning;
            });
        };

        if (anyPhaseHas('error')) {
            self.processLog.isVisible(true);
            self.processMessage.show(self.getMessageOnProcessEnd('error'), 'error');
            self.onProcessEnd(false);
            self.isCancelActionAvailable(true);
        } else if (anyPhaseHas('warning')) {
            self.processLog.isVisible(true);
            self.processMessage.show(self.getMessageOnProcessEnd('warning'), 'warning');
            self.onProcessEnd(true);
        } else {
            self.onProcessEnd(true);
        }
    };


    // noinspection JSUnusedLocalSymbols
    /**
     * Callback on failed AJAX call. Print the error and finish.
     *
     * @param response
     * @param responseData
     */
    var failCallback = function (response, responseData) {
        self.phase.update(self.currentProcessPhase(), 'error');
        indicateActivityEnd();
    };


    /**
     * AJAX call data used in the last call. Will be used to construct the data for the following AJAX call.
     *
     * @type {{}}
     */
    self.lastActionData = {};


    /**
     * Process one step at a time until the AJAX call doesn't return the "continue" flag in the response.
     *
     * For explanation about how we're processing asynchronous AJAX calls in a loop but without risking
     * a stack overflow, look here: http://metaduck.com/01-asynchronous-iteration-patterns.html
     */
    var oneStep = function (actionData) {

        // Perform the async call
        jQuery.post({
            url: ajaxurl,
            data: actionData,
            success: function (originalResponse) {
                var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                if (response.success) {

                    // Inform the user
                    var responseData = response.data;

                    self.updateProgress(responseData);

                    // Does the response contain instructions about next step?
                    var shouldContinue = _.has(responseData, 'continue') && responseData.continue;

                    if (shouldContinue) {
                        // Build actionData for next step and execute it
                        var actionData = maybeAppendAjaxArguments(responseData, self.lastActionData);

                        // This will be called immediately after the stack unwinds,
                        // preventing a stack overflow even for very high number of steps.
                        setTimeout(_.partial(oneStep, actionData), 0);

                    } else {
                        // No next steps, we're successfully done here.
                        indicateActivityEnd();

                        self.currentProcessPhase(self.getProcessPhaseCount());
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

        self.lastActionData = actionData;
    };


    /**
     * From the "argument source" and initialActionData, build a new actionData object.
     *
     * @param source
     * @param initialActionData
     * @returns {Object}
     */
    var maybeAppendAjaxArguments = function (source, initialActionData) {
        var actionData = _.extend(initialActionData);
        if (_.has(source, 'ajax_arguments')) {
            actionData = _.extendOwn(actionData, source['ajax_arguments']);
        }
        return actionData;
    };


    /**
     * Start the batch process.
     */
    self.startProcess = function () {
        self.processLog.append('Batch process started.');

        self.isCancelActionAvailable(false);
        self.phase.definition(self.getProcessPhases());

        self.onProcessStart();
        self.currentDialogStepNumber(self.getDialogStepNumberWithProcess());
        self.currentProcessPhase(1);
        self.isInProgress(true);

        var initialActionData = {
            action: self.getAjaxActionName(),
            wpnonce: self.getNonce(),
            options: self.getAjaxOptions()
        };

        var actionData = maybeAppendAjaxArguments(self.getl10n(), initialActionData);

        oneStep(actionData);
    };


};