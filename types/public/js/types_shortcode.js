/* eslint-disable */
/**
 * API and helper functions for the GUI on Types shortcodes.
 *
 * @since m2m
 * @package Types
 */

var Toolset = Toolset || {};

if ( typeof Toolset.Types === "undefined" ) {
	Toolset.Types = {};
}

/*
 * -------------------------------------
 * Shortcode GUI
 * -------------------------------------
 */

Toolset.Types.shortcodeManager = function( $ ) {

	var self = this;

	/**
	 * Shortcodes GUI API version.
	 *
	 * Access to it using the API methods, from inside this object:
	 * - self.getShortcodeGuiApiVersion
	 *
	 * Access to it using the API hooks, from the outside world:
	 * - types-filter-get-shortcode-gui-api-version
	 *
	 * @since m2m
	 */
	self.apiVersion = 193000;

	/**
	 * Whether the colorpicker functionality is available:
	 * it is included as a dependency on the backend,
	 * but the script is not available on frontend.
	 *
	 * @since 3.4
	 */
	self.hasColorpicker = ( typeof $.fn.wpColorPicker == 'function' );

	/**
	 * Get the current shortcodes GUI API version.
	 *
	 * @see types-filter-get-shortcode-gui-api-version
	 *
	 * @since m2m
	 */
	self.getShortcodeGuiApiVersion = function( version ) {
		return self.apiVersion;
	};

	/**
	 * Register the canonical Toolset hooks, both API filters and actions.
	 *
	 * @since m2m
	 */
	self.initHooks = function() {

		/*
		 * ###############################
		 * API filters
		 * ###############################
		 */

		/**
		 * Return the current shortcodes GUI API version.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'types-filter-get-shortcode-gui-api-version', self.getShortcodeGuiApiVersion );

		/**
		 * Clean the list of attributes from metaXXX helpers.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-types-computed-attribute-values', self.cleanTypesAttributes );

		/**
		 * Clean the list of attributes depending on the field type and the attributes selected.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-types-computed-attribute-values', self.adjustAttributes, 20, 2 );

		/**
		 * Adjust the attributes based on the item selector values.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-types-computed-attribute-values', self.adjustTypesMetaSelectorAttributes, 30, 2 );

		/**
		 * Generate complex shortcodes for checkbox, checkboxes and radio field when producing custom output per option.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-types-crafted-shortcode', self.adjustComposedShortcodes, 10, 2 );

		/*
		 * ###############################
		 * API actions
		 * ###############################
		 */

		/**
		 * Open the Types shortcode dialog on demand, given a set of data.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addAction( 'types-action-shortcode-dialog-do-open', self.shortcodeDialogOpen );

		/**
		 * Set the right dialog buttonpane buttons labels, after the dialog is opened, based on the current GUI action.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addAction( 'types-action-shortcode-dialog-preloaded', self.manageShortcodeDialogButtonpane );

		/**
		 * Set override values on Types shortcode dialogs.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addAction( 'types-action-shortcode-dialog-loaded', self.manageEditingOverrides );

		/**
		 * Generate extra attributes for field types that support custom output per option.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addAction( 'types-action-shortcode-dialog-loaded', self.manageMetaoptions );

		/**
		 * Manage some special selectors for options, like colorpickers,
		 * until we create a proper colorpicker option type.
		 *
		 * @param object dialogData
		 * @since 3.4
		 */
		Toolset.hooks.addAction( 'types-action-shortcode-dialog-loaded', self.manageSpecialSelectors );

		/**
		 * Display the Types shortcodes modal whenever the button that inserts shortcodes inside page builder inputs is clicked.
		 *
		 * @since 3.0.8
		 */
		Toolset.hooks.addAction( 'toolset-action-display-shortcodes-modal-for-page-builders', self.displayTypesShortcodesModalForPageBuilders );

		return self;

	};

	/**
	 * Init GUI templates.
	 *
	 * @uses wp.template
	 * @since m2m
	 */
	self.templates = {};
	self.initTemplates = function() {

		// Registers the typesUserSelector, typesViewsUserSelector, typesViewsTermSelector templates in the shared pool
		Toolset.hooks.doAction( 'toolset-filter-register-shortcode-gui-attribute-template', 'typesUserSelector', wp.template( 'toolset-shortcode-attribute-typesUserSelector' ) );
		Toolset.hooks.doAction( 'toolset-filter-register-shortcode-gui-attribute-template', 'typesViewsUserSelector', wp.template( 'toolset-shortcode-attribute-typesViewsUserSelector' ) );
		Toolset.hooks.doAction( 'toolset-filter-register-shortcode-gui-attribute-template', 'typesViewsTermSelector', wp.template( 'toolset-shortcode-attribute-typesViewsTermSelector' ) );

		// Gets the shared pool
		self.templates = _.extend( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-templates', {} ), self.templates );

		// Skype Template
        self.templates.attributes.skype = wp.template( 'toolset-shortcode-attribute-skype' );

		// Register custom templates for local usage
		if ( ! _.has( self.templates, 'info' ) ) {
			self.templates.info = {};
		}
		self.templates.info.postReferenceFieldWizard = [
			wp.template( 'toolset-shortcode-attribute-info-postReferenceField' ),
			wp.template( 'toolset-shortcode-attribute-info-postReferenceFieldWizardFirst' ),
			wp.template( 'toolset-shortcode-attribute-info-postReferenceFieldWizardSecond' ),
			wp.template( 'toolset-shortcode-attribute-info-postReferenceFieldWizardThird' )
		];
		self.templates.info.RFGWizard = [
			wp.template( 'toolset-shortcode-attribute-info-RFG' ),
			wp.template( 'toolset-shortcode-attribute-info-RFGFirst' ),
			wp.template( 'toolset-shortcode-attribute-info-RFGSecond' ),
			wp.template( 'toolset-shortcode-attribute-info-RFGThird' )
		];

		return self;

	}

	/**
	 * Init GUI dialogs.
	 *
	 * @uses jQuery.dialog
	 * @since m2m
	 */
	self.dialogs = {};
	self.dialogs.main = null;
	self.dialogs.shortcode = null;
	self.dialogs.postFieldInfoWizard = null;

	self.shortcodeDialogSpinnerContent = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="ajax-loader"></div>' +
		'<p>' + types_shortcode_i18n.action.loading + '</p>' +
		'</div>' +
		'</div>'
	);

	self.initDialogs = function() {

		/**
		 * Main dialog to list the available shortcodes.
		 *
		 * @since m2m
		 */
		if ( ! $( '#js-types-shortcode-gui-dialog-container-main' ).length ) {
			$( 'body' ).append( '<div id="js-types-shortcode-gui-dialog-container-main" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body"></div>' );
		}
		self.dialogs.main = $( '#js-types-shortcode-gui-dialog-container-main' ).dialog({
			dialogClass: 'toolset-dialog',
			autoOpen:	false,
			modal:		true,
			width:		'90%',
			title:		types_shortcode_i18n.title.dialog,
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass('modal-open');
				self.repositionDialog();
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			}
		});

		/**
		 * Canonical dialog to insert shortcodes.
		 *
		 * @since m2m
		 */
		if ( ! $( '#js-types-shortcode-gui-dialog-container-shortcode' ).length ) {
			$( 'body' ).append( '<div id="js-types-shortcode-gui-dialog-container-shortcode" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body"></div>' );
		}
		self.dialogs.shortcode = $( "#js-types-shortcode-gui-dialog-container-shortcode" ).dialog({
			dialogClass: 'toolset-dialog',
			autoOpen:	false,
			modal:		true,
			width:		'90%',
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.repositionDialog();
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-types-shortcode-gui-button-craft',
					text: types_shortcode_i18n.action.insert,
					click: function() {
						var shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, $( '#js-types-shortcode-gui-dialog-container-shortcode' ) );
						// shortcodeToInsert will fail on validtion failure
						if ( shortcodeToInsert ) {
							$( this ).dialog( "close" );
							Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
						}
					}
				},
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-secondary toolset-shortcode-gui-dialog-button-back js-types-shortcode-gui-button-back',
					text: types_shortcode_i18n.action.back,
					click: function() {
						$( this ).dialog( "close" );
						// Open the Types main dialog, or the Fields and Views dialog if Views is active
						self.openMainDialog();
					}
				},
				{
					class: 'button-secondary toolset-shortcode-gui-dialog-button-close js-types-shortcode-gui-button-close',
					text: types_shortcode_i18n.action.cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

		/**
		 * Information wizard dialog about post reference fields.
		 *
		 * @since m2m
		 */
		if ( ! $( '#js-types-shortcode-gui-dialog-container-post-field-info-wizard' ).length ) {
			$( 'body' ).append( '<div id="js-types-shortcode-gui-dialog-container-post-field-info-wizard" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body js-types-shortcode-gui-dialog-container-post-field-info-wizard"></div>' );
		}
		self.dialogs.postFieldInfoWizard = $( "#js-types-shortcode-gui-dialog-container-post-field-info-wizard" ).dialog({
			dialogClass: 'toolset-dialog',
			autoOpen:	false,
			modal:		true,
			width:		'90%',
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			wizardStep: 0,
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.repositionDialog();
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-pfiw-next' ), types_shortcode_i18n.action.wizard );
				$( this ).dialog( "option", "wizardStep", 0 );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-types-shortcode-gui-button-pfiw-next',
					text: types_shortcode_i18n.action.wizard,
					click: function() {
						self.postFieldInfoWizardNext();
					}
				},
				{
					class: 'button-secondary js-types-shortcode-gui-button-pfiw-previous',
					text: types_shortcode_i18n.action.previous,
					click: function() {
						self.postFieldInfoWizardPrevious();
					}
				}
			]
		});

		/**
		 * Information wizard dialog about post reference fields.
		 *
		 * @since m2m
		 */
		if ( ! $( '#js-types-shortcode-gui-dialog-container-rfg-info-wizard' ).length ) {
			$( 'body' ).append( '<div id="js-types-shortcode-gui-dialog-container-rfg-info-wizard" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body js-types-shortcode-gui-dialog-container-rfg-info-wizard"></div>' );
		}
		self.dialogs.RFGInfoWizard = $( "#js-types-shortcode-gui-dialog-container-rfg-info-wizard" ).dialog({
			dialogClass: 'toolset-dialog',
			autoOpen:	false,
			modal:		true,
			width:		'90%',
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			wizardStep: 0,
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.repositionDialog();
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).hide();
				$( this ).dialog( "option", "wizardStep", 0 );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-types-shortcode-gui-button-rfgiw-next',
					text: types_shortcode_i18n.action.wizard,
					click: function() {
						self.RFGInfoWizardStepNext();
					}
				},
				{
					class: 'button-secondary js-types-shortcode-gui-button-rfgiw-previous',
					text: types_shortcode_i18n.action.previous,
					click: function() {
						self.RFGInfoWizardStepPrevious();
					}
				}
			]
		});

		$( window ).resize( self.resizeWindowEvent );

		return self;
	}

	/**
	 * Callback for the window.resize event.
	 *
	 * @since m2m
	 */
	self.resizeWindowEvent = _.debounce( function() {
		self.repositionDialog();
	}, 200 );

	/**
	 * Reposition the Types dialogs based on the current window size.
	 *
	 * @since m2m
	 */
	self.repositionDialog = function() {
		var winH = $( window ).height() - 100;

		_.each( self.dialogs, function( dialog, key, list ) {
			dialog.dialog( "option", "maxHeight", winH );
			dialog.dialog( "option", "position", {
				my:        "center top+50",
				at:        "center top",
				of:        window,
				collision: "none"
			});
		});
	};

	/**
	 * Make sure that dialog buttons are adjustable regardiless the jQuery UI version.
	 *
	 * @param object $button
	 * @param string $text
	 */
	self.setButtonText = function( $button, $text ) {
		if ( $button.find( '.ui-button-text' ).length > 0 ) {
			$( '.ui-button-text', $button ).html( $text );
		} else {
			$button.html( $text );
		}
	};

	/**
	 * Open the main dialog to offer shortcodes, which can be the Types one, or the Fields and Views if Views is active.
	 *
	 * @since m2m
	 */
	self.openMainDialog = function() {
		if ( types_shortcode_i18n.conditions.plugins.toolsetViews ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-fields-and-views-dialog-do-open' );
		} else {
			self.openTypesDialog();
		}
	};

	/**
	 * Open the main Types dialog to offer shortcodes.
	 *
	 * @since m2m
	 */
	self.openTypesDialog = function() {
		self.dialogs.main.dialog( 'open' );
	}

	/**
	 * Init the Admin Bar button, if any.
	 *
	 * @since m2m
	 */
	self.initAdminBarButton = function() {
		if ( $( '.js-types-shortcode-generator-node a' ).length > 0 ) {
			$( '.js-types-shortcode-generator-node a' ).addClass( 'js-types-in-adminbar' );
		}
	};

	/**
	 * Set the right active editor and action when clicking any button, and open the main dialog.
	 *
	 * Acceptable selectors to trigger actions are:
	 * - Admin Bar: .js-types-in-adminbar
	 * - Editor Toolbar: .js-types-in-toolbar
	 *
	 * @since m2m
	 */
	$( document ).on( 'click','.js-types-in-adminbar', function( e ) {
		e.preventDefault();

		Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'create' );
		self.openTypesDialog();

		return false;
	});
	$( document ).on( 'click', '.js-types-in-toolbar', function( e ) {
		e.preventDefault();

		var typesInToolbarButton = $( this );
		if ( typesInToolbarButton.attr( 'data-editor' ) ) {
			window.wpcfActiveEditor = typesInToolbarButton.data( 'editor' );
		}

		Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );
		self.openTypesDialog();

		return false;
	});

	/**
	 * Close the main dialog when clicking on any of its items.
	 *
	 * @since m2m
	 */
	$( document ).on( 'click', '.js-types-shortcode-gui-group-list .js-types-shortcode-gui', function( e ) {
		e.preventDefault();

		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}
	});

	/**
	 * Manage the steps and buttons in the post reference information wizard dialog.
	 *
	 * @param step int
	 *
	 * @since m2m
	 */
	self.postFieldInfoWizardStep = function( step ) {
		if ( ! _.contains( [0, 1, 2, 3], step ) ) {
			return;
		}
		var dialogData = self.dialogs.postFieldInfoWizard.dialog( "option", "wizardData" );
		switch( step ) {
			case 0:
				self.dialogs.postFieldInfoWizard.dialog( 'open' ).dialog({
					title: dialogData.title
				});
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-pfiw-next' ), types_shortcode_i18n.action.wizard );
				break;
			case 1:
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-pfiw-next' ), types_shortcode_i18n.action.next );
				break;
			case 2:
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-pfiw-next' ), types_shortcode_i18n.action.next );
				break;
			case 3:
				$( '.js-types-shortcode-gui-button-pfiw-previous' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-pfiw-next' ), types_shortcode_i18n.action.got_it );
				break;
		}
		self.dialogs.postFieldInfoWizard.html( self.templates.info.postReferenceFieldWizard[ step ]( dialogData ) );
		self.dialogs.postFieldInfoWizard.dialog( "option", "wizardStep", step );
	};

	/**
	 * Manage the Next button click in the post reference information wizard dialog.
	 *
	 * After the last step, reopen the main dialog when inserting/creating/appending a shortcode.
	 * Just close the wizard in any other scenario, like when editing or skipping a shortcode,
	 * or when in the Views loop wizard.
	 *
	 * @since m2m
	 */
	self.postFieldInfoWizardNext = function() {
		var currentWizardStep = self.dialogs.postFieldInfoWizard.dialog( "option", "wizardStep" ),
			comingWizardStep = currentWizardStep + 1;

		if ( comingWizardStep > 3 ) {
			self.dialogs.postFieldInfoWizard.dialog( "close" );
			if ( _.contains( [ 'insert', 'create', 'append' ], Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-action', '' ) ) ) {
				self.openMainDialog();
			}
			return;
		}
		self.postFieldInfoWizardStep( comingWizardStep );
	};

	/**
	 * Manage the Previous button lick in the post reference information wizard dialog.
	 *
	 * @since m2m
	 */
	self.postFieldInfoWizardPrevious = function() {
		var currentWizardStep = self.dialogs.postFieldInfoWizard.dialog( "option", "wizardStep" ),
			comingWizardStep = currentWizardStep - 1;
		if ( comingWizardStep < 1 ) {
			comingWizardStep = 1;
		}
		self.postFieldInfoWizardStep( comingWizardStep );
	};

	/**
	 * Manage the steps and buttons in the repeating fields groups information wizard dialog.
	 *
	 * @param step int
	 *
	 * @since m2m
	 */
	self.RFGInfoWizardStep = function( step ) {
		if ( ! _.contains( [0, 1, 2, 3], step ) ) {
			return;
		}
		var dialogData = self.dialogs.RFGInfoWizard.dialog( "option", "wizardData" );
		switch( step ) {
			case 0:
				self.dialogs.RFGInfoWizard.dialog( 'open' ).dialog({
					title: dialogData.title
				});
				$( '.js-types-shortcode-gui-button-rfgiw-previous' ).hide();
				if ( types_shortcode_i18n.conditions.plugins.toolsetViews ) {
					self.setButtonText( $( '.js-types-shortcode-gui-button-rfgiw-next' ), types_shortcode_i18n.action.wizard );
				} else {
					self.setButtonText( $( '.js-types-shortcode-gui-button-rfgiw-next' ), types_shortcode_i18n.action.close );
				}
				break;
			case 1:
				$( '.js-types-shortcode-gui-button-rfgiw-previous' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-rfgiw-next' ), types_shortcode_i18n.action.next );
				break;
			case 2:
				$( '.js-types-shortcode-gui-button-rfgiw-previous' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-rfgiw-next' ), types_shortcode_i18n.action.next );
				break;
			case 3:
				$( '.js-types-shortcode-gui-button-rfgiw-previous' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-rfgiw-next' ), types_shortcode_i18n.action.got_it );
				break;
		}
		self.dialogs.RFGInfoWizard.html( self.templates.info.RFGWizard[ step ]( dialogData ) );
		self.dialogs.RFGInfoWizard.dialog( "option", "wizardStep", step );
	};

	/**
	 * Manage the Next button click in the repeating fields groups information wizard dialog.
	 *
	 * After the last step, reopen the main dialog when inserting/creating/appending a shortcode.
	 * Just close the wizard in any other scenario, like when editing or skipping a shortcode,
	 * or when in the Views loop wizard.
	 *
	 * @since m2m
	 */
	self.RFGInfoWizardStepNext = function() {
		var currentWizardStep = self.dialogs.RFGInfoWizard.dialog( "option", "wizardStep" ),
			comingWizardStep = currentWizardStep + 1;

		if (
			currentWizardStep == 0
			&& ! types_shortcode_i18n.conditions.plugins.toolsetViews
		) {
			self.dialogs.RFGInfoWizard.dialog( "close" );
			return;
		}

		if ( comingWizardStep > 3 ) {
			self.dialogs.RFGInfoWizard.dialog( "close" );
			return;
		}
		self.RFGInfoWizardStep( comingWizardStep );
	};

	/**
	 * Manage the Previous button lick in the repeating fields groups information wizard dialog.
	 *
	 * @since m2m
	 */
	self.RFGInfoWizardStepPrevious = function() {
		var currentWizardStep = self.dialogs.RFGInfoWizard.dialog( "option", "wizardStep" ),
			comingWizardStep = currentWizardStep - 1;
		if ( comingWizardStep < 1 ) {
			comingWizardStep = 1;
		}
		self.RFGInfoWizardStep( comingWizardStep );
	};

	/**
	 * Display a dialog for inserting a generic shortcode.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *
	 * @since m2m
	 */
	self.shortcodeDialogOpen = function( dialogData ) {

		// Race condition:
		// We close the main dialog before opening the shortcode dialog,
		// so we can keep the .modal-open classname in the document body, to:
		// - avoid scrolling
		// - prevent positioning issues with toolset_select2
		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}
		Toolset.hooks.doAction( 'wpv-action-wpv-fields-and-views-dialog-do-maybe-close' );

		// We are probably receive a base64-encoded string, which is necessary for preserving unicode characters
		// inside of field options (e.g. checkboxes option labels).
		if ( _.has( dialogData, 'parameters' ) && _.isString( dialogData.parameters ) ) {
			dialogData.parameters = JSON.parse(WPV_Toolset.Utils.editor_decode64(dialogData.parameters));
		}

		_.defaults( dialogData, {
			parameters: {},
			overrides: {},
			dialog: self.dialogs.shortcode,
			conditions: types_shortcode_i18n.conditions
		});

		if ( ! _.has( dialogData.parameters, 'metaType' )  ) {
			dialogData.parameters.metaType = 'typesGenericType';
		}

		// Post reference fields should not be insertable: fire the info wizard
		if ( 'post' === dialogData.parameters.metaType ) {
			self.dialogs.postFieldInfoWizard.dialog( "option", "wizardData", dialogData );
			self.postFieldInfoWizardStep( 0 );
			return;
		}

		// Repeating Field Groups fields should not be insertable: fire the info wizard
		if ( 'repeatable_field_group' === dialogData.parameters.metaType ) {
			self.dialogs.RFGInfoWizard.dialog( "option", "wizardData", dialogData );
			self.RFGInfoWizardStep( 0 );
			return;
		}

		/**
		 * Toolset hooks action: shortcode dialog requested. Types and shared versions.
		 *
		 * Nothing has happened yet, we just got a request to open the shortcode dialog.
		 *
		 * @since m2m
		 */
		Toolset.hooks.doAction( 'types-action-shortcode-dialog-requested', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-requested', dialogData );

		// Show the "empty" dialog with a spinner while loading dialog content
		self.dialogs.shortcode.dialog( 'open' ).dialog({
			title: dialogData.title
		});
		self.dialogs.shortcode.html( self.shortcodeDialogSpinnerContent );

		/**
		 * Toolset hooks action: shortcode dialog preloaded. Types and shared versions.
		 *
		 * The dialog is open and contains a spinner.
		 *
		 * @since m2m
		 */
		Toolset.hooks.doAction( 'types-action-shortcode-dialog-preloaded', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-preloaded', dialogData );

		// Warning!! The shortcodes data is stored in types_shortcode_i18n,
		// but assigning any of the objects it contains is done by reference
		// so it would modify permanently the original set.
		// Using $.extend with deep cloning.
		var typesShortcodeData = $.extend( true, {}, types_shortcode_i18n );

		// Load the specific field type attributes definitions, or a generic set
		if ( _.has( typesShortcodeData.attributes, dialogData.parameters.metaType ) ) {
			var shortcodeAttributes = typesShortcodeData.attributes[ dialogData.parameters.metaType ];
		} else {
			var shortcodeAttributes = typesShortcodeData.attributes[ 'typesGenericType' ];
		}

		// Inject the attributes for repeating fields
		if ( 'multiple' == dialogData.parameters.metaNature ) {
			if ( _.isEmpty( shortcodeAttributes.displayOptions.fields ) ) {
				shortcodeAttributes.displayOptions.fields = typesShortcodeData.repeatingAttributes;
			} else {
				shortcodeAttributes.displayOptions.fields = _.extend(
					shortcodeAttributes.displayOptions.fields,
					typesShortcodeData.repeatingAttributes
				);
			}
		}

		// All Types shortcodes require an item selector and a closing tag
		if ( 'posts' == dialogData.parameters.metaDomain ) {
			shortcodeAttributes = _.extend(
				shortcodeAttributes,
				Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-postSelector-attributes', {} )
			);
			shortcodeAttributes.postSelector.fields.content = { type: 'content', hidden: true };
		} else if ( 'terms' == dialogData.parameters.metaDomain ) {
			shortcodeAttributes = _.extend(
				shortcodeAttributes,
				{ typesViewsTermSelector: typesShortcodeData.selectorGroups.typesViewsTermSelector }
			);
			shortcodeAttributes.typesViewsTermSelector.fields.content = { type: 'content', hidden: true };
		} else if ( 'users' == dialogData.parameters.metaDomain ) {
			if ( 'users' == Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-gui-get-gui-target', 'posts' ) ) {
				shortcodeAttributes = _.extend(
					shortcodeAttributes,
					{ typesViewsUserSelector: typesShortcodeData.selectorGroups.typesViewsUserSelector }
				);
				shortcodeAttributes.typesViewsUserSelector.fields.content = { type: 'content', hidden: true };
			} else {
				shortcodeAttributes = _.extend(
					shortcodeAttributes,
					{ typesUserSelector: typesShortcodeData.selectorGroups.typesUserSelector }
				);
				shortcodeAttributes.typesUserSelector.fields.content = { type: 'content', hidden: true };
			}
		}

		// Add the templates and attributes to the main set of data, and render the dialog
		var templateData = _.extend(
			dialogData,
			{
				templates:  self.templates,
				attributes: shortcodeAttributes
			}
		);

		self.dialogs.shortcode.html( self.templates.dialog( templateData ) );

		// Initialize the dialog tabs, if needed
		if ( self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs-list > li' ).length > 1 ) {
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs' )
				.tabs({
					beforeActivate: function( event, ui ) {

						var valid = Toolset.hooks.applyFilters( 'toolset-filter-is-shortcode-attributes-container-valid', true, ui.oldPanel );
						if ( ! valid ) {
							event.preventDefault();
							ui.oldTab.focus().addClass( 'toolset-shortcode-gui-tabs-incomplete' );
							setTimeout( function() {
								ui.oldTab.removeClass( 'toolset-shortcode-gui-tabs-incomplete' );
							}, 1000 );
						}
					}
				})
				.addClass( 'ui-tabs-vertical ui-helper-clearfix' )
				.removeClass( 'ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all' );
			$( '#js-toolset-shortcode-gui-dialog-tabs ul, #js-toolset-shortcode-gui-dialog-tabs li' )
				.removeClass( 'ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
		} else {
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs-list' ).remove();
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs' ).addClass( 'toolset-shortcodes__tabs_single' );
		}

		/**
		 * Toolset hooks action: shortcode dialog loaded. Types, Types specific per field type, and shared versions.
		 *
		 * The dialog is open and contains the attributes GUI.
		 *
		 * @since m2m
		 */
		Toolset.hooks.doAction( 'types-action-shortcode-dialog-loaded', dialogData );
		if ( _.has( dialogData.parameters, 'metaType' ) ) {
			Toolset.hooks.doAction( 'types-action-shortcode-' + dialogData.parameters.metaType + '-dialog-loaded', dialogData );
		}
		Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-loaded', dialogData );

	};

	/**
	 * Manage existing attribute values when opening the shortcode dialog for editing it.
	 *
	 * Currently used by the Views loop wizard as it injects existing attribute values when editing.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *         attributes  object Pairs of attribute key and value to force in.
	 *         content     string Shortcode content to force in.
	 *     dialog     dialog jQuery UI dialog object.
	 *
	 * @since m2m
	 */
	self.manageEditingOverrides = function( dialogData ) {
		if ( _.has( dialogData.overrides, 'attributes' ) ) {
			_.each( dialogData.overrides.attributes, function( value, key, list ) {
				if ( dialogData.dialog.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-' + key ).length > 0 ) {
					var attribute_wrapper = dialogData.dialog.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-' + key ),
						attribute_type = attribute_wrapper.data( 'type' );
					switch ( attribute_type ) {
						case 'select':
						case 'select2':
							if ( attribute_wrapper.find( '.js-shortcode-gui-field option[value="' + value + '"]' ).length != 0 ) {
								attribute_wrapper.find( '.js-shortcode-gui-field' ).val( value ).trigger( 'change' );
							}
							break;
						case 'radio':
							if ( attribute_wrapper.find( '.js-shortcode-gui-field[value="' + value + '"]' ).length != 0 ) {
								attribute_wrapper.find( '.js-shortcode-gui-field[value="' +  value + '"]' ).prop( 'checked', true ).trigger( 'change' );
							}
							break;
						case 'number':
						case 'text':
						case 'url':
						case 'fixed':
							attribute_wrapper.find( '.js-shortcode-gui-field' ).val( value ).trigger( 'change ');
							break;
						case 'textarea':
							// @todo check this
							attribute_wrapper.find( '.js-shortcode-gui-field' ).val( value ).trigger( 'change' );
							break;

					}
				} else {
					//data.dialog.find( '.wpv-dialog' ).prepend( '<span class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper-for-' + key + '" data-attribute="' + key + '" data-type="param"><input type="hidden" name="' + key + '" value="' + value + '" disabled="disabled" /></span>' );
				}
			});
		}
		if (
			_.has( dialogData.overrides, 'content' )
			&& dialogData.overrides.content !== undefined
		) {
			// @todo check this
			dialogData.dialog.find( '.js-toolset-shortcode-gui-content' ).val( dialogData.overrides.content );
		}
	};

	/**
	 * Generate extra attributes for field types that support custom output per option.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *         attributes  object Pairs of attribute key and value to force in.
	 *         content     string Shortcode content to force in.
	 *     dialog     dialog jQuery UI dialog object.
	 *
	 * @since m2m
	 */
	self.manageMetaoptions = function( dialogData ) {
		if ( _.has( dialogData.parameters, 'metaOptions' ) ) {

			dialogData.dialog
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-metaOptions input' )
					.data( 'metaOptions', dialogData.parameters.metaOptions );

			if ( 'radio' == dialogData.parameters.metaType ) {
				self.manageMetaoptionsForRadio( dialogData );
			}
			if ( 'checkboxes' == dialogData.parameters.metaType ) {
				self.manageMetaoptionsForCheckboxes( dialogData );
			}

		}
	};

	/**
	 * Manage some special selectors for options, like colorpickers,
	 * until we create a proper colorpicker option type.
	 *
	 * @param object dialogData
	 * @since 3.4
	 */
	self.manageSpecialSelectors = function( dialogData ) {
		if ( 'image' === dialogData.parameters.metaType ) {
			var $paddingColor = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:padding_color input.js-shortcode-gui-field' );
			self.initColorpicker( $paddingColor );
		}
	};

	/**
	 * Initialize a colorpicker, if available, over some option fields.
	 *
	 * @param object $selector
	 * @since 3.4
	 */
	self.initColorpicker = function( $selector ) {
		if ( ! self.hasColorpicker ) {
			return;
		}
		$selector.wpColorPicker({
			change: function( event, ui ) {},
			clear: function() {},
			palettes: true
		});
	};

	/**
	 * Generate extra attributes for radio fields.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *         attributes  object Pairs of attribute key and value to force in.
	 *         content     string Shortcode content to force in.
	 *     dialog     dialog jQuery UI dialog object.
	 *
	 * @since m2m
	 */
	self.manageMetaoptionsForRadio = function( dialogData ) {
		var combo = dialogData.dialog.find( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo .js-toolset-shortcode-gui-dialog-item-group' ).detach(),//$( '.js-toolset-shortcode-gui-dialog-item-group', '.js-toolset-shortcode-gui-attribute-wrapper-for-outputCustomCombo' ).detach(),
			container = $( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo' );

		_.each( dialogData.parameters.metaOptions, function( value, key, list ) {
			var comboClone = combo.clone();

			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue' )
					.attr( 'data-attribute', 'selectedValue_' + key );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue input.js-shortcode-gui-field' )
					.attr( 'id', 'types_selectedValue_' + key );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue strong' )
					.html( function( index, html ) {
						return html.replace( '%%OPTION%%', value.title );
					});
			comboClone.appendTo( container );
		});
	};

	/**
	 * Generate extra attributes for checkboxes fields.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *         attributes  object Pairs of attribute key and value to force in.
	 *         content     string Shortcode content to force in.
	 *     dialog     dialog jQuery UI dialog object.
	 *
	 * @since m2m
	 */
	self.manageMetaoptionsForCheckboxes = function( dialogData ) {
		var combo = dialogData.dialog.find( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo .js-toolset-shortcode-gui-dialog-item-group' ).detach(),//$( '.js-toolset-shortcode-gui-dialog-item-group', '.js-toolset-shortcode-gui-attribute-wrapper-for-outputCustomCombo' ).detach(),
			container = $( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo' ),
			eachIndex = 0;

		_.each( dialogData.parameters.metaOptions, function( value, key, list ) {
			var comboClone = combo.clone();

			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue' )
					.attr( 'data-attribute', 'selectedValue_' + eachIndex );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue input.js-shortcode-gui-field' )
					.attr( 'id', 'types_selectedValue_' + eachIndex );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-selectedValue strong' )
					.html( function( index, html ) {
						return html.replace( '%%OPTION%%', value.title );
					});
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-unselectedValue' )
					.attr( 'data-attribute', 'unselectedValue_' + eachIndex );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-unselectedValue input.js-shortcode-gui-field' )
					.attr( 'id', 'types_unselectedValue_' + eachIndex );
			comboClone
				.find( '.js-toolset-shortcode-gui-attribute-wrapper-for-unselectedValue strong' )
					.html( function( index, html ) {
						return html.replace( '%%OPTION%%', value.title );
					});
			comboClone.appendTo( container );
			eachIndex++;
		});
	};

	/**
	 * Manage the attributes GUI based on the "output" attribte value.
	 *
	 * @since m2m
	 * @todo Split in specific methods per field type
	 */
	$( document ).on( 'change', '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-output]', function() {
		var metaType = $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val(),
			checkedValue = $( '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-output]:checked' ).val(),
			dialogContainer = $( '#js-types-shortcode-gui-dialog-container-shortcode' );

		switch ( metaType ) {
			case 'audio':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-preload', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-loop', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-autoplay', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-preload', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-loop', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-autoplay', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'checkbox':
				if ( 'custom' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideDown( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideUp( 'fast' );
				}
				break;

			case 'checkboxes':
				if ( 'custom' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-separator', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-separator', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'date':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:format', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style input.js-shortcode-gui-field:radio:checked', dialogContainer ).trigger( 'change' );
				}
				break;

			case 'email':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'embed':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'file':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'image':
				var selectedSize = $( '.js-shortcode-gui-field[name=types-size]:checked', dialogContainer ).val();
				self.manageImageFieldByOutputAndSize( checkedValue, selectedSize, dialogContainer );
				break;

			case 'numeric':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'radio':
				if ( 'raw' == checkedValue || 'normal' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-group-for-outputCustomCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'skype':
				if ( 'raw' == checkedValue ) {
					// legacy
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-button_style', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-class', dialogContainer ).slideUp( 'fast' );

					// 3.1
                    $( '.js-toolset-shortcode-gui-attribute-group-for-skype_button_style', dialogContainer ).slideUp( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-group-for-skype_chat_style', dialogContainer ).slideUp( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-wrapper-for-skype_preview', dialogContainer ).slideUp( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-wrapper-for-receiver', dialogContainer ).slideUp( 'fast' );
				} else {
					// legacy
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-button_style', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-class', dialogContainer ).slideDown( 'fast' );

                    // 3.1
                    $( '.js-toolset-shortcode-gui-attribute-group-for-skype_button_style', dialogContainer ).slideDown( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-group-for-skype_chat_style', dialogContainer ).slideDown( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-wrapper-for-skype_preview', dialogContainer ).slideDown( 'fast' );
                    $( '.js-toolset-shortcode-gui-attribute-wrapper-for-receiver', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'url':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-target', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:target', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-title', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-target', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-target input.js-shortcode-gui-field:radio:checked', dialogContainer ).trigger( 'change' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;

			case 'video':
				if ( 'raw' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-poster', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-preload', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-loop', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-autoplay', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-poster', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-preload', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-loop', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-autoplay', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', dialogContainer ).slideDown( 'fast' );
				}
				break;
		}
	});

	/**
	 * Manage the attributes GUI based on the "style" attribte value. Used for date fields.
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-style]', function() {
		var metaType = $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val(),
			checkedValue = $( '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-style]:checked' ).val(),
			dialogContainer = $( '#js-types-shortcode-gui-dialog-container-shortcode' );

		switch ( metaType ) {
			case 'date':
				if ( 'calendar' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:format', dialogContainer ).slideUp( 'fast' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-format input.js-shortcode-gui-field:radio:checked', dialogContainer ).trigger( 'change' );
				}
				break;
		}

	});

	/**
	 * Manage the attributes GUI based on the "size" attribte value. Used for image fields.
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-size]', function() {
		var metaType = $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val(),
			checkedValue = $( '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-size]:checked' ).val(),
			dialogContainer = $( '#js-types-shortcode-gui-dialog-container-shortcode' );

		switch ( metaType ) {
			case 'image':
				var selectedOutput = $( '.js-shortcode-gui-field[name=types-output]:checked', dialogContainer ).val();
				self.manageImageFieldByOutputAndSize( selectedOutput, checkedValue, dialogContainer );
				break;
		}

	});

	/**
	 * Manage the attributes GUI based on the "proportional" attribte value. Used for image fields.
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-proportional]', function() {
		var metaType = $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val(),
			checkedValue = $( '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-proportional]:checked' ).val(),
			dialogContainer = $( '#js-types-shortcode-gui-dialog-container-shortcode' );

		switch ( metaType ) {
			case 'image':
				if ( 'true' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize input.js-shortcode-gui-field:radio:checked', dialogContainer ).trigger( 'change' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:padding_color', dialogContainer ).slideUp( 'fast' );
				}
				break;
		}

	});

	/**
	 * Manage the attributes GUI based on the "resize" attribte value. Used for image fields.
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-resize]', function() {
		var metaType = $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val(),
			checkedValue = $( '#js-types-shortcode-gui-dialog-container-shortcode .js-shortcode-gui-field[name=types-resize]:checked' ).val(),
			dialogContainer = $( '#js-types-shortcode-gui-dialog-container-shortcode' );

		switch ( metaType ) {
			case 'image':
				if ( 'pad' == checkedValue ) {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color', dialogContainer ).slideDown( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color input.js-shortcode-gui-field:radio:checked', dialogContainer ).trigger( 'change' );
				} else {
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color', dialogContainer ).slideUp( 'fast' );
					$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:padding_color', dialogContainer ).slideUp( 'fast' );
				}
				break;
		}
	});

	/**
	 * Manage the options on image fields.
	 *
	 * @param string output
	 * @param string size
	 * @param object $dialogContainer
	 * @since 3.4
	 */
	self.manageImageFieldByOutputAndSize = function ( output, size, $dialogContainer ) {
		switch ( output ) {
			case 'raw':
				$( '.js-toolset-shortcode-gui-attribute-group-for-titleAltCombo', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-align', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-size', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:padding_color', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-class', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style', $dialogContainer ).slideUp( 'fast' );
				//$( '.js-toolset-shortcode-gui-attribute-group-for-attributesCombo', $dialogContainer ).slideUp( 'fast' );
				break;
			case 'url':
				$( '.js-toolset-shortcode-gui-attribute-group-for-titleAltCombo', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-align', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-padding_color', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:padding_color', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-class', $dialogContainer ).slideUp( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style', $dialogContainer ).slideUp( 'fast' );

				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-size', $dialogContainer ).slideDown( 'fast' );
				switch ( size ) {
					case 'custom':
						$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideDown( 'fast' );
						break;
					default:
						$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideUp( 'fast' );
						break;
				}
				break;
			default:
				$( '.js-toolset-shortcode-gui-attribute-group-for-titleAltCombo', $dialogContainer ).slideDown( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-align', $dialogContainer ).slideDown( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-size', $dialogContainer ).slideDown( 'fast' );

				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-class', $dialogContainer ).slideDown( 'fast' );
				$( '.js-toolset-shortcode-gui-attribute-wrapper-for-style', $dialogContainer ).slideDown( 'fast' );
				switch ( size ) {
					case 'custom':
						$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideDown( 'fast' );

						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional', $dialogContainer ).slideDown( 'fast' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional .js-shortcode-gui-field:checked', $dialogContainer ).trigger( 'change' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', $dialogContainer ).slideDown( 'fast' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize .js-shortcode-gui-field:checked', $dialogContainer ).trigger( 'change' );
						break;
					case 'full':
						$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideUp( 'fast' );

						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional', $dialogContainer ).slideUp( 'fast' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', $dialogContainer ).slideUp( 'fast' );
						break;
					default:
						$( '.js-toolset-shortcode-gui-attribute-group-for-sizeCombo', $dialogContainer ).slideUp( 'fast' );

						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional', $dialogContainer ).slideDown( 'fast' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-proportional .js-shortcode-gui-field:checked', $dialogContainer ).trigger( 'change' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize', $dialogContainer ).slideDown( 'fast' );
						$( '.js-toolset-shortcode-gui-attribute-wrapper-for-resize .js-shortcode-gui-field:checked', $dialogContainer ).trigger( 'change' );
						break;
				}
				break;
		}
		return self;
	};

    /**
	 * Skype Preview
     */
    self.attributeSkypeOnDialogReadyCountCalled = 0;
    self.attributeSkypeOnDialogReady = function() {
        // preview button
        let skypePreviewButton = $( '#toolset-skype-preview-button' );

        // check if the dialog is ready
        if( skypePreviewButton.length === 0 ) {
            if( self.attributeSkypeOnDialogReadyCountCalled == 50 ) {
                // if the dialog is not loaded after 5 seconds it will never be loaded I guess
                return;
            }

            self.attributeSkypeOnDialogReadyCountCalled += 1;
            setTimeout( self.attributeSkypeOnDialogReady, 100 );
            return;
        }

        // reset called count
        self.attributeSkypeOnDialogReadyCountCalled = 0;

        //
        let skypePreviewChat = $( '#toolset-skype-preview-chat' ),
            cssFile1Url = '//latest-swc.cdn.skype.com/v/0.80.87/css/swc-sdk.min.css',
            cssFile2Url = '//latest-swc.cdn.skype.com/v/0.80.87/css/swc-builder.min.css',
            interval,
            maxTimeToWaitForCDN = 5000,
            timeWaitedForCDN = 0,
            intervalTime = 250,
            tplLoading = $( '#toolset-attribute-skype-preview-loading' ),
            tplPreviewLoaded = $( '#toolset-attribute-skype-preview-loaded' ),
            tplPreviewOffline = $( '#toolset-attribute-skype-preview-offline' );


        /* Interval function to check if CSS of Skype CDN was loaded */
        function checkCSSLoadedIntervalCallback() {
            if( timeWaitedForCDN >= maxTimeToWaitForCDN ) {
                // waited too long for CDN
                CSSCouldNotBeLoaded();
                return;
            }

            // when the css is loaded the preview button will have the css 'cursor' attribute being set to 'pointer'
            // AND the chat preview has 'overflow' set to 'hidden'
            if( skypePreviewButton.css( 'cursor' ) == 'pointer' && skypePreviewChat.css( 'overflow' ) == 'hidden' ) {
                CSSLoaded();
            }

            timeWaitedForCDN += intervalTime;
        };

        /* The CSS of Skype CDN was loaded successfully */
        function CSSLoaded() {
            clearInterval( interval );
            previewControls();
            tplLoading.hide();
            tplPreviewLoaded.show();
        }

        /* Skype Preview Controls */
        function previewControls() {
            if( $( '#js-types-shortcode-gui-dialog-container-shortcode input[name=metaType]' ).val() != 'skype' ) {
            	// no skype field
				return;
			}

			let	buttonPreview = $( '#toolset-skype-preview-button' ),

				// style of button (bubble / rounded / rectangle)
				buttonStyle = $( '#types-button' ),

				// button color
				buttonColor = $( '#types-button-color' ),
				buttonColorDefault = '#00AFF0',

				// container for icon and label (only active when style of button is not bubble)
                buttonIconLabelContainer = $( '.js-toolset-shortcode-gui-attribute-group-for-skype_button_style_enhanced' ),

				// button icon
				buttonIcon = $( '#types-button-icon' ),
				buttonIconDefault = buttonIcon.val(),
				buttonIconUserInput = buttonIcon.val(),

				// button label
				buttonLabel = $( '#types-button-label' ),
				buttonLabelDefault = buttonLabel.val(),
				buttonLabelUserInput = buttonLabel.val(),

				// button link (this will get the user color as background)
				buttonLink = $( '#toolset-skype-preview-button-link' ),

				// button text (for user label)
				buttonText = $( '#toolset-skype-preview-button-text' ),

				// chat color
				chatColor = $( '#types-chat-color' ),
				chatColorDefault = '#80ddff',

				// chat message (will get the chat color as background)
				chatMessageMe = $( '#toolset-attribute-skype-preview-loaded .message.me' ),

				// chat send button
				chatSendButton = $( '#toolset-attribute-skype-preview-loaded .sendButton' ),

				// regex for hex color (supports 3 and 6 digits formats)
                regexHexColor = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;


			// button style
			buttonStyle.on( 'change', function() {
				buttonPreview.attr( 'class', 'skype-button ' + buttonStyle.val() );

				switch( buttonStyle.val() ) {
                    case 'bubble':
                        buttonIconLabelContainer.slideUp( 'fast' );
                        buttonIconUserInput = buttonIcon.val();
                        buttonLabelUserInput = buttonLabel.val();
                        buttonIcon.val( buttonIconDefault );
                        buttonLabel.val( buttonLabelDefault );
                        break;
                    default:
                        buttonIconLabelContainer.slideDown( 'fast' );
                        buttonIcon.val( buttonIconUserInput ).trigger( 'change' );
                        buttonLabel.val( buttonLabelUserInput );
				}
			} );

			// button icon
			buttonIcon.on( 'change', function() {
                buttonIconUserInput = buttonIcon.val();

				switch( buttonIcon.val() ) {
					case 'disabled':
                        buttonPreview.addClass( 'textonly' );
                        break;
					default:
						buttonPreview.removeClass( 'textonly' );
				}
			} );

			// button label
			buttonLabel.on( 'keydown keyup', function() {
                buttonLabelUserInput = buttonLabel.val();

				switch( buttonLabel.val() ) {
					case '':
						buttonText.html( 'Contact us' );
						break;
					default:
						buttonText.html( buttonLabel.val() );
				}
			} );

			// button color
			buttonColor.on( 'keydown keyup', function() {
				let color = buttonColor.val();

				if( color != buttonColorDefault && color.match( regexHexColor ) ) {
					buttonLink.css( 'background-color', color );
				} else {
					buttonLink.removeAttr( 'style' );
				}
			 } );

			// chat color
			chatColor.on( 'keydown keyup', function() {
				let color = chatColor.val().toLowerCase();

				if( color != chatColorDefault && color.match( regexHexColor ) ) {
					chatMessageMe.css( 'background-color', color );
					chatSendButton.css( 'background-color', 'rgb(105, 118, 123)' );
				} else {
                    chatMessageMe.removeAttr( 'style' );
                    chatSendButton.removeAttr( 'style' );
				}
			} );
		}

        /* The CSS of Skype CDN could not be loeaded */
        function CSSCouldNotBeLoaded() {
            clearInterval( interval );
            tplLoading.hide();
            tplPreviewOffline.show();
        }

        /* Start the Builder, by loading the Skype CSS files */
        $( '<link>' ).attr( { rel: "stylesheet", type: "text/css", href: cssFile1Url } ).appendTo("head");
        $( '<link>' ).attr( { rel: "stylesheet", type: "text/css", href: cssFile2Url } ).appendTo("head");

        interval = setInterval( checkCSSLoadedIntervalCallback, intervalTime );
    }

	/**
	 * Clean the attributes list from metaXXXX pairs, before composing the shortcode.
	 *
	 * @param shortcodeAttributeValues object
	 *
	 * @since m2m
	 */
	self.cleanTypesAttributes = function( shortcodeAttributeValues ) {
		shortcodeAttributeValues['metaType'] = false;
		shortcodeAttributeValues['metaNature'] = false;
		shortcodeAttributeValues['metaDomain'] = false;
		shortcodeAttributeValues['metaOptions'] = false;
		return shortcodeAttributeValues;
	};

	/**
	 * Clean the attributes list based on selected attribute values, before composing the shortcode.
	 *
	 * @param shortcodeAttributeValues object
	 * @param shortcodeData            object
	 *     rawAttributes object Pairs of attributes key and value, without processing
	 *
	 * @since m2m
	 * @todo Split in specific methods per field type
	 */
	self.adjustAttributes = function( shortcodeAttributeValues, shortcodeData ) {
		var rawAttributes = shortcodeData.rawAttributes;
		switch( rawAttributes.metaType ) {
			case 'audio':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['preload'] = false;
					shortcodeAttributeValues['loop'] = false;
					shortcodeAttributeValues['autoplay'] = false;
				}
				break;
			case 'checkbox':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['selectedValue'] = false;
					shortcodeAttributeValues['unselectedValue'] = false;
				}
				break;
			case 'checkboxes':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues = _.pick( shortcodeAttributeValues, function( value, key, object ) {
						return ( ! /selectedValue/.test( key ) );
					});
				}
				break;
			case 'date':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['style'] = false;
					shortcodeAttributeValues['format'] = false;
				} else {
					if (
						_.has( shortcodeAttributeValues, 'style' )
						&& 'calendar' == shortcodeAttributeValues.style
					) {
						shortcodeAttributeValues['format'] = false;
					}
				}
				break;
			case 'email':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['title'] = false;
					shortcodeAttributeValues['class'] = false;
					shortcodeAttributeValues['style'] = false;
				}
				break;
			case 'file':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['title'] = false;
					shortcodeAttributeValues['class'] = false;
					shortcodeAttributeValues['style'] = false;
				}
				break;
			case 'image':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& (
						'raw' == shortcodeAttributeValues.output
						|| 'url' == shortcodeAttributeValues.output
					)
				) {
					shortcodeAttributeValues['title'] = false;
					shortcodeAttributeValues['alt'] = false;
					shortcodeAttributeValues['align'] = false;
					shortcodeAttributeValues['proportional'] = false;
					shortcodeAttributeValues['resize'] = false;
					shortcodeAttributeValues['padding_color'] = false;
					shortcodeAttributeValues['class'] = false;
					shortcodeAttributeValues['style'] = false;
					if ( 'url' == shortcodeAttributeValues.output ) {
						shortcodeAttributeValues['url'] = 'true';
						shortcodeAttributeValues['output'] = false;
						if ( 'custom' == shortcodeAttributeValues['size'] ) {
							shortcodeAttributeValues['size'] = false;
						} else {
							shortcodeAttributeValues['width'] = false;
							shortcodeAttributeValues['height'] = false;
						}
					} else {
						shortcodeAttributeValues['size'] = false;
						shortcodeAttributeValues['width'] = false;
						shortcodeAttributeValues['height'] = false;
					}
				} else {
					if ( 'custom' != shortcodeAttributeValues['size'] ) {
						shortcodeAttributeValues['width'] = false;
						shortcodeAttributeValues['height'] = false;
					}
					if ( 'full' == shortcodeAttributeValues['size'] ) {
						shortcodeAttributeValues['resize'] = false;
						shortcodeAttributeValues['proportional'] = false;
						shortcodeAttributeValues['padding_color'] = false;
					} else {
						if ( 'false' === shortcodeAttributeValues['proportional'] ) {
							shortcodeAttributeValues['resize'] = false;
						}
						if ( 'pad' != shortcodeAttributeValues['resize'] ) {
							shortcodeAttributeValues['padding_color'] = false;
						}
					}
				}
				break;
			case 'numeric':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['format'] = false;
				}
				break;
			case 'radio':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues = _.pick( shortcodeAttributeValues, function( value, key, object ) {
						return ( ! /selectedValue/.test( key ) );
					});
				}
				break;
			case 'skype':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					// legacy
					shortcodeAttributeValues['button_style'] = false;
					shortcodeAttributeValues['class'] = false;
					shortcodeAttributeValues['style'] = false;

					// 3.1
                    shortcodeAttributeValues['button'] = false;
                    shortcodeAttributeValues['button-color'] = false;
                    shortcodeAttributeValues['button-icon'] = false;
                    shortcodeAttributeValues['button-label'] = false;
                    shortcodeAttributeValues['chat-color'] = false;
                    shortcodeAttributeValues['receiver'] = false;
				}
				break;
			case 'url':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['title'] = false;
					shortcodeAttributeValues['class'] = false;
					shortcodeAttributeValues['style'] = false;
					shortcodeAttributeValues['target'] = false;
				}
				break;
			case 'video':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'raw' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['width'] = false;
					shortcodeAttributeValues['height'] = false;
					shortcodeAttributeValues['poster'] = false;
					shortcodeAttributeValues['preload'] = false;
					shortcodeAttributeValues['loop'] = false;
					shortcodeAttributeValues['autoplay'] = false;
				}
				break;
		}
		return shortcodeAttributeValues;
	};

	/**
	 * Adjust the attributes list for the item selector values.
	 *
	 * @param shortcodeAttributeValues object
	 * @param shortcodeData            object
	 *     rawAttributes object Pairs of attributes key and value, without processing
	 *
	 * @since m2m
	 */
	self.adjustTypesMetaSelectorAttributes = function( shortcodeAttributeValues, shortcodeData ) {
		var rawAttributes = shortcodeData.rawAttributes;
		if ( 'users' == rawAttributes['metaDomain'] ) {
			if ( _.has( rawAttributes, 'item' ) ) {
				switch( rawAttributes['item'] ) {
					case 'author':
						shortcodeAttributeValues['user_is_author'] = 'true';
						break;
					case 'current':
					case false:
						shortcodeAttributeValues['current_user'] = 'true';
						break;
					case 'viewloop':
						// No attribute should be added now
						break;
					default:
						shortcodeAttributeValues['user_id'] = rawAttributes.item;
						break;
				}
				shortcodeAttributeValues['item'] = false;
			}
		}
		if ( 'terms' == rawAttributes['metaDomain'] ) {
			if (
				_.has( rawAttributes, 'id' )
				&& 'viewloop' == rawAttributes['id']
			) {
				shortcodeAttributeValues['id'] = false;
			}
		}
		return shortcodeAttributeValues;
	};

	/**
	 * Adjust the composed shortcode for field types that can produce extra shortcodes per option.
	 *
	 * @param craftedShortcode string
	 * @param shortcodeData    object
	 *     shortcode     string The shortcode handle
	 *     attributes    object Pairs of attributes key and value, after processing
	 *     rawAttributes object Pairs of attributes key and value, without processing
	 *
	 * @since m2m
	 * @todo Split for each field type
	 */
	self.adjustComposedShortcodes = function( craftedShortcode, shortcodeData ) {
		var rawAttributes = shortcodeData.rawAttributes,
			shortcodeAttributeValues = shortcodeData.attributes,
			composedShortcode = craftedShortcode,
			composedAttributeString = '';
		switch( rawAttributes.metaType ) {
			case 'checkbox':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'custom' === shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['output'] = false;

					if ( ! _.has( shortcodeAttributeValues, 'selectedValue' ) ) {
						shortcodeAttributeValues['selectedValue'] = '';
					}
					if ( ! _.has( shortcodeAttributeValues, 'unselectedValue' ) ) {
						shortcodeAttributeValues['unselectedValue'] = '';
					}

					if( 'true' === shortcodeAttributeValues['show_name'] ) {
						shortcodeAttributeValues['show_name'] = 'if-not-empty';
					}

					_.each( shortcodeAttributeValues, function( value, key ) {
						if (
							value
							&& -1 === _.indexOf( [ 'selectedValue', 'unselectedValue' ], key )
						) {
							composedAttributeString += " " + key + "='" + value + "'";
						}
					});

					composedShortcode = "[" + shortcodeData.shortcode
					    + composedAttributeString
						+ ' state="checked"]'
						+ shortcodeAttributeValues['selectedValue']
						+ "[/" + shortcodeData.shortcode + "]"
						+ "[" + shortcodeData.shortcode
					    + composedAttributeString
						+ ' state="unchecked"]'
						+ shortcodeAttributeValues['unselectedValue']
						+ "[/" + shortcodeData.shortcode + "]";

					craftedShortcode = composedShortcode;
				}
				break;

			case 'checkboxes':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'custom' === shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['output'] = false;
					shortcodeAttributeValues['separator'] = false;

					var composedShortcode = '',
					    composedAttributeString = '',
						shortcodeAttributeValuesClone = _.clone( shortcodeAttributeValues ),
						metaOptions = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-metaOptions input' ).data( 'metaOptions' ),
						loopIndex = 0;

					shortcodeAttributeValuesClone = _.pick( shortcodeAttributeValuesClone, function( value, key, object ) {
						return ( ! /selectedValue/.test( key ) );
					});

					_.each( metaOptions, function( value, key  ) {
						if ( _.has( shortcodeAttributeValues, 'selectedValue_' + loopIndex ) ) {
							composedAttributeString = '';
							_.each( shortcodeAttributeValuesClone, function( value, key ) {
								if ( value ) {
									composedAttributeString += " " + key + "='" + value + "'";
								}
							});
							composedShortcode += "[" + shortcodeData.shortcode
								+ composedAttributeString
								+ ' state="checked" option="' + loopIndex + '"]'
								+ shortcodeAttributeValues['selectedValue_' + loopIndex]
								+ "[/" + shortcodeData.shortcode + "]";
						}

						if ( _.has( shortcodeAttributeValues, 'unselectedValue_' + loopIndex ) ) {
							composedAttributeString = '';
							_.each( shortcodeAttributeValuesClone, function( value, key ) {
								if ( value ) {
									composedAttributeString += " " + key + "='" + value + "'";
								}
							});
							composedShortcode += "[" + shortcodeData.shortcode
								+ composedAttributeString
								+ ' state="unchecked" option="' + loopIndex + '"]'
								+ shortcodeAttributeValues['unselectedValue_' + loopIndex]
								+ "[/" + shortcodeData.shortcode + "]";
						}
						loopIndex++;
					});
					craftedShortcode = composedShortcode;
				}
				break;

			case 'radio':
				if (
					_.has( shortcodeAttributeValues, 'output' )
					&& 'custom' == shortcodeAttributeValues.output
				) {
					shortcodeAttributeValues['output'] = false;

					var composedShortcode = '',
					    composedAttributeString = '',
						shortcodeAttributeValuesClone = _.clone( shortcodeAttributeValues ),
						metaOptions = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-metaOptions input' ).data( 'metaOptions' );

					shortcodeAttributeValuesClone = _.pick( shortcodeAttributeValuesClone, function( value, key, object ) {
						return ( ! /selectedValue/.test( key ) );
					});

					_.each( metaOptions, function( value, key  ) {
						if ( _.has( shortcodeAttributeValues, 'selectedValue_' + key ) ) {
							composedAttributeString = '';
							_.each( shortcodeAttributeValuesClone, function( value, key ) {
								if ( value ) {
									composedAttributeString += " " + key + "='" + value + "'";
								}
							});
							composedShortcode += "[" + shortcodeData.shortcode
								+ composedAttributeString
								+ ' option="' + key + '"]'
								+ shortcodeAttributeValues['selectedValue_' + key]
								+ "[/" + shortcodeData.shortcode + "]";
						}
					});
					craftedShortcode = composedShortcode;
				}
				break;
		}
		return craftedShortcode;
	};

	/**
	 * Adjust the dialog buttons labels depending on the current GUI action.
	 *
	 * @param dialogData object
	 *     shortcode  string Shortcode name.
	 *     title      string Form title.
	 *     parameters object Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides  object Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *     dialog     dialog jQuery UI dialog object.
	 *
	 * @since m2m
	 */
	self.manageShortcodeDialogButtonpane = function( dialogData ) {
		switch ( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-action', '' ) ) {
			case 'save':
				$( '.js-types-shortcode-gui-button-back' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-craft' ), types_shortcode_i18n.action.save );
				break;
			case 'create':
			case 'append':
				$( '.js-types-shortcode-gui-button-back' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-craft' ), types_shortcode_i18n.action.create );
				break;
			case 'edit':
				$( '.js-types-shortcode-gui-button-back' ).hide();
				self.setButtonText( $( '.js-types-shortcode-gui-button-craft' ), types_shortcode_i18n.action.update );
				break;
			case 'insert':
			default:
				$( '.js-types-shortcode-gui-button-back' ).show();
				self.setButtonText( $( '.js-types-shortcode-gui-button-craft' ), types_shortcode_i18n.action.insert );
				break;
		}
	};

	//--------------------------------
	// Compatibility
	//--------------------------------

	/**
	 * Handle the event that is triggered by Fusion Builder when creating the WP editor instance.
	 *
	 * The event was added as per our request because Fusion Builder does not load the WP editor using
	 * the native PHP function "wp_editor". It creates the WP editor instance on JS, so no PHP actions
	 * to add custom media buttons like ours are available. It generates the media button plus the toolbar that
	 * contains it as javascript objects that it appends to its own template. It offers no way of adding our custom
	 * buttons to it.
	 *
	 * @param event    The actual event.
	 * @param editorId The id of the editor that is being created.
     *
	 * @since m2m
	 */
	$( document ).on( 'fusionButtons', function( event, editorId ) {
		if ( ! types_shortcode_i18n.conditions.plugins.toolsetViews ) {
			self.addTypesButtonToDynamicEditor( editorId );
		}
	});

	/**
	 * Handle the event that is triggered by Toolset Types when creating a WP editor instance.
	 *
	 * The event is fired when a WYSIWYG field is dynamically initialized in the backend.
	 *
	 * @param event			The actual event.
	 * @param editorId		The id of the editor that is being created.
	 *
	 * @since 2.0
	 */
	$( document ).on( 'toolset:types:wysiwygFieldInited toolset:forms:wysiwygFieldInited', function( event, editorId ) {
		if ( ! types_shortcode_i18n.conditions.plugins.toolsetViews ) {
			self.addTypesButtonToDynamicEditor( editorId );
		}
	});

	/**
	 * Add a Types button dynamically to any native editor that contains a media toolbar, given its editor ID.
	 *
	 * @since m2m
	 */
	self.addTypesButtonToDynamicEditor = function( editorId ) {
		var $mediaButtons = $( '#wp-' + editorId + '-media-buttons' ),
			button = '<span'
				+ ' class="button js-types-in-toolbar"'
				+ ' data-editor="' + editorId + '">'
				+ '<i class="icon-types-logo fa fa-types-custom ont-icon-18 ont-color-gray"></i>'
				+ '<span class="button-label">' + types_shortcode_i18n.title.button + '</span>'
				+ '</span>',
			$typesButton = $( button );

		if ( $mediaButtons.find( '.js-types-in-toolbar' ).length == 0 ) {
			$typesButton.appendTo( $mediaButtons );
		}
	};

	/**
	 * Hook a click callback to the small "plus" icon next to the Page Builders' text inputs (right now only supports
	 * Beaver Builder) to allow Toolset shortcodes inserting.
	 *
	 * @since 3.1.0
	 */
	$( document ).on( 'click', '.js-toolset-shortcode-in-page-builder-input', function( e ) {
		e.preventDefault();
		Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'append' );
		var $this = $( this ),
			$toolsetShortcodeInPageBuilderInputWrapper = $this.parent( '.js-toolset-shortcode-in-page-builder-input-wrapper' ),
			$toolsetShortcodeInPageBuilderInputWrapperTarget = $toolsetShortcodeInPageBuilderInputWrapper.find( '.js-toolset-shortcode-in-page-builder-input-target' );
		if (
			$toolsetShortcodeInPageBuilderInputWrapper.length > 0
			&& $toolsetShortcodeInPageBuilderInputWrapperTarget.length > 0
		) {
			// Set the selector of the text input where the shortcode will be inserted.
			Toolset.hooks.doAction( 'toolset-action-set-selector-to-append-shortcode', $toolsetShortcodeInPageBuilderInputWrapperTarget );
		}
		return false;
	});

	/**
	 * Displays the Types shortcodes modal whenever the button that inserts shortcodes inside page builder inputs is clicked.
	 *
	 * @since 3.0.8
	 */
	self.displayTypesShortcodesModalForPageBuilders = function() {
		if ( ! types_shortcode_i18n.conditions.plugins.toolsetViews ) {
			self.openTypesDialog();
		}
	};

	/**
	 * Init main method:
	 * - Init API hooks.
	 * - Init templates
	 * - Init dialogs.
	 * - Init the Admin Bar button.
	 *
	 * @since m2m
	 */
	self.init = function() {

		self.initHooks()
			.initTemplates()
			.initDialogs()
			.initAdminBarButton();

	};

	self.init();

}

jQuery( function( $ ) {
	Toolset.Types.shortcodeGUI = new Toolset.Types.shortcodeManager( $ );
});
