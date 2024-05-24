/* eslint-disable */

/**
 * Main ViewModel of the Related Content page.
 *
 * Holds the collection of related content ViewModels
 *
 * @param relatedContentModels
 * @constructor
 * @since m2m
 */
Types.page.extension.relatedContent.viewmodels.ListingViewModel = function(relatedContentModels) {
	var self = this;
	var currentShownPage = 1;

	// Apply the generic listing viewmodel.
	Toolset.Gui.ListingViewModel.call(
		self,
		relatedContentModels,
		{
			sortBy: 'displayName',
			itemsPerPage: relatedContentModels.itemsPerPage
		},
		function(relatedContent, searchString) {
			return relatedContent;
		}
	);


	/**
	 * Overrides search filtering
	 *
	 * @since m2m
	 */
	self.itemsFilteredBySearch = function() {
		return self.items();
	};


	/**
	 * Items found
	 *
	 * @since m2m
	 */
	self.itemsFound = ko.observable(relatedContentModels.relatedContent.items_found);


	/**
	 * Overrides total Pages
	 *
	 * @since m2m
	 */
	self.totalPages = ko.computed(function() {
		return Math.max(Math.ceil(self.itemsFound() / self.itemsPerPage()), 1);
	});


	/**
	 * Returns if it is a 'one' relatec conecction
	 *
	 * @since m2m
	 */
	self.isOnlyOneRelatedConection = ko.observable( relatedContentModels.onlyOneRelatedConection );


	self.isPostFieldEditingEnabled = ko.observable( relatedContentModels.enablePostFieldEditing );


	/**
	 * Returns if the main table is visible
	 *
	 * @since m2m
	 */
	self.isMainTableVisible = ko.computed(function() {
		return self.itemsFound() || ! self.isOnlyOneRelatedConection();
	});


	/**
	 * Overrides total Pages, needed because toolsetcommon twig pagination template
	 *
	 * @since m2m
	 */
	self.itemCount = ko.computed(function() {
		return self.itemsFound();
	});


	/**
	 * Returns main table style, depending on `onlyOneRelatedConection`
	 *
	 * @since m2m
	 */
	self.mainTableStyle = function() {
		return relatedContentModels.onlyOneRelatedConection
			? 'types-related-content-only-one-relations'
			: 'types-related-content-several-relations';
	};



	/**
	 * Contains the fields that are going to be visible
	 */
	self.visibleFields = ko.observable( relatedContentModels.relatedContent.fieldsListing );

	/**
	 * Checks if a field is visible for a specific type (post or relationship)
	 *
	 * @param {string} type Block type: post or relationship
	 * @param {string} field_slug Field slug
	 * @since m2m
	 */
	self.isFieldVisible = function( type, field_slug ) {
		return ko.computed({
			read: function() {
				return self.visibleFields()[ type ].includes( field_slug );
			}
		}, this);
	};


	/**
	 * Refresh data returned by an Ajax call
	 *
	 * @since m2m
	 */
	var refreshAjaxData = function(data) {
		self.itemsFound(data.relatedContent.items_found);
		relatedContentModels.relatedContent.items_found = data.items_found;
		relatedContentModels.relatedContent.disabled_fields_by_post = data.relatedContent.disabled_fields_by_post;
		relatedContentModels.relatedContent.disabled_fields_all = data.relatedContent.disabled_fields_all;
		self.createItemViewModels(data);
	};


	/**
	 * Overrides itemsToShow
	 *
	 * Always shows all the elements but loads the elements via Ajax
	 *
	 * @since m2m
	 */
	self.itemsToShow = ko.pureComputed(function (koObject, event) {
		if (self.currentPage() !== currentShownPage) {
			self.loadAjaxData(self.getCurrentSortBy(), self.currentPage(), true);
		}
		currentShownPage = self.currentPage();
		// If page is changed it shows the same results but the will be updated via Ajax.
		// In order to keep ES5 compatibility, ES6 async functions are not used
		return self.items();

	});


	/**
	 * Overrides listing sort using Ajax
	 *
	 * @since m2m
	 */
	var parentOnSort = self.onSort;
	self.onSort = function (propertyName) {
		if ( ! relatedContentModels.onlyOneRelatedConection ) {
			self.loadAjaxData(propertyName, 1, false);
			parentOnSort(propertyName);
		}
	};


	/**
	 * Loads data via Ajax
	 *
	 * @param {string} propertyName Slug of the column
	 * @param {int} page Page number
	 * @param {boolean} refresh If true the listing will be refresh, without sorting
	 */
	self.loadAjaxData = function(propertyName, page, refresh) {
		var $pageSpinner = jQuery('#types-related-content-' + relatedContentModels.relationship_slug + ' .toolset-page-spinner'),
		isDescending = self.sortIconClass(propertyName).match(/-desc/),
		isInactive = self.sortIconClass(propertyName).match(/-inactive/);

		$pageSpinner.addClass('overlay').fadeIn();
		self.isMainSpinnerVisible(true);

		var newDirection = 'ASC';
		if ( refresh ) {
			// Not sorting, the direction doesn't change.
			newDirection = isDescending ? 'DESC' : 'ASC';
		} else {
			if ( isInactive) {
				// If it is not the previously selected column, order by ASC
				newDirection = 'ASC';
			} else {
				newDirection = isDescending? 'ASC' : 'DESC';
			}
		}

		// If the field is the post name, or a field beloging to the post fields or relationship fields.
		var sortOrigin = ('displayName' === propertyName
			? 'post_title'
			// If the selected property belongs to a post or relationship field.
			: ( relatedContentModels.relatedContent.columns.post.filter(function(elem) { return elem.slug === propertyName; }).length
				? 'post'
				: 'relationship'
			)
		);

		var callback = function(messageType, message, resultCallback, response, data) {
			resultCallback(data);
			self.canConnectAnotherElement(data.canConnectAnother);

			self.isMainSpinnerVisible(false);
			$pageSpinner.fadeOut(function() {
				jQuery(this).removeClass('overlay');
			});
		};

		var failCallback = function() {
			// Do nothing for now
		};

		Types.page.extension.relatedContent.doAjax(
			'load',
			relatedContentModels.ajaxInfo.nonce,
			{
				relationship_slug: relatedContentModels.relationship_slug,
				page: page,
				related_post_type: relatedContentModels.ajaxInfo.relatedPostType,
				post_id: relatedContentModels.postId,
				items_per_page: relatedContentModels.itemsPerPage,
				sort_by : propertyName,
				sort: newDirection,
				sort_origin: sortOrigin
			},
			_.partial(callback, 'info', Types.page.extension.relatedContent.strings.misc.relatedContentUpdated || '', refreshAjaxData),
			_.partial(callback, 'error', Types.page.extension.relatedContent.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
		);
	};


	/**
	 * Sorting
	 */
	self.updateSort = function() {
		// TODO Sorting is made via Ajax
	};


	/**
	 * Fill field definitions with data from PHP.
	 *
	 * Result will be stored in the self.items() observable array.
	 *
	 * @param itemModels
	 * @since 2.2
	 */
	self.createItemViewModels = function(itemModels) {
		self.items(_.map(itemModels.relatedContent.data, function(itemModel) {
			return new Types.page.extension.relatedContent.viewmodels.RelatedContentViewModel(itemModel, self.itemActions, relatedContentModels.relationship_slug, self, relatedContentModels);
		}));
	};


	/**
	 * Add new Related Content
	 * Opens a new dialog with the options for creating a new related content
	 *
	 * @since m2m
	 */
	self.onAddNewRelatedContent = function() {
		Types.page.extension.relatedContent.viewmodels.AddNewDialogViewModel(relatedContentModels, function($window) {
			self.activateFieldJS('types-new-content-related-content-dialog-container-' + relatedContentModels.relationship_slug);
		}, self).display();
	};


	/**
	 * Opens select fields for displaying
	 *
	 * @since m2m
	 */
	self.onSelectFieldsDisplayed = function() {
		Types.page.extension.relatedContent.viewmodels.SelectFieldsDisplayedDialogViewModel(relatedContentModels, self).display();
	};




	/**
	 * Checks if a new association can be connected
	 *
	 * @since m2m
	 */
	self.canConnectAnotherElement = ko.observable(relatedContentModels.canConnectAnother);


    /**
	 * User Id
	 *
	 * @since 3.1
     */
    self.userId = relatedContentModels.userId;

	/**
	 * User Caps coming from relationship definition
	 * e.g.
	 * - self.advancedUserCaps.publish
	 *
	 * @since
	 */
	self.advancedUserCaps = relatedContentModels.advancedUserCaps;

	/**
	 * Checks if there is translatable post types involve.
	 *
	 * @since m2m
	 */
	self.hasTranslatableContent = ko.observable(relatedContentModels.hasTranslatableContent);


	/**
	 * Returns if page is in default language
	 *
	 * @since m2m
	 */
	self.isDefaultLanguage = ko.observable(relatedContentModels.isDefaultLanguage);

	/**
	 * This will be true if a default language post is required for an association to be created (which depends
	 * on the currently used version of the relationship database layer.
	 *
	 * @since 3.4
	 */
	self.requiresDefaultLanguageToConnect = ko.observable(relatedContentModels.requiresDefaultLanguageToConnect);


	/**
	 * Shows a WP pointer
	 *
	 * @param {HTMLElement} el The element bounded
	 *
	 * @since m2m
	 */
	self.showPointer = function(el) {
			var $this = jQuery(el);
			if (typeof type === 'undefined') {
					type = 'default';
			}

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
			$this.pointer({
					pointerClass: 'wp-toolset-pointer wp-toolset-types-pointer',
					content: '<h3>' + $this.data('header') + '</h3>' + '<p>' + $this.data('content') + '</p>',
					position: jQuery.extend(defaults, custom) // merge defaults and custom attributes
			}).pointer('open');
	};


	/**
	* Hides WP Pointers
	*/
	self.hideWPPointers = function() {
		jQuery('.wp-toolset-pointer').hide();
	};


	/**
	 * If there are language information: has records and WPML is activated
	 */
	self.hasLangInfo = ko.observable( self.itemsFound() && relatedContentModels.isWPMLActive );


    /**
     * If IPT is translatable
     */
    self.isIPTTranslatable = ko.observable(relatedContentModels.isIPTTranslatable && relatedContentModels.relatedContent.columns.relationship.length > 0);

	/**
	 * Handles creation/connect actions loading icon
	 */
	self.isMainSpinnerVisible = ko.observable(false);


	/**
	 * Connect Existing Related Content
	 * Opens a new dialog with the options for connecting an existing related content
	 *
	 * @since m2m
	 */
	self.onConnectExistingRelatedContent = function() {
		var select2ConnectingPost;

		/**
		 * Produce an option row value that is safe: Either escaped using the default Select2 logic or with
		 * only hardcoded bits of HTML code.
		 *
		 * @param {*} data
		 * @returns {string|*}
		 * @since 3.3.11
		 */
		var safelyBuildTemplate = function(data) {
			// https://github.com/select2/select2/issues/2990#issuecomment-270623763
			var escape = jQuery.fn.toolset_select2_original.defaults.defaults.escapeMarkup;
			var output = escape(data.text);

			// Prepend the language flag if provided.
			if( !!data.languageFlagUrl ) {
				output = '<span class="types-select2-language-flag"><img src="'
					+ escape( data.languageFlagUrl )
					+ '" class="types-language-flag" /></span> '
					+ output;
			}

			// Append the translation link if provided.
			if( !! data.translationLink ) {
				output += '<span class="types-select2-disabled-link"><a href="'
					+ escape( data.translationLink.url )
					+ '" target="_blank">'
					+ escape( data.translationLink.label )
					+ '</a></span> <span class="types-select2-disabled-tooltip">'
					+ escape( data.translationLink.tooltipText )
					+ '</span>';
			}

			return output;
		};

		Types.page.extension.relatedContent.viewmodels.ConnectExistingDialogViewModel(relatedContentModels, function($window) {
			self.activateFieldJS('types-connect-existing-content-dialog-container-' + relatedContentModels.relationship_slug);
			select2ConnectingPost = jQuery('input[data-rel=select2]').toolset_select2({
				allowClear: true,
				maximumSelectionSize: 1,
				placeholder: Types.page.extension.relatedContent.strings.misc.connectExistingPlaceholder,
				escapeMarkup: function(markup) {
					// Using escapeMarkup in Select2 this way is known to be a security vulnerability but we're preventing
					// that by escaping very selectively in the safelyBuildTemplate function above, so whatever markup reaches
					// this place is already safe.
					return markup;
				},
				templateResult: safelyBuildTemplate,
				templateSelection: safelyBuildTemplate,
				minimumInputLength: 3,
				ajax: {
					url: ajaxurl + '?action=' + relatedContentModels.ajaxInfo.actionName,
					dataType: 'json',
					method: 'post',
					delay: 500,
					data: function (params) {
						const ajaxData = {
							q: params.term,
							page: params.page,
							related_content_action: 'search_related_content',
							post_type: relatedContentModels.ajaxInfo.relatedPostType,
							relationship_slug: relatedContentModels.relationship_slug,
							current_post_id: relatedContentModels.postId,
							nonce: relatedContentModels.ajaxInfo.nonce,
							wpnonce: relatedContentModels.ajaxInfo.nonce,
						};

						// AJAX call is missing the WPML language information, we need to pass it along.
						const currentLang = WPV_Toolset.Utils.getParameterByName( 'lang' );
						if( null !== currentLang ) {
							ajaxData['current_language'] = currentLang;
						}

						return ajaxData;
					},
					processResults: function (data, params) {
						if (!data.success) {
							return {
								results: []
							}
						}
						return {
							results: data.data.items,
							pagination: data.data.pagination
						};
					},
					cache: false
				}
			}).on('change.select2', function() {
				// Enables/disables the dialog button, can't be done with ko.
				var value = jQuery(this).val(),
				$saveButton = jQuery(this).parents('.ui-dialog-content').next().find('button').first();

                // Compatibility issues here:
                // (1) jQuery.fb.button conflicts with Twitter Bootstrap (https://github.com/twbs/bootstrap/issues/6094)
                //     This cannot be used: saveButton.button( value ? 'enable' : 'disable' );
                // (2) jQuery datepicker may cause conflicts if we just add/remove the 'disabled' attribute:
                //     saveButton.removeAttr( 'disabled' ); saveButton.attr( 'disabled' );
                //     This was happening especially with ICL-MPP @ oursystem-6809. We also need to add and remove
                //     ui-* classes related to the button state.
                if (value) {
                    $saveButton.prop('disabled', false).removeClass('ui-button-disabled').removeClass('ui-state-disabled');
                } else {
                    $saveButton.prop('disabled', true).addClass('ui-button-disabled').addClass('ui-state-disabled');
                }
                // the tiny delay is required, otherwise it's fired to early
                setTimeout(function () {
                    // after a post is select the save button is focused, this way the workflow of the user is:
                    // Click on "Connect existing..." > Typing > "Enter" for selecting post > "Enter" to save & close the dialog
                    if (value) {
                        $saveButton.focus();
                    }
                }, 10);
			});
			select2ConnectingPost = jQuery('select[data-rel=select2]').length > 0
			? jQuery('select[data-rel=select2]').toolset_select2({
				allowClear: true,
				maximumSelectionSize: 1,
				placeholder: Types.page.extension.relatedContent.strings.misc.connectExistingSelectPlaceholder,
			})
			: jQuery('input[data-rel=select2]').toolset_select2({
				allowClear: true,
				maximumSelectionSize: 1,
				placeholder: Types.page.extension.relatedContent.strings.misc.connectExistingPlaceholder,
				escapeMarkup: function(markup) {
					// Using escapeMarkup in Select2 this way is known to be a security vulnerability but we're preventing
					// that by escaping very selectively in the safelyBuildTemplate function above, so whatever markup reaches
					// this place is already safe.
					return markup;
				},
				templateResult: safelyBuildTemplate,
				templateSelection: safelyBuildTemplate,
				minimumInputLength: 3,
				ajax: {
					url: ajaxurl + '?action=' + relatedContentModels.ajaxInfo.actionName,
					dataType: 'json',
					method: 'post',
					delay: 500,
					data: function (params) {
						const ajaxData = {
							q: params.term,
							page: params.page,
							related_content_action: 'search_related_content',
							post_type: relatedContentModels.ajaxInfo.relatedPostType,
							relationship_slug: relatedContentModels.relationship_slug,
							current_post_id: relatedContentModels.postId,
							nonce: relatedContentModels.ajaxInfo.nonce,
							wpnonce: relatedContentModels.ajaxInfo.nonce,
						};

						// AJAX call is missing the WPML language information, we need to pass it along.
						const currentLang = WPV_Toolset.Utils.getParameterByName( 'lang' );
						if( null !== currentLang ) {
							ajaxData['current_language'] = currentLang;
						}

						return ajaxData;
					},
					processResults: function (data, params) {
						if (!data.success) {
							return {
								results: []
							}
						}
						return {
							results: data.data.items,
							pagination: data.data.pagination
						};
					},
					cache: false
				}
			});
			
			select2ConnectingPost.on('change.select2', function() {
				// Enables/disables the dialog button, can't be done with ko.
				var value = jQuery(this).val(),
				$saveButton = jQuery(this).parents('.ui-dialog-content').next().find('button').first();

                // Compatibility issues here:
                // (1) jQuery.fb.button conflicts with Twitter Bootstrap (https://github.com/twbs/bootstrap/issues/6094)
                //     This cannot be used: saveButton.button( value ? 'enable' : 'disable' );
                // (2) jQuery datepicker may cause conflicts if we just add/remove the 'disabled' attribute:
                //     saveButton.removeAttr( 'disabled' ); saveButton.attr( 'disabled' );
                //     This was happening especially with ICL-MPP @ oursystem-6809. We also need to add and remove
                //     ui-* classes related to the button state.
                if (value) {
                    $saveButton.prop('disabled', false).removeClass('ui-button-disabled').removeClass('ui-state-disabled');
                } else {
                    $saveButton.prop('disabled', true).addClass('ui-button-disabled').addClass('ui-state-disabled');
                }
                // the tiny delay is required, otherwise it's fired to early
                setTimeout(function () {
                    // after a post is select the save button is focused, this way the workflow of the user is:
                    // Click on "Connect existing..." > Typing > "Enter" for selecting post > "Enter" to save & close the dialog
                    if (value) {
                        $saveButton.focus();
                    }
                }, 10);
			});
		}, self ).display();

		// the tiny delay is required, otherwise it's fired to early
		setTimeout( function(){
			// open the select2, so the user can start typing
			select2ConnectingPost.toolset_select2( 'open' );
		}, 10 );
	};


	/**
	 * Overrides Toolset.Gui.ListingViewModel::init()
	 */
	self.init = function () {
		/**
		 * Activate fields scripts
		 *
		 * @param {string} id ID of the parent html element
		 */
		self.activateFieldJS = function(id) {
			initialisedCREDForms = initialisedCREDForms.filter(function(item) {
				return item != id;
			});

			// event 'toolset_ajax_fields_loaded' once the fields are loaded in the dialog
			jQuery( document ).trigger( 'toolset_ajax_fields_loaded', [{form_id: id}] );

			if (typeof wptColorpicker !== 'undefined') {
				 wptColorpicker.init('body');
			}

			jQuery( 'textarea.wpt-wysiwyg', '#' + id ).each(function() {
				self.initWysiwygField( jQuery(this).attr('id') );
			});
		};


		self.createItemViewModels(relatedContentModels);
		// Binding only in its metabox.
		ko.applyBindings(self, jQuery('#types-related-content-' + relatedContentModels.relationship_slug)[0]);

		/**
		 * Some forms fields need to run a process for extra configuration. Those process are attached to jQuery(document).ready().
		 * But ko renders the text binding after document ready is triggered.
		 * So the most 'elegant' solution is to expand bindings with a new handler.
		 * This handler will be bound to a html element that contains the fields that needs to be displayed before running the process.
		 * ko runs the binding before the nested bindings and after them, so I will run the process when the fields were rendered.
		 */
		ko.bindingHandlers.afterBinding = {
			update: function(element, valueAccessor, allBindingsAccessor, data, context) {
				if (jQuery('.js-wpt-field').length > 1) {
					// TODO It is called several times, but ko has not an event attached to after binding, and it is not possible to know when is the last this binding is called.
					//self.activateFieldJS(relatedContentModels.relationship_slug);
				}
			}
		};


		// Fix accesing to the search field when it is in a dialog box.
		// @link https://stackoverflow.com/a/18487440/2103269
		jQuery(document).on('click', '.toolset_select2-search__field', function(event) {
			var $this = jQuery(event.target);
			jQuery('[tabindex]').removeAttr('tabindex');
			$this.focus()
		});

		// Initialize fields.
		if (typeof wptColorpicker !== 'undefined') {
			 wptColorpicker.init('body');
		}

		// Fixing skype modal. When skype settings modal is openned, the input/select elements can be reached because they lose focus.
		// It is necessary to hide the related content 'add new' or 'connect' modasl to make it work.
		jQuery(document).on('click', '.ui-dialog .js-wpt-skype-edit-button', function() {
			var $button = jQuery(this);
			self.modal = $button.parents('.ui-dialog:').first();
			self.overlay = self.modal.next();
			self.modal.fadeOut();
			self.overlay.fadeOut();
		});

		jQuery( 'body' ).on( 'thickbox:removed', function() {
			if ( self.modal && self.overlay ) {
				self.modal.fadeIn();
				self.overlay.fadeIn();
				self.modal = null;
				self.overlay = null;
			}
		} );

		// Handles enter key in "create" and "connect" relationships.
		jQuery( 'body' ).on( 'keydown', '.types-new-relationship-form input, types-new-relationship-form input', function(e) {
			if (e.key == "Enter") {
				jQuery(this).parents('.ui-dialog').first().find('.ui-dialog-buttonpane button.button-primary').click();
				return false;
			}
		})
	};


	/**
	 * Initialize WYSIWYG editors on demand
	 *
	 * If wp.editor is available (set by the textarea classname flag) use it to initialize the field;
	 * otherwise, show just a textarea.
	 *
	 * @param {string} id The underlying textarea id attribute.
	 */
	self.initWysiwygField = function( id ) {
		Toolset.Types.Compatibility.TinyMCE.InitWysiwyg.initWysiwygField(id);
	};

	self.init();
};


/**
 * Handles vanilla Form Validation. This method is called after ko renders every thing.
 *
 * "required" attribute makes vanilla form validation to be executed before our validation process.
 * 'invalid' event must be captured in each field. If validation fails, the quickedit panel have to be shown and scroll to it
 *
 * @param {Array} elements Elements rendered by ko.
 */
var wpcfVanillaFormValidation = function(elements) {
	elements.forEach(function(element) {
		if ( !!element.classList && element.classList.contains('types-related-content-several-relations') ) {
			var $container = jQuery(element);
			$container.find('[required]').on('invalid', function() {
				var $input = jQuery(this);
				// Hides browser validation warning.
				jQuery(document).click();
				var $quickedit = $input.parents('tr').first().prev().find('[data-bind*=showQuickEdit]');
				$quickedit.click();
				jQuery('html, body').animate({
					scrollTop: $quickedit.offset().top
				}, 500, function() {
					// reportValidity() is not supported by Edge
					$input.parents('form').first().valid();
				});
			});
		}
	} );
}
