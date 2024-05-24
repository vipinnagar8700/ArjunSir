Types.page.customFields.viewmodels.CustomFieldViewModel = function(model, fieldActions, listingViewModel) {

	var self = this;

	// Apply the ItemViewModel constructor on this object.
	Toolset.Gui.ItemViewModel.call(self, model, fieldActions);

	// Data properties
	//
	//
	self.displayName = ko.observable(model.displayName);
	// Neeeded for sorting, displayName has a link and it messes the sorting
	self.displayNameText = ko.observable(model.displayName.replace(/<[^>]*>/g, ''));
	self.descriptionGroup = ko.observable(model.description);
	self.isActive = ko.observable(model.isActive);
	self.slug = ko.observable(model.slug);
	self.taxonomies = ko.observable(model.taxonomies);
	self.postTypes = ko.observable(model.postTypes);
	self.editLink = ko.observable(model.editLink);
	self.availableFor = ko.observable(model.availableFor);
	self.groupId = model.id;
	self.containsRFG = ko.observable(model.containsRFG);
	self.isRequired = ko.observable( model.isRequired );

	// Display properties
	//
	//
	self.display = {
		// Returns the text for the Activate/Deactivate single action
		isActiveGroup: ko.pureComputed(function() {
			return self.isActive() ?
				Types.page.customFields.strings.rowAction.deactivate :
				Types.page.customFields.strings.rowAction.activate;
		}),

		// Returns the current domain: posts, users, terms
		currentDomain: Types.page.customFields.currentDomain

	};

	// Redirects the Edit single action
	self.onRedirectEditAction = function() {
		self.itemActions.editFieldGroup(model);
	};

	// Shows {DeleteDialogViewModel} for deleting confirmation
	// If it is confirmed then it will delete the field groups via Ajax
	self.onDeleteAction = function() {
		Types.page.customFields.viewmodels.DeleteDialogViewModel(self, function(isAccepted) {
			if (isAccepted) {
				self.itemActions.deleteFieldGroups(self);
			}
		}).display();
	};

	// Toggles active action, only for single actions, not bulk ones
	self.onChangeActiveAction = function() {
		self.itemActions.changeFieldGroupActive(self);
	};

	/**
	 * Determine CSS class for the tr tag depending on field status.
	 *
	 * @since 2.3
	 */
	self.trClass = ko.computed(function() {
		return self.isActive() ? '' : 'status-inactive';
	});


    /**
     * Check whether a currently selected bulk action can be performed on this field group.
     *
     * @since 3.0
     */
    self.isBulkActionAllowed = ko.computed(function() {
		if ( self.isRequired() ) {
			return false;
		}
        var bulkAction = listingViewModel.selectedBulkAction();
        return !('delete' === bulkAction && self.containsRFG());
    });


};
