/**
 * @package Toolset.LockOverlay
 * @since 3.3.5
 * @author riccardo.s
 * Controller for Toolset Lock Overlay
 * it defaults to Gutenberg editor page, but through localised variables can be appended to any element in any place
 * and through Underscore.template can have any kind of content
 */

window.Toolset = window.Toolset || {};
/**
 *
 * @type {*|{}}
 */
Toolset.LockOverlay = Toolset.LockOverlay || {};

/**
 *
 * @type {*|{escape: RegExp, evaluate: RegExp, interpolate: RegExp}}
 * @private
 * Make sure we encapsulate our _.templateSettings to avoid any conflict:
 * {{}} escape
 * <#> evaluate
 * {{{}}} escape
 */
Toolset._templateSettings = Toolset._templateSettings || {
	escape: /\{\{([^\}]+?)\}\}(?!\})/g,
	evaluate: /<#([\s\S]+?)#>/g,
	interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
};

/**
 *
 * @param $
 * @constructor
 * Main class for Toolset.LockOverlay module
 */
Toolset.LockOverlay.Main = function ($) {
	// private variables
	var self = this,
		name = ToolsetLockOverlaySettings.name,
		containerSelector = ToolsetLockOverlaySettings.selector,
		$editorWrap = $(ToolsetLockOverlaySettings.selector),
		postId = ToolsetLockOverlaySettings.post_id,
		postType = ToolsetLockOverlaySettings.post_type,
		templateSelector = ToolsetLockOverlaySettings.template_selector,
		templateData = ToolsetLockOverlaySettings.template_object,
		messageTemplateSelector = ToolsetLockOverlaySettings.message_template_selector,
		messageWrapSelector = '<div class="js-toolset-gutenberg-message-wrap-' + name + ' toolset-gutenberg-message-in-post-editor toolset-alert" />',
		$messageHideWrap = $(messageWrapSelector),
		dummyContainerSelector = 'js-toolset-dummy-container-' + name,
		$dummyContainer = $('<div class="toolset-lock-overlay-dummy-container ' + dummyContainerSelector + '" id="' + dummyContainerSelector + '"/>'),
		overlaySelector = 'js-toolset-post-editor-overlay-' + name,
		$overlay = $('<div class="toolset-post-editor-overlay toolset-alert ' + overlaySelector + '" id="' + overlaySelector + '" />'),
		overlayNonTransparentSelector = 'js-toolset-overlay-non-transparent-' + name,
		$overlayNonTransparent = $('<div class="toolset-overlay-non-transparent toolset-alert ' + overlayNonTransparentSelector + '" id="' + overlayNonTransparentSelector + '"/>'),
		$hideEditor = $('.js-toolset-hide-editor'),
		$hideOverlay = $('.js-toolset-show-editor');
	/**
	 * @return void
	 */
	self.init = function () {
		if ($('#' + dummyContainerSelector).length > 0) return;

		_.defer(self.bindEvents);
	};
	/**
	 * @return void
	 */
	self.bindEvents = function () {
		// Re-set the wrapper as it might not be defined before due to editor not being properly rendered yet.
		$editorWrap = $(ToolsetLockOverlaySettings.selector);
		self.hideEditorOnReady();
		self.hideEditor();
		self.showEditor();
	};
	/**
	 *
	 * @returns string
	 */
	self.getContainerSelector = function () {
		return containerSelector;
	};
	/**
	 *
	 * @returns int
	 * + operator is bitwise operator for parseInt( string ), cast to integer
	 */
	self.getPostId = function () {
		return +postId;
	};
	/**
	 *
	 * @returns string
	 */
	self.getPostType = function () {
		return postType;
	};
	/**
	 *
	 * @returns string
	 */
	self.getName = function () {
		return name;
	};
	/**
	 *
	 * @param value
	 * @return void
	 */
	self.setSettings = function (value) {
		var store = {};
		store['toolset-lock-overlay-' + self.getName()] = value;
		jQuery.jStorage.set(self.getPostId(), store);
	};
	/**
	 *
	 * @returns {boolean}
	 */
	self.getSettings = function () {
		var settings = jQuery.jStorage.get(self.getPostId());
		return settings && settings['toolset-lock-overlay-' + self.getName()] === true ? true : false;
	};
	/**
	 * @return void
	 */
	self.setVisibilityOnReady = function () {

		if (self.getSettings() === false) {
			self.hideGutenbergBlockButtons();
			self.animateOverlay('show', 'slow');
		} else {
			$messageHideWrap.fadeIn(300, function () {
				$dummyContainer.hide();
			});
		}
	};
	/**
	 *
	 * @param action
	 * @param speed
	 * @param callback
	 * @param args
	 */
	self.animateOverlay = function (action, speed, callback, args) {

		var params = {
			'show': [1, 0.6, 1],
			'hide': [0, 0, 0],
			'slow': [500, 600, 800],
			'fast': [300, 300, 300],
			'faster': [100, 200, 300]
		};

		if (params[action][0] > 0) {
			$dummyContainer.show();
		}

		$dummyContainer.animate({
			opacity: params[action][0]
		}, params[speed][0], function () {

			$overlay.animate({
				opacity: params[action][1],
				specialEasing: {
					background: "easeOutBounce"
				}
			}, params[speed][1]);

			$overlayNonTransparent.animate({
				opacity: params[action][2],
				specialEasing: {
					background: "easeOutBounce"
				}
			}, params[speed][2], function () {

			});

			if (typeof callback !== 'undefined' && typeof callback == 'function') {
				callback.apply(self, args);
			}

			if (params[action][0] === 0) {
				$dummyContainer.hide();
			}

		});
	};
	/**
	 * @return void
	 */
	self.emptyOverlay = function () {
		$dummyContainer.empty().hide();
	};
	/**
	 * @return void
	 */
	self.removeOverlay = function () {
		$dummyContainer.remove();
	};
	/**
	 * @return void
	 */
	self.setOverlay = function () {
		$overlay = $('<div class="toolset-post-editor-overlay toolset-alert ' + overlaySelector + '" id="' + overlaySelector + '" />');
		$overlayNonTransparent = $('<div class="toolset-overlay-non-transparent toolset-alert ' + overlayNonTransparentSelector + '" id="' + overlayNonTransparentSelector + '"/>');
		$dummyContainer = $('<div class="toolset-dummy-container ' + dummyContainerSelector + '" id="' + dummyContainerSelector + '"/>');
	};
	/**
	 * @return void
	 */
	self.showEditor = function () {
		$(document).on('click', '.js-toolset-show-editor', function () {
			self.animateOverlay('hide', 'fast', function () {
				$messageHideWrap.show(300);
				self.showGutenbergBlockButtons();
				self.setSettings(true);
			});
		});

	};
	/**
	 * @return void
	 */
	self.hideEditor = function () {
		$(document).on('click', '.js-toolset-hide-editor', function () {
			$messageHideWrap.fadeOut(300, function () {
				self.animateOverlay('show', 'faster');
				self.setSettings(false);
			});
			self.hideGutenbergBlockButtons();
		});
	};
	/**
	 *
	 * @param ready
	 */
	self.hideEditorOnReady = function (ready) {

		Toolset.hooks.doAction('toolset-lock-overlay-before-append');

		var template = $("#" + templateSelector).html(),
			messageTemplate = $('#' + messageTemplateSelector).html();

		templateData = Toolset.hooks.applyFilters('toolset-lock-overlay-template-data', templateData);

		$overlayNonTransparent.html(WPV_Toolset.Utils._template(template, templateData, Toolset._templateSettings));
		$messageHideWrap.html(WPV_Toolset.Utils._template(messageTemplate, templateData, Toolset._templateSettings));

		$overlay.addClass("toolset-lock-overlay-for-post-type-" + self.getPostType());
		$overlayNonTransparent.addClass("toolset-lock-overlay-non-transparent-for-post-type-" + self.getPostType());
		$editorWrap.css("position", "relative");
		$dummyContainer.append($overlay, $overlayNonTransparent);
		$editorWrap.append($dummyContainer, $messageHideWrap);

		self.setOverlayHeigtht();

		if (ready === true) {
			Toolset.hooks.doAction('toolset-lock-overlay-ready-to-append');
		} else {
			self.setVisibilityOnReady();
		}

		Toolset.hooks.doAction('toolset-lock-overlay-after-append');
	};
	/**
	 * @return void
	 */
	self.setOverlayHeigtht = function () {
        var check_height = $editorWrap[0];

        if( check_height.offsetHeight < $('body')[0].offsetHeight ) {
            // Adjustment for when the editor has a height higher than default (empty), without overflowing the viewport.
            $dummyContainer.height("99%");
        } else {
            // Adjustment for when the editor overflows the viewport.
            $dummyContainer.height("100%");
        }
	};
	/**
	 * @return void
	 */
	self.hideGutenbergBlockButtons = function () {
		$('div.editor-inserter').hide();
		$('div.editor-default-block-appender').hide();
	};
	/**
	 * @return void
	 */
	self.showGutenbergBlockButtons = function () {
		$('div.editor-inserter').show();
		$('div.editor-default-block-appender').show();
	};

	_.bindAll(self, 'bindEvents', 'hideGutenbergBlockButtons');

};

(function ($) {
	$(window).on('load', function () {
		Toolset.LockOverlay.main = {};
		Toolset.LockOverlay.Main.call(Toolset.LockOverlay.main, $);
		Toolset.LockOverlay.main.init();
	});
}(jQuery));
