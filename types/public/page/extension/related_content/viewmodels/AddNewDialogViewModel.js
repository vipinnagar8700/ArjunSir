/* eslint-disable */

/**
 * Viewmodel of the dialog for adding new association.
 *
 * Requires the 'ttypes-new-content-related-content-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @since m2m
 */
Types.page.extension.relatedContent.viewmodels.AddNewDialogViewModel = function(relatedContentModel, openCallback, listingModel) {

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
	 * Is page in a different language than default one?
	 *
	 * @return {boolean}
	 * @since m2m
	 */
	self.isMissingDefaultLanguage = ko.observable(
		! listingModel.isDefaultLanguage() && listingModel.requiresDefaultLanguageToConnect()
	);


	/**
	 * Ajax call for inserting the new related post type and its intemediary post fields
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
		if ( typeof tinyMCE !== 'undefined' ) {
			tinyMCE.triggerSave();
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
			listingModel.displayMessage(message, messageType);
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
			'insert',
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

		self.dialog_id = 'types-new-content-related-content-dialog-' + relatedContentModel.relationship_slug;

        var maxDialogWidth = relatedContentModel.relatedContent.columns.relationship.length > 0
            ? 1200 // with relationship fields
            : 600; // without relationship fields

		var dialog = Types.page.extension.relatedContent.main.createDialog(
			self.dialog_id,
			relatedContentModel.strings.misc['addNew'],
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
				dialogClass: 'toolset-dialog-new-related-content',
				width: jQuery( window ).width() < maxDialogWidth + 50 ? jQuery( window ).width() - 50 : maxDialogWidth
			}
		);

		dialog.$el.on('ddldialogclose', function () {
            // Putting this on the Close button action is not enough, there are other ways to close the dialog.
            cleanup(dialog);
        });


        /**
         * Subscribe to title to enable/disable the button
         */
        self.newAssociationTitle = ko.observable();
        self.newAssociationTitle.subscribe(function (value) {
            // Compatibility issues here:
            // (1) jQuery.fb.button conflicts with Twitter Bootstrap (https://github.com/twbs/bootstrap/issues/6094)
            //     This cannot be used: saveButton.button( value ? 'enable' : 'disable' );
            // (2) jQuery datepicker may cause conflicts if we just add/remove the 'disabled' attribute:
            //     saveButton.removeAttr( 'disabled' ); saveButton.attr( 'disabled' );
            //     This was happening especially with ICL-MPP @ oursystem-6809. We also need to add and remove
            //     ui-* classes related to the button state.
            var $button = jQuery(dialog.$el).next().find('button').first();
            if (value) {
                $button.prop('disabled', false).removeClass('ui-button-disabled').removeClass('ui-state-disabled');
            } else {
                $button.prop('disabled', true).addClass('ui-button-disabled').addClass('ui-state-disabled');
            }
        });


		// Needs to be called after open it in order to run the fields onload scripts.
		dialog['$el'].on('open', function() {
			openCallback(jQuery(this));
			// Init repetitive fields.
			if ( typeof wptRep !== 'undefined' ) {
				wptRep.init();
			}
			wptDate.init( this );

			// field conditions
            var formId = '#types-new-content-related-content-dialog-container-' + relatedContentModel.relationship_slug;

            wptCondTriggers[formId] = {};
            wptCondFields[formId] = {};

            relatedContentModel.relatedContent.conditionals.forEach( function( conditional) {
                var data = {};
                data[formId] = conditional;
                wptCond.addConditionals( data );
            });

            relatedContentModel.relatedContent.disabled_fields_all.forEach( function( inputId ) {
                dialog.$el.find('[data-wpt-name="wpcf[post][' + inputId + ']"]').parents('.js-wpt-field').first().remove();
            } );

            wptCond.check(formId, Object.keys(wptCondFields[formId]));
        }).trigger('open');

		// Repetitive elements have to add [post] or [relationship] to their template.
		jQuery(dialog.$el).find('.js-wpt-repadd').each(function() {
			var $this = jQuery(this);
			var type = $this.parents('.types-new-relationship-block').first().attr('rel');
			var $tpl = jQuery('#tpl-wpt-field-' + $this.data('wpt-id'));
			if ( ! $tpl.data('modified') ) {
				var html = $tpl.html();
				$tpl.html(
					html.replace('name="wpcf', 'name="wpcf[' + type + ']')
						.replace('name="_wptoolset_checkbox', 'name="_wptoolset_checkbox[' + type + ']')
				);
				$tpl.data('modified', true);
			}
		});

		ko.applyBindings(self, dialog.el);

	};


	return self;
};
