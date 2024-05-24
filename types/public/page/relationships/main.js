var Types = Types || {};

// the head.js object
Types.head = Types.head || head;

Types.page = Types.page || {};

// Everything related to this page.
Types.page.relationships = {};
Types.page.relationships.viewmodels = {};
Types.page.relationships.strings = {};
Types.page.relationships.urls = {};
Types.page.relationships.defaultPageTitle = document.title;

/**
 * Page controller class.
 *
 * Handles page initialization.
 *
 * @constructor
 * @since 2.0
 */
Types.page.relationships.Class = function() {

	var self = this;

	// Extend the generic listing page controller.
	Toolset.Gui.ListingPage.call( self );

	// Methods required by the generic listing page controller
	//
	//

	self.beforeInit = function() {

		var modelData = self.getModelData();
		//noinspection JSUnresolvedVariable
		Types.page.relationships.jsPath = modelData.jsIncludePath;
		Types.page.relationships.typesVersion = modelData.typesVersion;

		self.initStaticData( modelData );

		Types.page.relationships.urls = modelData.urls;

		// Set observable array for potential intermediary post types.
		self.observablePotentialIntermediaryPostTypes = ko.observable( self.modelData['potentialIntermediaryPostTypes'] );
	};

	self.loadDependencies = function( nextStep ) {
		var typesVersion = Types.page.relationships.typesVersion;
		// Continue after loading the view of the listing table.
		Types.head.load(
			Types.page.relationships.jsPath + '/viewmodels/ListingViewModel.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/RelationshipViewModel.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/WizardViewModel.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/dialogs/DeleteRelationship.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/dialogs/DeleteIntermediaryPostType.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/dialogs/ConfirmChangeCardinality.js?ver=' + typesVersion,
			Types.page.relationships.jsPath + '/viewmodels/dialogs/MergeRelationships.js?ver=' + typesVersion,
			nextStep,
		);
	};

	self.getMainViewModel = function() {
		return new Types.page.relationships.viewmodels.ListingViewModel( self.getModelData().relationships );
	};

	self.initStaticData = function( modelData ) {
		Types.page.relationships.strings = modelData.strings || Types.page.relationships.strings;
		Types.page.relationships.itemsPerPage = modelData.itemsPerPage || {};
		Types.page.relationships.delete_intermediary_post_type_action = modelData.delete_intermediary_post_type_action || null;
		Types.page.relationships.delete_intermediary_post_type_nonce = modelData.delete_intermediary_post_type_nonce || null;
	};

	/**
	 * Confirms that the user is still creating the relationship
	 *
	 * @since m2m
	 */
	self.isCreatingRelationship = function() {
		return ( jQuery( '#types-wizard-relationship-screen:hidden' ).length === 0 );
	};

	/**
	 * Warn user when leaving the page with unsaved changes.
	 *
	 * @since m2m
	 */
	self.afterInit = function() {
		WPV_Toolset.Utils.setConfirmUnload( function() {
			return self.viewModel.hasUnsavedItems()
				|| self.isCreatingRelationship();
		}, null, self.getString( ['confirmUnload'] ) );

		/**
		 * Handling page change by history
		 *
		 * @since m2m
		 */
		window.addEventListener( 'popstate', function() {
			self.isNavigatingThroughHistory = true;
			openScreenFromURL();
		} );

		// Loads the URLed-page
		openScreenFromURL();

	};

	// Page-specific methods and properties
	//
	//

	var defaultAnimationLength = 200;


	self.postSlugToDisplayName = function( postSlug, label ) {
		if (!_.has( self.modelData.postTypes, postSlug )) {
			return postSlug;
		}

		return self.modelData.postTypes[postSlug][label];
	};

	self.removeDeletedIntermediaryPostType = function(removed) {
		self.observablePotentialIntermediaryPostTypes( ( self.observablePotentialIntermediaryPostTypes() ).filter( function( item ){ return item.slug !== removed } ) );
	};

	self.getPotentialIntermediaryPostTypes = function() {
		return self.observablePotentialIntermediaryPostTypes();
	};

	self.fieldTypeToIcon = function( fieldType ) {
		if (!_.has( self.modelData['fieldTypeIcons'], fieldType )) {
			return '';
		}

		return self.modelData['fieldTypeIcons'][fieldType];
	};

	/**
	 * Change the main page title.
	 *
	 * @param titleKey Name of the new string in Types.page.relationships.strings.title.
	 * @since m2m
	 */
	self.setTitle = function( titleKey ) {
		// A <span> tag is needed because there could be some buttons inside the title
		var $title = jQuery( 'div#wpbody-content > div.wrap > h1' );

		$title.fadeOut( defaultAnimationLength, function() {
			// Because of this function is in main.js, I can't use ko:visible so I use styles depending of h1.class
			$title.attr( 'class', titleKey );
			$title.find( 'span' ).text( Types.page.relationships.strings.title[titleKey] );
			$title.fadeIn();
			self.setPageTitle();
		} );
	};

	/**
	 * Sets page title, it takes history state for getting the title
	 *
	 * @since m2m
	 */
	self.setPageTitle = function() {
		var historyState = history.state;
		if (historyState) {
			if (!_.isUndefined( historyState.screen )) {
				switch (historyState.screen) {
					case 'edit':
						document.title = self.getString( ['pageTitle', 'edit'] ).replace( '%s', historyState.displayName ) + self.getString( ['pageTitle', 'sep'] ) + Types.page.relationships.defaultPageTitle;
						break;
					case 'addNew':
						document.title = self.getString( ['pageTitle', 'add'] ) + self.getString( ['pageTitle', 'sep'] ) + Types.page.relationships.defaultPageTitle;
						break;
					default:
						document.title = Types.page.relationships.defaultPageTitle;
				}
			}
		}
	};

	/**
	 * Hides WP Pointers
	 */
	self.hideWPPointers = function() {
		jQuery( '.wp-toolset-pointer' ).hide();
	};

	/**
	 * Shows a WP pointer
	 *
	 * @param {HTMLElement} el The element bounded
	 *
	 * @since m2m
	 */
	self.showPointer = function( el ) {
		var $this = jQuery( el );

		// default options
		var defaults = {
			edge: 'left', // on which edge of the element tooltips should be shown: ( right, top, left, bottom )
			align: 'middle', // how the pointer should be aligned on this edge, relative to the target (top, bottom, left, right, middle).
			offset: '15 0 ', // pointer offset - relative to the edge
		};

		// custom options passed in HTML "data-" attributes
		var custom = {
			edge: $this.data( 'edge' ),
			align: $this.data( 'align' ),
			offset: $this.data( 'offset' ),
		};

		self.hideWPPointers();
		var content = '<p>' + $this.data( 'content' ) + '</p>';
		if ($this.data( 'header' )) {
			content = '<h3>' + $this.data( 'header' ) + '</h3>' + content;
		}

		var extraClass = $this.hasClass( 'types-pointer-tooltip' ) ? ' types-pointer-tooltip' : '';

		$this.pointer( {
			pointerClass: 'wp-toolset-pointer wp-toolset-types-pointer' + extraClass,
			content: content,
			position: jQuery.extend( defaults, custom ), // merge defaults and custom attributes
		} ).pointer( 'open' );
	};

	/**
	 * Switch to the Edit Relationship screen.
	 *
	 * @param {Types.page.relationships.viewmodels.RelationshipViewModel} relationshipDefinition
	 * @since m2m
	 */
	self.editRelationship = function( relationshipDefinition ) {
		var $editScreen = jQuery( '#types-current-relationship-screen' ),
			$activeScreen = jQuery( '#toolset-page-content' ).find( '> .toolset-actual-content-wrapper > div:visible' );

		// This will cause Knockut to re-render the whole Edit Relationship template
		self.viewModel.currentRelationshipDefinition( relationshipDefinition );

		// History handling
		if (!self.isNavigatingThroughHistory) {
			history.pushState( {
					screen: 'edit',
					slug: relationshipDefinition.slug(),
					displayName: relationshipDefinition.displayName(),
				},
				null,
				Types.page.relationships.urls.edit + relationshipDefinition.slug(),
			);
		}
		self.isNavigatingThroughHistory = false;

		self.setTitle( 'edit' );

		// Display screen options which have been previously hidden and are applicable only for the Edit Relationship screen.
		jQuery( '#screen-options-link-wrap' ).show();

		var $container = jQuery( '#types_relationship_settings' ).find( 'div.inside' ),
			$settingsOverlay = $container.find( 'div.overlay' ),
			$content = $container.find( 'div.main-box-content' ),
			originalSettingsHeight = 0; // right now the height is still 0

		var overlayHeight = function() {
			// In the Settings metabox, make the heights of overlay and the main box content the same.
			// We need to resort to JavaScript because neither of the values are known, and we don't even
			// know which one is going to be bigger: http://stackoverflow.com/a/11461499
			var currentSettingsHeight = $content.height(),
				maxHeight = Math.max( $settingsOverlay.height(), currentSettingsHeight );

			// Store the original height which will be needed if user goes to the advanced edit mode.
			if (0 === originalSettingsHeight) {
				originalSettingsHeight = currentSettingsHeight;
			}

			if (maxHeight > 0) {
				// only set height, when the box is visible
				$settingsOverlay.height( maxHeight );
				$content.height( maxHeight );
			}
		};
		// For use in another scripts
		self.overlayHeight = overlayHeight;

		jQuery( '#types_relationship_settings-hide, #types_relationship_settings .hndle' ).on( 'click', function() {
			// #types_relationship_settings-hide
			//   caculate overlay height when "Settings" is activate through "Screen Options"
			// #types_relationship_settings .hndle
			//   For the case closed Settings are hidden through "Screen Options" we also need to calculate
			//   the height when the collapse toggle is used

			// the short delay is important as it took a moment until the box is fully displayed
			// (multiple delays as some machines not work with the shorter delays)
			setTimeout( function() {
				overlayHeight();
			}, 50 );
			setTimeout( function() {
				overlayHeight();
			}, 200 );
			setTimeout( function() {
				overlayHeight();
			}, 500 );
			setTimeout( function() {
				overlayHeight();
			}, 1000 );
		} );

		var displayScreen = function() {
			// We need to re-register metabox toggles because the Edit screen content
			// has just been re-rendered by knockout.
			postboxes.add_postbox_toggles( pagenow );

			// overlay height
			overlayHeight();

			// Minor adjustments of the Settings metabox.
			Toolset.hooks.addAction( 'types-relationships-enable-advanced-settings', function() {

				// Return the metabox back to the content height once the overlay is hidden.
				$content.animate( { height: originalSettingsHeight }, defaultAnimationLength );

				// Fix the column width once the table is rendered (so it doesn't change when the content changes).
				var $table = $content.find( 'table.widefat' );
				$table.find( 'td' ).each( function() {
					var currentWidth = jQuery( this ).width();
					jQuery( this ).css( 'width', currentWidth + 'px' );
				} );
			} );
		};

		if ($editScreen[0] !== $activeScreen[0]) {
			$activeScreen.first().fadeOut( defaultAnimationLength, function() {
				$editScreen.addClass( 'current' ).siblings().removeClass( 'current' );
				$editScreen.fadeIn( defaultAnimationLength, function() {
					displayScreen();
				} );
			} );
		} else {
			displayScreen();
		}
	};

	/**
	 * Show the "Delete Relationship" confirmation dialog and handle the output.
	 *
	 * Either deactivates or deletes a given relationship (or does nothing).
	 *
	 * @param relationshipDefinition
	 */
	self.deleteRelationship = function( relationshipDefinition ) {
		var dialog = Types.page.relationships.viewmodels.dialogs.DeleteRelationship( relationshipDefinition, function( result ) {
			switch (result) {
				case 'delete':
					self.showRelationships();
					self.viewModel.deleteRelationship( relationshipDefinition );
					break;
				case 'deactivate':
					relationshipDefinition.onDeactivate();
					break;
			}
		} );

		dialog.display();
	};

	/**
	 * Switch to the Relationship listing screen.
	 *
	 * @since m2m
	 */
	self.showRelationships = function() {
		var $activeScreen = jQuery( '#toolset-page-content' ).find( '> .toolset-actual-content-wrapper > div:visible' ),
			$listingScreen = jQuery( '.toolset-listing-wrapper' );

		// Hide screen options which are relevant only for the Edit Relationship screen.
		// This needs to be reapplied even if we already are on the relationship listing (first page load)
		jQuery( '#screen-options-link-wrap' ).hide();

		// When navigating through the history, user can go to the same page 'jumping' a history state
		if ($activeScreen[0] === $listingScreen[0]) {
			return;
		}

		// History handling
		if (!self.isNavigatingThroughHistory) {
			history.pushState( { screen: 'listing' }, null, Types.page.relationships.urls.listing );
		}
		self.isNavigatingThroughHistory = false;

		self.setTitle( 'listing' );

		$activeScreen.fadeOut( defaultAnimationLength, function() {
			$listingScreen.addClass( 'current' ).siblings().removeClass( 'current' );

			$listingScreen.fadeIn( defaultAnimationLength );

			// Clear all actions on the Edit Relationship screen because they're going to be recreated if needed.
			Toolset.hooks.removeAction( 'types-relationships-enable-advanced-settings' );

			Toolset.hooks.doAction( 'types-relationships-switch-to-relationship-listing' );
		} );
	};

	/**
	 * Shows Add New Relationship Wizard
	 *
	 * @since m2m
	 */
	self.showAddNewRelationshipWizard = function() {
		var $wizardScreen = jQuery( '#types-wizard-relationship-screen' );
		var $activeScreen = jQuery( '#toolset-page-content' ).find( '> .toolset-actual-content-wrapper > div:visible' );

		// Hide screen options which are relevant only for the Edit Relationship screen.
		// This needs to be reapplied even if we already are on the relationship listing (first page load)
		jQuery( '#screen-options-link-wrap' ).hide();

		// When navigating through the history, user can go to the same page 'jumping' a history state
		if ($activeScreen[0] === $wizardScreen[0]) {
			return;
		}

		if (!self.isNavigatingThroughHistory) {
			history.pushState( { screen: 'addNew' }, null, Types.page.relationships.urls.addNew );
		}
		self.isNavigatingThroughHistory = false;

		self.setTitle( 'wizard' );

		$activeScreen.fadeOut( defaultAnimationLength, function() {
			$wizardScreen.addClass( 'current' ).siblings().removeClass( 'current' );

			$wizardScreen.fadeIn( defaultAnimationLength );
			Toolset.hooks.doAction( 'types-relationships-wizard-enter' );
			jQuery( '#post-body-content' ).show();
		} );
	};

	/**
	 * Is navigating through the browser history?
	 *
	 * @since m2m
	 */
	self.isNavigatingThroughHistory = false;

	/**
	 * Gets the current page from the URL
	 *
	 * @since m2m
	 */
	var openScreenFromURL = function() {
		// Gets parameters from document.location
		var action = WPV_Toolset.Utils.getParameterByName( 'action' );
		var slug = WPV_Toolset.Utils.getParameterByName( 'slug' );
		var callback;
		switch (action) {
			case 'edit':
				if (slug) {
					var model = _.find(
						Types.page.relationships.main.viewModel.items(),
						function( model ) {
							return model.slug() === slug;
						},
					);
					if (typeof ( model ) !== 'undefined') {
						callback = function() {
							self.editRelationship( model );
						};
					}
				}
				break;
			case 'add_new':
				callback = function() {
					self.showAddNewRelationshipWizard();
				};
				break;
			default:
				callback = function() {
					self.showRelationships();
				};
				break;
		}
		callback();
	};

	/**
	 * Handles all the interaction with the server.
	 *
	 * @type {{action: {update: string}, doAjax: Types.page.relationships.Class.ajax.doAjax}}
	 */
	self.ajax = {

		// Actions recognized by the server.
		action: {
			update: 'update',
			create: 'create',
			delete: 'delete',
			cardinality: 'cardinality',
		},

		/**
		 * Perform an AJAX call.
		 *
		 * @param {string} actionName One of the names defined in self.ajax.action.
		 * @param {RelationshipViewModel|[RelationshipViewModel]} relationships One or more relationships
		 *     to perform the action on.
		 * @param {function} successCallback Callback that gets the AJAX response if the call succeeds.
		 * @param {function} failCallback Optional callback for the case of failure. If not provided,
		 *     successCallback will be used.
		 *
		 * @since m2m
		 */
		doAjax: function( actionName, relationships, successCallback, failCallback ) {

			if (!_.isArray( relationships )) {
				relationships = [relationships];
			}

			var ajaxData = {
				action: 'types_relationships_action',
				relationship_action: actionName,
				wpnonce: self.modelData.nonce,
				relationship_definitions: relationships,
			};

			if (typeof ( failCallback ) === 'undefined') {
				failCallback = successCallback;
			}

			jQuery.post( {
				async: true,
				url: ajaxurl,
				data: ajaxData,

				success: function( originalResponse ) {
					var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );

					self.debug( 'AJAX response', ajaxData, originalResponse );

					if (response.success) {
						successCallback( response, response.data || {} );
					} else {
						failCallback( response, response.data || {} );
					}
				},

				error: function( ajaxContext ) {
					console.log( 'Error:', ajaxContext.responseText );
					failCallback( { success: false, data: {} }, {} );
				},
			} );
		},
	};

};

