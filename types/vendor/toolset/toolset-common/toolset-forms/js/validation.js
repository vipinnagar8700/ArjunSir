/* eslint-disable */

/*
 * Validation JS
 *
 * - Initializes validation on selector (forms)
 * - Adds/removes rules on elements contained in var wptoolsetValidationData
 * - Checks if elements are hidden by conditionals
 *
 * @see class WPToolset_Validation
 */

// Note: We're always adding Gutenberg metabox form identifiers; even though the validation rules may be initialized later,
// we need the forms in those metaboxes to already have a jQuery validator object attached to them. Otherwise,
// validation rules are not applied properly and the whole house of cards breaks down.
var wptValidationForms = ( wptValidationForms || [] ).concat([ '.metabox-location-normal', '.metabox-location-advanced' ]);
var wptValidationDebug = false;
//Contains IDs for CRED form that were already initialised, to prevent multiple initialisation
var initialisedCREDForms = [];

var wptValidation = (function ($) {

	var hasInitBeenPerformed = false;

	function init() {

		if( hasInitBeenPerformed ) {
			return;
		}

		hasInitBeenPerformed = true;

		/**
		 * Check the file extension in an URL for the list of allowed ones.
		 *
		 * @since the dawn of age
		 */
		$.validator.addMethod( 'extension', function( value, element, param ) {
			param = typeof param === 'string' ? param.replace( /,/g, '|' ) : param;
			if ( $( element ).attr( 'res' ) && $( element ).attr( 'res' ) !== '' ) {
				return true;
			}

			// Sometimes, an image (or any file, for that matter) may be retrieved from an external url that requires
			// additional GET parameters. We should not let that prevent the user
			// from using such an URL in the custom field.
			var valueWithoutParameters = (
				'string' === typeof value && value.length > 0
					? value.split( '?' )[ 0 ]
					: value
			);

			return (
				this.optional( element )
				|| valueWithoutParameters.match( new RegExp( '.(' + param + ')$', 'i' ) )
			);
		} );

		/**
		 * add hexadecimal to validator method
		 */
		$.validator.addMethod("hexadecimal", function (value, element, param) {
			return ( value == "" || /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(value) );
		});

		/**
		 * add method to validate usernames
		 */
		$.validator.addMethod("username", function (value, element, param) {
			return ( value == "" || /^[a-zA-Z0-9 .@\_\-]+$/i.test(value) );
		});

		/**
		 * Added mock "mime_type" validator method because it is presents in wpt-data-validate
		 * on file-related fields. Just in case!
		 */
		$.validator.addMethod("mime_type", function (value, element, param) {
			return true;
		});

		/**
		 * add equalto method
		 */
		$.validator.addMethod("equalto", function (value, element, param) {
			if (param[1]) {
				return ( value == $("input[name='" + param[1] + "']").val() );
			}
			return false;
		});

		/**
		 * add skype to validator method
		 */
		$.validator.addMethod("skype", function (value, element, param) {
			return ( value == "" || /^([a-z0-9\:\.\_\,\-\#]+)$/i.test(value) );
		});

		/**
		 * add extension to validator method require
		 */
		$.validator.addMethod("required", function (value, element, param) {
			var _name = $(element).attr('name');
			var _value = $(element).val();

			// check if dependency is met
			// Last commits includes "required" parameter so this condition is not necessary.
			/*
			if (!this.depend(param, element)) {
				return "dependency-mismatch";
			}
			*/
			switch (element.nodeName.toLowerCase()) {
				case 'select':
					if ( element.multiple ) {
						return _value && _value.constructor === Array && _value.length > 0;
					}
					return ( _value && _.isString(_value) && _value.trim().length > 0 );
				case 'input':
					if (jQuery(element).hasClass("wpt-form-radio")) {
						var val = jQuery('input[name="' + _name + '"]:checked').val();

						if (wptValidationDebug) {
							console.log("radio " + (typeof val != 'undefined' && val && _.isString(val) && val.trim().length > 0));
						}

						return ( typeof val != 'undefined' && val && _.isString(val) && val.trim().length > 0 );
					}

					var fieldType = $( element ).prop( 'type' );

					if (
						'file' === fieldType
						&& _.contains( [ 'file', 'audio', 'video', 'image' ], $( element ).attr( 'data-wpt-type' ) )
						&& _.isString(value) && value.trim().length > 0
					) {
						// File inputs from Toolset forms with a selected value.
						// If value is empty, default to the empty companion checked below which holds current value.
						return true;
					}

                    var $element = jQuery(element).siblings('input[type="hidden"]').first();
                    var elementFieldType = $element.attr('data-wpt-type');
                    if ($element
                        && !$element.prop("disabled")
                        && ( elementFieldType === 'file'
							|| elementFieldType === 'video'
							|| elementFieldType === 'audio'
							|| elementFieldType === 'image' )
					) {
						var val = $element.val();
						if (wptValidationDebug) {
							console.log("hidden " + (val && _.isString(val) && val.trim().length > 0));
						}

						return ( val && _.isString(val) && val.trim().length > 0 );
					}

					if (jQuery(element).attr('type') == "checkbox") {
						if (wptValidationDebug) {
							console.log("checkbox " + (element.checked));
						}
						return element.checked;
					}

					if (jQuery(element).hasClass("hasDatepicker")) {
						if (wptValidationDebug) {
							console.log("hasDatepicker");
						}
						return false;
					}

					if (this.checkable(element)) {
						if (wptValidationDebug) {
							console.log("checkable " + (this.getLength(value, element) > 0));
						}
						return ( this.getLength(value, element) > 0 );
					}

					if (wptValidationDebug) {
						console.log(_name + " default: " + value + " val: " + _value + " " + (_.isString(_value) && _value.trim().length > 0));
					}

					return ( _.isString(_value) && _value.trim().length > 0 );
				default:
					return ( _.isString(value) && value.trim().length > 0 );
			}
		});

		/**
		 * Add validation method for datepicker adodb_xxx format for date fields
		 */
		$.validator.addMethod(
			"dateADODB_STAMP",
			function (a, b) {
				return this.optional(b) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(a) && -12219292800 < a && a < 32535215940
			},
			"Please enter a valid date"
		);

		if (wptValidationDebug) {
			console.log("INIT");
			console.log(wptValidationForms);
		}

		if ( $('body').hasClass('wp-admin') ) {
			// This still needs to run in Types to initialize validation in some specific scenarios (one that we know:
			// using classic editor on a post type without the actual post content enabled).
			//
			// We must not execute this in CRED as it would cause a regression of cred-2427.
			_.each(wptValidationForms, function (formID) {
				// Only apply to non CRED elements, CRED ones will be init on cred_form_ready
				if(formID.indexOf('#cred') === -1){
					_initValidation(formID);
					applyRules(formID);
				}
			});
		}
	}

	function _initValidation(formID) {
		if (wptValidationDebug) {
			console.log("_initValidation " + formID);
		}
		var $form = $(formID);
		$form.validate({
			// :hidden is kept because it's default value.
			// All accepted by jQuery.not() can be added.
			ignore: 'input[type="hidden"]:not(.js-wpt-date-auxiliar,.js-toolset-media-field-hidden),:not(.js-wpt-validate)',
			errorPlacement: function (error, element) {
				error.insertBefore(element);
			},
			highlight: function (element, errorClass, validClass) {

				// If a post is inside a repeatable field group, extract its ID and invoke the action
				// to highlight it. We cannot do it here, it needs to be handled properly by the
				// RFG item's model (also with respect to nested RFGs, etc.)
				var rfgPostId = $(element).parents('tbody[data-item-id]').data('item-id');
				if( !!rfgPostId ) {
					Toolset.hooks.doAction( 'toolset-validation-highlight-element', rfgPostId );
				}

				// Expand container
				$( element ).parents( '.collapsible' ).slideDown();

				if ( ['#post', '.metabox-location-normal', '.metabox-location-advanced'].indexOf(formID) !== -1 ) {
					var box = $(element).parents('.postbox');
					if (box.hasClass('closed')) {
						$('.handlediv', box).trigger('click');
					}
				}
				$(element).parent('div').addClass('has-error');
				// $.validator.defaults.highlight(element, errorClass, validClass); // Do not add class to element
			},
			unhighlight: function (element, errorClass, validClass) {
				$("input#publish, input#save-post").removeClass("button-primary-disabled").removeClass("button-disabled");
				$(element).parent('div').removeClass('has-error');
				// $.validator.defaults.unhighlight(element, errorClass, validClass);
			},
			invalidHandler: function (form, validator) {
				if (formID == '#post') {
					$('#publishing-action .spinner').css('visibility', 'hidden');
					$('#publish').on('click', function () {
						$('#publishing-action .spinner').css('visibility', 'visible');
					});
					$("input#publish").addClass("button-primary-disabled");
					$("input#save-post").addClass("button-disabled");
					$("#save-action .ajax-loading").css("visibility", "hidden");
					$("#publishing-action #ajax-loading").css("visibility", "hidden");
				}
			},
			errorElement: 'small',
			errorClass: 'wpt-form-error'
		});

		// On some pages the form may not be ready yet at this point (e.g. Edit Term page).
		jQuery(function () {
			if (wptValidationDebug) {
				console.log(formID);
			}

			jQuery(document).off('submit', formID, null);
			jQuery(document).on('submit', formID, function () {
				// Notes for a future and better self:
				// - The ELSE statement supposedly run when ( ! isValidForm ) never runs, because jQuery.validate
				//   will halt the form submission in that case, and this submit event callback never gets fired. Mmmmm OK.
				// - The only trustable part of this think is the toolset-form-onsubmit-validation-success event, and
				//   maybe the toolset-ajax-submit one, although its naming is a disaster.

				if (wptValidationDebug) {
					console.log("submit " + formID);
				}

				if ( undefined == $form.attr( 'data-form' ) ) {
					return;
				}

				var $formSettings = $form.data( 'form' );

				if ( ! _.has( $formSettings, 'use_ajax' ) ) {
					return;
				}

				if (wptValidationDebug) {
					console.log("validation...");
				}

				var isAjaxForm = ( $formSettings.use_ajax && 1 == $formSettings.use_ajax );
				var isValidForm = $form.valid();

				if (isValidForm) {

					if (wptValidationDebug) {
						console.log("form validated " + $form);
					}

					/**
					 * toolset-form-onsubmit-validate-success
					 *
					 * Event triggered when a cred form on submit is validated
					 *
					 * @since 2.5.1
					 */
					Toolset.hooks.doAction( 'toolset-form-onsubmit-validation-success', formID, isAjaxForm, $formSettings );

				} else {

					if (wptValidationDebug) {
						console.log("form not valid!");
					}

					/**
					 * toolset-form-onsubmit-validate-error
					 *
					 * Event triggered when a cred form on submit is NOT validated
					 *
					 * @since 2.5.1
					 */
					Toolset.hooks.doAction( 'toolset-form-onsubmit-validation-error', formID, isAjaxForm, $formSettings );
				}

				//If form is an ajax form return false on submit
				if (isAjaxForm) {

					/**
					 * toolset-ajax-submit
					 *
					 * Event submit of ajax form, it is triggered ONLY when onsubmit belogns to a form that is ajax and it is valid as well
					 *
					 * @since 2.5.1
					 */
					Toolset.hooks.doAction( 'toolset-ajax-submit', formID, isValidForm, $formSettings );
					return false;
				}
			});
		});
	}

	function isIgnored($el) {
		var ignore = $el.parents('.js-wpt-field').hasClass('js-wpt-validation-ignore') || // Individual fields
			$el.parents('.wpt-conditional-hidden').hasClass('js-wpt-validation-ignore'); // Types group of fields
		return ignore;
	}

	function applyRules(container) {
		$('[data-wpt-validate]', $(container)).each(function () {
			_applyRules($(this).data('wpt-validate'), this, container);
		});
	}

	function _applyRules(rules, selector, container) {
		var element = $(selector, $(container));

		if (element.length > 0) {
			if (isIgnored(element)) {
				element.rules('remove');
				element.removeClass('js-wpt-validate');
			} else if (!element.hasClass('js-wpt-validate')) {
				_.each(rules, function (value, rule) {
					var _rule = {messages: {}};
					_rule[rule] = value.args;
					if (value.message !== 'undefined') {
						_rule.messages[rule] = value.message;
					}
					element.rules('add', _rule);
					element.addClass('js-wpt-validate');
				});
			}
		}
	}

	return {
		init: init,
		applyRules: applyRules,
		isIgnored: isIgnored,
		_initValidation: _initValidation
	};

})(jQuery);

jQuery(document).on('toolset_ajax_fields_loaded', function (evt, data) {
	wptValidation._initValidation('#' + data.form_id);
	wptValidation.applyRules('#' + data.form_id);
});

jQuery(function () {
	// Note: We call wptValidation.init() to cover all possible edge cases (especially when combined with
	// conditional display of custom fields). Multiple calls do no harm because the init method does nothing
	// if called for the second time and more.
	wptCallbacks.reset.add(function () {
		wptValidation.init();
	});

	wptCallbacks.addRepetitive.add(function (container) {
		wptValidation.init();
		wptValidation.applyRules(container);
	});
	wptCallbacks.removeRepetitive.add(function (container) {
		wptValidation.init();
		wptValidation.applyRules(container);
	});
	wptCallbacks.conditionalCheck.add(function (container) {
		wptValidation.init();
		wptValidation.applyRules(container);
	});
});
