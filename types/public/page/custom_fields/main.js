var Types = Types || {};

// the head.js object
Types.head = Types.head || head;

Types.page = Types.page || {};

// Everything related to this page.
Types.page.customFields = {};
Types.page.customFields.viewmodels = {};
Types.page.customFields.strings = {};


/**
 * Page controller class.
 *
 * Handles page initialization.
 *
 * @constructor
 * @since 2.3
 */
Types.page.customFields.Class = function () {

    var self = this;

    // Extend the generic listing page controller.
    Toolset.Gui.ListingPage.call(self);


    /**
     * Fill the Types.page.customFields.strings object with data passed from PHP.
     *
     * @param modelData
     * @since 2.3
     */
    self.initStaticData = function (modelData) {
        Types.page.customFields.strings = modelData.strings || {};
        Types.page.customFields.itemsPerPage = modelData.itemsPerPage || {};
        Types.page.customFields.ajaxInfo = modelData.ajaxInfo || {};

        Types.page.customFields.strings.misc = Types.page.customFields.strings.misc || {};
        Types.page.customFields.ajaxInfo = modelData.ajaxInfo || {};
        Types.page.customFields.currentDomain = modelData.currentDomain || {};
        Types.page.customFields.itemsPerPage = modelData.itemsPerPage || {};
        Types.page.customFields.addNewLinks = modelData.addNewLinks || {};
        Types.page.customFields.tabs = modelData.tabs || {};
    };


    /**
     * Initialize everything AJAX-related here.
     *
     * @since 2.3
     */
    self.initAjax = function () {

        /**
         * Perform an AJAX call on field definitions.
         *
         * @param {string} fieldAction Name of the action, see Types_Ajax::callback_custom_fields_action() for details.
         * @param {string} nonce Name of the nonce for this action.
         * @param {object|[object]} fields One or more models of fields this action applies to.
         * @param {object} data Custom action-specific data.
         * @param {function} successCallback Callback to be used after AJAX call is completed. It will get two parameters,
         *     the complete AJAX response and the 'data' element for convenience.
         * @param {function} [failCallback] Analogous to successCallback for the case of failure. If missing,
         *     successCallback will be used instead.
         *
         * @since 2.3
         */
        Types.page.customFields.doAjax = function (fieldAction, nonce, fields, data, successCallback, failCallback) {

            if (!_.isArray(fields)) {
                fields = [fields];
            }

            var ajaxData = {
                action: Types.page.customFields.ajaxInfo.fieldGroupAction.name,
                field_action: fieldAction,
                wpnonce: nonce,
                fields: fields,
                domain: Types.page.customFields.currentDomain,
                action_specific: data
            };


            if (typeof(failCallback) === 'undefined') {
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


    self.beforeInit = function () {

        var modelData = self.getModelData();
        //noinspection JSUnresolvedVariable
        Types.page.customFields.jsPath = modelData.jsIncludePath;
        Types.page.customFields.typesVersion = modelData.typesVersion || 'unset';

        self.initStaticData(modelData);
        self.initAjax();
        self.addKnockoutBindingHandlers();

    };


    /**
     * Additional, page-specific knockout binding handlers.
     *
     * @since 2.3
     */
    self.addKnockoutBindingHandlers = function () {

        var highlightedBorder = function (element, valueAccessor) {
            var isHighlighted = ko.unwrap(valueAccessor());
            if (isHighlighted) {
                jQuery(element).addClass('types-highlighted-border').focus();
            } else {
                jQuery(element).removeClass('types-highlighted-border');
            }
        };

        /**
         * Binding for highlighting a button.
         *
         * @since 2.3
         */
        ko.bindingHandlers.highlightedBorder = {
            init: highlightedBorder,
            update: highlightedBorder
        };

    };


    self.loadDependencies = function (nextStep) {
        var typesVersion = Types.page.customFields.typesVersion;
        // Continue after loading the view of the listing table.
        Types.head.load(
            Types.page.customFields.jsPath + '/viewmodels/ListingViewModel.js?ver=' + typesVersion,
            Types.page.customFields.jsPath + '/viewmodels/CustomFieldViewModel.js?ver=' + typesVersion,
            Types.page.customFields.jsPath + '/viewmodels/AddNewDialogViewModel.js?ver=' + typesVersion,
            Types.page.customFields.jsPath + '/viewmodels/DeleteDialogViewModel.js?ver=' + typesVersion,
            nextStep
        );
    };


    self.getMainViewModel = function () {
        return new Types.page.customFields.viewmodels.ListingViewModel(self.getModelData().customFields);
    };

    var tabsHandler = function () {
        jQuery(document).on('click', '.js-toolset-nav-tab', function (e) {
            e.preventDefault();
            var $clicked_tab = jQuery(this),
                target = $clicked_tab.data('target'),
                current = jQuery('.js-toolset-nav-tab.nav-tab-active').data('target');
            if (!$clicked_tab.hasClass('nav-tab-active')) {
                jQuery('.js-toolset-nav-tab.nav-tab-active').removeClass('nav-tab-active');
                jQuery('.js-toolset-tabbed-section-item-' + current).fadeOut('fast', function () {
                    jQuery('.js-toolset-tabbed-section-item').removeClass('toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item');
                    $clicked_tab.addClass('nav-tab-active');
                    jQuery('.js-toolset-tabbed-section-item-' + target).fadeIn('fast', function () {
                        jQuery(this).addClass('toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item');
                    });
                });
            }
        });
    };

    self.afterInit = function () {
        Types.page.menuLinkAdjuster.addMenuParams({key: 'domain', value: Types.page.customFields.currentDomain});
        tabsHandler();
    };

};


// Make everything happen.
Types.page.customFields.main = new Types.page.customFields.Class(jQuery);
Types.head.ready(Types.page.customFields.main.init);
