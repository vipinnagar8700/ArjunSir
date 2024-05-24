/**
 * Viewmodel of the dialog for selecting which fields are displayed.
 *
 * Requires the 'types-select-fields-related-content-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @since m2m
 */
Types.page.extension.relatedContent.viewmodels.SelectFieldsDisplayedDialogViewModel = function(relatedContentModel, listingModel) {

	var self = this;

	self.isFieldVisible = listingModel.isFieldVisible;


	/**
	 * Shows a WP pointer
	 *
	 * @param {HTMLElement} el The element bounded
	 *
	 * @since m2m
	 */
	self.showPointer = function(el) {
		var $this = jQuery(el);

		// default options
		var defaults = {
			edge: "left", // on which edge of the element tooltips should be shown: ( right, top, left, bottom )
			align: "middle", // how the pointer should be aligned on this edge, relative to the target (top, bottom, left, right, middle).
			offset: "15 0 " // pointer offset - relative to the edge
		};

		// custom options passed in HTML "data-" attributes
		var custom = {
			edge: $this.data('edge'),
			align: $this.data('align'),
			offset: $this.data('offset')
		};

		self.hideWPPointers();
		var content = '<p>' + $this.data('content') + '</p>';
		if ($this.data('header')) {
			content = '<h3>' + $this.data('header') + '</h3>' + content;
		}

		var extraClass = $this.hasClass('types-pointer-tooltip') ? ' types-pointer-tooltip' : '';

		var $pointer = $this.pointer({
			pointerClass: 'wp-toolset-pointer wp-toolset-types-pointer' + extraClass,
			content: content,
			position: jQuery.extend(defaults, custom) // merge defaults and custom attributes
		});
		$pointer.pointer('open');
		$pointer.pointer('widget').css('z-index', 10000000);
	};


	/**
	 * Hides WP Pointers
	 */
	self.hideWPPointers = function() {
		jQuery('.wp-toolset-pointer').hide();
	};


	/**
	 *
	 */
	self.handleAllSelected = function( event, sender ) {
		var $dialog = jQuery( self.dialog.el );
		var $selectAll = $dialog.find( '#select-all-' + relatedContentModel.relationship_slug );
		var $inputs = $dialog.find( 'input:checkbox[name^=field]:not(:disabled)' );
		if ( $selectAll[0] === sender.target ) {
			// Clicking on select all
			$inputs.prop( 'checked', $selectAll.is( ':checked' ) );
		} else {
			// Clickning on other inputs
			var all = $inputs.filter( ':checked' ).length === $inputs.length;
			$selectAll.prop( 'checked', all );
		}
		return true;
	};


	/**
	 * Ajax call for updating list of selected fields
	 *
	 * @since m2m
	 */
	self.onApplySelectedFields = function() {
		var $container = jQuery( self.dialog.$el );

		var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;
		var $inputs = $container.find( 'input:checkbox[name^=field]:not(:disabled), input[data-rel=ajax]' );
		var formData = $inputs.serializeArray();

		var callback = function(messageType, message, resultCallback, response, data) {
			if ( messageType === 'error' ) {
				listingModel.displayMessage(message, messageType);
			}
			resultCallback(data);
		};

		var successCallback = function(data) {
			listingModel.visibleFields( data );
		};

		var failCallback = function() {
			// Do nothing for now
		};

		Types.page.extension.relatedContent.doAjax(
			'update_fields_displayed',
			nonce,
			formData || {},
			_.partial(callback, 'info', '', successCallback),
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
			jQuery(dialog.$el).ddldialog('close').remove();
			ko.cleanNode(dialog.el);
			self.hideWPPointers();
		};

		self.dialog_id = 'types-select-fields-related-content-dialog-' + relatedContentModel.relationship_slug;

		var buttons = [];
        var thereAreFields = relatedContentModel.relatedContent.columns.post.length || relatedContentModel.relatedContent.columns.relationship.length || relatedContentModel.relatedContent.columns.relatedPosts.length;
		if ( thereAreFields ) {
			buttons.push( {
				text: relatedContentModel.strings.button['apply'],
				click: function() {
					self.onApplySelectedFields();
					cleanup(self.dialog);
				},
				'class': 'button button-primary'
			} );
		}
		buttons.push( {
			text: relatedContentModel.strings.button['cancel'],
			click: function() {
				cleanup(self.dialog);
			},
			'class': ! thereAreFields ? 'prueba' : 'kk button wpcf-ui-dialog-cancel'
		} );

		self.dialog = Types.page.extension.relatedContent.main.createDialog(
            self.dialog_id,
            relatedContentModel.strings.misc.selectFieldsTitle,
            {},
            buttons,
			{
				dialogClass: 'toolset-dialog-columns-to-be-displayed'
			}
        );

		// Needs to be called after open it in order to update "select all".
		self.updateSelectAll = function() {
			var mockSender = {
				target: jQuery( self.dialog.$el ).find( 'input:checkbox[name^=field]:not(:disabled)' ).first()[0]
			}
			self.handleAllSelected( null, mockSender );
		}

		ko.applyBindings(self, self.dialog.el);
	};


	return self;
};
