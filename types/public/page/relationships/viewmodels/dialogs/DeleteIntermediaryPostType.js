
Types.page.relationships.viewmodels.dialogs = Types.page.relationships.viewmodels.dialogs || {};

/**
 * A dialog that asks for confirmation before deleting an IPT.
 *
 * Possible results are 'delete'. Anything else means the dialog was canceled.
 *
 * @param relationshipSlug
 * @param intermediaryPostType
 * @param {function} closeCallback
 * @returns {Types.page.relationships.viewmodels.dialogs.DeleteIntermediaryPostType}
 * @constructor
 * @since m2m
 */
Types.page.relationships.viewmodels.dialogs.DeleteIntermediaryPostType = function( relationshipSlug, intermediaryPostType, closeCallback ) {

    var self = this;

    var strings = Types.page.relationships.strings['deleteIntermediaryPostTypeDialog'];

    self.dialog = null;
    self.postTypeDisplayName = ko.pureComputed(function() { return intermediaryPostType.type; });
    self.alertVisible = ko.observable( true );
    self.progressVisible = ko.observable( false );
    self.totalAmount = ko.observable( 0 );
    self.progressAmount = ko.observable( 0 );
    self.remainingAmount = ko.observable( 0 );
    self.progressAssociationsAmount = ko.observable( 0 );
    self.totalAssociationsAmount = ko.observable( 0 );
    self.remainingAssociationsAmount = ko.observable( 0 );
    /**
     * keeps track of the process status
     * @type bool
     */
    self.deleteProcessCompleted = ko.observable( false );
    /**
     * keeps track of the number of groups deleted
     * @type int
     */
    self.deletedGroups = ko.observable( 0 );

    /**
     * Display the dialog.
     */
    self.display = function() {

        var dialogButtons = [];

        dialogButtons.push({
            text: strings['delete'],
            click: function() {
                closeCallback('delete');
            },
            'class': 'button toolset-danger-button types-delete-ipts-btn js-types-delete-ipts-button'
        });
        dialogButtons.push({
            text: strings['finish'],
            click: function() {
                self.cleanup();
                closeCallback('finish');
            },
            'class': 'button button-primary types-finish-delete-ipts-btn hidden'
        });
        dialogButtons.push({
            text: strings['cancel'],
            click: function() {
                self.cleanup();
                closeCallback('cancel');
            },
            'class': 'button wpcf-ui-dialog-cancel'
        });

        self.dialog = Types.page.relationships.main.createDialog(
            'types-confirm-post-type-deleting',
            strings.title + ' "' + intermediaryPostType.type + '"',
            {},
            dialogButtons
        );

        ko.applyBindings(self, self.dialog.el);
    };

    self.cleanup = function( ) {
        self.dialog.$el.ddldialog('close');
        ko.cleanNode(self.dialog.el);
    };

    return self;

};
