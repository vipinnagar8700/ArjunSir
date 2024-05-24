/**
 * Viewmodel of the dialog for an action confirmation for translatable.
 *
 * Requires the 'types-translatable-content-related-content-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 * a boolean determining whether user has accepted the change.
 * @param {string} title Title of the modal.
 * @param {string} message Specific message.
 * @param {String} actionButtonText Action button text
 * @param {Object} response Ajax response object.
 * @since 2.3
 */
Types.page.extension.relatedContent.viewmodels.TranslatableContentDialogViewModel = function(closeCallback, title, message, actionButtonText, response) {

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
			'types-translatable-content-related-content-dialog',
			title,
			_.extend(response.data, {message: message}),
			[
				{
					text: actionButtonText,
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
