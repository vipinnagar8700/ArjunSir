
// I don't know why I need to use this global var in order to make slug conflict work
var wizardModelGlobal;

/**
 * Viewmodel of the wizard screen.
 *
 * @param {ListingViewModel} listingModel
 * @since m2m
 */
Types.page.relationships.viewmodels.WizardViewModel = function (listingModel) {

    var self = this;
    wizardModelGlobal = self;

    /**
     * Main controller (main.js)
     *
     * @since m2m
     */
    var main = Types.page.relationships.main;


    /**
     * Stores the relationship type
     *
     * @since m2m
     */
    self.relationshipType = ko.observable('');


    /**
     * Stores the parent post type
     *
     * @since m2m
     */
    self.parentPostType = ko.observable('');


    /**
     * Stores the child post type
     *
     * @since m2m
     */
    self.childPostType = ko.observable('');


    /**
     * Relationship name plural
     *
     * @since m2m
     */
    self.relationshipNamePlural = ko.observable('');


    /**
     * Relationship name singular
     *
     * @since m2m
     */
    self.relationshipNameSingular = ko.observable('');


    /**
     * Relationship name plural
     *
     * @since m2m
     */
    self.relationshipNameSlug = ko.pureComputed(function () {
        jQuery('#types-wizard-name-slug').trigger( 'paste' );
        return wpcf_slugize(self.relationshipNameSingular());
    });


    /**
     * Add fields nonce
     *
     * @since m2m
     */
    self.getNonce = function () {
        return Types.page.relationships.main.modelData.nonce;
    };

    /**
     * Shows/hides visible intermediary checkbox
     *
     * @since m2m
     */
    self.createIntermediaryPostType = ko.observable(true);

	/**
	 * Autodeleting of intermediary posts together with the associations they belong to.
	 *
	 * Enabled by default even for other than many-to-many relationships, in case the
	 * cardinality is later increased.
	 *
	 * @since 3.2
	 */
	self.isAutodeletingIntermediaryPosts = ko.observable( true );

	self.isIntermediarySectionVisible = ko.pureComputed( function () {
		return self.relationshipType() === 'many-to-many';
	} );


    /**
     * Checks if it is saving
     *
     * @since m2m
     */
    self.isSaving = ko.observable(false);


    /**
     * There are custom relationships fields
     *
     * @since m2m
     */
    self.thereAreFields = ko.notifyingWritableComputed({
        read: function (v) {
            var thereAre = jQuery('input[name^="wpcf[fields]"]').length > 0;
            // If there are fields, forces to be checked
            if (thereAre) {
                self.createIntermediaryPostType(true);
            }
            return thereAre;
        },
        write: function (v) {
        }
    });


    /**
     * Adding some hooks
     *
     * @since m2m
     */
    if (-1 === listingModel.hooksAdded.indexOf('types-relationships-wizard-exit')) {
        listingModel.hooksAdded.push('types-relationships-wizard-exit');
        /**
         * IMPORTANT: now WizardViewModel is not data dependent but if that changes
         * and you want to add some actions in the hook, please, remove the hook and
         * then create a new one
         */
        Toolset.hooks.addAction('types-relationships-wizard-exit', function () {
            jQuery('#types-wizard-form').trigger('reset');
            jQuery('.wizard-limit-slider').slider('value', self.MAX_SLIDER_POSITION);
            jQuery('#types-wizard-add-field').addClass('hidden');
            jQuery('.toolset-postbox').remove();
            listingModel.resetWizardModel();
        });
    }

    if (-1 === listingModel.hooksAdded.indexOf('types-relationships-wizard-enter')) {
        listingModel.hooksAdded.push('types-relationships-wizard-enter');
        /**
         * IMPORTANT: now WizardViewModel is not data dependent but if that changes
         * and you want to add some actions in the hook, please, remove the hook and
         * then create a new one
         */
        Toolset.hooks.addAction('types-relationships-wizard-enter', function () {
            listingModel.removeDisplayedMessage();
            // Reset form
            Toolset.hooks.doAction('types-relationships-wizard-exit');
        });
    }

    /**
     * Trigger observables when fields have changed
     *
     * @since m2m
     */
    self.fieldsHaveChanged = function () {
        self.thereAreFields(true);
        self.selectedFields(true);
    };


    /**
     * Wizard step object
     *
     * @param {string} id ID of the step
     * @param {array} type A list of allowed relationships types, empty means all of them.
     * @since m2m
     */
    function Step(index, id, type) {
        var obj = this;
        obj.index = index;
        obj.id = id;
        obj.type = type;
    }


    /**
     * Steps model
     */
    self.stepModels = ko.observableArray(
        [
            new Step(0, '#types-wizard-step-1', []),
            new Step(1, '#types-wizard-step-2', []),
            new Step(2, '#types-wizard-step-3', ['one-to-many', 'many-to-many']),
            new Step(3, '#types-wizard-step-4', ['many-to-many']),
            new Step(4, '#types-wizard-step-5', []),
            new Step(5, '#types-wizard-step-6', []),
        ]
    );


    /**
     * Current step
     */
    self.currentStep = ko.observable(self.stepModels()[0]);

    /**
     * Goes to next wizard step
     *
     * @since m2m
     */
    self.goNextWizardStep = function () {
        // Hides opened pointers
        hideWPPointers();

        var currentStepIndex = self.currentStep().index;
        var nextStep = _.find(self.stepModels(), function (item) {
            return item.index > currentStepIndex && (0 === item.type.length || item.type.indexOf(self.relationshipType()) >= 0);
        });

        var $contentActive = jQuery(self.currentStep().id);
        var $next = jQuery(nextStep.id);

        $contentActive.fadeOut(function () {
            jQuery("html, body").animate({scrollTop: 0});
            $next.fadeIn();
            self.currentStep(nextStep);
        });
        // Due to mess between KO and jQuery UI, needs to force change event to rewrite the default option
        if (nextStep.id === '#types-wizard-step-3') {
            jQuery('input[data-bind*=onLimitChange]').trigger( 'change' );
        }
        // When passing from Relationships Fields to Names it is neccesary to update
        // the observable
        if (nextStep.id === '#types-wizard-step-5') {
            // Why using jQuery events? there is a bug in ko, if I bind textInput and then event {keyup}, the second one is never triggered.
            // If I change the order of the bounds, again, the second one is not triggered.
            // So I need jQuery events handling.
            // Why added here? Because it has to be attached after ko bindings.
            jQuery('#types-wizard-name-plural, #types-wizard-name-singular').off('keyup').on('keyup', function () {
                self.labelsAreModifiedManually = true;
            });

            self.fieldsHaveChanged();

            // Setting defaults for Names, ko is not used because the user can go back and change observables values
            $plural = jQuery('#types-wizard-name-plural');
            $singular = jQuery('#types-wizard-name-singular');
            if (self.relationshipNamePlural().length === 0 || !self.labelsAreModifiedManually) {
                self.relationshipNamePlural(self.getSelectedParentPostType().plural + ' ' + self.getSelectedChildPostType().plural);
            }
            if (self.relationshipNameSingular().length === 0 || !self.labelsAreModifiedManually) {
                self.relationshipNameSingular(self.getSelectedParentPostType().singular + ' ' + self.getSelectedChildPostType().singular);
                jQuery('#types-wizard-name-slug').val(wpcf_slugize($singular.val())).trigger( 'paste' );
            }

            // Refresh summary limits due to slider doesn't trigger ko bounds.
            jQuery('input[data-bind*=onLimitChange]').trigger( 'change' );
        }

    };


    /**
     * Goes to prev wizard step
     *
     * @since m2m
     */
    self.goPrevWizardStep = function () {
        // Hides opened pointers
        hideWPPointers();

        var currentStep = self.currentStep();
        var prevStep = _.find(self.stepModels().slice().reverse(), function (item) {
            return item.index < currentStep.index && (0 === item.type.length || item.type.indexOf(self.relationshipType()) >= 0);
        });

        jQuery(currentStep.id).fadeOut(function () {
            jQuery("html, body").animate({scrollTop: 0});
            jQuery(prevStep.id).fadeIn();
            self.currentStep(prevStep);
        });
    };

    /**
     * Relationships radio buttons
     *
     * @since m2m
     */
    self.relationshipType = ko.observable('');
    self.relationshipSupportsFields = ko.observable(false);

    self.relationshipType.subscribe(function (relationship) {
        switch (relationship) {
            case 'many-to-many':
                self.relationshipSupportsFields(true);
                break;
            case 'one-to-one':
            case 'one-to-many':
                self.relationshipSupportsFields(false);
                break;
        }
    });


    /**
     * Returns if the "create intermediary post" checkbox is disabled
     *
     * @since m2m
     */
    self.isCreateIntermediaryPostDisabled = ko.computed(function () {
        return (self.thereAreFields() && self.relationshipSupportsFields()) || !self.isIntermediarySectionVisible();
    });

    /**
     * Stores the parent post type for following steps
     *
     * @param {Object} data knockout object
     * @param {Event} event change event
     * @since m2m
     */
    self.setParentPostType = ko.observable();
    self.setParentPostType.subscribe(function (value) {
        self.parentPostType(value);

        // Hides opened pointers
        hideWPPointers();
    });


    /**
     * Stores the child post type for following steps
     *
     * @param {Object} data knockout object
     * @param {Event} event change event
     * @since m2m
     */
    self.setChildPostType = ko.observable();
    self.setChildPostType.subscribe(function (value) {
        self.childPostType(value);
        // Hides opened pointers
        hideWPPointers();
    });


    /**
     * Returns the plural and singular of a parent post type
     *
     * @return {object} Post type object: plural, singular
     * @since m2m
     */
    self.getSelectedParentPostType = ko.pureComputed(function () {
        var postType = self.parentPostType();
        return postType
            ? Types.page.relationships.main.modelData.postTypes[postType]
            : {singular: '', plural: '', slug: ''};
    });


    /**
     * Returns the plural and singular of a child post type
     *
     * @return {object} Post type object: plural, singular
     * @since m2m
     */
    self.getSelectedChildPostType = ko.pureComputed(function () {
        var postType = self.childPostType();
        return postType
            ? Types.page.relationships.main.modelData.postTypes[postType]
            : '';
    });

    /**
     * Trigger some actions when the form is reset
     *
     * @since m2m
     */
    jQuery('#types-wizard-form').on('reset', function () {
        // Empty setters.
        self.childPostType('');
        self.parentPostType('');
        self.relationshipType('');
    });


    /**
     * Exits from New Relationship Wizard
     *
     * @param {Function} cb Optional. In case some extra functionality is needed, for example to show a different screen
     * @since m2m
     */
    self.onExitWizard = function () {
        Toolset.hooks.doAction('types-relationships-wizard-exit');
        if (arguments.length && _.isFunction(arguments[0])) {
            var cb = arguments[0];
            cb();
        } else {
            Types.page.relationships.main.showRelationships();
        }
    };


    /**
     * Closes the wizard dialog
     *
     * @since m2m
     */
    if (-1 === listingModel.hooksAdded.indexOf('types-relationships-wizard-close')) {
        listingModel.hooksAdded.push('types-relationships-wizard-close');
        /**
         * IMPORTANT: now WizardViewModel is not data dependent but if that changes
         * and you want to add some actions in the hook, please, remove the hook and
         * then create a new one
         */
        Toolset.hooks.addAction('types-relationships-wizard-close', function () {
            self.onExitWizard();
        });
    }


    /**
     * Gets relationship selected
     *
     * @return  {object} Object containing relationship type, image (perhaps is needed) and classname
     * @since m2m
     */
    self.getSelectedRelationship = ko.pureComputed(function () {
        var rt = self.relationshipType();

        return {
            relationshipType: rt,
            className: 'wizard-container wizard-container-' + (rt ? rt : ''),
        }
    });


    /**
     * Gets explination limits text
     *
     * @param {string} role Role type: parent, child
     * @return {string}
     * @since m2m
     */
    self.getLimitsExplanationText = function (role) {
        var text = main.getString(['wizard', 'limits']);
        var parent = self.getPostTypeByRole(role).singular;
        var child = self.getPostTypeByRole(role, true).plural;

        return text
            .replace('%PARENT%', parent)
            .replace('%CHILD%', child)
    };


    /**
     * Gets the post type by role
     *
     * @param {string} role Role type: parent, child
     * @param {boolean} inverse If inverse role is selected
     * @since m2m
     */
    self.getPostTypeByRole = function (role, inverse) {
        if (typeof inverse === 'undefined') {
            inverse = false;
        }
        var isParent = 'parent' === role;
        return (
            (inverse ? !isParent : isParent)
                ? self.getSelectedParentPostType()
                : self.getSelectedChildPostType()
        )
    };


    /**
     * Gets parent/child summary description
     *
     * @param {string} role Role type: parent, child
     * @return {string}
     * @since m2m
     */
    self.summaryRelationshipDescription = function (role) {
        return ko.pureComputed(function () {
            var relationshipType = self.relationshipType();
            var text = relationshipType == 'one-to-one'
                ? main.getString(['wizard', 'summaryDescriptionOneToOne'])
                : main.getString(['wizard', 'summaryDescription']);
            if (relationshipType == 'one-to-one') {
                var parent = self.getPostTypeByRole(role).singular;
            } else {
                var parent = self.getPostTypeByRole(role).plural;
            }
            var child = self.getPostTypeByRole(role, true).singular;
            var number = (
                'parent' === role
                    ? self.relationshipsLimitsParent()
                    : self.relationshipsLimitsChild()
            );
            if (isNaN(number)) {
                number = main.getString(['wizard', 'infinite']);
            }


            return text
                .replace('%PARENT%', parent)
                .replace('%CHILD%', child)
                .replace('%NUMBER%', number)
        });
    };


    /**
     * Returns post types list
     *
     * @return {object[]} The same postTypes array including the index (slug) as another property
     * @since m2m
     */
    self.postTypes = ko.observableArray(_.map(Types.page.relationships.main.modelData.postTypes,
        function (value, key) {
            value.slug = key;
            return value;
        }
    ));


    /**
     * Returns if the relationship involves a translatable post type
     *
     * @sinbce m2m
     */
    self.hasTranslatablePostType = ko.pureComputed(function () {
        var postTypes = self.postTypes();
        var parentType = self.parentPostType();
        var childType = self.childPostType();
        var parentPostTypes = postTypes.find(function (item) {
            return item.slug === parentType;
        });
        var childPostTypes = postTypes.find(function (item) {
            return item.slug === childType;
        });

        return (
            (!!parentPostTypes && parentPostTypes.isTranslatable)
            ||
            (!!childPostTypes && childPostTypes.isTranslatable)
        );
    });


    /**
     * Gets has translatable post types warning
     *
     * @return {string}
     * @since m2m
     */
    self.translatableWarningText = ko.observable(main.getString(['wizard', 'translatableWarning']));


    /**
     * Handles parent post type
     *
     * @param {HTMLElement} el The element bounded ko $element
     * @return {boolean} If the item of the parent list is enabled
     * @since m2m
     */
    self.isParentPostTypeEnabled = function (parentPostType) {
        var relationship_type = self.relationshipType();
        var childPostType = self.childPostType();
        return (childPostType != parentPostType);
    };


    /**
     * Handles child post type
     *
     * @param {HTMLElement} el The element bounded ko $element
     * @return {boolean} If the item of the child list is enabled
     * @since m2m
     */
    self.isChildPostTypeEnabled = function (childPostType) {
        var relationship_type = self.relationshipType();
        var parentPostType = self.parentPostType();
        return (childPostType != parentPostType);
    };


    /**
     * Returns if the next button is enabled
     *
     * @param {integer} tabNumber The tab position
     * @return {boolean} Depending of the tab it returns if the next button is visible.
     * @since m2m
     */
    self.isNextButtonEnabled = function (tabNumber) {
        switch (tabNumber) {
            case 1:
                return self.getSelectedRelationship().relationshipType != '';
            case 2:
                return self.parentPostType() != '' && self.childPostType() != '';
            case 3:
            case 4:
				return true;
            case 5:
                return ! wizardModelGlobal.thereIsSlugConflict();
            default:
                return false;
        }
    };


    /*******************
     *
     * Tabs class names
     *
     *******************/
    self.isRelationshipTypeStepActive = ko.computed(function () {
        return 0 <= self.currentStep().index;
    });
    self.isPostTypesStepActive = ko.computed(function () {
        return 1 <= self.currentStep().index;
    });
    self.isCustomLimitsStepDisabled = ko.computed(function () {
        if (!self.relationshipType()) return false;
        var item = self.stepModels()[2];
        return (0 === item.type.length || item.type.indexOf(self.relationshipType()) === -1);
    });
    self.isCustomLimitsStepActive = ko.computed(function () {
        return 2 <= self.currentStep().index && !self.isCustomLimitsStepDisabled();
    });
    self.isRelationshipFieldsStepDisabled = ko.computed(function () {
        if (!self.relationshipType()) return false;
        var item = self.stepModels()[3];
        return (0 === item.type.length || item.type.indexOf(self.relationshipType()) === -1);
    });
    self.isRelationshipFieldsStepActive = ko.computed(function () {
        return 3 <= self.currentStep().index && !self.isRelationshipFieldsStepDisabled();
    });
    self.isNamesStepActive = ko.computed(function () {
        return 4 <= self.currentStep().index;
    });
    self.isSummaryStepActive = ko.computed(function () {
        return 5 <= self.currentStep().index;
    });


    /**
     * Current class
     */
    self.isRelationshipTypeStepCurrent = ko.computed(function () {
        return 0 === self.currentStep().index;
    });
    self.isPostTypesStepCurrent = ko.computed(function () {
        return 1 === self.currentStep().index;
    });
    self.isCustomLimitsStepCurrent = ko.computed(function () {
        return 2 === self.currentStep().index && !self.isCustomLimitsStepDisabled();
    });
    self.isRelationshipFieldsStepCurrent = ko.computed(function () {
        return 3 === self.currentStep().index && !self.isRelationshipFieldsStepDisabled();
    });
    self.isNamesStepCurrent = ko.computed(function () {
        return 4 === self.currentStep().index;
    });
    self.isSummaryStepCurrent = ko.computed(function () {
        return 5 === self.currentStep().index;
    });


    /**
     * Checks if the Limit is visible
     *
     * @since m2m
     */
    self.isWizardLimitVisible = ko.pureComputed(function () {
        return self.getSelectedRelationship().relationshipType == 'many-to-many';
    });


    /**
     * Checks if the relationship summary is visible
     *
     * @since m2m
     */
    self.isWizardRelationshipSummaryVisible = ko.pureComputed(function () {
        return self.getSelectedRelationship().relationshipType != 'one-to-many';
    });


    /**
     * Retreviews selected fields for showing in summary
     *
     * @since m2m
     */
    self.selectedFields = ko.notifyingWritableComputed({
        read: function (v) {
            return _.map(
                jQuery('.toolset-postbox h3'), function (item) {
                    return {text: jQuery(item).html()};
                }
            );
        },
        write: function (v) {
        }
    })


    /**
     * Shows a WP pointer
     *
     * @param {HTMLElement} el The element bounded
     *
     * @since m2m
     */
    self.showPointer = function (el) {
        Types.page.relationships.main.showPointer(el);
    };


    /**
     * Handles syncronization between input and slider
     *
     * @param {HTMLElement} el The ko $element
     * @since m2m
     */
    self.onLimitChange = function (el) {
        var value = el.value;
        if (value.match(/^[\+-]?\d+$/) && parseInt(value) < 2) {
            value = 2;
            el.value = "2";
        }
        // Because of there is jQuery involve, this shortcut is needed
        if (el.id.match(/parent/)) {
            self.relationshipsLimitsParent(el.value);
        } else {
            self.relationshipsLimitsChild(el.value);
        }

        var $slider = jQuery('[data-related=' + el.id + ']');

        // If there is not a number, then is "No limit"
        var sliderValues = $slider.data('sliderValues');
        if (!/^\d+$/.test(value)) {
            $slider.slider('value', sliderValues.length + 1);
            return;
        }

        // Looking for the closest value
        for (var i in sliderValues) {
            var next = parseInt(i) + 1;
            if (sliderValues.length - 1 === i || (sliderValues[i] <= value && sliderValues[next] > value)) {
                $slider.data('sync', false);
                $slider.slider('value', next + 1);
                return;
            }
        }
    };

    /**
     * Used to updates summary descriptions
     *
     * @since m2m
     */
    self.relationshipsLimitsParent = ko.observable('');
    self.relationshipsLimitsChild = ko.observable('');


    /**
     * Handles the labels changes in order to not change them when post type is modified after manually update.
     */
    self.labelsAreModifiedManually = false;


    /**
     * Used for showing/hiding slug conflict message
     *
     * @since m2m
     */
    self.thereIsSlugConflict = ko.observable(false);


    /**
     * Slug conflict message
     *
     * @since m2m
     */
    self.slugConflictMessage = ko.observable('');


    /**
     * Controls if aliases inputs are enabled
     *
     * @since m2m
     */
    self.isEnabledAliases = ko.observable(false);

    /**
     * Simulates sprintf
     *
     * @param {string} format Main text.
     * @param {string} value Text.
     * @return {string}
     * @since m2m
     */
    self.roleLabelTitle = function (format, value) {
        return format.replace('%s', value);
    };


    /**
     * Creates the relationships, loads the new one, refresh the list and open
     * the editor
     *
     * @since m2m
     */
    self.onCreate = function () {
        self.isSaving(true);
        var newRelationshipModel = {isActive: true};

        var formData = {};
        _.each(jQuery('#types-wizard-form').serializeArray(), function (item) {
            formData[item.name] = item.value;
        });

        // Cardinality
        // Because of sync with jQuery Slider, if ko.observable is called it resets the values
        var parent_limit = formData['parent_limit'];
        if (!parent_limit.match(/^\d+$/)) {
            parent_limit = -1;
        }
        var child_limit = formData['child_limit'];
        if (!child_limit.match(/^\d+$/)) {
            child_limit = -1;
        }

        switch (formData['relationship_type']) {
            case 'one-to-one':
                newRelationshipModel.cardinality = {
                    child: {
                        min: 0,
                        max: 1
                    },
                    parent: {
                        min: 0,
                        max: 1
                    }
                };
                break;
            case 'one-to-many':
                newRelationshipModel.cardinality = {
                    child: {
                        min: 0,
                        max: child_limit
                    },
                    parent: {
                        min: 0,
                        max: 1
                    }
                }
                break;
            case 'many-to-many':
                newRelationshipModel.cardinality = {
                    child: {
                        min: 0,
                        max: child_limit
                    },
                    parent: {
                        min: 0,
                        max: parent_limit
                    }
                }
                break;
        }

        // displayName
        var displayName = self.relationshipNamePlural();
        if (!displayName) {
            displayName = self.getSelectedParentPostType().plural + ' ' + self.getSelectedChildPostType().plural;
        }
        newRelationshipModel.displayName = displayName;

        // displayNameSingular
        var displayNameSingular = self.relationshipNameSingular();
        if (!displayNameSingular) {
            displayNameSingular = self.getSelectedParentPostType().singular + ' ' + self.getSelectedChildPostType().singular;
        }
        newRelationshipModel.displayNameSingular = displayNameSingular;

        // Slug
        var slug = formData['name_slug'] ? formData['name_slug'] : self.relationshipNameSlug();
        if (!slug) {
            slug = wpcf_slugize(displayNameSingular);
        }
        newRelationshipModel.slug = newRelationshipModel.newSlug = slug;

        // Types
        newRelationshipModel.types = {
            child: {
                domain: 'posts',
                types: [self.getSelectedChildPostType().slug]
            },
            parent: {
                domain: 'posts',
                types: [self.getSelectedParentPostType().slug]
            },
            intermediary: {
                exists: 2,
                type: slug
            }
        };

        // Driver
        newRelationshipModel.driver = 'toolset';

        /**
         * Field groups, not handled by RelationshipViewModel
         */

        /**
         * Transforms the array of input files into a nested array of values properties:
         * example:
         *    'wpcf[fields][select-1104179618][options][default]="some text"'
         *    resutls:
         *      {
		 *        wpcf: {
		 *          fields {
		 *            select-1104179618: {
		 *              options: {
		 *                default: 'some text'
		 *              }
		 *            }
		 *          }
		 *        }
		 *      }
         *
         * @param {array} list Array of nexted index
         * @param {string} value Final value
         * @return {object}
         * @since m2m
         */
        var nestedObject = function (list, value) {
            if (!list.length) return '"' + value + '"';
            var elem = list[0];
            return '{"' + elem.replace(/[\[\]]/g, '') + '":' + nestedObject(list.splice(1), value) + '}';
        }

        // Gests the field inputs and transform then in a JSON object
        var fieldsObjects = [];
        _.each(jQuery('[name^=wpcf]').serializeArray(), function (ele) {
            fieldsObjects.push(JSON.parse(nestedObject(ele.name.match(/\[([^\]]+)\]/g), ele.value)));
        });

        // Join the fields data into a single object.
        // Due to creating nested object is complicate, it will be created according to the known fields structure.
        var fields = {};
        _.each(fieldsObjects, function (obj) {
            var field_name = _.keys(obj.fields)[0];
            if (!_.has(fields, field_name)) {
                fields[field_name] = {};
            }
            var field_option = _.keys(obj.fields[field_name])[0];
            if ('options' === field_option || 'validate' === field_option) {
                // 'options' has nested options too.
                if (!_.has(fields[field_name], field_option)) {
                    fields[field_name][field_option] = {};
                }
                var option_field = _.keys(obj.fields[field_name][field_option])[0];
                // The part of the 'options': title, value, ...
                if ('default' === option_field) {
                    // Default is a single option
                    fields[field_name][field_option][option_field] = obj.fields[field_name][field_option][option_field];
                } else {
                    // The rest has several suboptions
                    if (!_.has(fields[field_name][field_option], option_field)) {
                        fields[field_name][field_option][option_field] = {};
                    }
                    var option_element = _.keys(obj.fields[field_name][field_option][option_field])[0];
                    fields[field_name][field_option][option_field][option_element] = obj.fields[field_name][field_option][option_field][option_element];
                }
            } else {
                // Rest of the options
                fields[field_name][field_option] = obj.fields[field_name][field_option];
            }
        });

		newRelationshipModel.wpcf = { fields: fields };
		newRelationshipModel.intermediary = self.createIntermediaryPostType() && self.isIntermediarySectionVisible();
		newRelationshipModel.isAutodeletingIntermediaryPosts = self.isAutodeletingIntermediaryPosts();
		newRelationshipModel.visible = jQuery( '#types-wizard-create-visible' ).is( ':checked' );

        // Roles labels
        jQuery('[name^=role]').serializeArray().forEach(function (elem) {
            var parts = elem.name.match(/([^\[]+)\[([^\]]+)\]/);
            if (!newRelationshipModel[parts[1]]) {
                newRelationshipModel[parts[1]] = {};
            }
            newRelationshipModel[parts[1]][parts[2]] = elem.value;
        });
        var ajax = Types.page.relationships.main.ajax;

        var finalize = function () {
            self.isSaving(false);
        };

        var handleFailure = function (response) {
            Types.page.relationships.main.viewModel.displayMessagesFromAjax(response.data || {}, 'error', 'There was an error when saving the relationship.');
            finalize();
            // todo force page reload before saving anything?
        };

        var handleSuccess = function (response, responseData) {
            // We expect exactly one updated definition.
            if (
                !_.has(responseData, 'updated_definitions')
                || !_.isArray(responseData['updated_definitions'])
                || 1 !== responseData['updated_definitions'].length
            ) {
                handleFailure(response);
                return;
            }

            Types.page.relationships.main.viewModel.displayMessagesFromAjax(responseData, 'info', 'Relationship has been saved.');
            self.onExitWizard(function () {
                var listingViewModel = Types.page.relationships.main.viewModel;
                var relationship = new Types.page.relationships.viewmodels.RelationshipViewModel(
                    responseData.updated_definitions[0], listingViewModel.itemActions, listingViewModel
                );
                listingViewModel.items.push(relationship);
                var items = Types.page.relationships.main.viewModel.items();
                self.onExitWizard();
            });
            finalize();
        };

        ajax.doAjax(ajax.action.create, newRelationshipModel, handleSuccess, handleFailure);


    };


    /**
     * Knockout new binding Sortable Items
     *
     * Make the element bound sortable
     *
     * @since m2m
     */
    ko.bindingHandlers.typesFieldsSortable = {
        // Inits the element as sortable
        init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            var $container = jQuery(element);
            $container.sortable({
                axis: 'y',
                handle: '.toolset-postbox-title',
                forcePlaceholderSize: true,
                placeholder: 'toolset-fields-placeholder'
            });
        }
    }

    /**
     * Hides WP Pointers
     */
    var hideWPPointers = function () {
        Types.page.relationships.main.hideWPPointers();
    };

    /**
     * Start checking for rewrite slug conflicts within relationships.
     *
     * Displays a warning (not error) message after the input field as long as there is a conflict, but doesn't block the
     * form submitting.
     *
     * @since m2m
     */
    var initRewriteSlugChecker = function () {
        var $rewriteSlugInput = jQuery('#types-wizard-name-slug');

        var checker = Types.slugConflictChecker.build(
            $rewriteSlugInput,
            ['relationships_rewrite_slugs'],
            'relationships_rewrite_slugs',
            $rewriteSlugInput.val(),
            jQuery('input[name="types_check_slug_conflicts_nonce"]').val(),
            function (isConflict, displayMessage) {
                wizardModelGlobal.thereIsSlugConflict(isConflict);
                wizardModelGlobal.slugConflictMessage(displayMessage);
            }
        );

        checker.bind();

		var rewriteSlugInputDebouncedChecker = function() {
			wizardModelGlobal.thereIsSlugConflict( true );
			wizardModelGlobal.slugConflictMessage( main.getString(['wizard', 'slugConflictPlaceholder']) );

            _.defer(function () {
                checker.check();
            });
		};

		var rewriteSlugInputDebouncer = _.debounce( rewriteSlugInputDebouncedChecker, 100 );

		$rewriteSlugInput.on( 'keyup input cut paste', function() {
			rewriteSlugInputDebouncer();
		});
    };


    /**
     * Init Setup
     *
     * @since m2m
     */
    if (-1 === listingModel.hooksAdded.indexOf('types-relationships-wizard-init')) {
        listingModel.hooksAdded.push('types-relationships-wizard-init');
        /**
         * IMPORTANT: now WizardViewModel is not data dependent but if that changes
         * and you want to add some actions in the hook, please, remove the hook and
         * then create a new one
         */
        Toolset.hooks.addAction('types-relationships-wizard-init', function () {
            // Setup sliders
            self.MAX_SLIDER_POSITION = 29;

            var sliderValues = [];
            for (var sliderValueIndex = 2; sliderValueIndex < 32; sliderValueIndex++) {
                // Calculates the real value [2, 3, 4, ..., 9, 10, 20, 30, ..., 90, 100, 200, 300, ..., 900, 1000, infinity]
                var units = sliderValueIndex % 10;
                var tens = parseInt(sliderValueIndex / 10);
                var value = Math.pow(10, tens) * units;
                if (value) {
                    sliderValues.push(value);
                }
            }
            sliderValues.push(main.getString(['wizard', 'noLimit']));


            // Handles change 'slide' event
            var sliderChangeEvent = function (event, ui) {
                $slider = jQuery(this);
                if ($slider.data('sync')) {
                    $input = jQuery('#' + $slider.data('related'));
                    // Because of there is jQuery involve, this shortcut is needed
                    if ($input.length > 0) {
                        var value = $input.val();
                        if ($input.attr('id').match(/parent/)) {
                            self.relationshipsLimitsParent(value);
                        } else {
                            self.relationshipsLimitsChild(value);
                        }

                        $input.val(sliderValues[ui.value - 2]);
                    }
                }
            };

            // Handles change 'slide' event
            var activateSliderSync = function (event, ui) {
                jQuery(this).data('sync', true);
            };

            jQuery('.wizard-limit-slider').slider({
                slide: sliderChangeEvent,
                change: sliderChangeEvent,
                min: 2,
                max: self.MAX_SLIDER_POSITION,
            }).slider('value', self.MAX_SLIDER_POSITION)
                .on('mousedown', activateSliderSync)
                .data('sync', true)
                .data('sliderValues', sliderValues); // Avoid sync when user change <input>

            jQuery('.types-wizard-roles-opener').on('click', function () {
                var $roles = jQuery('.types-wizard-roles');
                $roles.slideToggle(function () {
                    wizardModelGlobal.isEnabledAliases(!$roles.is(':hidden'));
                });
                return false;
            });

            initRewriteSlugChecker();
        });
    } else {
        Toolset.hooks.doAction('types-relationships-wizard-init');
    }

    return self;
};


// IE fallback for Array.prototype.find
if (! Array.prototype.find) {
    Object.defineProperty( Array.prototype, 'find', {
        value: function(predicate) {
            // 1. Let O be ? ToObject(this value).
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If IsCallable(predicate) is false, throw a TypeError exception.
            if (typeof predicate !== 'function') {
                throw new TypeError('predicate must be a function');
            }

            // 4. If thisArg was supplied, let T be thisArg; else let T be undefined.
            var thisArg = arguments[1];

            // 5. Let k be 0.
            var k = 0;

            // 6. Repeat, while k < len
            while (k < len) {
                // a. Let Pk be ! ToString(k).
                // b. Let kValue be ? Get(O, Pk).
                // c. Let testResult be ToBoolean(? Call(predicate, T, « kValue, k, O »)).
                // d. If testResult is true, return kValue.
                var kValue = o[k];
                if (predicate.call(thisArg, kValue, k, o)) {
                    return kValue;
                }
                // e. Increase k by 1.
                k++;
            }

            // 7. Return undefined.
            return undefined;
        },
        configurable: true,
        writable: true
    });
}
