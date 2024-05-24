/**
 * ViewModel of a related content.
 *
 * @extends Toolset.Gui.ItemViewModel
 *
 * @param {{ association_uid: int, post_id: int, displayName: string, editPage: string, fields: {input: {post: string, relationship: string}}, preview: object }} model
 *	 Field definition model.
 * @param {Object} relatedContentActions An object with methods to perform actions on field definitions.
 * @param {string} container_id The ID of the container HTML element
 * @param {Types.page.extension.relatedContent.viewmodels.ListingViewModel} listingViewModel Types.page.extension.relatedContent.viewmodels.ListingViewModel
 * @param {Object} relatedContentModels Main model data get from main.js @see Types_Page_Extension_Meta_Box_Related_Content::build_js_data()
 *
 * @since m2m
 */
Types.page.extension.relatedContent.viewmodels.RelatedContentViewModel = function(model, relatedContentActions, container_id, listingViewModel, relatedContentModels) {
	var self = this;

	// Apply the ItemViewModel constructor on this object.
	Toolset.Gui.ItemViewModel.call(self, model, relatedContentActions);

	// Can User Edit Post
    self.canUserEditPost = ko.pureComputed( function() {
    	if( ! listingViewModel.advancedUserCaps.publish && model.is_published ) {
    		// user is not allowed to touch published posts (no matter if it's his own)
			return false;
		}

        if( listingViewModel.advancedUserCaps.edit_any ) {
        	// user can edit any posts
        	return true;
		}

		if( listingViewModel.advancedUserCaps.edit_own && listingViewModel.userId == model.author_id ) {
        	// user is allowed to edit his own posts and this is one of them
			return true;
		}

		return false;
    } );

    // Can User Delete Post
    self.canUserDeletePost = ko.pureComputed( function() {
        if( ! listingViewModel.advancedUserCaps.publish && model.is_published ) {
            // user is not allowed to touch published posts (no matter if it's his own)
            return false;
        }

        if( listingViewModel.advancedUserCaps.delete_any ) {
            // user can delete any posts
            return true;
        }

        if( listingViewModel.advancedUserCaps.delete_own && listingViewModel.userId == model.author_id ) {
            // user is allowed to delete his own posts and this is one of them
            return true;
        }

        return false;
    } );

	self.isFieldVisible = listingViewModel.isFieldVisible;


	self.isPostTrashed = ( 'trash' === model.post_status );


	/**
	 * WPML post flag for post
	 *
	 * @since m2m
	 */
	self.flag = ko.observable(model.flag);


	/**
	 * WPML post flag for IPT
	 *
	 * @since m2m
	 */
	self.fieldsFlag = ko.observable(model.fieldsFlag);


	/**
	 * Currently displayed message.
	 *
	 * Text can contain HTML code. Type can be 'info' or 'error' for different message styles.
	 *
	 * @since m2m
	 */
	self.displayedMessage = ko.observable({text: '', type: 'info'});

	/**
	 * Determine how the message is being displayed at the moment.
	 *
	 * Allowed values are those of the threeModeVisibility knockout binding.
	 *
	 * @since m2m
	 */
	self.messageVisibilityMode = ko.observable('remove');


	/**
	 * Determine CSS class for the message, based on it's type.
	 *
	 * @since m2m
	 */
	self.messageNagClass = ko.pureComputed(function () {
		switch (self.displayedMessage().type) {
			case 'error':
				return 'notice-error';
			case 'warning':
				return 'notice-warning';
			case 'info':
			default:
				return 'notice-success';
		}
	});


	/**
	 * Hide the message completely.
	 *
	 * @since m2m
	 */
	self.removeDisplayedMessage = function () {
		self.messageVisibilityMode('remove');
	};

	// We're reusing the actual observable, not just its value.
	self.isPostFieldEditingEnabled = listingViewModel.isPostFieldEditingEnabled;

	/**
	 * Enables the post related fields
	 *
	 * For WYSIWYG fields, removes the overlay that covers them
	 *
	 * @param {object} object KO object
	 * @param {Event} event Event triggered
	 * @since m2m
	 */
	self.enablePostFields = function(object, event) {
		var $button = jQuery(event.target);
		//$button.parents('td').first().find("[name*='wpcf[post]']").removeAttr('disabled');
		//$button.parents('td').first().find(".js-wpt-wysiwyg-disabled-overlay").remove();
		if ($button.parents('div').first().find(':checkbox').is(':checked')) {
			var $container = $button.parents('.types-quick-edit-fields').first();
			var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;
			Types.page.extension.relatedContent.doAjax(
				'enable_fields',
				nonce,
				{},
				function() {},
				function() {}
			);
		}
		//$button.parents('td').first().find('.types-warning').slideUp();
		self.isPostFieldEditingEnabled(true);
	};


	/**
	 * Overrides
	 */
	self.isSelectedForBulkAction = function() { return true; }


	/**
	 * If it involves translatable content, it will retreview translated content and show it and ask for confirmation.
	 *
	 * @param {Function} callback Delete or disconnect callback.
	 * @param {string} title Title of the modal.
	 * @param {string} message Specific message.
	 * @param {string} actionButtonText Action button text.
	 * @since m2m
	 */
	var confirmTranslatableContent = function(callback, title, message, actionButtonText) {
		self.beginAction(model);
		var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;
		Types.page.extension.relatedContent.doAjax(
			'get_translatable_content',
			nonce,
			{
				'association_uid': model.association_uid
			},
			// Success.
			function( response ) {
				Types.page.extension.relatedContent.viewmodels.TranslatableContentDialogViewModel(callback, title, message, actionButtonText, response).display();
			},
			function() {
			}
		);
	}

	/**
	 * Disconnect row action
	 *
	 * @param {object} object KO object
	 * @param {Event} event Event triggered
	 * @since m2m
	 */
	self.onDisconnectAction = function(object, event) {
		var $button = jQuery(event.target);

		var disconnectRelationship = function(isAccepted) {
			if(isAccepted) {
				var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;

				/**
				 * Shows message
				 *
				 * @param {Object} response XHR response
				 * @param {string} type Message type
				 */
				var showMessage = function( response, type ) {
					self.finishAction(model);
					self.messageVisibilityMode('show');
					listingViewModel.displayMessage(response.data.message, type);
				}

				var successCallback = function(response) {
					showMessage( response, 'info' );
					listingViewModel.loadAjaxData(listingViewModel.getCurrentSortBy(), listingViewModel.currentPage(), true);
					listingViewModel.canConnectAnotherElement(response.data.canConnectAnother);
				}

				var errorCallback = function(response) {
					if ( ! response.data.message ) {
						response.data.message = Types.page.extension.relatedContent.strings.misc.undefinedAjaxError || 'undefined error';
					}
					showMessage( response, 'error' );
				}
				self.beginAction(model);
				Types.page.extension.relatedContent.doAjax(
					'disconnect',
					nonce,
					{
						'association_uid': model.association_uid,
						post_id: model.post_id
					},
					successCallback,
					errorCallback
				);
			} else {
				self.finishAction(model);
			}
		};

		if ( listingViewModel.hasTranslatableContent() ) {
			confirmTranslatableContent(
				disconnectRelationship,
				Types.page.extension.relatedContent.strings.misc['disconnectRelatedContent'],
				Types.page.extension.relatedContent.strings.misc['doYouReallyWantDisconnect'],
				Types.page.extension.relatedContent.strings.button['disconnect']
			);
		} else if ( !model.has_intermediary_fields ) {
			// The relationship doesn't have fields so it can be disconnected without warning
			disconnectRelationship(true);
		} else {
			Types.page.extension.relatedContent.viewmodels.DisconnectDialogViewModel(disconnectRelationship).display();
		}
	};


	/**
	 * Trash row action
	 *
	 * @since m2m
	 */
	self.onDeleteAction = function() {

		var deleteRelationship = function(isAccepted) {
			if(isAccepted) {
				var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;

				var successCallback = function(response) {
					self.finishAction(model);
					self.messageVisibilityMode('show');
					listingViewModel.displayMessage(response.data.message, 'info');
					listingViewModel.loadAjaxData(listingViewModel.getCurrentSortBy(), listingViewModel.currentPage(), true);
					listingViewModel.canConnectAnotherElement(response.data.canConnectAnother);
				};
				self.beginAction(model);
				Types.page.extension.relatedContent.doAjax(
					'delete',
					nonce,
					{
						'association_uid': model.association_uid,
						'post_id': model.post_id
					},
					successCallback,
					function() {}
				);
			}
		};

		if ( listingViewModel.hasTranslatableContent() ) {
			confirmTranslatableContent(
				deleteRelationship,
				Types.page.extension.relatedContent.strings.misc['deleteRelatedContent'],
				Types.page.extension.relatedContent.strings.misc['doYouReallyWantTrash'],
				Types.page.extension.relatedContent.strings.button['delete']
			);
		} else {
			deleteRelationship(true);
		}
	};

	self.onRestoreAction = function() {
		self.beginAction( model );
		Types.page.extension.relatedContent.doAjax(
			'restore',
			Types.page.extension.relatedContent.ajaxInfo.nonce,
			{
				'association_uid': model.association_uid,
				'post_id': model.post_id,
			},
			function( response ) {
				self.finishAction( model );
				self.messageVisibilityMode( 'show' );
				listingViewModel.displayMessage( response.data.message, 'info' );
				listingViewModel.loadAjaxData( listingViewModel.getCurrentSortBy(), listingViewModel.currentPage(), true );
			},
			function() {
			},
		);
	};


	/**
	 * Shows quick edit container
	 *
	 * @param {object} object KO object
	 * @param {Event} event Event triggered
	 * @since m2m
	 */
	self.showQuickEdit = function(object, event) {
		var $container = jQuery(event.target).parents('tr').first().next().show().find('.types-quick-edit-fields');
		wptDate.init( $container[0] );
		// Nested forms are not valid and the browser removes the child one during loading, so this workaround is needed.
		var $fakeForm = $container.parent(); // It should be a form, but it is created as a div.
		if ( ! $fakeForm.parent().is('form') ) {
			var id = $fakeForm.attr('id');
			$fakeForm.removeAttr('id');
			$container.wrap( '<form id="' + id + '">' );
		}
		$container.slideDown();
		listingViewModel.activateFieldJS(self.relatedContentFormID());
		// Init repetitive fields.
		if ( typeof wptRep !== 'undefined' ) {
			wptRep.init();
		}

		// Hide fields when group terms doesn't match with post terms
		var postId = $container.find('[name=post_id]').val();
		var disabledFieldByPost = relatedContentModels.relatedContent.disabled_fields_by_post;
		if ( !! disabledFieldByPost[ postId ] ) {
			disabledFieldByPost[ postId ].forEach( function( inputId ) {
				$container.find('[data-wpt-name="wpcf[post][' + inputId + ']"]').parents('.js-wpt-field').first().remove();
			} );
		}
		// Set fields conditionals
		var formId = '#' + $container.parent().attr('id');

		wptCondTriggers[formId] = {};
		wptCondFields[formId] = {};
		relatedContentModels.relatedContent.conditionals.forEach( function( conditional) {
			var data = {};
			data[formId] = conditional;
			wptCond.addConditionals( data );
		});
		wptCond.check(formId, Object.keys(wptCondFields[formId]));
		// Conditionals.js enables fields that may be disabled initially
		if ( ! self.isPostFieldEditingEnabled() && $container.find('.types-warning:visible').length ) {
			$container.find('[data-bind*=disableInputs]').find("[name*='wpcf[post]']").attr('disabled', 'disabled');
		}

		// Repetitive elements have to add [post] or [relationship] to their template.
		$container.find('.js-wpt-repadd').each(function() {
			var $this = jQuery(this);
			var type = $this.parents('.types-new-relationship-block, .types-quick-edit-fields-block').first().attr('rel');
			var $tpl = jQuery('#tpl-wpt-field-' + $this.data('wpt-id'));
			if ( ! $tpl.data('modified') ) {
				var html = $tpl.html();
				$tpl.html(
					html.replace(/name="wpcf/g, 'name="wpcf[' + type + ']')
                    .replace(/name="_wptoolset_checkbox/g, 'name="_wptoolset_checkbox[' + type + ']')
				);
				$tpl.data('modified', true);
			}
		});
	}


	/**
	 * Hides the quick edit layer
	 *
	 * @param {object} object KO object
	 * @param {Event} event Event triggered
	 * @since m2m
	 */
	self.onCancelQuickEdit = function(object, event) {
		jQuery(event.target).parents('.types-quick-edit-fields').first().slideUp(function() {
			jQuery(this).parents('tr').first().hide();
		});
	}


	/**
	 * Hides the quick edit layer
	 *
	 * @param {object} object KO object
	 * @param {Event} event Event triggered
	 * @since m2m
	 */
	self.onUpdateQuickEdit = function(object, event) {
		var $button = jQuery(event.target);
		$button.next().css('visibility', 'visible');
		var $container = $button.parents('.types-quick-edit-fields').first();
		// Do form validation.
		if ( ! $container.parent().valid() ) {
			$button.next().css('visibility', 'hidden');
			return false;
		}

		if ( typeof tinyMCE !== 'undefined' ) {
			tinyMCE.triggerSave();
		}
		// Restore radio buttons names
		$container.find( ':radio' ).each( function() {
			var $this = jQuery(this);
			$this.attr( 'name', $this.data( 'previousName' ) );
		} );

		// We need to make sure the submitanyway library works for this form as well, to preserve the correct behavior
		// when saving conditionally hidden fields.
		jQuery( $container.parent() ).each( function() {
			Toolset.Types.SubmitAnyway.onsubmit( this );
		})

		var formData = $container.parent().find('select, input, textarea, hidden').serializeArray();
		var nonce = Types.page.extension.relatedContent.ajaxInfo.nonce;
		self.messageVisibilityMode('hide');

		var callback = function(messageType, message, resultCallback, response, data) {
			self.messageVisibilityMode('show');
			var finalMessage = typeof data.message !== 'undefined'
				? data.message
				: message;
			var finalMessageType = typeof data.messageType !== 'undefined'
				? data.messageType
				: messageType;
			self.displayedMessage({text: finalMessage, type: finalMessageType});
			$button.next().css('visibility', 'hidden');
			resultCallback(data);
		};

		var successCallback = function(data) {
			model = data.results[0];
			self.updateModelObject(model);
			self.previewData(model.fields.preview)
		}

		var failCallback = function() {
			// Do nothing for now
		}

		Types.page.extension.relatedContent.doAjax(
			'update',
			nonce,
			formData || {},
			_.partial(callback, 'info', Types.page.extension.relatedContent.strings.misc.relatedContentUpdated || '', successCallback),
			_.partial(callback, 'error', Types.page.extension.relatedContent.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
		);
	}


	/**
	 * Returns the edit post page.
	 *
	 * @since m2m
	 */
	self.editPostLink = function() {
		return model.editPage;
	}


	/**
	 * Handles columns data refreshing
	 */
	self.previewData = ko.observable(model.fields.preview);


	/**
	 * Displays the value of a field
	 *
	 * @param {string} slug The field slug
	 * @param {string} type The type of field: post or relationship
	 * @return {string} field value
	 * @since m2m
	 */
	self.displayFieldValue = function(type, slug) {
		return ko.computed(function () {
			if ( 'relatedPosts' === type ) {
				var related = model['relatedPosts'][slug];
				return !_.isUndefined(related)
					? '<a href="' + related['editPage'] + '">' + related['displayName'] + '</a>'
					: '';
			}
			return !_.isUndefined(self.previewData()[type][slug])
				? self.previewData()[type][slug].rendered
				: '';
		}, this)();
	}


	/**
	 * Fields are grouped by wpcf: wpcf[book], wpcf[artist], ...
	 * The Ajax calld needs to send the inputs fields in two different groups:
	 * post and relationship
	 * post represents the post fields and relationship the relatoinship fields and they can't be mixed
	 * This function adds a new level before the field name: wpcf[my_type][book], wpcf[my_type][artist]
	 *
	 * @param {string} html HTML containing the fields
	 * @param {string} type The new level for input names
	 * @return {string} HTML content
	 * @since m2m
	 */
	function groupFieldsByType(html, type) {
		return html
			// visible field inputs
			.replace(/wpcf\[/g, 'wpcf[' + type + '][')
			// hidden field inputs for checkboxes / checkbox (required for saving empty or 0)
			.replace(/_wptoolset_checkbox\[/g, '_wptoolset_checkbox[' + type + '][');
	}


	/**
	 * Displays the HTML container with the edit fields
	 *
	 * @param {string} type Posts or relationships
	 * @return {string} HTML container
	 * @since m2m
	 */
	self.displayEditFields = function(type) {
		return !_.isUndefined(model.fields.input[type])
			? groupFieldsByType(model.fields.input[type], type)
			: '';
	};

    /**
	 * Function to check if relationship fields are available or not
	 *
     * @returns {boolean}
     */
	self.hasRelationshipFields = function() {
		if( model.fields.input['relationship'] === '' ){
			return false;
		}

		return true;
	};

	/**
	 * Returns post ID
	 */
	self.relatedContentPostId = function() {
		return model.post_id;
	}


	/**
	 * Returns association UID
	 */
	self.relatedContentAssociationUID = function() {
		return model.association_uid;
	}


	self.relatedContentFormID = function() {
		return 'types-related-content-quickedit-' + self.relatedContentAssociationUID();
	}
	/**
	 * Returns the warning label id based on association UID
	 */
	self.warningLabelId = function() {
		return 'dont_block_' + model.association_uid;
	}


	/**
	 * Returns associated post role
	 */
	self.relatedContentRole = function() {
		return model.role;
	}


	/**
	 * Returns associated post fields title
	 */
	self.postFieldsTitle = function() {
		return model.strings.titles.postHeading;
	}


	/**
	 * Returns associated post label ID
	 */
	self.postTitleId = function() {
		return 'post-title-' + model.association_uid;
	};


	/**
	 * Returns associated post title label
	 */
	self.postTitleLabel = function() {
		return model.strings.titles.postTitleLabel;
	};


	/**
	 * Returns associated post title value
	 */
	self.postTitleValue = function() {
		return model.displayName;
	};


	jQuery('form#post').on( 'submit', function() {
		var valid = true;
		jQuery('[id^=types-related-content-quickedit]:visible').each(function() {
			valid = valid && jQuery(this).valid();
		});
		return valid;
	});
};
