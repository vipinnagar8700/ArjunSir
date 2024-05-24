/**
 * Viewmodel of the dialog for disconnect association.
 *
 * Requires the 'types-disconnect-association-related-content-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *	 a boolean determining whether user has accepted the change.
 * @since 2.3
 */
Types.page.extension.relatedContent.viewmodels.DisconnectDialogViewModel = function(closeCallback) {

	var self = this;

	/**
	 * Display the dialog.
	 */
	self.display = function() {

		var cleanup = function(dialog) {
			jQuery(dialog.$el).ddldialog('close');
			ko.cleanNode(dialog.el);
		};

		var dialog = Types.page.extension.relatedContent.main.createDialog(
			'types-disconnect-association-related-content-dialog',
			Types.page.extension.relatedContent.strings.misc['disconnectRelatedContent'],
			{},
			[
				{
					text: Types.page.extension.relatedContent.strings.button['disconnect'],
					click: function() {
						cleanup(dialog);
						closeCallback(true);
					},
					'class': 'button toolset-danger-button'
				},
				{
					text: Types.page.extension.relatedContent.strings.button['cancel'],
					click: function() {
						cleanup(dialog);
						closeCallback(false);
					},
					'class': 'button wpcf-ui-dialog-cancel'
				}
			]
		);

		ko.applyBindings(self, dialog.el);
	};


	return self;
};
