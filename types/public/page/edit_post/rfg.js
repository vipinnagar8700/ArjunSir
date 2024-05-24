/* eslint-disable */

/**
 * @refactoring !!! The whole RFG codebase needs to be restructured and documented for easier navigation. Consider using head.js for JS assets.
 */
;(function( $ ) {
	var Types = Types || {};
	Types.RepeatableGroup = {};
	Types.RepeatableGroup.Model = {};

	var staticData = JSON.parse( WPV_Toolset.Utils.editor_decode64( $( '#types_rfg_model_data' ).html() ) ),
		lastActiveGroupPerLevel = {};

	var isHorizontalViewActive = false;

	/**
	 * Function to update HTML of inputs related to user changes
	 *
	 * This is needed becauses otherwise we will lose user input after resorting. It's a problem combining sorting
	 * with knockout. Because after sorting the element must be re-applied, therefore we must save the item before
	 * sorting, otherwise knockout would use the original inputs.
	 * The easiest way to save the user changes is to update the DOM.
	 *
	 * How to avoid this?
	 * For this we need to get a proper rendering for the input fields, which should than be controlled by knockout.
	 * Currently we just passing the full html of the field input to knockout.
	 *
	 * https://stackoverflow.com/questions/1388893/jquery-html-in-firefox-uses-innerhtml-ignores-dom-changes#1388965
	 */
	var oldHTML = $.fn.html;
	$.fn.typesUpdateHtml = function() {
		if( arguments.length ) return oldHTML.apply( this, arguments );
		$( "input,button", this ).each( function() {
			this.setAttribute( 'value', this.value );
		} );

		updateWYSIWYGHtml.apply( this );

		$( "input:radio,input:checkbox", this ).each( function() {
			if( this.checked ) this.setAttribute( 'checked', 'checked' );
			else this.removeAttribute( 'checked' );
		} );
		$( "option", this ).each( function() {
			if( this.selected ) this.setAttribute( 'selected', 'selected' );
			else this.removeAttribute( 'selected' );
		} );
		return oldHTML.apply( this );
	};

	/**
	 * This function is used to reinitialize tinyMCE editor for WYSIWYG fields.
	 * And resets the editor content lost after sorting.
	 */
	function updateWYSIWYGHtml() {
		$( "textarea", this ).each( function() {
			if( $(this).hasClass( 'wp-editor-area' ) ) {
				var editorID = this.getAttribute('id');
				//attempt to fetch value from the base textarea element first
				//if empty get the tinyMCE content
				//this is needed get up to date value from both text/visual editors
				var editorContent = $(this).val() || tinymce.get( editorID ).getContent()
				initWysiwygField( editorID );
				tinymce.get( editorID ).setContent( editorContent );
			} else {
				this.innerHTML = this.value;
			}
		} );
	}

	Types.RepeatableGroup.Model.Col = function( index ) {
		self = this;
		self.index = index;
		self.isVisible = ko.observable( false );
	};

	/**
	 * Group model
	 *
	 * @param data
	 * @param level
	 * @param field
	 * @constructor
	 */
	Types.RepeatableGroup.Model.Group = function( data, level, field ) {
		var self = this;

		self.id = data.id || 1;
		self.parent_post_id = data.parent_post_id || 0;
		self.title = data.title || '';
		self.level = level || 1;
		self.field = field || null;
		self.controlsActive = data.controlsActive || 0;
		self.wpmlIsTranslationModeSupported = data.wpmlIsTranslationModeSupported || 0;
		self.wpmlFilterExistsForOriginalData = data.wpmlFilterExistsForOriginalData || 0;
		self.wpmlIsDefaultLanguageActive = data.wpmlIsDefaultLanguageActive || 0;
		self.isTranslatable = data.isTranslatable || 0;
		self.visible = ko.observable( false );

		/**
		 * Determines if self.items is filled with group items or if they need to be loaded first.
		 * @since 3.3.2
		 */
		self.isPopulated = ko.observable( typeof(data.isPopulated) !== 'undefined' ? data.isPopulated : true );

		/**
		 * Note: Works only for the vertical view (not relevant for the horizontal one).
		 *
		 * @returns {*|jQuery|HTMLElement}
		 * @since 3.3.2
		 */
		self.getElement = function() {
			var selector = '.c-rgx [data-rfg-id=' + self.id + ']';
			if( !! self.getParent()) {
				selector += '[data-parent-post-id=' + self.getParent() + ']';
			}
			return $(selector);
		};

		/**
		 * Get the parent RFG item ID (if it exists).
		 *
		 * @returns {null|int}
		 * @since 3.3.2
		 */
		self.getParent = function() {
			return ( !!self.field && !!self.field.item ? self.field.item.id : null );
		};

		// Map Headlines
		self.headlines = ko.observableArray( ko.utils.arrayMap( data.headlines || [],
			function( headlineData ) {
				return new Types.RepeatableGroup.Model.Headline( headlineData, self );
			} ) );

		// Map Items
		self.items = ko.observableArray( ko.utils.arrayMap( data.items || [],
			function( itemData ) {
				return new Types.RepeatableGroup.Model.Item( itemData, self );
			} ) );

		/**
		 * For the horizontal view we have a bunch of extra steps to make conditions work
		 * as all fields share the title in form of the table headline row
		 */
		if( isHorizontalViewActive ) {
			// cols
			self.cols = ko.observableArray();

			var calculateIsColVisibleTimeout = [];

			/**
			 * Calculates if an col must be shown or not. Even for
			 * hidden fields the cell will be shown if one of all fields is visible
			 *
			 * The real calculation happens in self._calculateIsColVisible(), which will be
			 * called with an timeout of 50ms, which will be overwritten by each field of the same
			 * col to make sure this is only called when all fields visibility was updated.
			 *
			 * @param col
			 */
			self.calculateIsColVisible = function( col ) {
				if( typeof calculateIsColVisibleTimeout[col.index] != 'undefined' ) {
					clearTimeout( calculateIsColVisibleTimeout[col.index] )
				}

				// delay here as all fields need to be updated before calculation for cols visibility
				calculateIsColVisibleTimeout[col.index] = setTimeout(
					self._calculateIsColVisible,
					50,
					col
				);
			};

			self._calculateIsColVisible = function( col ) {
				var fieldVisible = false;

				for (let i = 0; i < self.items().length; i++) {
					let field = self.items()[i].fields()[col.index];
					if( field.fieldConditionsMet() ) {
						// one field visible = col visible
						fieldVisible = true;

						// no need to check further fields
						break;
					}
				}

				// col is visible
				col.isVisible( fieldVisible );

				// just to be sure there are no glitches on the position fixed elements
				setTimeout( Types.RepeatableGroup.Functions.cssExtension, 200 );
			};

			/**
			 * table cols
			 */
			for (let i = 0; i < self.headlines().length; i++) {
				let col = new Types.RepeatableGroup.Model.Col( i );
				self.cols.push( col );
			}

			// function to refresh col visibility
			self.refreshColVisibility = function() {
				for (let i = 0; i < self.cols().length; i++) {
					self.calculateIsColVisible( self.cols()[i] );
				}
			};

			// do refresh once on group init
			self.refreshColVisibility();
		}

		/**
		 * Toggle visibility.
		 *
		 * @param {boolean} [becomesVisible] If set, it determines the new state. Otherwise, the visibility
		 *     will become the opposite of the current value.
		 */
		self.toggleGroupVisibility = function(becomesVisible) {
			$( 'div.tooltip' ).remove(); // remove any tooltip

			if ( 'undefined' === typeof(becomesVisible) ) {
				becomesVisible = ! self.visible();
			}
			self.visible( becomesVisible );

			// for vertical view
			if( self.visible && $( '.js-rgy' ).length ) {
				// only one group per level should be visible
				if( lastActiveGroupPerLevel[self.level] && lastActiveGroupPerLevel[self.level] !== self  ) {
					lastActiveGroupPerLevel[self.level].visible( false );
				}

				lastActiveGroupPerLevel[self.level] = self;
			}

			// Pass the visibility information down to the group items if we're in the horizontal view: They're
			// visible immediately.
			if(isHorizontalViewActive) {
				_.each( self.items(), function( item ) {
					item.toggleVisibility();
				} );
			}

			if( self.field === null ) {
				// nothing else to do for a non nested group
				return;
			}

			// nested group - count active groups (for rowspan)
			if( self.visible() ) {
				if( self.field.item.currentNestedActiveGroup !== null && self.field.item.currentNestedActiveGroup !== self ) {
					self.field.item.currentNestedActiveGroup.toggleGroupVisibility();
				}
				self.field.item.activeNestedGroups( self.field.item.activeNestedGroups() + 1 );
				self.field.item.currentNestedActiveGroup = self;
			} else {
				self.field.item.activeNestedGroups( self.field.item.activeNestedGroups() - 1 );
			}

			Types.RepeatableGroup.Functions.initLegacyFields();
			Types.RepeatableGroup.Functions.cssExtension();

			_.defer(self.determineOverflowFix);
		};

		self.startItemDeletion = function( item ) {
			item.startDeletionCountdown();
		};

		/**
		 * Remove item from group
		 * @param item
		 */
		self.removeItem = function( item ) {
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: staticData.action.name,
					skip_capability_check: true,
					wpnonce: staticData.action.nonce,
					remove_id: item.id,
					belongs_to_post_id: item.group.parent_post_id,
					repeatable_group_action: 'json_repeatable_group_remove_item',
					parent_post_translation_override: Types.RepeatableGroup.Functions.getNewPostTranslationOverride(),
				},
				dataType: 'json',
				success: function( response ) {
					if( response.success ) {
						self.items.remove( item );
						if( self.items().length === 0 ) {
							self.visible( false );
						}
					} else {
						// system error
						item.stopDeletionCountdown();
						alert( response.data );
					}

				},
				error: function( response ) {
					console.log( response );
				}
			} );

			Types.RepeatableGroup.Functions.cssExtension();
		};


		self.isAddingItem = ko.observable(false);

		/**
		 * Add item to group
		 * @param field
		 */
		self.addItem = function( field ) {
			var parentPostId =  typeof field.item !== 'undefined'
				? field.item.id
				: self.parent_post_id;

			self.isAddingItem(true);

			// ajax call to create new item... return will be the form inputs
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: staticData.action.name,
					skip_capability_check: true,
					wpnonce: staticData.action.nonce,
					parent_post_id: parentPostId,
					repeatable_group_action: 'json_repeatable_group_add_item',
					repeatable_group_id: self.id,
					parent_post_translation_override: Types.RepeatableGroup.Functions.getNewPostTranslationOverride(),
				},
				dataType: 'json',
				success: function( response ) {
					if( response.success ) {
						if( self.items().length === 0 ) {
							self.toggleGroupVisibility();
						}

						Types.RepeatableGroup.Functions.applyTinyMCEToolbarSettings(response.data);

						var newItem = self.processAddedItem(response.data.item, response.data.fieldConditions);

						newItem.toggleVisibility();
						newItem.title( '' );
						newItem.editTitleStart();
						Types.RepeatableGroup.Functions.cssExtension();
						// This is necessary for the initialization of the Address field in a horizontal view.
						Types.RepeatableGroup.Functions.ajaxFieldsLoadedEvent();
					} else {
						if( response.data.message ) {
							alert( response.data.message );
						}
					}
				},
				error: function( response ) {
					console.log( response );
				},
				complete: function() {
					self.isAddingItem(false);
				}
			} );
		};

		/**
		 * Process a new item that needs to be added to the group.
		 *
		 * Disregarding if it's a newly created item or if the group is being populated additionally.
		 *
		 * @param itemData
		 * @param fieldConditions
		 * @returns {Types.RepeatableGroup.Model.Item}
		 * @since 3.3.2
		 */
		self.processAddedItem = function(itemData, fieldConditions) {
			var newItem = new Types.RepeatableGroup.Model.Item( itemData, self );
			self.items.push( newItem );

			// set field conditions for new item
			if( 'undefined' !== typeof fieldConditions ) {
				Types.RepeatableGroup.Functions.setFieldConditions( fieldConditions );
			}

			// Yoast integration
			initYoastFields( [ itemData ] );

			// Refresh col visibility for horizontal view
			if( isHorizontalViewActive ) {
				newItem.group.refreshColVisibility();
			}

			// legacy control of file fields must be initialized again
			Types.RepeatableGroup.Functions.initLegacyFields(newItem);

			return newItem;
		};


		self.listHeadlines = function() {
			var headlinesList = '';
			ko.utils.arrayForEach( this.headlines(), function( headline ) {
				headlinesList += headline.title + '<br />';
			} );
			return headlinesList;
		};

		// Disable Item Title Introduction
		self.itemTitleIntroductionActive = ko.observable( data.itemTitleIntroductionActive );

		self.disableTitleIntroduction = function( item ) {
			self.itemTitleIntroductionActive( false );
			item.editTitleStart();

			// store decision to usermeta
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: staticData.action.name,
					skip_capability_check: true,
					wpnonce: staticData.action.nonce,
					repeatable_group_action: 'json_repeatable_group_item_title_introduction_dismiss',
				},
				dataType: 'json',
				success: function( response ) {},
				error: function( response ) {}
			} );
		};

		/**
		 * True means that the group's pop-up in the vertical mode is larger than the viewport and needs to be
		 * adjusted. Updated by self.determineOverflowFix().
		 *
		 * @since 3.3.2
		 */
		self.needsOverflowFix = ko.observable(false);

		/**
		 * Used for overwriting the "transform" style of a nested group in the vertical mode.
		 *
		 * See self.determineOverflowFix() for more information.
		 *
		 * @since 3.3.2
		 */
		self.verticalTransformStyle = ko.observable(null);

		/**
		 * Determine the HTML class for the nested group in the vertical mode.
		 *
		 * @since 3.3.2
		 */
		self.nestedContainerElementClass = ko.computed(function() {
			if( Types.RepeatableGroup.VIEW_VERTICAL === Types.RepeatableGroup.Functions.getViewMode()) {
				var elementClass = 'c-rgx c-rgy__body--nested';
				if(self.needsOverflowFix()) {
					elementClass += ' c-rgy--overflow-fix';

					if( Toolset.hooks.applyFilters( 'types-gutenberg-is-active', false ) ) {
						elementClass += ' c-rgy--overflow-fix-gutenberg';
					}
				}
				return elementClass;
			}
		});

		/**
		 * Determine height of the nested group in the vertical mode.
		 *
		 * @since 3.3.2
		 */
		self.nestedContainerElementMaxHeight = ko.computed(function() {
			if( Types.RepeatableGroup.VIEW_VERTICAL === Types.RepeatableGroup.Functions.getViewMode()) {
				var elementMaxHeight = 'none';
				if(self.needsOverflowFix()) {
					elementMaxHeight = $(window).innerHeight()*0.9 + 'px';
				}

				return elementMaxHeight;
			}
		});

		/**
		 * Determine width of the nested group in the horizontal mode.
		 *
		 * @since 3.3.2
		 */
		self.nestedContainerElementMaxWidth = ko.computed(function() {
			if( Types.RepeatableGroup.VIEW_HORIZONTAL === Types.RepeatableGroup.Functions.getViewMode()) {
				var elementMaxWidth = $('.c-rg').innerWidth() - 60 + 'px';

				return elementMaxWidth;
			}
		});

		/**
		 * Determine if an overflow fix is needed for a nested field group in the vertical mode.
		 *
		 * @since 3.3.2
		 */
		self.determineOverflowFix = function() {
			if( ! self.visible() ) {
				self.needsOverflowFix(false);
				return;
			}

			var viewportHeight = $(window).height();
			var $el = self.getElement();
			if(! $el.length) {
				return;
			}

			var elementHeight = $el.height();

			if(elementHeight > viewportHeight) {
				self.needsOverflowFix( true );
			} else {
				// If we're already in the "overflow fix" mode, the $el will never be longer than the viewport,
				// because the overflow fix is already applied. So, instead we check the nested element with
				// the list of children.
				var $innerEl = $el.find('.c-rgy__items--nested');
				if( self.needsOverflowFix() && $innerEl.length && $innerEl.height() > viewportHeight) {
					self.needsOverflowFix( true );
				} else {
					self.needsOverflowFix( false );
				}
			}

			// If the overflow fix is being applied and the element escapes from the top of the screen,
			// adjust the "transform" rule to shift it directly below the admin bar.
			// 42px = 32px admin bar + 10px margin
			// Applied in vertical mode only.
			if(self.needsOverflowFix() && $el.offset().top < 42) {
				self.verticalTransformStyle('translate(0, calc(-50% - (' + $el.offset().top + 'px - 42px)))');
			} else {
				self.verticalTransformStyle(null);
			}
		};

		self.visible.subscribe(function() { _.defer(self.determineOverflowFix) } );

		self.items.subscribe(function() { _.defer(self.determineOverflowFix) });

		self.isPopulating = ko.observable(false);

		self.showButtonClick = function() {
			if(self.isPopulated()) {
				self.toggleGroupVisibility();
			} else {
				self.populateGroup(self.toggleGroupVisibility);
			}
		};

		/**
		 * Populate this group with items if it hasn't been done already.
		 *
		 * @param {function|null} successCallback If provided, will be called after the group has been populated.
		 * @since 3.3.2
		 */
		self.populateGroup = function(successCallback) {
			if(self.isPopulated() || self.isPopulating()) {
				return;
			}
			self.isPopulating(true);

			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: staticData.action.name,
					skip_capability_check: true,
					wpnonce: staticData.action.nonce,
					parent_post_id: self.getParent(),
					repeatable_group_action: 'json_repeatable_group',
					repeatable_group_id: self.id,
					parent_post_translation_override: Types.RepeatableGroup.Functions.getNewPostTranslationOverride(),
				},
				dataType: 'json',
				success: function( response ) {
					if( response.success ) {
						Types.RepeatableGroup.Functions.applyTinyMCEToolbarSettings(response.data);

						_.each(response.data.repeatableGroup.items, function( item ) {
							self.processAddedItem(item)
						});

						// set field conditions for rfg items
						Types.RepeatableGroup.Functions.setFieldConditions( response.data.repeatableGroup.fieldConditions );

						// run field validation after fields are loaded
						Types.RepeatableGroup.Functions.ajaxFieldsLoadedEvent();

						Types.RepeatableGroup.Functions.cssExtension();

						self.isPopulated(true);

						if(!!successCallback) {
							successCallback();
						}
					} else {
						console.log( 'Repeatable Field Group with ID "' + self.id + '" could not be loaded.' );
					}
				},
				error: function( response ) {
					console.log( response );
				},
				complete: function() {
					self.isPopulating(false);
				}
			} );
		};

		/**
		 * Hook on WPToolset_Form_Conditional script toggle event to determine if a rfg field should be shown or not
		 */
		jQuery( document ).on( 'js_event_toolset_forms_conditional_field_toggled', function( e, data ) {
			var fieldName = data.container.attr( 'name' );
			if( ! fieldName || ! fieldName.startsWith( "types-repeatable-group" ) ) {
				// no rfg item field
				return;
			}

			// get id and field slug
			var explodeName = fieldName.match(/\[(.*?)]\[(.*?)]/);

			var itemId = parseInt( explodeName[1] ),
				fieldSlug = explodeName[2];

			// find affected item by item id
			var affectedItem = ko.utils.arrayFirst( self.items(), function( item ) {
				return item.id === itemId;
			});

			if( ! affectedItem ) {
				// no item found
				return;
			}

			// find affected field by field slug
			var affectedField = ko.utils.arrayFirst( affectedItem.fields(), function( field ) {
				return field.metaKey === fieldSlug || field.metaKey === 'wpcf-' + fieldSlug;
			});

			if( ! affectedField ) {
				// no field found
				return;
			}

			// set visibility
			affectedField.fieldConditionsMet( !!data.visible );

			// Horizontal specific - col visibility
			if( isHorizontalViewActive ) {
				let indexOfField = affectedField.item.fields.indexOf( affectedField );
				affectedField.item.group.calculateIsColVisible( affectedField.item.group.cols()[indexOfField] );
			}
		} );
	};


	/**
	 * Headline model
	 *
	 * @param data
	 * @param group
	 * @constructor
	 */
	Types.RepeatableGroup.Model.Headline = function( data, group ) {
		this.group = group;
		this.title = data.title || '';
		this.wpmlIsCopied = data.wpmlIsCopied || 0;
	};


	/**
	 * Item model
	 *
	 * @param data
	 * @param group
	 * @constructor
	 */
	Types.RepeatableGroup.Model.Item = function( data, group ) {
		var self = this;
		self.id = data.id || 0;
		self.title = ko.observable( data.title || '' );
		self.titleBeforeChange = self.title();
		self.group = group;
		self.activeNestedGroups = ko.observable( 0 );
		self.currentNestedActiveGroup = null;
		self.secondsToDelete = 4;
		self.shouldBeDeleted = ko.observable( false );
		self.shouldBeDeletedSeconds = ko.observable( self.secondsToDelete );
		self.shouldBeDeletedCountdown = null;
		self.visible = ko.observable( false );
		self.summaryString = ko.observable( data.id );
		self.fields = ko.observableArray( ko.utils.arrayMap( data.fields || [],
			function( fieldData ) {
				return new Types.RepeatableGroup.Model.Field( fieldData, self );
			} )
		);

		/*
		 * Edit Item Title
		 */
		self.editTitleTriggerVisible = ko.observable( false );
		self.editTitleTriggerShow = function() { self.editTitleTriggerVisible( true ); };
		self.editTitleTriggerHide = function() { self.editTitleTriggerVisible( false ); };

		self.editTitleActive = ko.observable( false );

		// Callback when the user starts editing the tilte
		self.editTitleStart = function( index ) {
			self.editTitleActive( true );

			// this will select all text of the title input
			$( '.js-rg-title-input:focus' ).select();

			// allow to use "tabulator" to go to next rfg item title
			$( window ).on( 'keydown.types-rfg-change-title-'+self.id, function( e ){
				if( e.key === "Tab" ) { // tab
					if( self.group.items()[index] && ! self.group.items()[index].visible() ) {
						// current item fields are not visible. In this case "Tab" will go to next item title
						var nextItemIndex = ( self.group.items()[index+1] ) ? index + 1 : 0;
						self.group.items()[nextItemIndex].editTitleStart(nextItemIndex);
						return false;
					}
				}
			});
			// allow to use "enter" to save and end the title editing
			// (without this "enter" would trigger post update and reload the page)
			$( window ).on( 'keydown.types-rfg-save-title-'+self.id, function( e ){
				if( e.key === "Enter" ) { // enter
					$( '.js-rg-title-input:focus' ).blur(); // unfocus input
					return false;
				}
			});
		};

		// Callback when the user finished editing the title (triggered on blur() of title input)
		self.editTitleDone = function() {
		   // edit done = remove "tabulator" & "enter" event
			$( window ).off( 'keydown.types-rfg-change-title-'+self.id );
			$( window ).off( 'keydown.types-rfg-save-title-'+self.id );

			// save title
			if( self.title() !== self.titleBeforeChange ) {
				self.titleBeforeChange = self.title();

				$.ajax( {
					url: ajaxurl,
					type: 'POST',
					data: {
						action: staticData.action.name,
						skip_capability_check: true,
						wpnonce: staticData.action.nonce,
						repeatable_group_action: 'json_repeatable_group_item_title_update',
						item_id: self.id,
						item_title: self.title()
					},
					dataType: 'json',
					success: function( response ) {
						if( ! response.success ) {
							// Technical issue
							alert( response.data );
						}
					},
					error: function( response ) {}
				} );
			}
		};

		// we need a special transition for the introduction tooltip, it can't be just
		ko.bindingHandlers.typesRFGTitleIntroductionVisible = {
			init: function( el, observable ) {
				// trigger update callback on init
				$( el ).toggle( observable() );
			},
			update: function( el, observable ) {
				// fade in / out and display block/none afterwards to make sure it's clickable or not
				var $el = $( el );
				if ( observable() ) {
					$el.fadeIn( 120, function() { $el.show() } );
				} else {
					$el.fadeOut( 120, function() { $el.hide() } );
				}
			}
		};

		/*
		 * Start Deletion
		 */
		self.startDeletionCountdown = function() {
			self.shouldBeDeleted( true );

			self.shouldBeDeletedCountdown = setInterval( function() {
				if( self.shouldBeDeletedSeconds() === 0 ) {
					clearInterval( self.shouldBeDeletedCountdown );
					self.group.removeItem( self );
				} else {
					self.shouldBeDeletedSeconds( self.shouldBeDeletedSeconds() - 1 );
				}
			}, 1000 );
		};

		/*
		 * Stop Deletion
		 */
		self.stopDeletionCountdown = function() {
			self.shouldBeDeleted( false );
			self.shouldBeDeletedSeconds( self.secondsToDelete );
			clearInterval( self.shouldBeDeletedCountdown );
		};

		/*
		 * We need to store the element, because otherwise the element will be re-applied
		 * to the view model, which would mean a lose of all user changes (input fields).
		 */
		self.storeItemForSortable = function( el ) {
			ko.utils.domData.set( el, 'originalItem', self );
		};

		self.updateFields = function(onlyWYSIWYG) {
			ko.utils.arrayForEach( this.fields(), function( field ) {
				if( field.repeatableGroup !== null ) {
					ko.utils.arrayForEach( field.repeatableGroup.items(), function( item ) {
						item.updateFields();
					} );
				}
				if(true === onlyWYSIWYG) {
					field.updateWYSIWYGEditors();
				} else {
					field.typesUpdateHtmlInput();
				}
			} );
		};

		/**
		 * Toggle visibility
		 */
		self.toggleVisibility = function() {
			/* Allow only one open item
			ko.utils.arrayForEach( self.group.items(), function( item ) {
				if( item.visible ) {
					item.visible( false );
				}
			} );
			*/

			self.visible( ! self.visible() );
			Types.RepeatableGroup.Functions.initLegacyFields();

			// Fire 'toolset_types_rfg_item_toggle' event when the item is toggled
			$( document ).trigger( 'toolset_types_rfg_item_toggle', [ self ] );

			if( self.visible() ) {
				// disable summary on open
				self.summaryString( '' );
			} else {
				// update summary if closed
				ko.utils.arrayForEach( this.fields(), function( field ) {
					if( field.repeatableGroup === null ) {
						field.typesUpdateHtmlInput();
					}
				} );

				self.updateSummary();
			}

			// Have to remove these classes because if don't it is not uploaded.
			jQuery( '[data-item-id=' + self.id + '] .js-wpt-remove-on-submit' ).removeClass( 'js-wpt-remove-on-submit' );

			if(self.visible()) {
				// trigger WYSIWYG reInit once the item becomes visible
				jQuery( document ).trigger( 'toolset:types:reInitWYSIWYG', data);
			}

			// This cannot happen sooner because re-initializing WYSIWYG fields may change their height
			// (when initializing for the first time) and that influences the correct height of the
			// item deletion overlay.
			Types.RepeatableGroup.Functions.cssExtension();

			if(self.group) {
				_.defer(function() { self.group.determineOverflowFix(); });
			}

			if(self.visible() && self.hasNestedGroups()) {
				// When this item is displayed, directly populate all nested RFGs it contains.
				// That will speed things up for the user considerably.
				_.defer(function() {
					_.each(self.nestedGroups(), function(group) {
						if(!group.isPopulated()) {
							group.populateGroup(null);
						}
					})
				});
			}
		};

		/**
		 * An array of nested groups within this item.
		 * @return array
		 * @since 3.3.2
		 */
		self.nestedGroups = ko.computed(function() {
			return _.map(
				_.filter(self.fields(), function(field) { return null !== field.repeatableGroup; }),
				function(field) {
					return field.repeatableGroup;
				}
			);
		});

		/**
		 * @return bool
		 * @since 3.3.2
		 */
		self.hasNestedGroups = ko.computed(function() {
			return self.nestedGroups().length > 0;
		});


		self.updateSummary = function() {
			var newSummaryString = '';
			ko.utils.arrayForEach( this.fields(), function( field ) {
				if( field.repeatableGroup === null ) {
					field.updateUserValue();
					if( field.userValue !== '' ) {
						newSummaryString = newSummaryString + field.userValue + ', ';
					}
				}
			} );

			newSummaryString = newSummaryString.replace( /,\s*$/, '' );
			newSummaryString = newSummaryString.slice(0, 100) + ( newSummaryString.length > 100 ? '...' : '' );

			self.summaryString( newSummaryString );
		};

		self.updateSummary();

		/**
		 * When a particular element needs to be highlighted, make sure the group with the RFG item is visible.
		 *
		 * Handle recursively for nested RFGs.
		 *
		 * @since 3.3
		 */
		Toolset.hooks.addAction( 'toolset-validation-highlight-element', function( itemId ) {
			if (itemId !== self.id) {
				return;
			}

			// If the group is nested, show the parents first.
			//
			// The recursion is constructed so that it starts showing groups from the top level to the deepest one.
			if (null !== self.group.field && null !== self.group.field.item) {
				Toolset.hooks.doAction( 'toolset-validation-highlight-element', self.group.field.item.id );
			}

			if (!self.group.visible()) {
				self.group.toggleGroupVisibility();
			}
		} );
	};

	/**
	 * Field model
	 *
	 * @param data
	 * @param item
	 * @constructor
	 */
	Types.RepeatableGroup.Model.Field = function( data, item ) {
		var self = this;
		self.item = item;
		self.title = data.title || '';
		self.metaKey = data.metaKey || '';
		self.slug = data.slug || '';
		self.wpmlIsCopied = data.wpmlIsCopied || 0;
		self.htmlInput = data.htmlInput || '';
		self.element = '';
		self.userValue = data.value || '';
		self.fieldConditionsMet =
			$( self.htmlInput ).filter( '.js-toolset-conditional' ).length  // class for fields with conditions
			&& $( self.htmlInput ).filter( '.wpt-hidden' ).length           // legacy class when the field is hidden
			? ko.observable( false )
			: ko.observable( true );

		self.repeatableGroup = ( "repeatableGroup" in data )
			? new Types.RepeatableGroup.Model.Group( data.repeatableGroup, self.item.group.level + 1, self )
			: null;

		self.setElement = function( el ) {
			self.element = el;
		};

		self.typesUpdateHtmlInput = function() {
			self.htmlInput = $( self.element ).typesUpdateHtml();
		};

		self.updateWYSIWYGEditors = function() {
			updateWYSIWYGHtml.apply($( self.element ));
		}

		self.updateUserValue = function() {
			var newValue = '';

			$( 'input[type="checkbox"][name^="types-repeatable-group"]:checked, input[type="radio"][name^="types-repeatable-group"]:checked', self.htmlInput ).each( function() {
				var inputLabel = $( this ).parent().find( 'label' ).html();
				inputLabel = inputLabel.slice(0, 25 ) + ( inputLabel.length > 25 ? '...' : '' );
				newValue = newValue + inputLabel + ', ';
			} );

			$( 'input[type="text"], textarea', self.htmlInput ).each( function() {
				if( this.value !== '' ) {
					newValue = this.value.slice(0, 25 ) + ( this.value.length > 25 ? '...' : '' );
				}
			} );

			self.userValue = newValue.replace( /,\s*$/, '' );
		};

		/**
		 * Get original translation data
		 */
		self.getOriginalTranslation = function( field, trigger ) {
			var $el = $( trigger.target );

			// remove hover tooltip
			$el.trigger( 'mouseout' );
			$el.removeClass( 'js-wpcf-tooltip' );

			// tooltip which shows original language
			var $tooltip = $el.next();

			if ( $tooltip.data( 'translation-loaded' ) == 0 ) {
				$.ajax( {
					url: ajaxurl,
					type: 'POST',
					data: {
						action: staticData.action.name,
						skip_capability_check: true,
						wpnonce: staticData.action.nonce,
						repeatable_group_action: 'json_repeatable_group_field_original_translation',
						repeatable_group_id: self.item.id,
						field_meta_key: self.metaKey,
						field_slug: self.slug,
					},
					dataType: 'json',
					success: function( response ) {
						$tooltip.data( 'translation-loaded', 1 );
						if ( 'raw_html' === response.data.type ) {
							$tooltip.html( response.data.payload );
						} else if ( 'structured' === response.data.type ) {
							var koViewModel = new Types.RepeatableGroup.TranslationPreview( response.data.payload );
							$tooltip.html( $( '#tplTranslationPreview' ).html() );
							ko.applyBindings( koViewModel, $tooltip[0] );
						}

					},
					error: function( response ) {
						console.log( response );
					},
				} );
			}

			$tooltip.toggle();
			$el.toggleClass( 'field-translation-trigger-active' );
		};
	};

	/**
	 * Viewmodel for the (structured) field translation preview tooltip.
	 *
	 * @param data Response from the json_repeatable_group_field_original_translation AJAX call.
	 * @constructor
	 */
	Types.RepeatableGroup.TranslationPreview = function( data ) {
		var self = this;

		self.translations = ko.observableArray();

		self.activeTranslation = ko.pureComputed( function() {
			return _.find(self.translations(), function(translation) {
				return translation.isActive();
			});
		});

		self.currentPreview = ko.pureComputed(function() {
			var activeTranslation = self.activeTranslation();
			if( ! activeTranslation ) {
				// This should never happen. If there are no translations, we won't receive structured data for the
				// tooltip in the first place.
				return 'No translation available.';
			}

			return activeTranslation.value;
		});

		self.init = function() {
			var translations = _.map(data.translations, function(translationModel) {
				var translationViewModel = {
					languageFlagUrl: translationModel['language_flag_url'],
					languageCode: translationModel['language_code'],
					isActive: ko.observable(false),
					'value': translationModel['value'],
				}

				// Make sure there is always at most one active translation.
				translationViewModel.isActive.subscribe(function(newValue) {
					if( newValue ) {
						var otherActiveTranslations = _.filter(self.translations(), function(maybeActiveTranslation) {
							return maybeActiveTranslation.isActive() && maybeActiveTranslation !== translationViewModel;
						});
						_.each(otherActiveTranslations, function(activeTranslation) {
							activeTranslation.isActive(false);
						});
					}
				})

				return translationViewModel;
			});

			self.translations(translations);

			var firstTranslation = _.first(self.translations());
			if ( !! firstTranslation ) {
				firstTranslation.isActive(true);
			}
		};


		self.init();

	};

	/**
	 * Collection of generic functions
	 */
	Types.RepeatableGroup.Functions = {
		/*
		 * cssExtension
		 */
		'cssExtension': function() {
			var rgx = $( '.js-rgx' ),
				rgy = $( '.js-rgy' );

			// task for horizontal view
			if( rgx.length ) {

				// adjust the size of the container with the delete countdown
				rgx.find( '.js-rg-countdown' ).each( function() {
					$( this ).parent().css( 'position', 'relative' );
					var parentTr = $( this ).closest( 'tr' );
					var isNested = parentTr.closest( '.js-rgx__td--group-container' );
					var width = parentTr.get( 0 ).clientWidth;

					if( isNested.length > 0 ) {
						width = 0;
						$( this ).closest( 'tr' ).find( 'td' ).each( function() {
							width += $( this ).get( 0 ).clientWidth;
						} );
					}

					$( this ).css( {
						'width': width - parentTr.find( 'th' ).first().get( 0 ).clientWidth - parentTr.find( 'th' ).last().get( 0 ).clientWidth + 'px',
						'line-height': parentTr.get( 0 ).clientHeight - 1 + 'px',
						'height': parentTr.get( 0 ).clientHeight - 1 + 'px'
					} );
				} );
			}

			// task for vertical view
			if( rgy.length ) {

				// adjust the size of the container with the delete countdown
				rgy.find( '.js-rg-countdown' ).each( function() {
					var parent = $( this ).parent();
					parent.css( 'position', 'relative' );

					var width = parent.get( 0 ).clientWidth,
						height= parent.get( 0 ).clientHeight;

					$( this ).css( {
						'width': width + 'px',
						'line-height': height + 'px',
						'height': height + 'px'
					} );
				} );
			}

		},

		/**
		 * Make legacy fields work
		 *
		 * @param {Types.RepeatableGroup.Model.Item|undefined} newItem If an item model is provided, the validation
		 *     will be initialized for its fields. Useful when adding new items after the validation has already
		 *     been initialized for the whole page.
		 */
		'initLegacyFields': function( newItem ) {
			if( typeof wptDate !== 'undefined' ) {
				wptDate.init('body');
			}

			if( typeof wptColorpicker !== 'undefined' ) {
				wptColorpicker.init('body');
			}

			if( typeof wptValidation !== 'undefined' ) {
				wptValidation.init();

				// When we're in the classic editor, we also need to apply validation rules on a new item when it's added.
				// In the block editor, all rules are automatically (re)applied before form submission and this would only break stuff.
				if ( typeof newItem !== 'undefined' && ! Toolset.hooks.applyFilters( 'types-gutenberg-is-active', false ) ) {
					var itemSelector = 'tbody[data-item-id=' + newItem.id + '] .c-rgy__item--fields';
					wptValidation.applyRules(itemSelector);
				}
			}

			if( typeof wptSkype !== 'undefined' ) {
				wptSkype.init();
			}
		},

		ajaxFieldsLoadedEvent: function() {
			// Note that the form may be also '.metabox-location-normal' or '.metabox-location-advanced'
			// instead of '#post'.
			jQuery( document ).trigger( 'toolset_ajax_fields_loaded', [{form_id: 'post'}] );
		},

		/**
		 * Set Conditions
		 */
		'setFieldConditions': function( conditions ) {
			if( conditions && wptCond) {
				Types.RFGSetFieldConditionsRunning = true;

				// wptCond.addConditionals( conditions ) fails when the triggers/fields for formId are undefined
				// better fixing it here to prevent any side effects (for which this behaviour might be necessary)
				_.each( conditions, function ( condition, formID ) {
					if ( _.size( condition.triggers ) && typeof wptCondTriggers[formID] == 'undefined' ) {
						wptCondTriggers[formID] = {};
					}
					if ( _.size( condition.fields )  && typeof wptCondFields[formID] == 'undefined' ) {
						wptCondFields[formID] = {};
					}
					if ( _.size( condition.custom_triggers ) && typeof wptCondCustomTriggers[formID] == 'undefined' ) {
						wptCondCustomTriggers[formID] = {};
					}
					if ( _.size( condition.custom_fields ) && typeof wptCondCustomFields[formID] == 'undefined' ) {
						wptCondCustomFields[formID] = {};
					}
				} );

				// add conditionals
				wptCond.addConditionals( conditions );

				// check show/hide for all conditions, this is important for rfg field conditions,
				// which use a field outside of the rfg for the condition.
				$.each( wptCondTriggers, function( formID, triggers ) {
					$.each( triggers, function( trigger, field ) {
						// When there are a lot fo conditions, it freezes the browser. Adding a minimun timeout lets the browser continue working.
						setTimeout(
							() => wptCond.check( formID, field ),
							10
						);
					} )
				} );

				Types.RFGSetFieldConditionsRunning = false;
			}
		},

		/**
		 * Scan all fields and return only textareas with wpt-wysiwyg class
		 * @param {Array} items
		 * @param {Array} ids
		 * @returns {Array}
		 */
		'getTinyMCEIds' : function ( items, ids ) {
			$.each( items, function( groupItem, groupItemValue ) {
				$.each( groupItemValue.fields, function( singleGroupFields, singleGroupFieldsValues ) {
					if( singleGroupFieldsValues.hasOwnProperty( 'repeatableGroup' ) ) {
						// nested group
						ids = Types.RepeatableGroup.Functions.getTinyMCEIds( singleGroupFieldsValues.repeatableGroup.items, ids );
					} else {
						var fieldObject = jQuery( singleGroupFieldsValues.htmlInput );
						if( jQuery( 'textarea', fieldObject ).hasClass( 'wpt-wysiwyg' ) ){
							var editorID = jQuery( 'textarea', fieldObject ).attr( 'id' );
							ids.push( editorID );
						}
					}
				} )
			} );

			return ids;
		},

		/**
		 * If an AJAX response includes toolbar settings for the TinyMCE editor,
		 * extract it and apply it on the types_tinymce_compatibility_l10n global variable.
		 *
		 * @param ajaxResponseData
		 * @since 3.3.1
		 */
		applyTinyMCEToolbarSettings: function (ajaxResponseData) {
			if (!_.has(ajaxResponseData, 'tinyMCEToolbarSettings')) {
				return;
			}

			if ('undefined' !== typeof types_tinymce_compatibility_l10n) {
				return;
			}

			window.types_tinymce_compatibility_l10n = {
				'editor_settings': ajaxResponseData['tinyMCEToolbarSettings']
			};
		},

		getViewMode: function() {
			return $( '.js-rgy' ).length ? Types.RepeatableGroup.VIEW_VERTICAL : Types.RepeatableGroup.VIEW_HORIZONTAL;
		},

		/**
		 * If the current page is a new one, with a post translation being translated, extract the
		 * new post's future language and TRID, to be used in WpmlTridAutodraftOverride.
		 */
		getNewPostTranslationOverride: function() {
			var matchNewPostPage = new RegExp( /.*\/wp-admin\/post-new.php\?.*/gm, 'i' );
			var isNewPostPage = ( null !== matchNewPostPage.exec( window.location.href ) );

			if ( ! isNewPostPage ) {
				return null;
			}

			return {
				trid: WPV_Toolset.Utils.getParameterByName( 'trid', window.location.href ),
				lang_code: WPV_Toolset.Utils.getParameterByName( 'lang', window.location.href ),
			};
		}
	};

	Types.RepeatableGroup.VIEW_VERTICAL = 'rgy';
	Types.RepeatableGroup.VIEW_HORIZONTAL = 'rgx';


	/**
	 * Mapper for the autogenerated (using ko.mapping) viewModel
	 */
	Types.RepeatableGroup.Mapper = {
		'repeatableGroup': {
			create: function( options ) {
				return new Types.RepeatableGroup.Model.Group( options.data, 0 );
			}
		}
	};


	/**
	 * Sortable Items
	 */
	ko.bindingHandlers.typesRepeatableGroupSortable = {
		init: function( el, valueAccessor, allBindingsAccesor, context ) {
			var element = $( el ),
				list = valueAccessor(),
				sortableHandle = '.c-rgx_sort--handle',
				sortableContainer = $( '.c-rgx__body' );

			if( list().length ) {
				if( list()[0].group.controlsActive === 0 ) {
					return;
				}
			}
			element.sortable( {
				axis: 'y',
				scroll: true,
				handle: sortableHandle,
				tolerance: 'pointer',
				cancel: ".c-rgx__sort--item-disabled",
				scroll: false,
				forcePlaceholderSize: true,
				start: function( e, ui ) {
					// size the placeholder propably
					ui.placeholder.find( 'tr' ).height( ui.helper.outerHeight() );
					ui.placeholder.css( 'visibility', 'inherit' );
					ui.placeholder.find( 'tr:first-child' ).children( 'td' ).replaceWith( function( i, html ) {
						return '<th class="c-rgx__th" style="opacity: 1;">' + html + '</th>';
					} );

					var el = ui.item[ 0 ];
					ko.utils.domData.set( el, 'originalIndex', ko.utils.arrayIndexOf( ui.item.parent().children(), el ) - 1 );
					// make sure placeholder has same width as helper
					var helperCells = ui.helper.find( 'tr:first-child' ).children();
					ui.placeholder.find( 'tr:first-child' ).children().each( function( index ) {
						$( this ).width( helperCells.eq( index ).width() );
					} );

					retainRadioButtonCheckedState(el);

					// hide overflow otherwise scrollbars could be produced by dragging the dragged item outside the box
					sortableContainer.css( 'overflow-y', 'hidden' );
				},
				sort: function() {
					Types.RepeatableGroup.Functions.cssExtension();
				},
				stop: function(event, ui) {
					// reset overflow
					sortableContainer.css( 'overflow-y', 'visible' );
					Types.RepeatableGroup.Functions.cssExtension();

					var el = ui.item[ 0 ];
					var item = ko.utils.domData.get( el, 'originalItem' );
					item.updateFields(true);
				},
				helper: function( e, tbody ) {
					// 1:1 copy for the helper
					var originalCells = tbody.find( 'tr:first-child' ).children(),
						helper = tbody.clone();

					// make sure helper has same width as original
					helper.find( 'tr:first-child' ).children().each( function( index ) {
						$( this ).width( originalCells.eq( index ).width() );
					} );

					return helper;
				},
				update: function( event, ui ) {
					// this whole update function resorts the elements in the knockout observable array
					// the nasty part here is that this must happen without reinitialise the element
					// (otherwise any user changes to the input would be resetted)
					var el = ui.item[ 0 ];
					var item = ko.utils.domData.get( el, 'originalItem' ),
						newIndex = ko.utils.arrayIndexOf( ui.item.parent().children(), ui.item[ 0 ] ) - 1;

					item.updateFields();

					if( newIndex >= list().length ) newIndex = list().length - 1;
					if( newIndex < 0 ) newIndex = 0;

					ui.item.remove();

					list.splice( ko.utils.domData.get( el, 'originalIndex' ), 1 );
					list.splice( newIndex, 0, item );
				}
			} );
		}
	};

	/**
	 * Initialize WYSIWYG editors on demand
	 *
	 * @param {string} id The underlying textarea id attribute.
	 */
	function initWysiwygField( id ) {
		Toolset.Types.Compatibility.TinyMCE.InitWysiwyg.initWysiwygField(id);
	}

	/**
	 * Retains the value of radio buttons early-on before the new HTML form elements are placed.
	 *
	 * @param {object} el The sorted DOM element to modify.
	 */
	function retainRadioButtonCheckedState(el) {
		var elSelector = el.className;
		var itemId = el.getAttribute('data-item-id');
		var elFormattedSelector = '.' + elSelector + '[data-item-id="'+itemId+'"]';

		$('.wpt-form-radio:checked', elFormattedSelector).each(function(radioIndex, radioEl) {
			var radioID = radioEl.getAttribute('id');
			$('#' + radioID, el).each(function(retainedRadioIndex, retainedRadioEl) {
				retainedRadioEl.checked = true;
			});
		});
	}

	/**
	 * To init YOAST fields
	 * @param items
	 */
	function initYoastFields( items ) {
		if( ! staticData.yoastActive ) {
			return;
		}
		jQuery.each( items, function( fieldKey, item ) {
			jQuery.each( item.fields, function( fieldKey, fieldArr ) {
				if( typeof fieldArr.yoast != 'undefined' ) {
					jQuery( document ).trigger( 'toolset_types_yoast_add_field', fieldArr.yoast );
				} else if( fieldArr.hasOwnProperty( 'repeatableGroup' ) ) {
					initYoastFields( fieldArr.repeatableGroup.items );
				}
			} );
		} );
	}

	$( document ).on( 'toolset:types:reInitWYSIWYG', function( event, fieldItem ) {
		var tinyMCEEditors = Types.RepeatableGroup.Functions.getTinyMCEIds( [fieldItem], [] );

		if(tinyMCEEditors.length === 0){
			return;
		}

		$.each( tinyMCEEditors, function( editor, editorValue ) {
			initWysiwygField( editorValue );
		});
	});

	/**
	 * Initialize the groups
	 * We're loading the groups after page load via Ajax
	 */
	$( function() {
		var positioningInit = false;
		var repeatableGroups = $( 'div[data-types-repeatable-group]' );

		if( repeatableGroups.length ) {
			var tplRepeatableGroup = $( '#tplRepeatableGroup' ).html();

			// load all items of all groups
			repeatableGroups.each( function() {
				var repeatableGroup = $( this );

				var $postIdEl = jQuery( '#post_ID' );
				if( parseInt( staticData.post_id ) === 0 && $postIdEl.length ) {
					staticData.post_id = $postIdEl.val();
				}

				if( parseInt( staticData.post_id ) === 0 ) {
					repeatableGroup.find( '.js-rgx__notice_loading' ).hide();
					repeatableGroup.find( '.js-rgx__notice_save_post_first' ).show();
				} else {
					$.ajax( {
						url: ajaxurl,
						type: 'POST',
						data: {
							action: staticData.action.name,
							skip_capability_check: true,
							wpnonce: staticData.action.nonce,
							parent_post_id: staticData.post_id,
							repeatable_group_action: 'json_repeatable_group',
							repeatable_group_id: repeatableGroup.data( 'types-repeatable-group' ),
							parent_post_translation_override: Types.RepeatableGroup.Functions.getNewPostTranslationOverride(),
						},
						dataType: 'json',
						success: function( response ) {
							if( response.success ) {
								repeatableGroup.html( tplRepeatableGroup );
								isHorizontalViewActive = !!$( '.js-rgx' ).length;
								Types.RepeatableGroup.Functions.applyTinyMCEToolbarSettings(response.data);

								var groupModelRoot = ko.mapping.fromJS( response.data, Types.RepeatableGroup.Mapper );
								ko.applyBindings( groupModelRoot, repeatableGroup.get( 0 ) );

								Types.RepeatableGroup.Functions.cssExtension();
								if( positioningInit === false ) {
									positioningInit = true;
									// we need this on resize and scroll to make sure the fixed positioned columns are always correctly positioned
									$( window ).on( 'resize scroll', Types.RepeatableGroup.Functions.cssExtension );
								}

								Types.RepeatableGroup.Functions.initLegacyFields();

								// Get WYSIWYG Fields and reinitialize tinyMCE editors
								var tinyMCEEditors = Types.RepeatableGroup.Functions.getTinyMCEIds( response.data.repeatableGroup.items, [] );
								$.each( tinyMCEEditors, function( editor, editorValue ) {
									initWysiwygField( editorValue );
								});

								// run field validation after fields are loaded
								Types.RepeatableGroup.Functions.ajaxFieldsLoadedEvent();

								// Yoast integration
								initYoastFields( response.data.repeatableGroup.items );

								// set field conditions for rfg items
								Types.RepeatableGroup.Functions.setFieldConditions( response.data.repeatableGroup.fieldConditions );

								// Mark the group in horizontal view as visible on page load (which it is, unlike in the vertical view).
								//
								// We need to do this after it actually becomes visible in the browser, since this triggers
								// some events where that's expected (specifically, when initializing maps in the Address field).
								if( isHorizontalViewActive ) {
									_.defer( function() {
										groupModelRoot.repeatableGroup.toggleGroupVisibility( true );
									} );
								}
							} else {
								// todo proper response if rfg couldn't be loaded
								console.log( 'Repeatable Field Group with ID "' + repeatableGroup.data( 'types-repeatable-group' ) + '" could not be loaded.' );
							}
						},

						error: function( response ) {
							// todo proper response
							console.log( response );
						}
					} );
				}
			} );
		}

		// Check conditionals after adding items or initialy
		Toolset.hooks.addAction( 'toolset-conditionals-add-conditionals', function( id ) {
			if( typeof arguments == 'undefined'
				|| typeof arguments[0] == 'undefined'
				|| typeof arguments[0]['#post'] == 'undefined'
				|| typeof arguments[0]['#post'].fields == 'undefined' ) {
				// no valid data
				return;
			}

			Object.keys( arguments[0]['#post'].fields ).forEach( function( groupId ) {
				var id = groupId.replace( /^.*\[(\d+)\].*$/, '$1' );
				jQuery( '[data-item-id=' + id + '] [name]' ).each( function() {
					var name = this.getAttribute( 'name' );
					if ( !!wptCondFields['#post'][ name ] ) {
						wptCond.check( '#post', [ name ] );
					}
				} );
			} );
		} );
	} );

	// block vertical / horizontal view switch when there was some change done
	function onChangeLockViewSwitch() {
		$( document ).on( 'keydown.rfgBlockViewSwitch change.rfgBlockViewSwitch', '.c-rg :input', function() {
			if( typeof Types.RFGSetFieldConditionsRunning != 'undefined' && Types.RFGSetFieldConditionsRunning ) {
				return;
			}

			// deregister event (no need to run twice)
			$( document ).off( 'keydown.rfgBlockViewSwitch change.rfgBlockViewSwitch' );

			// disable button
			$( '.js-rfg-view-switch' ).addClass( 'js-wpcf-tooltip js-rfg-view-switch-disabled' );

			// disable link of button
			$( '.js-rfg-view-switch-disabled' ).on( 'click.rfgBlockViewSwitch', function( e ) {
				e.preventDefault();
			} );
		} );
	}
	onChangeLockViewSwitch();

	// when switching vertical / horizontal view, override the link URL and refresh the page
	// using the current URL instead - it may have changed since the page has been loaded,
	// for example when saving a new post in the block editor
	//
	// Also, handle the case when there are unsaved changes and we don't want to allow the switch to happen
	// (while the link itself is not displayed as disabled).
	$( document ).on( 'click', '.js-rfg-view-switch', function( e ) {
		e.preventDefault();

		var $switch = $(this),
			isSwitchingDisabled = $switch.hasClass( 'js-rfg-view-switch-disabled' );

		if( isSwitchingDisabled ) {
			return;
		}

		var selectedView = $switch.data('view-setting'),
			urlToRedirect = WPV_Toolset.Utils.updateUrlQuery( 'rgview', selectedView );

		window.location = urlToRedirect;
	} );

	// Saving draft button when post is not saved yet
	$( document ).on( 'click', '#wpcf-save-post', function() {
		// Save post button.
		$( '#save-post').click();
		// Disables and show spinner.
		$( this ).attr('disabled', 'disabled').next().addClass( 'is-active' );
	} );

	// Make sure last clicked metabox is on front
	$( document ).on( 'click', '.postbox[id^="wpcf-group-"]', function() {
		$( '.postbox[id^="wpcf-group-"]' ).css( 'z-index', 1 );
		$( this ).css( 'z-index', 2 );
	} );

    // Make sure that RFG view is changeable after save on block editor
    jQuery( function() {
		if( typeof wp !== "undefined"
			&& typeof wp.data !== "undefined"
			&& typeof wp.data.subscribe !== "undefined"
			&& typeof wp.data.select !== "undefined"
		) {
			const { subscribe } = wp.data;
			let savingStarted = false;

			subscribe( () => {
				const blockEditor = wp.data.select( 'core/editor' );
				if( ! blockEditor ) {
					// Can be both null and undefined.
					return;
				}
				const isSaving = blockEditor.isSavingPost();

				if( isSaving ) {
					savingStarted = true;
				}

				if( ! isSaving && savingStarted ) {
					// save done
					savingStarted = false;

					// unlock RFG view switch
					$( '.js-rfg-view-switch-disabled' ).off( 'click.rfgBlockViewSwitch' );
					$( '.js-rfg-view-switch' ).removeClass( 'js-wpcf-tooltip js-rfg-view-switch-disabled' );

					// enable the lock again on any further change
					onChangeLockViewSwitch();
				}
			} );
		}
	} );
})( jQuery );
