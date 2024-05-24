/**
 * Represents the listing of custom fields.
 *
 * @param {{[object]}} customFieldsModels Relationship model data coming from PHP.
 * @constructor
 */
Types.page.customFields.viewmodels.ListingViewModel = function(customFieldsModels) {

    var self = this;
    // Apply the generic listing viewmodel.
    var parentListingViewModel = Toolset.Gui.ListingViewModel.call(
        self,
        customFieldsModels,
        {
            sortBy: 'displayName',
            itemsPerPage: Types.page.customFields.itemsPerPage
        },
        function (customField, searchString) {
            return _.some([customField.slug(), customField.displayName()], function (value) {
                return (typeof(value) !== 'undefined' && value.toLowerCase().indexOf(searchString.toLowerCase()) > -1);
            });
        }
    );

    /**
     * Fill field groups with data from PHP.
     *
     * Result will be stored in the self.items() observable array.
     * Data is stored grouped by domain
     *
     * @param itemModels
     * @since 2.3
     */
    self.createItemViewModels = function (itemModels) {
        self.items(_.map(itemModels.data[self.currentDomain()], function (itemModel) {
            return new Types.page.customFields.viewmodels.CustomFieldViewModel(itemModel, self.itemActions, self);
        }));
    };

    self.currentDomain = ko.observable(customFieldsModels.currentDomain);


    /**
     * Gets 'no items found' text depending of the domain
     */
    self.noItemsFound = ko.computed(function () {
        var messageType = (
            parentListingViewModel.searchString()
            || '0' === jQuery('#toolset_fields_per_page').val()
        )
            ? 'search'
            : self.currentDomain();
        return Types.page.customFields.strings.misc.noItemsFound[messageType];
    });

    // ------------------------------------------------------------------------
    // Event handlers
    // ------------------------------------------------------------------------


    // Popups the dialog
    self.onAddNewCustomFieldAction = function () {
        Types.page.customFields.viewmodels.AddNewDialogViewModel(self, function (isAccepted, updatedGroups) {
        }).display();
    };


    /**
     * Handle user's input on a field group action in a generic way.
     *
     * Works for both bulk and single actions.
     *
     * @param {[object]|object} fieldGroups One or more selected field groups.
     * @param {string} fieldGroupActionName Name of the field action to be passed through AJAX.
     * @param {function} onSuccess Callback on action success. It will recieve the complete response, response data and
     *         the array of original field groups (allways an array) as parameters.
     * @param onFailure Callback to be used when there is an error of some kind. Same as onSuccess with only two parameters.
     * @param {object|undefined} actionData Custom action data that will be passed through the AJAX call.
     * @since 2.3
     */
    self.handleFieldGroupAction = function (fieldGroups, fieldGroupActionName, onSuccess, onFailure, actionData) {

        if (!_.isArray(fieldGroups)) {
            fieldGroups = [fieldGroups];
        }

        if (0 === fieldGroups.length) {
            // No message is needed because the bulk action mechanism should never allow this.
            console.log('no fields selected');
            return;
        }

        self.doFieldAction(fieldGroupActionName, fieldGroups, actionData || {}, _.partial(onSuccess, _, _, fieldGroups), onFailure);

    };


    /**
     * Run an action on one or more field groups.
     *
     * Handles all GUI updates as well as the underlying AJAX call.
     *
     * @param {string} fieldGroupAction Name of the action to be performed on a field (see
     *         Types_Ajax::callback_custom_fields_action() for details).
     * @param {{[Types.page.customFields.viewmodels.CustomFieldViewModel]}} fieldGroups One or more field groups this action applies to.
     * @param {object|undefined} [data] Custom action-specific data.
     * @param {function|undefined} [successCallback] Callback function that will be called on success. It will get
     *         two parameters, the full AJAX response and the "data" part for convenience.
     * @param {function|undefined} [failCallback] Callback function that will be called on failuer. Same params as above.
     * @since 2.3
     */
    self.doFieldAction = function (fieldGroupAction, fieldGroups, data, successCallback, failCallback) {

        self.beginAction(fieldGroups);
        self.hideDisplayedMessage();

        //noinspection JSUnresolvedVariable
        var nonce = Types.page.customFields.ajaxInfo.fieldGroupAction.nonce;
        var callback = function (messageType, genericMessageString, callback, response, data) {

            var messageText = data.message || genericMessageString;

            // If we have an array of messages, ue that instead.
            if (_.has(data, 'messages') && _.isArray(data.messages)) {
                var messages = _.without(data.messages, '');
                if (0 === messages.length) {
                    // keep the default text
                } else if (1 === messages.length) {
                    messageText = (messages[0]);
                } else {
                    // This will display a simple list of messages.
                    messageText = Types.page.customFields.templates.renderUnderscore('messageMultiple', {
                        messages: messages
                    });
                }
            }

            self.displayMessage(messageText, messageType);

            if (_.isFunction(callback)) {
                callback(response, data);
            }

            self.finishAction(fieldGroups);
        };

        //noinspection JSUnresolvedVariable
        Types.page.customFields.doAjax(
            fieldGroupAction,
            nonce,
            self.getCustomFieldsIds(fieldGroups),
            data || {},
            _.partial(callback, 'info', Types.page.customFields.strings.misc.genericSuccess || '', successCallback),
            _.partial(callback, 'error', Types.page.customFields.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
        );

    };

    /**
     * Change the content when the tab is selected
     *
     * @since 2.3
     * @param {string} domain The domain related to the tab
     * @param {Toolset.Gui.ListingViewModel} data The object user by ko
     * @param {Event} event The event triggered
     */
    self.onTabChange = function (domain, data, event) {
        // When history navigation, if the tab is focussed, it may be confussing to the user
        event.target.blur();
        parentListingViewModel.searchString('');
        if (_.contains(_.keys(customFieldsModels.data), domain)) {
            self.currentDomain(domain);
            Types.page.customFields.currentDomain = domain;
            self.createItemViewModels(customFieldsModels);
            // Changes the page url
            history.pushState(domain, null, Types.page.customFields.tabs[domain].url);
        }
    };


    /**
     * Handles history navigation
     */
    window.addEventListener('popstate', function (event) {
        domain = event.state ? event.state : self.currentDomain();
        self.currentDomain(domain);
        self.createItemViewModels(customFieldsModels);
        // Active the domain tab
        jQuery('.nav-tab-active').removeClass('nav-tab-active');
        jQuery('.toolset-tab-controls a[data-target="' + domain + '"]').addClass('nav-tab-active');
    });


    /**
     * Returns a list of group ids
     *
     * @param {array|Types.page.customFields.viewmodels.CustomFieldViewModel} fieldGroups A list or a single CustomFieldViewModel
     */
    self.getCustomFieldsIds = function (fieldGroups) {
        if (!_.isArray(fieldGroups)) fieldGroups = [fieldGroups];
        return _.map(fieldGroups, function (fieldGroup) {
            return _.pick(fieldGroup, 'groupId');
        })
    };


    /**
     * Array of objects describing available bulk actions.
     *
     * It will be used by knockout to populate the select input field dynamically.
     *
     * @returns {[{value:string,displayName:string,handler:function|undefined}]}
     * @since 2.3
     */
    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: Types.page.customFields.strings.bulkAction.select
        },
        {
            value: 'delete',
            displayName: Types.page.customFields.strings.bulkAction.delete,
            handler: function (fieldGroups) {
                Types.page.customFields.viewmodels.DeleteDialogViewModel(fieldGroups, function (isAccepted) {
                    if (isAccepted) {
                        self.itemActions.deleteFieldGroups(fieldGroups);
                    }
                }).display();
            }
        },
        {
            value: 'activate',
            displayName: Types.page.customFields.strings.bulkAction.activate,
            handler: function (fieldGroups) {
                self.itemActions.activateFieldGroup(fieldGroups);
            }
        },
        {
            value: 'deactivate',
            displayName: Types.page.customFields.strings.bulkAction.deactivate,
            handler: function (fieldGroups) {
                self.itemActions.deactivateFieldGroup(fieldGroups);
            }
        }
    ]);


    /**
     * Update field definition viewmodels by new models.
     *
     * If a model's slug matches field definition, it will be updated.
     *
     * @param fieldModels
     * @param sourceGroups
     * @since 2.3
     */
    self.updateFieldGroupModels = function (fieldModels, sourceGroups) {
        _.each(fieldModels, function (fieldModel) {
            if (_.has(fieldModel, 'slug')) {

                // Find the definition by it's slug
                var fieldGroups = _.find(sourceGroups, function (fieldGroup) {
                    // Comparing also by metaKey because the slug can change under some circumstances.
                    return (fieldGroup.slug() === fieldModel.slug);
                });
                if (typeof(fieldGroups) !== 'undefined') {
                    fieldGroups.updateModelObject(fieldModel);
                } else {
                    // todo report error
                }

            } else {
                // todo report error
            }

        });

    };


    /**
     * Item actions
     */

    self.itemActions = {
        editFieldGroup: function (fieldGroupModel) {
            document.location.href = fieldGroupModel.editLink;
        },

        deleteFieldGroups: _.partial(
            self.handleFieldGroupAction,
            _,
            'delete_group',
            function (response, data, fieldGroups) {
                // onSuccess
                // The item needs to be removed from the original data in order to
                //refresh the items when the tab has changed
                var activations = [];
                _.each(fieldGroups, function (item) {
                    activations[item.groupId] = true;
                });
                customFieldsModels.data[self.currentDomain()] = _.filter(
                    customFieldsModels.data[self.currentDomain()],
                    function (element) {
                        return !activations[element.id];
                    }
                );
                self.items.removeAll(fieldGroups);
            },
            function (response) {
                // onFailure
            },
            {}
        ),

        changeFieldGroupActive: _.partial(
            self.handleFieldGroupAction,
            _,
            'toggle_active',
            function (response, data, fieldGroups) {
                // onSuccess
                // toggle activate in the model
                toggleActiveStatusInModel(data.results);
                self.updateFieldGroupModels(data.results, fieldGroups);
                // If it is sorted by isActive, update sorting
                if (parentListingViewModel.getCurrentSortBy() === 'isActive') {
                    parentListingViewModel.updateSort();
                }
            },
            function (response) {
                // onFailure
            },
            {}
        ),

        activateFieldGroup: _.partial(
            self.handleFieldGroupAction,
            _,
            'activate_group',
            function (response, data, fieldGroups) {
                // onSuccess
                // toggle activate in the model
                toggleActiveStatusInModel(data.results);
                self.updateFieldGroupModels(data.results, fieldGroups);
                // If it is sorted by isActive, update sorting
                if (parentListingViewModel.getCurrentSortBy() === 'isActive') {
                    parentListingViewModel.updateSort();
                }
            },
            function (response) {
                // onFailure
            },
            {}
        ),

        deactivateFieldGroup: _.partial(
            self.handleFieldGroupAction,
            _,
            'deactivate_group',
            function (response, data, fieldGroups) {
                // onSuccess
                // toggle activate in the model
                toggleActiveStatusInModel(data.results);
                self.updateFieldGroupModels(data.results, fieldGroups);
                // If it is sorted by isActive, update sorting
                if (parentListingViewModel.getCurrentSortBy() === 'isActive') {
                    parentListingViewModel.updateSort();
                }
            },
            function (response) {
                // onFailure
            },
            {}
        )
    };


    /**
     * Changes the "isActive" property from the model so when the listing is refreshed the values are shown ok.
     *
     * @param {Object[]} elements List of elements returned from the Ajax action
     * @since m2m
     */
    var toggleActiveStatusInModel = function (elements) {
        var activations = [];
        _.each(elements, function (item) {
            activations[item.id] = item.isActive;
        });
        _.each(customFieldsModels.data[self.currentDomain()], function (item, key) {
            if ( _.has( activations, item.id ) ) {
                customFieldsModels.data[self.currentDomain()][key].isActive = activations[item.id];
            }
        });
    };


    /**
     * Tabs
     */
    self.tabs = ko.observableArray(_.toArray(Types.page.customFields.tabs));


    /**
     * Content div is the same for each tab, so it has to have class names of
     * each tab in order to make tabs selecting works
     */
    self.getTabContentStyles = ko.pureComputed(function () {
        var classes = 'js-toolset-tabbed-section-item toolset-tabbed-section-item toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item ';
        var domains = _.keys(Types.page.customFields.tabs);
        for (var i = 0; i < domains.length; i++) classes += ' js-toolset-tabbed-section-item-' + domains[i] + ' toolset-tabbed-section-item-' + domains[i];
        return classes;
    });


    /**
     * Field Control Box strings
     */
    self.fieldControlBox = ko.pureComputed(function () {
        return Types.page.customFields.tabs[self.currentDomain()].field_control;
    });


    self.init();

};
