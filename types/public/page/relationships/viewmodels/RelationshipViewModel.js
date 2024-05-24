
Types.page.relationships.viewmodels.RelationshipViewModel = function(modelSource, fieldActions, listingViewModel) {
    var self = this;

    var model = modelSource;

    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call(self, model, fieldActions);

    Toolset.Gui.Mixins.AdvancedItemViewModel.call(self);
    // Prevent a refactoring:
    var createModelProperty = self.createModelProperty;
    var updateViewModelFromModel = self.updateViewModelFromModel;

    // Helper methods
    //
    //
    self.cardinalityClass = ko.pureComputed(function() {
        var parentMax = self.cardinality.parent.max(),
            parentPart = (parentMax > 1 || parentMax === INFINITE_CARDINALITY) ? 'many' : 'one',
            childMax = self.cardinality.child.max(),
            childPart = (childMax > 1 || childMax === INFINITE_CARDINALITY) ? 'many' : 'one',
            result = { parent: parentPart, child: childPart };
            if ( jQuery('.cardinality-class-option input:radio[value='+result.parent+'-to-'+result.child+']').is(':disabled') ) {
                result = { parent: 'many', child: 'many' };
            }
        return result;
    });


    var isManyToMany = ko.pureComputed(function() {
        var cardinalityClass = self.cardinalityClass();
        return ( cardinalityClass.parent === 'many' && cardinalityClass.child === 'many' );
    });


    /**
     * Renders a list of post type labels in a human-readable form.
     *
     * Labels will be separated by commas, except the last comma, which will be replaced by 'or'.
     *
     * @param {string} role What post types should be listed: 'parent'|'child'.
     * @param {string} label Label type: 'plural'|'singular'.
     * @returns {string}
     */
    var buildHumanReadablePostTypeList = function(role, label) {
        var postTypes = _.map(self.types[role].types(), function(postTypeSlug) {
            return Types.page.relationships.main.postSlugToDisplayName(postTypeSlug, label);
        });

        if(postTypes.length === 0) {
            return Types.page.relationships.strings['noPostTypesPlaceholder'];
        }

        // Thanks http://stackoverflow.com/a/29234240.
        var formatList = function (listOfValues){
            var lastSeparator = ' ' + Types.page.relationships.strings['or'] + ' ';
            if (listOfValues.length === 1) {
                return listOfValues[0];
            } else if (listOfValues.length === 2) {
                return listOfValues.join(lastSeparator);
            } else if (listOfValues.length > 2) {
                return listOfValues.slice(0, -1).join(', ') + lastSeparator + listOfValues.slice(-1);
            }
        };

        return formatList(postTypes);
    };



    var getTheOtherRole = function(role) {
        return (role === 'parent' ? 'child' : 'parent');
    };


    self.getModel = function() { return model; };


    // Data properties
    //
    //

    // This is done in order to keep "slug" always intact, so the server can recognize the relationship definition
    // even if the slug is renamed.
    model.newSlug = model.slug;

    self.slug = createModelProperty(ko.observable, model, 'newSlug');

    self.displayName = createModelProperty(ko.observable, model, 'displayName');

    self.displayNameSingular = createModelProperty(ko.observable, model, 'displayNameSingular');

    self.isActive = createModelProperty(ko.observable, model, 'isActive');

	/**
	 * Autodeleting of intermediary posts together with the associations they belong to.
	 *
	 * @since 3.2
	 */
	self.isAutodeletingIntermediaryPosts = createModelProperty( ko.observable, model, 'isAutodeletingIntermediaryPosts' );

	/**
	 * Visibility of the intermediary post type in admin menus
	 *
	 * @since 3.4.2
	 */
	self.isDisplayingIntermediaryInAdminMenu = createModelProperty( ko.observable, model, 'isDisplayingIntermediaryInAdminMenu' );

    self.cardinality = {
        parent: {
            min: createModelProperty(ko.observable, model, ['cardinality', 'parent', 'min'] ),
            max: createModelProperty(ko.observable, model, ['cardinality', 'parent', 'max'])
        },
        child: {
            min: createModelProperty(ko.observable, model, ['cardinality', 'child', 'min']),
            max: createModelProperty(ko.observable, model, ['cardinality', 'child', 'max'])
        }
    };


    /**
     * @type {number} Infinite number of posts in the "maximum" part of cardinality.
     *
     * The value comes from m2m API, Toolset_Relationship_Cardinality::INFINITY.
     */
    const INFINITE_CARDINALITY = -1;


    /**
     * Type information.
     *
     * Domain is an observable with a string, types one with a string array.
     * @type {{parent: {domain: *, types: *}, child: {domain: *, types: *}}}
     */
    self.types = {
        parent: {
            domain: createModelProperty(ko.observable, model, ['types', 'parent', 'domain']),
            types: createModelProperty(ko.observableArray, model, ['types', 'parent', 'types'])
        },
        child: {
            domain: createModelProperty(ko.observable, model, ['types', 'child', 'domain']),
            types: createModelProperty(ko.observableArray, model, ['types', 'child', 'types'])
        }
    };


    // Display properties
    //
    //

    self.isActiveDisplay = ko.pureComputed(function() {
        return (self.isActive()
            ? self.getPostTypesDisabledMessage() + Types.page.relationships.strings.yes
            : Types.page.relationships.strings.no);
    });

    /**
     * Needs legacy support
     *
     * @since m2m
     */
    self.needsLegacySupport = ko.observable( modelSource.needsLegacySupport );

    /**
     * Adds an icon with a hover tooltip if there are disabled post types.
     *
     * @since m2m
     */
    self.getPostTypesDisabledMessage = function() {
        if ( model.postTypeDisabledNames !== false ) {
            var html = '<span class="fa fa-exclamation-circle js-show-tooltip types-pointer-tooltip" data-content="';
            if ( model.postTypeDisabledNames.length > 1 ) {
                html += Types.page.relationships.strings.disabledPostTypesPlural.replace('%s', model.postTypeDisabledNames.join(', '));
            } else {
                html += Types.page.relationships.strings.disabledPostTypesSingular.replace('%s', model.postTypeDisabledNames);
            }
            html += '"  data-bind="event: {mouseover: function() {display.showPointer($element);}, mouseout: function() {display.hidePointer($element);} }"></span> ';
            return html;
        } else {
            return '';
        }
    };


    /**
     * Allows to inject HTML and bind the descendants
     *
     * @link https://stackoverflow.com/a/31402450/2103269
     * @since m2m
     */
    ko.bindingHandlers.htmlWithBinding = {
        'init': function() {
            return { 'controlsDescendantBindings': true };
        },
        'update': function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            element.innerHTML = valueAccessor();
            ko.applyBindingsToDescendants(bindingContext, element);
        }
    };

    self.associationFields = createModelProperty(ko.observableArray, model, 'associationFields');

    self.hasAssociationFields = ko.pureComputed(function(){
        return ( self.associationFields().length > 0 );
    });


    self.intermediaryPostType = createModelProperty(ko.observable, model, ['types', 'intermediary', 'type']);


    /**
     * Sets limits by role for an observable
     *
     * @link https://gist.github.com/hereswhatidid/8205263
     */
    ko.extenders.limitByRole = function( target, intRange ) {
        //create a writeable computed observable to intercept writes to our observable
        var result = ko.computed({
            read: target,  //always return the original observables value
            write: function( newValue ) {
                // Not initialized yet.
                if ( ! self.display ) {
                    return;
                }
                if ( self.display && self.display.cardinalityClassString() === 'one-to-one' ) {
                    intRange.min = 1;
                }
                // noinspection JSCheckFunctionSignatures
                var current = Number(target()),
                    newValueAsNum = isNaN( newValue ) ? 0 : parseInt( +newValue, 10 ),
                    valueToWrite = newValueAsNum;
                if ( newValueAsNum < intRange.min ) {
                    valueToWrite = intRange.min;
                }

                if ( newValueAsNum > intRange.max && intRange.max !== INFINITE_CARDINALITY ) {
                    valueToWrite = intRange.max;
                }
                // Empty value is equal to INFINITE_CARDINALITY
                if ( newValue === '' ) {
                    target( newValue );
                    return;
                }
                //only write if it changed
                if ( valueToWrite !== current ) {
                    target(valueToWrite);
                } else {
                    //if the tested value is the same, but a different value was written, force a notification for the current field
                    if ( newValue !== current ) {
                        target.notifySubscribers( valueToWrite );
                    }
                }
            }
        }).extend({ notify: 'always' });

        //initialize with current value to make sure it is rounded appropriately
        result( target() );

        //return the new computed observable
        return result;
    };


    /**
     * @bool
     */
    self.previousRelationshipTypeWasManyToMany = isManyToMany();


    /**
     * Aliases checkboxes need to get initial value from isEnabledAliases, but this one also needs the checkboxes value, so cheking checkboxes initialization is required.
     */
    var checkboxHasBeenInitialized = false;


    //noinspection JSUnusedGlobalSymbols
    self.display = {

        advancedMode: {
            userUnderstands: ko.observable(false),
            isAvailable: ko.pureComputed(function() {
                return self.display.advancedMode.userUnderstands();
            }),

            // Not to be used directly.
            isEnabledValue: ko.observable(false),

            isEnabled: ko.pureComputed({
                read: function() {
                    return self.display.advancedMode.isEnabledValue();
                },
                write: function(value) {
                    if(true === value) {
                        Toolset.hooks.doAction('types-relationships-enable-advanced-settings');
                    } else {
                        // We want the user to have to click the "I understand" checkbox again, when they return to
                        // editing this relationship.
                        self.display.advancedMode.userUnderstands(false);
                    }

                    self.display.advancedMode.isEnabledValue(value);
                }
            })
        },

        cardinalityClassString: ko.computed({
            read: function() {
                var cardinalityClass = self.cardinalityClass();
                return cardinalityClass.parent + '-to-' + cardinalityClass.child;
            },
            write: function(value) {
                self.previousRelationshipTypeWasManyToMany = isManyToMany();
                var cardinalityParts = value.split('-to-'),
                    cardinalityClass = {
                        parent: cardinalityParts[0],
                        child: cardinalityParts[1]
                    };

                var applyCardinalityClass = function(cardinality, role) {
                    switch(cardinality) {
                        case 'one':
                            self.cardinality[role].max(1);
                            break;
                        case 'many':
                            // If previous value was one, set it to no limit,
                            // otherwise keep it unchanged.
                            var previousMax = self.cardinality[role].max(),
                                nextMax = ( previousMax === 1 ? INFINITE_CARDINALITY : previousMax);
                            self.cardinality[role].max(nextMax);
                            break;
                    }
                    self.cardinality[role].min(0);
                };

                _.each(['parent', 'child'], function(role) {
                    applyCardinalityClass(cardinalityClass[role], role);
                });
            }
        }),


        description: ko.pureComputed(function() {

            // Build a description from related post types, showing the cardinality in both numeric and a visual way.
            // The presence of association fields is also indicated.
            var cardinalityToString = function(element) {
                // No limitation whatsoever
                if(self.cardinality[element].min() === 0 && self.cardinality[element].max() === INFINITE_CARDINALITY) {
                    return '*';
                }

                var getCardinalityValue = function(value) {
                    return (INFINITE_CARDINALITY === value ? '*' : value);
                };

                // Exact number of elements
                if(self.cardinality[element].min() === self.cardinality[element].max()) {
                    return getCardinalityValue(self.cardinality[element].min())
                }

                // Arbitrary cardinality
                var result = getCardinalityValue(self.cardinality[element].min())
                    + ' .. '
                    + getCardinalityValue(self.cardinality[element].max());
                return result;
            };

            var getDescriptionForElement = function(element) {
                var result = _.map(self.types[element].types(), function(postTypeSlug) {
                        return Types.page.relationships.main.postSlugToDisplayName(postTypeSlug, 'plural');
                    }).join(', ')
                    + ' [' + cardinalityToString(element) + '] ';

                return result;
            };

            var cardinalityToStringMapping = {
                one: {
                    to: {
                        one: {
                            with_relationship: function (rel) { return '< ' + rel + ' >'; },
                            without_relationship: function () { return '<>'; }
                        },
                        many: {
                            with_relationship: function (rel) { return '<< ' + rel + ' >'; },
                            without_relationship: function() { return '<<' }
                        }
                    }
                },
                many: {
                    to: {
                        one: {
                            with_relationship: function(rel) { return '< ' + rel + ' >>'; },
                            without_relationship: function() { return '>>'; }
                        },
                        many: {
                            with_relationship: function(rel) { return '<< ' + rel + ' >>' },
                            without_relationship: function() { return '<<>>' }
                        }
                    }
                }
            };

            var cardinalityClass = self.cardinalityClass(),
                associationName = ( self.intermediaryPostType() ? self.displayName() :''),
                relationshipStatus = ( self.intermediaryPostType() ? 'with_relationship' : 'without_relationship'),
                middlePart = cardinalityToStringMapping[cardinalityClass.parent]
                    .to[cardinalityClass.child][relationshipStatus](associationName);

            var result = getDescriptionForElement('parent')
                + ' ' + middlePart + ' '
                + getDescriptionForElement('child');

            return result;
        }),


        maximumLimit: function() {

            var readFunction = function(role) {
                var maxLimit = self.cardinality[role].max();

                if(maxLimit === INFINITE_CARDINALITY) {
                    return '';
                }

                return maxLimit.toString();
            };

            var writeFunction = function(role, value) {
                value = parseInt(value);
                if( isNaN(value) || Math.floor(value) !== value || value < 1 ) {
                    value = INFINITE_CARDINALITY;
                }
                self.cardinality[role].max(value);
            };

            return {
                parent: ko.computed({
                    read: _.partial(readFunction,'parent'),
                    write: _.partial(writeFunction,'parent')
                }).extend( {limitByRole: {min: 1, max: INFINITE_CARDINALITY} } ),
                child: ko.computed({
                    read: _.partial(readFunction,'child'),
                    write: _.partial(writeFunction,'child')
                }).extend( {limitByRole: {min: 2, max: INFINITE_CARDINALITY} } )
            }
        }(),

        roleAlias: {
            parent: {
                slug: createModelProperty(ko.observable, model, ['roleNames', 'parent'] ),
                singular: createModelProperty(ko.observable, model, ['roleLabelsSingular', 'parent'] ),
                plural: createModelProperty(ko.observable, model, ['roleLabelsPlural', 'parent'] ),
            },
            child: {
                slug: createModelProperty(ko.observable, model, ['roleNames', 'child'] ),
                singular: createModelProperty(ko.observable, model, ['roleLabelsSingular', 'child'] ),
                plural: createModelProperty(ko.observable, model, ['roleLabelsPlural', 'child'] ),
            }
        },


        /**
         * Handles checkbox aliases selection
         */
        isAliasSelectorChecked: {
          parent: ko.observable(false),
          child: ko.observable(false)
        },

        /**
         * Returns if the role alias has content.
         *
         * @param {string} role Role type (parent/child)
         * @since m2m
         */
        isEnabledAliases: function( role ) {
            return ko.pureComputed(function() {
                var slug = self.display.roleAlias[ role ].slug();
                var singular = self.display.roleAlias[ role ].singular();
                var plural = self.display.roleAlias[ role ].plural();
                var areVisible = ( ! [ '', model.defaultLabels[ role ].name ].includes( slug )
                  || ! [ '', model.defaultLabels[ role ].role_labels_singular ].includes( singular )
                  || ! [ '', model.defaultLabels[ role ].role_labels_plural ].includes( plural ) );
                var checked = self.display.isAliasSelectorChecked[ role ]();
                return checkboxHasBeenInitialized
                    ? checked
                    : areVisible;
            } );
        },


        /**
         * Handles checkbox aliases selection
         */
        isAliasSelectorChecked: {
          parent: ko.observable(false),
          child: ko.observable(false)
        },

        /**
         * Returns if the role alias has content.
         *
         * @param {string} role Role type (parent/child)
         * @since m2m
         */
        isEnabledAliases: function( role ) {
            return ko.pureComputed(function() {
                var slug = self.display.roleAlias[ role ].slug();
                var singular = self.display.roleAlias[ role ].singular();
                var plural = self.display.roleAlias[ role ].plural();
                var areVisible = ( '' !== slug || '' !== singular || '' !== plural );
                var checked = self.display.isAliasSelectorChecked[ role ]();
                return checkboxHasBeenInitialized
                    ? checked
                    : areVisible;
            } );
        },


        maximumLimitToString: function() {

            var readFunction = function(role) {
                var maxLimit = self.cardinality[role].max();

                if(maxLimit === INFINITE_CARDINALITY) {
                    return Types.page.relationships.strings['infinite'];
                }

                return maxLimit.toString();
            };

            return {
                parent: ko.pureComputed(_.partial(readFunction, 'parent')),
                child: ko.pureComputed(_.partial(readFunction, 'child'))
            }

        }(),

        // If there are associations the limits can be lower that the max number of associations grouped by role.
        minimumLimit: ko.observable({
            parent: self.cardinality.parent.min(),
            child: self.cardinality.child.min(),
        }),

        minimumLimitWarning: ko.observable(''),

        postTypesWithAssociations: ko.observable( {
            parent: [],
            child: []
        } ),

        showSlugWarning: ko.observable(false),


        onSlugChange: function() {
            self.display.showSlugWarning(self.changedProperties().indexOf('newSlug') !== -1);
            return true;
        },

        // Since we're assuming only a single post type on each side of the relationship,
        // this converts from the post type array to a single value (and back).
        postType: function() {

            var readFunction = function(role) {
                var postTypes = self.types[role].types();
                if(postTypes.length === 0) {
                    return '';
                } else {
                    return postTypes[0];
                }
            };

            var writeFunction = function(role, postType) {
                self.types[role].types.removeAll();
                self.types[role].types.push(postType);
            };

            return {
                parent: ko.computed({
                    read: _.partial(readFunction, 'parent'),
                    write: _.partial(writeFunction, 'parent')
                }),
                child: ko.computed({
                    read: _.partial(readFunction, 'child'),
                    write: _.partial(writeFunction, 'child')
                })
            }

        }(),


        isPostTypeOptionEnabled: function(role, postType) {
          /*
            // Not implemented yet
            if( isManyToMany() ) {
                return true;
            }
           */

            var postTypeInOtherRole = self.display.postType[getTheOtherRole(role)]();

            return (postTypeInOtherRole !== postType);
        },


        isActive: {
            isStatusMenuExpanded: ko.observable(false),
            lastInput: ko.observable(self.isActive()),
            applyLastInput: function() {
                self.isActive(self.display.isActive.lastInput());
                self.display.isActive.isStatusMenuExpanded(false);
            },
            cancelLastInput: function() {
                self.display.isActive.lastInput(self.isActive());
                self.display.isActive.isStatusMenuExpanded(false);
            }
        },

        isMaximumLimitEnabled: function(role) {
            var cardinalityClass = self.cardinalityClass();
            return ( cardinalityClass[role] === 'many' );
        },


        isSaving: ko.observable(false),

        postTypeLists: {
            parent: {
                singular: ko.pureComputed(_.partial(buildHumanReadablePostTypeList, 'parent', 'singular')),
                plural: ko.pureComputed(_.partial(buildHumanReadablePostTypeList, 'parent', 'plural'))
            },
            child: {
                singular: ko.pureComputed(_.partial(buildHumanReadablePostTypeList, 'child', 'singular')),
                plural: ko.pureComputed(_.partial(buildHumanReadablePostTypeList, 'child', 'plural'))
            }
        },

        intermediaryPostType: {
        	changedToExisting: ko.observable(false),
            exists: ko.pureComputed(function() {
                return (typeof self.intermediaryPostType() !== 'undefined' && self.intermediaryPostType().length > 0);
            }),
            plural: ko.pureComputed(function() {
                if(! self.display.intermediaryPostType.exists()) {
                    return Types.page.relationships.strings['noIntermediaryPostType'];
                }
                return Types.page.relationships.main.postSlugToDisplayName(self.intermediaryPostType(), 'plural');
            }),
            onEditPostType: function() {
                self.onSave(function() {
                    window.location.href = model['types']['intermediary']['editPostTypeUrl'];
                });
            },
            onEditFields: function() {
                // Save and then redirect to the page for editing the post field group.
                self.onSave(function() {
                    var url = self.hasAssociationFields() ? model['types']['intermediary']['editFieldGroupUrl'] : model['types']['intermediary']['addFieldGroupUrl'];
                    window.location.href = url;
                });
            },
            onDeletePostType: function() {
                self.deleteIntermediaryPostTypeDialogOpen( self.slug(), model['types']['intermediary'] );
            },
            isSelectingExistingPostType: ko.observable(false),
            selectedExistingPostType: ko.observable(''),
            selectExistingPostType: function() {
                self.intermediaryPostType(self.display.intermediaryPostType.selectedExistingPostType());
                self.display.intermediaryPostType.isSelectingExistingPostType(false);
                self.display.intermediaryPostType.changedToExisting(true);
            },
            potentialIntermediaryPostTypes: ko.pureComputed(function() {
                var results = _.reject(
                        Types.page.relationships.main.getPotentialIntermediaryPostTypes(),
                        function(postType) {
                            return (_.contains(self.types.parent.types(), postType.slug) || _.contains(self.types.child.types(), postType.slug));
                        }
                );
                return results;
            }),
            allowSelectingExistingIntermediaryPostType: ko.pureComputed(function() {
                var availablePostTypes = self.display.intermediaryPostType.potentialIntermediaryPostTypes();
                return ( availablePostTypes.length > 0 )
            })
        },

        relationshipSettingsInfo: ko.observable(''),

        associationFields: ko.pureComputed(function () {
            var result = _.map(self.associationFields(), function(fieldModel) {
                return {
                    slug: fieldModel['slug'],
                    displayName: fieldModel['displayName'],
                    icon: 'fa-lg ' + Types.page.relationships.main.fieldTypeToIcon(fieldModel['type'])
                }
            });

           return result;
        }),

        allowsAssociationFields: ko.pureComputed(function() {
            var cardinalityClass = self.cardinalityClass();
            return (cardinalityClass.child === 'many' && cardinalityClass.parent === 'many');
        }),

        showPointer: function(element) {
            Types.page.relationships.main.showPointer(element);
        },

        hidePointer: function(element) {
            Types.page.relationships.main.hideWPPointers();
        }
    };

    /**
     * Setting initial aliases checkbox selectors
     */
     self.display.isAliasSelectorChecked.parent( self.display.isEnabledAliases( 'parent' )() );
     self.display.isAliasSelectorChecked.child( self.display.isEnabledAliases( 'child' )() );

    checkboxHasBeenInitialized = true;


    /**
     * Updates aliases model
     */
    ['parent', 'child'].forEach( function( role ) {
        self.display.isAliasSelectorChecked[ role ].subscribe(function( value ) {
            if ( ! value ) {
                self.display.roleAlias[ role ].slug('');
                self.display.roleAlias[ role ].singular('');
                self.display.roleAlias[ role ].plural('');
            }
        } );
    } );


    /**
     * Stores advanced limit data retreviewed from an Ajax call
     *
     * @since m2m
     */
    self.advancedCardinalityData = null;


    /**
     * Limits handler
     *
     * If the relationships has x association, the limit can't be lower than it
     *
     * @since m2m
     */
    self.display.advancedMode.userUnderstands.subscribe( function( checked ) {
        if ( checked ) {
            var ajax = Types.page.relationships.main.ajax;
            var successCallback = function( res ) {
                self.display.minimumLimit({
                    parent: res.data.cardinality.parent.min,
                    child: res.data.cardinality.child.min,
                });
                self.display.minimumLimitWarning( res.data.strings.minimumLimitWarning );
                self.display.postTypesWithAssociations( res.data.postTypesWithAssociations );
            };
            ajax.doAjax(ajax.action.cardinality, { slug: model.slug }, successCallback, function() {});
        }
    } );

    /**
     * Shows/hides the limit warning depending on the role
     *
     * @param {string} role Parent or child.
     * @since m2m
     */
    self.display.isMinimumLimitWarningVisible = function( role ) {
        return ko.computed(function () {
            var visible = self.display.minimumLimit()[role] > 1 && self.display.advancedMode.isEnabled();
            // If visible, forced height can be removed in order to fit the content inside the box
            if (visible) {
                setTimeout( function() {
                    jQuery('.main-box-content').css('height', '');
                }, 500 );
            }
            return visible;
        }, this );
    },


    /**
     * Check if the relationship element has associations, if the post type is included in the list of post types, it will return true
     *
     * @param {string} role Parent or child.
     * @param {string} type Post type.
     * @since m2m
     */
    self.display.postTypeNotIncludedListWithAssociations = function( role, type ) {
        return ko.computed( function() {
            if ( !self.display.postTypesWithAssociations()[role].length ) {
                return false;
            }
            return !self.display.postTypesWithAssociations()[role].includes(type);
        }, this );
    },

    /**
     * Depending on advanced limits, the relationship types one-to-any can be disabled
     *
     */
    self.display.isOneToAnyEnabled = function( cardinality ) {
        return ko.computed( function() {
            if ( 'many-to-many' == cardinality ) {
                return true;
            }
            return self.display.minimumLimit().parent < 2;
        }, this );
    };


    isManyToMany.subscribe(function(newValue) {
        if(false === newValue && self.display.postType.parent() === self.display.postType.child() ) {
            self.display.postType.child('');
        }
    });


    /**
     * keep track of the remaining posts to delete
     * @type {number}
     */
    self.postsRemaining = 0;
    /**
     * keep track of already deleted posts
     * @type {number}
     */
    self.postsDeleted = 0;
    /**
     * keep track of the remaining associations to update
     * @type {number}
     */
    self.associationsRemaining = 0;
    /**
     * keep track of already updated posts
     * @type {number}
     */
    self.associationsUpdated = 0;
    /**
     * store dialog object
     * @type {null}
     */
    self.deleteDialog = null;

    /**
     *
     * @type {null/string}
     */
    self.currentIntermediaryPostType = null;

    /**
     *
     * @type {number}
     */
    self.totalPostsToDelete = 0;

    /**
     *
     * @type {null/string}
     */
    self.relationshipSlug = null;
    /**
     * Show the "Delete Intermediary Post Type" confirmation dialog and handle the output.
     *
     * Either deactivates or deletes a given relationship (or does nothing).
     * @param relationshipSlug
     * @param intermediaryPostType
     */
    self.deleteIntermediaryPostTypeDialogOpen = function( relationshipSlug, intermediaryPostType ) {
        self.currentIntermediaryPostType = intermediaryPostType;
        self.relationshipSlug = relationshipSlug;

        self.deleteDialog = Types.page.relationships.viewmodels.dialogs.DeleteIntermediaryPostType( self.relationshipSlug, self.currentIntermediaryPostType, function( result ) {
            switch ( result ) {
                case 'delete':
                    self.deleteIntermediaryPostType(self.relationshipSlug, self.currentIntermediaryPostType, self.deletePostsSuccessCallback, self.deletePostsFailCallback);
                    break;
                case 'cancel':
                    console.log( 'cancel');
                    break;
                case 'finish':
                    console.log( 'finish');
                    break;
            }
        });

        self.deleteDialog.display();
    };

    var hideDialogButtonsWhenTriggersDelete = function( $dialog ){
        $dialog.parent().find('.ui-dialog-buttonpane').hide();
        $dialog.parent().find('button.js-types-delete-ipts-button').hide();
        $dialog.parent().find('button.wpcf-ui-dialog-cancel').hide();
    };

    self.confirmCardinalityChangeDialogOpen = function(relationshipSlug, intermediaryPostType, saveCallback, saveCallbackArguments ) {
        self.currentIntermediaryPostType = intermediaryPostType;
        self.relationshipSlug = relationshipSlug;

        self.deleteDialog = Types.page.relationships.viewmodels.dialogs.ConfirmChangeCardinality( self.relationshipSlug, self.currentIntermediaryPostType, self.display.cardinalityClassString(), function( result ) {
            switch ( result ) {
                case 'delete':
                    self.deleteIntermediaryPostType(self.relationshipSlug, self.currentIntermediaryPostType, self.deletePostsSuccessCallback, self.deletePostsFailCallback, saveCallback, saveCallbackArguments );
                    break;
                case 'cancel':
                    console.log( 'cancel');
                    break;
            }
        });

        self.deleteDialog.display();
    };

    /**
     * void
     */
    var cleanUpPostsAndGroupsMetaBoxAfterDelete = function( disableAdvancedModeEnabled ){
        var undefined;

        self.associationFields( [] );
        self.intermediaryPostType( undefined )
        self.intermediaryPostType( '' );

        if( disableAdvancedModeEnabled ){
            self.display.advancedMode.isEnabled( false );
        }
        self.display.isSaving( false );
        self.changedProperties( [] );
    };

    /**
     *
     * @param response
     * @param responseData
     */
    self.deletePostsSuccessCallback = function ( response, responseData, saveCallback, saveCallbackArguments, postType ) {
        if( self.totalPostsToDelete === 0 ){
            self.totalPostsToDelete = parseInt( responseData.results.post_type_posts.total_posts );
            self.totalAssociationsToUpdate = parseInt( responseData.results.post_type_associations.total_associations );
        }
        self.postsDeleted += parseInt( responseData.results.post_type_posts.deleted_posts );
        self.postsRemaining = self.totalPostsToDelete - self.postsDeleted;
        self.associationsUpdated += parseInt( responseData.results.post_type_associations.updated_associations );
        self.associationsRemaining = self.totalAssociationsToUpdate - self.associationsUpdated;
        hideDialogButtonsWhenTriggersDelete( self.deleteDialog.dialog.$el );
        self.deleteDialog.alertVisible( false );
        self.deleteDialog.progressVisible( true );
        self.deleteDialog.totalAmount( self.totalPostsToDelete );
        self.deleteDialog.progressAmount( self.postsDeleted );
        self.deleteDialog.remainingAmount( self.postsRemaining );
        if ( !!self.deleteDialog.totalAssociationsAmount ) {
            self.deleteDialog.totalAssociationsAmount( self.totalAssociationsToUpdate );
        }
        if ( !!self.deleteDialog.progressAssociationsAmount ) {
            self.deleteDialog.progressAssociationsAmount( self.associationsUpdated );
        }
        if ( !!self.deleteDialog.remainingAssociationsAmount ) {
            self.deleteDialog.remainingAssociationsAmount( self.associationsRemaining );
        }

        var percent = parseInt( ( ( self.totalAssociationsToUpdate + self.totalPostsToDelete ) - ( self.postsRemaining + self.associationsRemaining ) ) * 100 / ( self.totalPostsToDelete + self.totalAssociationsToUpdate ) );
        self.deleteDialog.dialog.$el.find('#intermediary-progress-bar span').css({width: percent + '%'});

        // Delete the post type from the observable array.
        Types.page.relationships.main.removeDeletedIntermediaryPostType(postType.type);

        if( self.postsRemaining === 0 ){
            var disableAdvancedModeEnabled = true;
            // let the user read the final result and then close
            _.delay( function(){
                // if it is a change cardinality action
                if( ! _.isUndefined( saveCallback ) && _.isFunction( saveCallback ) ){
                    disableAdvancedModeEnabled = false;
                    _.delay( function(){
                        self.deleteDialog.cleanup();
                        saveCallback.call( self, saveCallbackArguments );
                    }, 2000 ); // let the user read the summary before closing the dialog (on change cardinality only)

                // if it is a IPT delete action
                } else  {
                    self.deleteDialog.dialog.$el.parent().find('.ui-dialog-buttonpane').show();
                    self.deleteDialog.dialog.$el.parent().find('button.types-finish-delete-ipts-btn').show();
                }

                // in both cases prepare summary data and clean up metabox
                self.deleteDialog.deletedGroups( responseData.results.post_type_data ? responseData.results.post_type_data.deleted_groups : 0 );
                self.deleteDialog.deleteProcessCompleted( true );
                self.deleteDialog.progressVisible( false );
                cleanUpPostsAndGroupsMetaBoxAfterDelete( disableAdvancedModeEnabled );

            }, 1500); // let the user read the progress is completed
        } else {
            self.deleteIntermediaryPostType(self.relationshipSlug, self.currentIntermediaryPostType, self.deletePostsSuccessCallback, self.deletePostsFailCallback, saveCallback, saveCallbackArguments );
        }
    };

    /**
     *
     * @param response
     * @param responseData
     */
    self.deletePostsFailCallback = function ( response, responseData ) {
        console.log( 'fail', response, responseData );
        self.deleteDialog.cleanup();
    };

    /**
     * Delete an Intermediary Post Type via AJAX, update the collection of viewmodels and show the result.
     *
     * @since m2m
     * @param intermediaryPostType
     * @param successCallback
     * @param failCallback
     */
    self.deleteIntermediaryPostType = function( relationshipSlug, intermediaryPostType, successCallback, failCallback, saveCallback, saveCallbackArguments ) {

        if( !intermediaryPostType || !Types.page.relationships.delete_intermediary_post_type_action || !Types.page.relationships.delete_intermediary_post_type_nonce ) {
            return;
        }

        var ajaxData = {
            action: Types.page.relationships.delete_intermediary_post_type_action,
            wpnonce: Types.page.relationships.delete_intermediary_post_type_nonce,
            relationship: relationshipSlug,
            post_type: intermediaryPostType
        };


        if (typeof(failCallback) == 'undefined') {
            failCallback = successCallback;
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function (originalResponse) {
                var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                if (response.success) {
                    successCallback( response, response.data || {}, saveCallback, saveCallbackArguments, intermediaryPostType );
                } else {
                    failCallback(response, response.data || {});
                }
            },
            error: function (ajaxContext) {
                console.log('Error:', ajaxContext.responseText);
                failCallback({success: false, data: {}}, {});
            }
        });
    };
    // Row actions
    //
    //
    self.onEdit = function() {
        Types.page.relationships.main.editRelationship(self);
    };


    self.onDisplayNameClick = function() {
        self.onEdit();
    };


    self.onDelete = function() {
        Types.page.relationships.main.deleteRelationship(self);
    };


    self.onDeactivate = function() {
        self.isActive(false);
        self.onSave();
    };


    // Edit screen action
    self.onStopEditing = function() {
        Types.page.relationships.main.showRelationships();
    };


    self.isValidToSave = ko.pureComputed(function() {
        if(
            self.display.postType.parent().length === 0
            || self.display.postType.child().length === 0
        ) {
            return false;
        }

        return true;
    });

    /**
     * Tells if a change has been made in Cardinality from many-to-many to other relationship kind
     * @return bool
     */
    self.relationshipTypeChangedFromManyToManyToOther = function(){
        return _.indexOf( self.changedProperties(), 'cardinality' ) && self.previousRelationshipTypeWasManyToMany && self.display.cardinalityClassString() !== 'many-to-many' && self.display.intermediaryPostType.exists();
    };

    /**
     * Save the relationship definition and show the result message(s).
     *
     * Finally, update the viewmodel with changes from the server.
     *
     * @since m2m
     */
    self.onSave = function(successCallback) {
        if( self.relationshipTypeChangedFromManyToManyToOther() ){
            self.confirmCardinalityChangeDialogOpen( self.slug(), model['types']['intermediary'], self.saveRelationship, successCallback );
        } else {
            self.saveRelationship( successCallback );
        }
    };

    self.saveRelationship = function (successCallback) {
        // Checks if role slugs are different.
        if ( self.display.roleAlias.parent.slug() !== ''
          && wpcf_slugize( self.display.roleAlias.parent.slug() )  === wpcf_slugize( self.display.roleAlias.child.slug() ) ) {
            Types.page.relationships.main.viewModel.displayMessagesFromAjax( {}, 'error', Types.page.relationships.strings.rolesSlugMustBeDifferent );
            return;
        }
        self.display.isSaving(true);

        var previousSlug = self.getModel().slug,
            ajax = Types.page.relationships.main.ajax,

            finalize = function () {
                self.display.isSaving(false);
            },

            handleFailure = function (response) {
                Types.page.relationships.main.viewModel.displayMessagesFromAjax(response.data || {}, 'error', 'There was an error when saving the relationship.');
                finalize();
                // todo force page reload before saving anything?
            },

            handleSuccess = function (response, responseData) {

                // We expect exactly one updated definition.
                if (
                    !_.has(responseData, 'updated_definitions')
                    || !_.isArray(responseData['updated_definitions'])
                    || 1 !== responseData['updated_definitions'].length
                ) {
                    handleFailure(response);
                    return;
                }
                // If the slug has change it has to access the new page.
                var newSlug = responseData.updated_definitions[0].slug;
                if (previousSlug !== newSlug) {
                    history.pushState({screen: 'editing'}, null, document.location.href.replace(/slug=[\w\d-_]+/, 'slug=' + newSlug));
                }
                Types.page.relationships.main.viewModel.displayMessagesFromAjax(responseData, 'info', 'Relationship has been saved.');
                updateViewModelFromModel(_.first(responseData['updated_definitions']), model);
                // Reset changedProperties.
                self.changedProperties([]);
                // Reset the intermediary change state
                self.display.intermediaryPostType.changedToExisting(false);
                finalize();
                if (typeof(successCallback) === 'function') {
                    successCallback();
                }
            };

        ajax.doAjax(ajax.action.update, model, handleSuccess, handleFailure);
    };


    /**
     * Check whether a currently selected bulk action can be performed on this field group.
     *
     * @since 3.0
     */
    self.isBulkActionAllowed = ko.computed(function() {
        var bulkAction = listingViewModel.selectedBulkAction();

        if( 'merge' === bulkAction ) {

            // Allow a relationship to be selected for merging only if
            // - it is an one-to-many relationship
            // - the child element domain+type is used in exactly two relationships (including this one)
            // - the second relationship acceptable is also one-to-many
            // - the second relationship has a different parent domain+type
            // - it's not a polymorphic relationship
            //
            // Accepting anything else might result in a serious mess after the merging.
            //
            // Follow-up validation is taking place on the server side.
            if( self.cardinalityClass().parent !== 'one' || self.cardinalityClass().child !== 'many' ) {
                return false;
            }

            if(self.types.child.types().length > 1 || self.types.parent.types().length > 1 ) {
                return false;
            }

            // We can afford this because we are not merging polymorphic relationships (even if they become possible in the future).
            var myChildPostType = _.first(self.types.child.types());
            var myParentPostType = _.first(self.types.parent.types());

            var relationshipsWithSameChildPostType = _.filter(_.without(listingViewModel.items(), self), function(relationship) {
                var otherChildPostType = _.first(relationship.types.child.types());

                if(relationship.types.child.domain() !== self.types.child.domain()) {
                    return false;
                }

                return ( otherChildPostType === myChildPostType );
            });

            if(relationshipsWithSameChildPostType.length > 1) {
                // This would mean that after the transformation, we'd have another relationship pointed at the IPT,
                // which is not allowed.
                return false;
            }

            var isRelationshipMergeable = function(relationship) {

                var otherChildPostType = _.first(relationship.types.child.types());
                var otherParentPostType = _.first(relationship.types.parent.types());

                if(relationship.cardinalityClass().parent !== 'one' || relationship.cardinalityClass().child !== 'many' ) {
                    return false;
                }
                if(relationship.types.child.domain() !== self.types.child.domain()) {
                    return false;
                }

                if(relationship.types.child.types().length !== 1) {
                    return false;
                }

                if(
                    relationship.types.parent.domain() === self.types.parent.domain()
                    && otherParentPostType === myParentPostType
                ) {
                    return false;
                }

                return ( otherChildPostType === myChildPostType );
            };

            var selectedItems = _.without(listingViewModel.selectedItems(), self);

            if(selectedItems.length === 0) {
                // Discard this relationship if there is more than one acceptable option for the second relationship.
                var otherAcceptableRelationships = _.filter(
                    _.without(listingViewModel.items(), self),
                    isRelationshipMergeable
                );
                if (otherAcceptableRelationships.length !== 1) {
                    return false;
                }
            } else if(selectedItems.length === 1 && ! isRelationshipMergeable(_.first(selectedItems))) {
                // One relationship is already selected but it's not mergeable together with this one.
                return false;
            } else if(selectedItems.length > 1) {
                // Already got selected two or more items, we cannot select another.
                return false;
            }

            // We can merge this, yay
        }

        return true;
    });


    // Responses to global events
    //
    //
    Toolset.hooks.addAction('types-relationships-switch-to-relationship-listing', function() {
        self.display.advancedMode.isEnabled(false);
    });

};


// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
        value: function(searchElement, fromIndex) {

            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            // 1. Let O be ? ToObject(this value).
            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If len is 0, return false.
            if (len === 0) {
                return false;
            }

            // 4. Let n be ? ToInteger(fromIndex).
            //    (If fromIndex is undefined, this step produces the value 0.)
            var n = fromIndex | 0;

            // 5. If n ≥ 0, then
            //  a. Let k be n.
            // 6. Else n < 0,
            //  a. Let k be len + n.
            //  b. If k < 0, let k be 0.
            var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

            function sameValueZero(x, y) {
                return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
            }

            // 7. Repeat, while k < len
            while (k < len) {
                // a. Let elementK be the result of ? Get(O, ! ToString(k)).
                // b. If SameValueZero(searchElement, elementK) is true, return true.
                if (sameValueZero(o[k], searchElement)) {
                    return true;
                }
                // c. Increase k by 1.
                k++;
            }

            // 8. Return false
            return false;
        }
    });
}
