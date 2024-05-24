/**
 * Backend script for the Toolset View Elementor widget.
 *
 * @since 3.0.5
 */

var ToolsetCommon = ToolsetCommon || {};
ToolsetCommon.PageBuilderWidget	= ToolsetCommon.PageBuilderWidget || {};
ToolsetCommon.PageBuilderWidget.Elementor = ToolsetCommon.PageBuilderWidget.Elementor || {};

ToolsetCommon.PageBuilderWidget.Elementor.ViewWidget = function( $ ) {

	var self = this;

	self.onEditViewButtonClick = function( view ) {
		var selectedViewID = view.options.elementSettingsModel.attributes.view || '0';
		if ( '0' !== selectedViewID ) {
			window.open( window.toolsetPageBuilderElementorWidgetViewStrings.editViewURL + selectedViewID , '_blank' );
		} else {
			alert( window.toolsetPageBuilderElementorWidgetViewStrings.selectViewFirstMessage );
		}
	};

	self.disableViewSelectionDefaultOption = function( selector ) {
		selector.find( 'option[value="0"]' ).attr( 'disabled', true );
	};

	/**
	 * Handles the visibility toggling of the "Custom Search" controls section and the included controls by setting the values
	 * of some hidden Elementor widget controls.
	 *
	 * The method is triggered upon 'panel/open_editor/widget/toolset-view', thus when the sidebar of the widget is opened.
	 *
	 * @param panel
	 * @param model
	 * @param view
	 */
	self.toggleCustomSearchSection = function( panel, model, view ) {
		// When a change is detected in the DOM subtree of the panel (the sidebar)...
		$( panel.$el ).on('DOMSubtreeModified', function(e) {
			if ( 0 === e.target.innerHTML.length ) {
				return;
			}

			// ... try to find the View selection control of the widget...
			var $viewSelector = $( this ).find( 'select[data-setting=view]' );
			//.. and if a value is already selected, then disable the default value of the control.
			if ( '0' !== $viewSelector.val() ) {
				self.disableViewSelectionDefaultOption( $viewSelector );
			}

			if ( $viewSelector.length ) {
				// Hook an "on-change" event callback...
				$( $viewSelector ).off( 'change' ).on( 'change', function() {
					self.disableViewSelectionDefaultOption( $( this ) );
					var data = {
						action: window.toolsetPageBuilderElementorWidgetViewStrings.hasCustomSearchAction,
						wpnonce: window.toolsetPageBuilderElementorWidgetViewStrings.hasCustomSearchNonce,
						view_id: this.value
					};

					// ... that triggers an AJAX call to "read" the custom search settings of the selected View.
					$.post({
						url: window.toolsetPageBuilderElementorWidgetViewStrings.ajaxURL,
						data: data,
						success: function( response ) {
							if ( response.success ) {
								// If the View includes a custom search...
								if ( response.data.hasCustomSearch ) {
									// ... set the hidden "has_custom_search" control value (that handles the visibility of
									// the "Custom Search" widget controls section) to true (show the section) ...
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasCustomSearchControlKey, 'true' );
								} else {
									// ... otherwise set it to false (hide the section).
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasCustomSearchControlKey, 'false' );
								}

								// Same with the hidden "has_submit_button" control value (that handles the visibility of some warnings when a
								// submit button is not included in the View).
								if ( response.data.hasSubmitButton ) {
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasSubmitButtonControlKey, 'true' );
								} else {
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasSubmitButtonControlKey, 'false' );

									// We are forcibly setting the "Where do you want to display the results?" control to "In other place on this same page"
									// as Views that contain custom search without a submit button can only include search results on the same page.
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.formOnlyDisplayControlKey, window.toolsetPageBuilderElementorWidgetViewStrings.formOnlyDisplayControlValueSamePage );
								}

								if ( response.data.hasBlocks ) {
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasBlocksControlKey, 'true' );
								} else {
									model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.hasBlocksControlKey, 'false' );
								}
							} else {
								alert( response.data.message );
							}
						},
						error: function () {
							alert( window.toolsetPageBuilderElementorWidgetViewStrings.ajaxErrorMessage );
						}
					});
				} );
			}
		});
	};

	self.addSearchResultsToPage = function( view ) {
		var selectedOtherPage = view.options.elementSettingsModel.attributes.other_page || '0',
			selectedViewID = view.options.elementSettingsModel.attributes.view || '0';
		if ( '0' !== selectedOtherPage ) {
			window.open( window.toolsetPageBuilderElementorWidgetViewStrings.editPostForResultsURL.replace( '%1$s', selectedOtherPage ).replace( '%2$s', selectedViewID ), '_blank' );
		}
	};

	self.dismissAddingSearchResultsToPage = function( view ) {
		view._parent.model.setSetting( window.toolsetPageBuilderElementorWidgetViewStrings.dismissAddingSearchResultsToPage, 'true' );
	};

	/**
	 * When a View with image preloading is set (set to true by default),
	 * the preview loads a hidden View container. This happens because image preloading
	 * is initialized during "document ready", which is too early for this widget,
	 * and even more: it happens on an asset not included here.
	 *
	 * Likewise, some Views layout styles need adjustment:
	 * - Masonry.
	 *
	 * We are running this callback upon Toolset View Elementor widget ready to refresh the View pagination whenever the View
	 * has one.
	 *
	 * @param $scope
	 */
	self.reInitViewsElements = function( $scope ) {
		if ( $scope.find( '.js-wpv-view-layout' ).length > 0 ) {
			$scope.find( '.js-wpv-view-layout' ).css( { "visibility": "visible" } );
		}
		if ( $scope.find( '.tb-masonry' ).length > 0 ) {
			$scope.find( '.tb-masonry .tb-brick' ).css( { "grid-row-end": "span 89" } );
		}
	};

	self.attachEvents = function() {
		if ( window.toolsetPageBuilderElementorWidgetViewStrings.isPreviewMode ) {
			elementor.channels.editor.on( 'toolset:pageBuilderWidgets:elementor:editor:editView', self.onEditViewButtonClick );

			elementor.channels.editor.on( 'toolset:pageBuilderWidgets:elementor:editor:addSearchResultsToPage', self.addSearchResultsToPage );

			elementor.channels.editor.on( 'toolset:pageBuilderWidgets:elementor:editor:addSearchResultsToPageDismiss', self.dismissAddingSearchResultsToPage );

			elementor.hooks.addAction( 'panel/open_editor/widget/toolset-view', self.toggleCustomSearchSection );

			elementorFrontend.hooks.addAction( 'frontend/element_ready/toolset-view.default', self.reInitViewsElements );
		}
	};

	self.init = function() {
		self.attachEvents();
	};

	self.init();
};

jQuery( window ).on( 'elementor/frontend/init', function() {
	ToolsetCommon.PageBuilderWidget.Elementor.ViewWidget = new ToolsetCommon.PageBuilderWidget.Elementor.ViewWidget( jQuery );
});
