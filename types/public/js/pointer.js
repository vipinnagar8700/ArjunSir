var ToolsetTypes = ToolsetTypes || {};
ToolsetTypes.Utils = ToolsetTypes.Utils || {};

/**
 * This object can be used for showing a standard Toolset pointer with proper Types classes.
 *
 * @type {{show, hideAll}}
 * @since 3.0
 */
ToolsetTypes.Utils.Pointer = function () {

    var hideWPPointers = function() {
        jQuery('.wp-toolset-pointer').hide();
    };

    var showPointer = function(el) {
        var $this = jQuery(el);

        // default options
        var defaults = {
            edge: "left", // on which edge of the element tooltips should be shown: ( right, top, left, bottom )
            align: "middle", // how the pointer should be aligned on this edge, relative to the target (top, bottom, left, right, middle).
            offset: "15 0 " // pointer offset - relative to the edge
        };

        // custom options passed in HTML "data-" attributes
        var custom = {
            edge: $this.data('edge'),
            align: $this.data('align'),
            offset: $this.data('offset')
        };

        hideWPPointers();
        var content = '<p>' + $this.data('content') + '</p>';
        if ($this.data('header')) {
            content = '<h3>' + $this.data('header') + '</h3>' + content;
        }

        var extraClass = $this.hasClass('types-pointer-tooltip') ? ' types-pointer-tooltip' : '';

        $this.pointer({
            pointerClass: 'wp-toolset-pointer wp-toolset-types-pointer ' + extraClass,
            content: content,
            position: jQuery.extend(defaults, custom) // merge defaults and custom attributes
        }).pointer('open');
    };

    return {
        show: showPointer,
        hideAll: hideWPPointers
    }
}();