// noinspection JSUnusedLocalSymbols
/**
 * Role slugs format
 *
 * @link https://stackoverflow.com/a/15926931
 */
ko.bindingHandlers.formatSlug = {
	init: function( element, valueAccessor, allBindingsAccessor ) {
		ko.utils.registerEventHandler( element, 'focusout', function() {
			var observable = valueAccessor()[0];
			var role = valueAccessor()[1];
			var value = jQuery( element ).val();
			var newValue = observable( value );
			// Have tried using ko but I don't know why I couldn't.
			var $alert = jQuery( element ).parents( 'div, td' ).find( '[data-slug="' + role + '"]' );
			if (!!value && !newValue.length) {
				$alert.slideDown();
				setTimeout( function() {
					$alert.slideUp();
				}, 4000 );
			} else {
				$alert.slideUp();
			}

			jQuery( element ).val( newValue );
			jQuery( element ).trigger( 'keyup' );
		} );
	},
	update: function( element, valueAccessor ) {
		var value = ko.utils.unwrapObservable( valueAccessor()[0] );
		if (typeof value !== 'function') {
			jQuery( element ).val( value );
		}
	},
};

// Make everything happen.
Types.page.relationships.main = new Types.page.relationships.Class( jQuery );
Types.head.ready( Types.page.relationships.main.init );
