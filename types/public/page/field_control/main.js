var Types = Types || {};

// the head.js object
Types.head = Types.head || head;

Types.page = Types.page || {};

// Everything related to this page.
Types.page.fieldControl = {};
Types.page.fieldControl.viewmodels = {};
Types.page.fieldControl.strings = {};


/**
 * Page controller class.
 *
 * Handles page initialization.
 *
 * @constructor
 * @since 2.0
 */
Types.page.fieldControl.Class = function() {

    var self = this;

    // Extend the generic listing page controller.
    Toolset.Gui.ListingPage.call(self);
    

    /**
     * Fill the Types.page.fieldControl.strings object with data passed from PHP.
     *
     * @param modelData
     * @since 2.0
     */
    self.initStaticData = function(modelData) {
        Types.page.fieldControl.strings = modelData.strings || {};
        Types.page.fieldControl.strings.misc = Types.page.fieldControl.strings.misc || {};
        Types.page.fieldControl.fieldTypeDefinitions = modelData.fieldTypeDefinitions || {};
        Types.page.fieldControl.ajaxInfo = modelData.ajaxInfo || {};
        Types.page.fieldControl.currentDomain = modelData.currentDomain || {};
        Types.page.fieldControl.groups = modelData.groups || {};
        Types.page.fieldControl.typeConversionMatrix = modelData.typeConversionMatrix || {};
        Types.page.fieldControl.itemsPerPage = modelData.itemsPerPage || {};
    };


    /**
     * Initialize everything AJAX-related here.
     *
     * @since 2.0
     */
    self.initAjax = function() {

        /**
         * Perform an AJAX call on field definitions.
         *
         * @param {string} fieldAction Name of the action, see Types_Ajax::callback_field_control_action() for details.
         * @param {string} nonce Name of the nonce for this action.
         * @param {object|[object]} fields One or more models of fields this action applies to.
         * @param {object} data Custom action-specific data.
         * @param {function} successCallback Callback to be used after AJAX call is completed. It will get two parameters,
         *     the complete AJAX response and the 'data' element for convenience.
         * @param {function} [failCallback] Analogous to successCallback for the case of failure. If missing,
         *     successCallback will be used instead.
         *
         * @since 2.0
         */
        Types.page.fieldControl.doAjax = function (fieldAction, nonce, fields, data, successCallback, failCallback) {


            if (!_.isArray(fields)) {
                fields = [fields];
            }

            var ajaxData = {
                action: 'types_field_control_action',
                field_action: fieldAction,
                wpnonce: nonce,
                fields: fields,
                domain: Types.page.fieldControl.currentDomain,
                action_specific: data
            };


            if (typeof(failCallback) == 'undefined') {
                failCallback = successCallback;
            }

            jQuery.ajax({
                async: true,
                type: 'POST',
                url: ajaxurl,
                data: ajaxData,

                success: function (originalResponse) {
                    var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                    self.debug('AJAX response', ajaxData, originalResponse);

                    if (response.success) {
                        successCallback(response, response.data || {});
                    } else {
                        failCallback(response, response.data || {});
                    }
                },

                error: function (ajaxContext) {
                    console.log('Error:', ajaxContext.responseText);
                    failCallback({success: false, data: {}}, {});
                }
            });

        }
    };


    self.beforeInit = function() {

        var modelData = self.getModelData();
        //noinspection JSUnresolvedVariable
        Types.page.fieldControl.jsPath = modelData.jsIncludePath;
        Types.page.fieldControl.typesVersion = modelData.typesVersion;

        self.initStaticData(modelData);
        self.initAjax();
        self.addKnockoutBindingHandlers();
        
    };


    /**
     * Additional, page-specific knockout binding handlers.
     * 
     * @since 2.2
     */
    self.addKnockoutBindingHandlers = function() {

        var highlightedBorder = function(element, valueAccessor) {
            var isHighlighted = ko.unwrap(valueAccessor());
            if(isHighlighted) {
                jQuery(element).addClass('types-highlighted-border').focus();
            } else {
                jQuery(element).removeClass('types-highlighted-border');
            }
        };

        /**
         * Binding for highlighting a button.
         *
         * @since 2.0
         */
        ko.bindingHandlers.highlightedBorder = {
            init: highlightedBorder,
            update: highlightedBorder
        };
        
    };


    self.loadDependencies = function(nextStep) {
        var typesVersion = Types.page.fieldControl.typesVersion;
        // Continue after loading the view of the listing table.
        Types.head.load(
            Types.page.fieldControl.jsPath + '/viewmodels/BulkChangeManagementStatusDialogViewModel.js?ver=' + typesVersion,
            Types.page.fieldControl.jsPath + '/viewmodels/DeleteDialogViewModel.js?ver=' + typesVersion,
            Types.page.fieldControl.jsPath + '/viewmodels/ChangeAssignDialogViewModel.js?ver=' + typesVersion,
            Types.page.fieldControl.jsPath + '/viewmodels/ChangeFieldTypeDialogViewModel.js?ver=' + typesVersion,
            Types.page.fieldControl.jsPath + '/viewmodels/FieldDefinitionViewModel.js?ver=' + typesVersion,
            Types.page.fieldControl.jsPath + '/viewmodels/ListingViewModel.js?ver=' + typesVersion,
            nextStep
        );
    };


    self.getMainViewModel = function() {
        return new Types.page.fieldControl.viewmodels.ListingViewModel(self.getModelData().fieldDefinitions);
    };


    self.afterInit = function() {
        Types.page.menuLinkAdjuster.addMenuParams({key: 'domain', value: Types.page.fieldControl.currentDomain});
    };

};


// Make everything happen.
Types.page.fieldControl.main = new Types.page.fieldControl.Class(jQuery);
Types.head.ready(Types.page.fieldControl.main.init);