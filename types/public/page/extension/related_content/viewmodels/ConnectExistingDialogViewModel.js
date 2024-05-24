/**
 * Viewmodel of the dialog for connecting an existing content.
 *
 * Requires the 'types-connect-existing-content-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 *     a boolean determining whether user has accepted the change.
 * @since m2m
 *
 * @param relatedContentModel
 * @param openCallback
 * @param listingModel
 */
Types.page.extension.relatedContent.viewmodels.ConnectExistingDialogViewModel = function(relatedContentModel, openCallback, listingModel) {

	var self = this;


	/**
	 * Gets relationship slug
	 *
	 * @return {string}
	 * @since m2m
	 */
	self.relationshipSlug = function() {
		return relatedContentModel.relationship_slug;
	};


	/**
	 * Ajax call for inserting the new related post and its intemediary post fields
	 *
	 * @param {object} dialog The dialog box
	 * @since m2m
	 */
	self.onSaveNewRelationship = function(dialog) {
		var $container = jQuery(dialog.$el);
		// Do form validation.
		if ( ! $container.find('form').valid() ) {
			return false;
		}

		if ( $container.find('textarea.wpt-wysiwyg').length > 0 ) {
			if ( undefined !== window.tinyMCE ) {
				//update tinyMCE fields base html textarea value
				tinyMCE.triggerSave();
			}
		}

		var formData = $container.find('input, textarea, select').serializeArray();
		var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;
		var callback = function(_messageType, _message, resultCallback, response, data) {
			var message = typeof data.message !== 'undefined'
				? data.message
				: _message;
			var messageType = typeof data.messageType !== 'undefined'
				? data.messageType
				: _messageType;
			listingModel.displayMessage( message, messageType);
			resultCallback(data);
			listingModel.isMainSpinnerVisible(false);
		};

		var successCallback = function(data) {
			var model = data.results[0];
			listingModel.loadAjaxData(listingModel.getCurrentSortBy(), listingModel.currentPage(), true);
			listingModel.canConnectAnotherElement(model.canConnectAnother);
		};

		var failCallback = function() {
			// Do nothing for now
		};

		listingModel.isMainSpinnerVisible(true);
		Types.page.extension.relatedContent.doAjax(
			'connect',
			nonce,
			formData || {},
			_.partial(callback, 'info', Types.page.extension.relatedContent.strings.misc.relatedContentUpdated || '', successCallback),
			_.partial(callback, 'error', Types.page.extension.relatedContent.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
		);

		return true;
	};

	/**
	 * Display the dialog.
	 *
	 * @since m2m
	 */
	self.display = function() {

		var cleanup = function(dialog) {
			var formId = dialog.$el.find('form').attr('id');
			initialisedCREDForms = initialisedCREDForms.filter(function(item) {
				return item !== formId;
			});
			jQuery(dialog.$el).ddldialog('close').remove();
			ko.cleanNode(dialog.el);
		};

		self.dialog_id = 'types-connect-existing-content-dialog-' + relatedContentModel.relationship_slug;

		var maxDialogWidth = relatedContentModel.relatedContent.columns.relationship.length > 0
			? 1200 // with relationship fields
			: 600; // without relationship fields

		var dialog = Types.page.extension.relatedContent.main.createDialog(
			self.dialog_id,
			relatedContentModel.strings.misc['connectExisting'],
			{},
			[
				{
					text: relatedContentModel.strings.button['save'],
					disabled: true,
					click: function() {
						if ( self.onSaveNewRelationship(dialog) ) {
							cleanup(dialog);
						}
					},
					'class': 'button button-primary'
				},
				{
					text: relatedContentModel.strings.button['cancel'],
					click: function() {
						cleanup(dialog);
					},
					'class': 'button wpcf-ui-dialog-cancel'
				}
			],
			{
				dialogClass: 'toolset-dialog-connect-related-content',
                width: jQuery( window ).width() < maxDialogWidth + 50 ? jQuery( window ).width() - 50 : maxDialogWidth
			}
		);

		dialog.$el.on('ddldialogclose', function () {
            // Putting this on the Close button action is not enough, there are other ways to close the dialog.
            cleanup(dialog);
        });

		// disable autofocus of jquery dialog, because this makes it impossible to focus the select2 input
        var backupjQueryUiDialogPrototypeFocusTabbable = jQuery.ui.dialog.prototype._focusTabbable;
        jQuery.ui.dialog.prototype._focusTabbable = jQuery.noop;

        // - reapply default behaviour of _focusTabbable when the dialog is closed
        dialog.$el.on('ddldialogclose', function () {
            jQuery.ui.dialog.prototype._focusTabbable = backupjQueryUiDialogPrototypeFocusTabbable;
        } );

		// Needs to be called after open it in order to run the fields onload scripts.
		dialog['$el'].on('open', function() {
			openCallback(jQuery(this));
			// Init repetitive fields.
			if ( typeof wptRep !== 'undefined' ) {
				wptRep.init();
			}
			wptDate.init( this );
			self.afterDialogRendered();
		}).trigger('open');

		// Repetitive elements have to add [post] or [relationship] to their template.
		jQuery(dialog.$el).find('.js-wpt-repadd').each(function() {
			var $this = jQuery(this);
			var type = $this.parents('.types-new-relationship-block').first().attr('rel');
			var $tpl = jQuery('#tpl-wpt-field-' + $this.data('wpt-id'));
			if ( ! $tpl.data('modified') ) {
				var html = $tpl.html();
				$tpl.html( html.replace('name="wpcf', 'name="wpcf[' + type + ']') );
				$tpl.data('modified', true);
			}
		});

		ko.applyBindings(self, dialog.el);
	};

	/**
	 * Needs to add some conditionals after the dialog is rendered, 'open' event is before it, so a new binding is neccesary.
	 */
	self.afterDialogRendered = function() {
			// Set fields conditionals
			var formId = '#types-connect-existing-content-dialog-container-' + relatedContentModel.relationship_slug;

			wptCondTriggers[formId] = [];
			wptCondFields[formId] = [];
			relatedContentModel.relatedContent.conditionals.forEach( function( conditional) {
				var data = {};
				data[formId] = conditional;
				wptCond.addConditionals( data );
			});
	};

	return self;
};
