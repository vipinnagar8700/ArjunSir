
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
Types.page.relationships.viewmodels.dialogs.ConfirmChangeCardinality = function( relationshipSlug, intermediaryPostType, newCardinality, closeCallback ) {

    var self = this;

    var strings = Types.page.relationships.strings['changeCardinalityDialog'];

    self.dialog = null;
    self.postTypeDisplayName = ko.pureComputed(function() { return intermediaryPostType.type; });
    self.alertVisible = ko.observable( true );
    self.progressVisible = ko.observable( false );
    self.totalAmount = ko.observable( 0 );
    self.progressAmount = ko.observable( 0 );
    self.remainingAmount = ko.observable( 0 );
    self.newCardinality = ko.observable( newCardinality );
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
            text: strings['apply_and_save'],
            click: function() {
                closeCallback('delete');
            },
            'class': 'button button-primary types-confirm-change-cardinality js-types-delete-ipts-button'
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
            'types-confirm-cardinality-change',
            strings.title,
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
