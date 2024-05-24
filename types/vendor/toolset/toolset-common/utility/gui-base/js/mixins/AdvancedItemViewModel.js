var Toolset = Toolset || {};
Toolset.Gui = Toolset.Gui || {};
Toolset.Gui.Mixins = Toolset.Gui.Mixins || {};

/**
 * Mixin intended for extending Toolset.Gui.ItemViewModel with some advanced functionality,
 * mainly for easy synchronization between the viewmodel and the underlying model objects.
 *
 * It allows to bind a model property (even if nested) to a knockout observable and updates the model on change.
 * It also tracks the last value that has been persisted on the server and it offers a very easy way to determine
 * whether the viewmodel has unsaved changes.
 *
 * Example (schematic) usage:
 *
 * MyViewModel = function(modelSource) {
 *      var self = this;
 *
 *      Toolset.Gui.Mixins.AdvancedItemViewModel.call(self);
 *
 *      var model = modelSource;
 *
 *      self.myObservable = self.createModelProperty(ko.observable, model, 'propertyNameInModel');
 *      self.myObservableArray = self.createModelProperty(ko.observable, model, ['topLevelProperty', 'secondLevelProperty']);
 *
 *      function checkStatus() {
 *          if(self.hasChanged()) {
 *              alert('This item needs to be saved!');
 *          }
 *      }
 *
 *      function afterSaving(updatedModelFromServer) {
 *          // This is necessary to reset the list of changed properties and reflect any additional changes
 *          // that the server might have made to the model.
 *          self.updateViewModelFromModel(updatedModelFromServer, model);
 *      }
 *
 *      function accessObservable() {
 *          // As any other observable:
 *          return self.myObservable();
 *      }
 *
 *      function cancelEditing() {
 *          // Discard all unsaved changes:
 *          self.resetChanges();
 *      }
 * }
 *
 * @since 3.0.7
 * @constructor
 */
