//noinspection JSUnusedAssignment
var Types = Types || {};

Types.settings = Types.settings || {};

// Generic Types setting saving mechanism
//
//

(function ($) {

    var modelData = JSON.parse(WPV_Toolset.Utils.editor_decode64($('#types_model_data').html()));
    Types.settings.ajaxInfo = modelData.ajaxInfo || {};

    Types.settings.save = function (setting_id) {

        //noinspection JSUnresolvedVariable
        var data = {
            action: 'types_settings_action',
            setting: setting_id,
            setting_value: $("input[name^='" + setting_id + "']").serialize(),
            wpnonce: Types.settings.ajaxInfo.fieldAction.nonce,
        };

        $(document).trigger('js-toolset-event-update-setting-section-triggered');

        $.ajax({
            type: "POST",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function (response) {
                if (response.success) {
                    $(document).trigger('js-toolset-event-update-setting-section-completed');
                } else {
                    $(document).trigger('js-toolset-event-update-setting-section-failed');
                }
            },
            error: function (ajaxContext) {
                $(document).trigger('js-toolset-event-update-setting-section-failed');
            },
            complete: function () {

            }
        });
    };

    $('body').on('change', '[data-types-setting-save]', function () {
        Types.settings.save($(this).attr('name'));
    });


})(jQuery);


// Controller for Knockout-controlled sections
//
//

Types.Gui = Types.Gui || {};

/**
 * Toolset Settings page controller.
 *
 * This object is also acting as the main viewmodel which holds the collection of the settings > m2m section viewmodels.
 *
 * @param $
 * @constructor
 * @since m2m
 */
Types.Gui.SettingsScreen = function ($) {

    var self = this;

    // Parent constructor
    Toolset.Gui.AbstractPage.call(self);

    /**
     * Augment self, turning it into the main viewmodel for the page.
     *
     * @returns {Types.Gui.SettingsScreen}
     * @since m2m
     */
    self.getMainViewModel = function () {

        var modelData = self.getModelData();

        if( modelData === false ) {
            return self;
        }

        /**
         * This will evaluate to true if any of the sections managed here have actions in progress.
         *
         * @since m2m
         */
        self.isActionInProgress = ko.pureComputed(function () {
            return _.reduce(self.sections, function (result, section) {
                return result || section.isInProgress();
            }, false);
        });


        // Model fields are descibed in the toolset_get_troubleshooting_sections filter inline doc.
        self.sections = {

			// Create viewmodels for m2m activation section
            m2mActivation: function() {

                var vm = {};

                /** Is the action in progress at the moment? */
                vm.isInProgress = ko.observable(false);

                vm.openActivationDialog = function () {
                    Toolset.hooks.doAction('types-open-m2m-migration-dialog');
                };

                return vm;

            }(),
		};

        // Initialize the main viewmodel.
        ko.applyBindings(self, $('.js-toolset-toolset_is_m2m_enabled').get(0) );
        return self;
    };


    self.afterInit = function () {

        // Discourage the user from leaving the page while an action is in progress.
        WPV_Toolset.Utils.setConfirmUnload(
            function () {
                if( ! self.getModelData() ) {
                    return false;
                }

                return self.isActionInProgress();
            },
            null,
            self.getString('confirmUnload')
        );

    }

};

// Make everything happen.
Types.Gui.settingsScreen = new Types.Gui.SettingsScreen(jQuery);
head.ready(Types.Gui.settingsScreen.init);
