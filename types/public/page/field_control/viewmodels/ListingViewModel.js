/**
 * Main ViewModel of the Field Control page.
 *
 * Holds the collection of field definition ViewModels, handles their sorting and filtering (search).
 *
 * @param fieldDefinitionModels
 * @constructor
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.ListingViewModel = function(fieldDefinitionModels) {

    var self = this;

    // Apply the generic listing viewmodel.
    Toolset.Gui.ListingViewModel.call(
        self,
        fieldDefinitionModels,
        {
            sortBy: 'displayName',
            itemsPerPage: Types.page.fieldControl.itemsPerPage
        },
        function(fieldDefinition, searchString) {
            return _.some([fieldDefinition.slug(), fieldDefinition.displayName()], function (value) {
                return (typeof(value) !== 'undefined' && value.toLowerCase().indexOf(searchString.toLowerCase()) > -1);
            });
        }
    );


    /**
     * Run an action on one or more field definitions.
     *
     * Handles all GUI updates as well as the underlying AJAX call.
     *
     * @param {string} fieldAction Name of the action to be performed on a field (see
     *     Types_Ajax::callback_field_control_action() for details).
     * @param {{[Types.page.fieldControl.viewmodels.FieldDefinitionViewModel]}} fieldDefinitions One or more field definitions this action applies to.
     * @param {object|undefined} [data] Custom action-specific data.
     * @param {function|undefined} [successCallback] Callback function that will be called on success. It will get
     *     two parameters, the full AJAX response and the "data" part for convenience.
     * @param {function|undefined} [failCallback] Callback function that will be called on failuer. Same params as above.
     * @since 2.0
     */
    self.doFieldAction = function(fieldAction, fieldDefinitions, data, successCallback, failCallback) {

        self.beginAction(fieldDefinitions);
        self.hideDisplayedMessage();

        //noinspection JSUnresolvedVariable
        var nonce = Types.page.fieldControl.ajaxInfo.fieldAction.nonce;

        var callback = function(messageType, genericMessageString, callback, response, data) {

            var messageText = data.message || genericMessageString;

            // If we have an array of messages, ue that instead.
            if(_.has(data, 'messages') && _.isArray(data.messages)) {
                var messages = _.without(data.messages, '');
                if(0 === messages.length) {
                    // keep the default text
                } else if(1 === messages.length) {
                    messageText = (messages[0]);
                } else {
                    // This will display a simple list of messages.
                    messageText = Types.page.fieldControl.main.templates.renderUnderscore('messageMultiple', {
                        messages: messages
                    });
                }
            }

            self.displayMessage(messageText, messageType);

            if(_.isFunction(callback)) {
                callback(response, data);
            }

            self.finishAction(fieldDefinitions);
        };

        //noinspection JSUnresolvedVariable
        Types.page.fieldControl.doAjax(
            fieldAction,
            nonce,
            self.getFieldDefinitionModels(fieldDefinitions),
            data || {},
            _.partial(callback, 'info', Types.page.fieldControl.strings.misc.genericSuccess || '', successCallback),
            _.partial(callback, 'error', Types.page.fieldControl.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
        );

    };


    /**
     * Obtain up-to-date models from given field definitions.
     *
     * @param {{[Types.page.fieldControl.viewmodels.FieldDefinitionViewModel]}} fieldDefinitions
     * @returns {{[object]}} Models with the same properties as the original model had.
     * @since 2.0
     */
    self.getFieldDefinitionModels = function(fieldDefinitions) {
        return _.map(fieldDefinitions, function(fieldDefinition) { return fieldDefinition.getModelObject(); });
    };


    /**
     * Update field definition viewmodels by new models.
     *
     * If a model's slug matches field definition, it will be updated.
     *
     * @param fieldModels
     * @param sourceDefinitions
     * @since 2.0
     */
    self.updateFieldDefinitionModels = function(fieldModels, sourceDefinitions) {

        _.each(fieldModels, function(fieldModel) {

            if(_.has(fieldModel, 'slug')) {

                // Find the definition by it's slug
                var fieldDefinition = _.find(sourceDefinitions, function (fieldDefinition) {
                    // Comparing also by metaKey because the slug can change under some circumstances.
                    return (fieldDefinition.slug() == fieldModel.slug || fieldDefinition.metaKey() == fieldModel.metaKey);
                });

                if(typeof(fieldDefinition) != 'undefined') {
                    fieldDefinition.updateModelObject(fieldModel);
                } else {
                    // todo report error
                }

            } else {
                // todo report error
            }

        });

    };


    /**
     * Handle user's input on a field action in a generic way.
     *
     * Works for both bulk and single actions.
     *
     * @param {[object]|object} fieldDefinitions One or more selected field definitions.
     * @param {function|null} conflictFilter Function that for a given field definition returns true if the action
     *     cannot be applied on it.
     * @param {string} conflictStringName Name of the string in Types.page.fieldControl.strings.misc that will be used
     *     for the message about conflicting field definitions.
     * @param {string} fieldActionName Name of the field action to be passed through AJAX.
     * @param {function} onSuccess Callback on action success. It will recieve the complete response, response data and
     *     the array of original field definitions (allways an array) as parameters.
     * @param onFailure Callback to be used when there is an error of some kind. Same as onSuccess with only two parameters.
     * @param {object|undefined} actionData Custom action data that will be passed through the AJAX call.
     * @since 2.0
     */
    self.handleFieldActionInput = function(fieldDefinitions, conflictFilter, conflictStringName, fieldActionName, onSuccess, onFailure, actionData) {

        if(!_.isArray(fieldDefinitions)) {
            fieldDefinitions = [fieldDefinitions];
        }

        if(0 == fieldDefinitions.length) {
            // No message is needed because the bulk action mechanism should never allow this.
            console.log('no fields selected');
            return;
        }

        if(_.isFunction(conflictFilter)) {
            var conflictingDefinitions = _.filter(fieldDefinitions, conflictFilter);

            if(0 < conflictingDefinitions.length) {

                var messageText = Types.page.fieldControl.strings.misc[conflictStringName] + ' '
                    + Types.page.fieldControl.strings.misc['unselectAndRetry'];

                self.displayMessage(
                    Types.page.fieldControl.main.templates.renderUnderscore('messageDefinitionList', {
                        message: messageText,
                        items: conflictingDefinitions
                    }),
                    'error'
                );
                return;
            }
        }

        self.doFieldAction(fieldActionName, fieldDefinitions, actionData || {}, _.partial(onSuccess, _, _, fieldDefinitions), onFailure);

    };


    /**
     * An object with methods to perform actions on field definitions.
     *
     * Each action accepts an array of field definitions, or a single field definition, as first parameter.
     *
     * @since 2.0
     */
    self.itemActions = {

        manageWithTypes: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return fieldDefinition.isUnderTypesControl();
            },
            'fieldsAlreadyManaged',
            'manage_with_types',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
                console.log("fail", response);
                // todo report error
            }
        ),


        stopManagingWithTypes: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return !fieldDefinition.isUnderTypesControl();
            },
            'fieldsAlreadyUnmanaged',
            'stop_managing_with_types',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
                console.log("fail", response);
                // todo report error
            }
        ),


        changeGroupAssignment: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter because there is no bulk action for this
            null,
            null,
            'change_group_assignment',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),


        deleteFields: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return !fieldDefinition.isUnderTypesControl();
            },
            'cannotDeleteUnmanagedFields',
            'delete_field',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.items.removeAll(fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),


        changeFieldType: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter needed
            null,
            null,
            'change_field_type',
            _,
            _
        ),


        changeFieldCardinality: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter needed
            null,
            null,
            'change_field_cardinality',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),

        // For referencing form outside the ListingViewModel.
        updateFieldDefinitionModels: self.updateFieldDefinitionModels
    };


    /**
     * Array of objects describing available bulk actions.
     *
     * It will be used by knockout to populate the select input field dynamically.
     *
     * @returns {[{value:string,displayName:string,handler:function|undefined}]}
     * @since 2.0
     */
    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: Types.page.fieldControl.strings.bulkAction.select
        },
        {
            value: 'delete',
            displayName: Types.page.fieldControl.strings.bulkAction.delete,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.DeleteDialogViewModel(fieldDefinitions, function(isAccepted) {
                    if(isAccepted) {
                        self.itemActions.deleteFields(fieldDefinitions);
                    }
                }).display();
            }
        },
        {
            value: 'manageWithTypes',
            displayName: Types.page.fieldControl.strings.bulkAction.manageWithTypes,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.BulkChangeManagementStatusDialogViewModel(fieldDefinitions, true, function(isAccepted) {
                    if(isAccepted) {
                        self.itemActions.manageWithTypes(fieldDefinitions);
                    }
                }).display();
            }
        },
        {
            value: 'stopManagingWithTypes',
            displayName: Types.page.fieldControl.strings.bulkAction.stopManagingWithTypes,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.BulkChangeManagementStatusDialogViewModel(fieldDefinitions, false, function(isAccepted) {
                    if(isAccepted) {
                        self.itemActions.stopManagingWithTypes(fieldDefinitions);
                    }
                }).display();
            }
        }
    ]);


    /**
     * Fill field definitions with data from PHP.
     *
     * Result will be stored in the self.items() observable array.
     *
     * @param itemModels
     * @since 2.2
     */
    self.createItemViewModels = function(itemModels) {
        self.items(_.map(itemModels, function(itemModel) {
            return new Types.page.fieldControl.viewmodels.FieldDefinitionViewModel(itemModel, self.itemActions);
        }));
    };


    self.init();
};