Toolset.Gui.Mixins.AdvancedItemViewModel = function () {

    var self = this;

    Toolset.Gui.Mixins.KnockoutExtensions.call(self);

    var modelPropertyToSubscribableMap = [];

    self.changedProperties = ko.observableArray();

    self.hasChanged = ko.pureComputed(function() {
        return (self.changedProperties().length > 0);
    });


    /**
     * Accepts a complex object and a path made from property names. Returns the object that holds
     * the last property and the property name.
     *
     * Used for updating the model with changes in viewmodel. Since the model may not be flat,
     * we need to determine the actual object on which the property will be assigned.
     *
     * @param {*} model
     * @param {[string]|string} propertyNames Array of consecutive property names that make the path
     *     from the model object to the actual value.
     * @returns {{lastModelPart: *, lastPropertyName: string}}
     * @since m2m
     */
    var getModelSubObject = function(model, propertyNames) {
        // Accept a single property name as well.
        if(!_.isArray(propertyNames)) {
            propertyNames = [propertyNames];
        }

        if( propertyNames.length === 1) {
            // Same if we have an array with a single property name.
            return {
                lastModelPart: model,
                lastPropertyName: _.first(propertyNames)
            };
        } else {
            // For more than one nesting level, we'll traverse down to the last object.
            return {
                lastModelPart: _.reduce(_.initial(propertyNames), function(modelPart, propertyName) {
                    return modelPart[propertyName]
                }, model),
                lastPropertyName: _.last(propertyNames)
            };
        }
    };


    /**
     * Create a Knockout subscribable from a model's property, and setup a subscription so that
     * all changes are reflected back to the model.
     *
     * Also, bind to the self.hasChanged() property to indicate that this viewmodel needs an update.
     *
     * @param subscribableConstructor A ko.subscribable constructor, that means either ko.observable or
     *     ko.observableArray, ko.computed, ko.pureComputed, ...
     * @param {*} model Model to update on change.
     * @param {string|[string]} propertyNames Name of the model's property to update. If the property is in a nested
     *     object, this should be an array of property names that make the path to it.
     * @returns {*} The newly created subscribable
     *
     * @since m2m
     */
    self.createModelProperty = function(subscribableConstructor, model, propertyNames) {
        var modelSubObject = getModelSubObject(model, propertyNames);

        // Actually create the subscribable (observable).
        var currentValue = modelSubObject.lastModelPart[modelSubObject.lastPropertyName];

		// Beware: Sometimes, we may be passing arrays around. We need to make sure that
		// the value in subscribable and subscribable._lastPersistedValue are actually
		// two different objects. That's why JSON.parse(JSON.stringify(currentValue)).
		//
		// Details: https://stackoverflow.com/questions/597588/how-do-you-clone-an-array-of-objects-in-javascript
		var getValueDeepCopy = function (originalValue) {
			return ('undefined' === typeof(originalValue) ? undefined : JSON.parse(JSON.stringify(originalValue)));
		};
		var subscribable = subscribableConstructor(getValueDeepCopy(currentValue));

        // Make sure the subscribable will be synchronized with the model.
        Toolset.ko.synchronize(subscribable, modelSubObject.lastModelPart, modelSubObject.lastPropertyName);

		// Attach another subscribable of the same type to it, which will hold the last
		// value that was persisted to the databse.
		subscribable._lastPersistedValue = subscribableConstructor(getValueDeepCopy(currentValue));

        // When the subscribable changes (and only if it actually changes), update the array of changed properties
        // on this viewmodel. That will allow for sending only relevant changes to be persisted.
        subscribable.subscribe(function(newValue) {
            // We can't just use === because the value may be an array.
            if(!_.isEqual(subscribable._lastPersistedValue(), newValue)) {
                if(!_.contains(self.changedProperties(), propertyNames)) {
                    self.changedProperties.push(propertyNames);
                }
            } else {
                // If the value *became* equal again, we also need to indicate there's no need for saving anymore.
                self.changedProperties.remove(propertyNames);
            }
        });

        // When the last persisted value changes, we mirror the change in GUI (this allows the PHP part
        // to further change the stored data, e.g. generate an unique slug, etc.)
        subscribable._lastPersistedValue.subscribe(function(newPersistedValue) {
            subscribable(JSON.parse(JSON.stringify(newPersistedValue)));
            self.changedProperties.remove(propertyNames);
        });

        // This will be needed for applying the changes after persisting.
        modelPropertyToSubscribableMap.push({
            path: propertyNames,
            subscribable: subscribable
        });

        return subscribable;
    };


    /**
     * Update the current model with data from a model object coming from the server.
     * Any unsaved changes will be overwritten.
     *
     * @param updatedModel
     * @param originalModel
     */
    self.updateViewModelFromModel = function(updatedModel, originalModel) {

        // The self.slug observable is bound to model.newSlug, which we never get from the server.
        updatedModel.newSlug = updatedModel.slug;

        // model.slug is never updated otherwise.
        originalModel.slug = updatedModel.slug;

        _.each(modelPropertyToSubscribableMap, function(propertyToSubscribable) {

            var modelSubObject = getModelSubObject(updatedModel, propertyToSubscribable.path),
                lastPersistedValueSubscribable = propertyToSubscribable.subscribable._lastPersistedValue,
                newPersistedValue = modelSubObject.lastModelPart[modelSubObject.lastPropertyName];

            // This will also update the actual "parent" subscribable because of
            // the binding set up in createModelProperty
            lastPersistedValueSubscribable(newPersistedValue);
        })
    };


    /**
     * Reset all unsaved (unpersisted) changes.
     */
    self.resetChanges = function() {
        _.each(modelPropertyToSubscribableMap, function(propertyToSubscribable) {
            var lastPersistedValue = propertyToSubscribable.subscribable._lastPersistedValue();
            propertyToSubscribable.subscribable(lastPersistedValue)
        });
    }
};
