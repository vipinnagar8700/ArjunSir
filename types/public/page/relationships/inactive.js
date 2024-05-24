var Types = Types || {};
Types.Gui = Types.Gui || {};

/**
 * Page controller for the Relationships page if m2m is not enabled.
 *
 * Just open the m2m migration dialog when the button is clicked.
 *
 * @constructor
 */
Types.Gui.InactiveRelationshipScreen = function() {

    var self = this;

    self.openMigrationDialog = function() {
        Toolset.hooks.doAction('types-open-m2m-migration-dialog');
    };

    self.init = function() {
        jQuery(document).on('click', '.types-relationships-inactive__run-migration-button', self.openMigrationDialog);
    }
};


// Make everything happen.
Types.Gui.inactiveRelationshipScreen = new Types.Gui.InactiveRelationshipScreen();
head.ready(Types.Gui.inactiveRelationshipScreen.init);
