/**
 * ViewModel of a single field definition.
 * 
 * @extends Toolset.Gui.ItemViewModel
 * 
 * @param {{isUnderTypesControl:bool,isRepetitive:bool,groups:string[],type:string,displayName:string,slug:string,metaKey:string}} model
 *     Field definition model.
 * @param fieldActions An object with methods to perform actions on field definitions.
 * 
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.FieldDefinitionViewModel = function(model, fieldActions) {

    var self = this;
    
    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call(self, model, fieldActions);
    
    /** Reusable strings. */
    self.labels = {
        notManagedByTypes: '<em>' + Types.page.fieldControl.strings.misc['notManagedByTypes'] + '</em>'
    };


    // ------------------------------------------------------------------------
    // Model properties
    // ------------------------------------------------------------------------

    self.isUnderTypesControl = ko.observable(model.isUnderTypesControl);

    self.isRepetitive = ko.observable(model.isRepetitive);

    self.groups = ko.observableArray(model.groups);

    self.type = ko.observable(model.type);

    self.displayName = ko.observable(model.displayName);

    self.slug = ko.observable(model.slug);
    
    self.metaKey = ko.observable(model.metaKey);


    // ------------------------------------------------------------------------
    // Computed properties for display purposes
    // ------------------------------------------------------------------------
    
    
    self.display = {

        groupList: ko.pureComputed(function () {
            if (!self.isUnderTypesControl()) {
                return self.labels.notManagedByTypes;
            } else {
                var groupNameList = _.map(self.groups(), function (groupSlug) {
                    if (_.has(Types.page.fieldControl.groups, groupSlug)) {
                        return Types.page.fieldControl.groups[groupSlug].displayName;
                    } else {
                        return groupSlug;
                    }
                });
                return groupNameList.join(', ');
            }
        }),

        type: ko.pureComputed(function() {

            if(!self.isUnderTypesControl()) {
                return self.labels.notManagedByTypes;
            } else if(_.has(Types.page.fieldControl.fieldTypeDefinitions, self.type())) {
                return Types.page.fieldControl.fieldTypeDefinitions[self.type()].displayName;
            } else {
                return '<em>' + self.type() + '</em>';
            }

        }),

        changeManagementStatusActionLabel: ko.pureComputed(function() {
            //noinspection JSUnresolvedVariable
            return Types.page.fieldControl.strings.rowAction.manageByTypes[ self.isUnderTypesControl() ? 'no' : 'yes' ];
        }),

        
        changeCardinalityActionLabel: ko.pureComputed(function() {
            //noinspection JSUnresolvedVariable
            return Types.page.fieldControl.strings.rowAction.changeCardinality[ self.isRepetitive() ? 'makeSingle' : 'makeRepetitive' ];
        })
    
    };


    /**
     * Determine CSS class for the tr tag depending on field status.
     *
     * @since 2.0
     */
    self.trClass = ko.computed(function() {
        if(!self.isUnderTypesControl()) {
            return 'types-field-not-managed-by-types';
        } else if(0 == self.groups().length) {
            return 'types-field-not-used-in-groups';
        } else {
            return '';
        }
    });

    

    self.canChangeCardinality = ko.pureComputed(function() {
        if(!self.isUnderTypesControl()) {
            // we can't manage this field
            return false;
        } else if(self.isRepetitive()) {
            // allways allow for switching from repetitive to single
            return true;
        } else if(_.has(Types.page.fieldControl.strings.fieldTypeDefinitions, self.type())) {
            // for single fields, depends on type definition
            //noinspection JSUnresolvedVariable
            return Types.page.fieldControl.strings.fieldTypeDefinitions[self.type()].canBeRepetitive || false;
        } else {
            // disable for safety if the type is unknown
            return false;
        }
    });

    
    self.typeForSorting = ko.pureComputed(function() {
        if(self.isUnderTypesControl()) {
            return self.display.type();
        } else {
            return '';
        }
    });

    
    // ------------------------------------------------------------------------
    // Event handlers
    // ------------------------------------------------------------------------

    
    self.onChangeAssignmentAction = function() {
        Types.page.fieldControl.viewmodels.ChangeAssignDialogViewModel(self, function(isAccepted, updatedGroups) {
            if(isAccepted) {
                self.itemActions.changeGroupAssignment(self, {group_slugs: updatedGroups});
            }
        }).display();
    };


    self.onChangeTypeAction = function() {

        Types.page.fieldControl.viewmodels.ChangeFieldTypeDialogViewModel(self, function(isAccepted, typeToConvertInto, newCardinality) {

            if(!isAccepted) {
                // Action cancelled.
                return;
            }

            // Depending on user's changes, we may do two AJAX calls, or one, or none.
            var isTypeChangeNeeded = (self.type() != typeToConvertInto);
            var isCardinalityChangeNeeded = (self.isRepetitive() != ('single' != newCardinality));

            var finalSuccessCallback = function(response, data, fieldDefinitions) {
                self.itemActions.updateFieldDefinitionModels(data.results, fieldDefinitions);
            };
            
            var failCallback = function(response) { };
            
            var doCardinalityChange = _.partial(self.itemActions.changeFieldCardinality, self, {target_cardinality: newCardinality});
            
            if(isTypeChangeNeeded) {
                if(isCardinalityChangeNeeded) {

                    // The most complex scenario. Change the field type, and if the action is a success, continue with changing
                    // it's cardinality.
                    self.itemActions.changeFieldType(
                        self, 
                        function(response, data, fieldDefinitions) {
                            // onSuccess
                            self.itemActions.updateFieldDefinitionModels(data.results, fieldDefinitions);
                            doCardinalityChange();
                        }, 
                        failCallback, 
                        {field_type: typeToConvertInto}
                    );
                } else {

                    // Only change field type.
                    self.itemActions.changeFieldType(self, finalSuccessCallback, failCallback, {field_type: typeToConvertInto});
                }
            } else {
                if(isCardinalityChangeNeeded) {

                    // Only change the cardinality
                    doCardinalityChange();

                } else {
                    // Nothing to do at all.
                }
            }
                        
        }).display();
    };


    self.onChangeManagementStatusAction = function() {
        if(self.isUnderTypesControl()) {
            self.itemActions.stopManagingWithTypes(self);
        } else {
            self.itemActions.manageWithTypes(self);
        }
    };


    self.onDeleteAction = function() { 
        Types.page.fieldControl.viewmodels.DeleteDialogViewModel(self, function(isAccepted) {
            if(isAccepted) {
                self.itemActions.deleteFields(self);
            }
        }).display();
    };


};

