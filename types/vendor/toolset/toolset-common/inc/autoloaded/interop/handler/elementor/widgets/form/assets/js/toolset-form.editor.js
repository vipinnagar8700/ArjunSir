/**
 * Backend script for the Toolset Form Elementor widget.
 *
 * @since 3.0.7
 */

var ToolsetCommon = ToolsetCommon || {};
ToolsetCommon.PageBuilderWidget	= ToolsetCommon.PageBuilderWidget || {};
ToolsetCommon.PageBuilderWidget.Elementor = ToolsetCommon.PageBuilderWidget.Elementor || {};

ToolsetCommon.PageBuilderWidget.Elementor.FormWidget = function( $ ) {

	var self = this;

	self.onEditFormButtonClick = function( data ) {
		var selectedFormID = data.options.elementSettingsModel.attributes.form || '0';
		if ( '0' !== selectedFormID ) {
			window.open( window.toolsetPageBuilderElementorWidgetFormStrings.editFormURL + selectedFormID , '_blank' );
		} else {
			alert( window.toolsetPageBuilderElementorWidgetFormStrings.selectFormFirstMessage );
		}
	};

	self.disableFormSelectionDefaultOption = function( selector ) {
		selector.find( 'option[value="0"]' ).attr( 'disabled', true );
	};

	/**
	 * Handles the visibility toggling of the resource (post/user) controls section and the included controls by setting the values
	 * of some hidden Elementor widget controls.
	 *
	 * The method is triggered upon 'panel/open_editor/widget/toolset-view', thus when the sidebar of the widget is opened.
	 *
	 * @param panel
	 * @param model
	 * @param view
	 */
	self.toggleResourceToEditSection = function( panel, model, view ) {
		// When a change is detected in the DOM subtree of the panel (the sidebar)...
		$( panel.$el ).on('DOMSubtreeModified', function(e) {
			if ( 0 === e.target.innerHTML.length ) {
				return;
			}

			// ... try to find the Form selection control of the widget...
			var $formSelector = $( this ).find( 'select[data-setting=form]' );
			//.. and if a value is already selected, then disable the default value of the control.
			if ( '0' !== $formSelector.val() ) {
				self.disableFormSelectionDefaultOption( $formSelector );
			}

			if ( $formSelector.length ) {
				// Hook an "on-change" event callback...
				$( $formSelector ).off( 'change' ).on( 'change', function() {
					self.disableFormSelectionDefaultOption( $( this ) );
					var selectedFormObject = window.toolsetPageBuilderElementorWidgetFormStrings.allForms[ $formSelector.val() ];
					// If the selected form is an edit form...
					if ( 'edit' === selectedFormObject.form_action ) {
						// ... set the resource to edit as the editing resource of the form,
						model.setSetting( window.toolsetPageBuilderElementorWidgetFormStrings.resourceToEditControlKey, selectedFormObject.form_type );
					} else {
						// ... otherwise set the resource to edit as the empty value because the selected form is a "new" form.
						model.setSetting( window.toolsetPageBuilderElementorWidgetFormStrings.resourceToEditControlKey, '' );
					}
				} );
			}
		});
	};

	self.attachEvents = function() {
		if ( window.toolsetPageBuilderElementorWidgetFormStrings.isPreviewMode ) {
			elementor.channels.editor.on( 'toolset:pageBuilderWidgets:elementor:editor:editForm', self.onEditFormButtonClick );

			elementor.hooks.addAction( 'panel/open_editor/widget/toolset-form', self.toggleResourceToEditSection );
		}
	};

	self.init = function() {
		self.attachEvents();
	};

	self.init();
};

jQuery( window ).on( 'elementor/frontend/init', function() {
	ToolsetCommon.PageBuilderWidget.Elementor.FormWidget = new ToolsetCommon.PageBuilderWidget.Elementor.FormWidget( jQuery );
});
