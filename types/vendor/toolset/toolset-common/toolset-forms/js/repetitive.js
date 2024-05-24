/*
 * Repetitive JS.
 *
 *
 */
var wptRep = (function ($) {
    var count = {};

    function getParentNode( $this ) {
		if ($this.hasClass('js-wpt-field-items')) {// This happens on the frontent
			return $this;
		}

		// This happens on the backend
		return $this.find('.js-wpt-field-items');
	}


	function getFieldRoot( $this ) {
		return $this.closest( '.js-wpt-field' );
	}

    function isFieldReadOnly( $this ) {
		var $parent = getFieldRoot( $this );
		return $parent.length && $parent.hasClass('js-wpt-field-is-copied');
	}

    function initRepetitiveFields() {
        // Reorder label and description for repeatable fields.
        // Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
		//
		// Adjusted to also always do the same reordering for single file-type fields (see WPToolset_Types::filterField).
		//
		// Update: Include most fields into this (js-wpt-field-extract-label), so that the label position is consistent.
		//
		// @refactoring KILL THIS WITH FIRE
        $('.js-wpt-repetitive, .js-wpt-field-with-files, .js-wpt-field-extract-label').each(function () {
            var $this = $(this);
            if ($('body').hasClass('wp-admin')) {
                var title = $('label:not(.js-wpt-auxiliar-label)', $this).first().clone();
                var description = $('.description:not(.js-wpt-auxiliar-description)', $this).first().clone();
                $('.js-wpt-field-item', $this).each(function () {
                    $('label:not(.js-wpt-auxiliar-label)', $this).remove();
                    $('.description:not(.js-wpt-auxiliar-description)', $this).remove();
                });
                $this.prepend(description).prepend(title);
            }

            _toggleCtl(getParentNode( $this ));
        });

    }
    function init() {
        _.defer(function() {
            initRepetitiveFields();
        });

        // Add field
        $(document).off('click', '.js-wpt-repadd', null);
        $(document).on('click', '.js-wpt-repadd', function( e, $insertAfterThis ) {
            e.preventDefault();

            var $this = $(this);
			var tpl;
            var $parent = $this.closest('.js-wpt-field-items');

            if (1 > $parent.length) {
                return;
            }

            if ( isFieldReadOnly( $this ) ) {
            	return;
			}

            if ($('body').hasClass('wp-admin')) {
                // Get template from the footer templates by wpt-id data attribute
                tpl = $('<div>' + $('#tpl-wpt-field-' + $this.data('wpt-id')).html() + '</div>');
                // Remove label and descriptions from the template
                // Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
                $('label:not(.js-wpt-auxiliar-label)', tpl).first().remove();
                $('.description:not(.js-wpt-auxiliar-description)', tpl).first().remove();
                // Adjust ids and labels where needed for the template content
                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('wpt-form-el');
                    tpl.find('label[for="' + $this.attr('id') + '"]').attr('for', uniqueId);
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('wpt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }
            } else {
                /**
                 * template
                 */
                tpl = $('<div>' + $('#tpl-wpt-field-' + $this.data('wpt-id')).html() + '</div>');

                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('wpt-form-el');
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('wpt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }


            }
			// Insert the template before the button, unless there is a node to insert this after
			var tplToInsert = tpl.html().replace(/\[%%(\d+)%%\]/g, '[' + _count + ']')
			if ( typeof $insertAfterThis !== 'undefined' ) {
				$insertAfterThis.after( tplToInsert );
			} else {
				$this.before( tplToInsert );
			}
            wptCallbacks.addRepetitive.fire($parent);
            _toggleCtl($parent);
            $this.trigger('blur');// To prevent it from staying on the active state

            /*
             * Fires after the creation of a new repetitive field holding the parent div for the field
             * @since 2.5.0
             */
            jQuery(document).trigger('toolset_repetitive_field_added', $parent);

            return false;
        });
        // Delete field
        $(document).off('click', '.js-wpt-repdelete', null);
        $(document).on('click', '.js-wpt-repdelete', function (e) {
            e.preventDefault();

            var $this = $(this);

			if ( isFieldReadOnly( $this ) ) {
				return;
			}

			var $parent = $this.closest('.js-wpt-field-items');
            if ($('body').hasClass('wp-admin')) {
                var value;
				var isNotLastItemToDelete = ( $( '.js-wpt-field-item', $parent ).length > 1 );
				if (isNotLastItemToDelete) {
					// Allow deleting if there's more than one field item
					$this.parents( '.js-wpt-field-item' ).remove();
				} else {
					// If it's the last item, just clear it.
					$this.parent().parent().find( 'input, textarea' ).val( null );
					$this.parent().find( '.js-wpt-file-preview' ).html( null );
				}

				var formID = $this.parents( 'form' ).attr( 'id' );
				wptCallbacks.removeRepetitive.fire( formID );

				// if image, try delete images
				// TODO check this, I do not like using parent() for this kind of things
				if ('image' === $this.data( 'wpt-type' )) {
					value = $this.parent().parent().find( 'input' ).val();
					$parent.parent().append(
						'<input type="hidden" name="wpcf[delete-image][]" value="' + value + '"/>',
					);
				}
            } else {
                if ($('.wpt-repctl', $parent).length > 1) {
                    $(this).closest('.wpt-repctl').remove();
                    wptCallbacks.removeRepetitive.fire(formID);
                }
            }
            _toggleCtl($parent);
            return false;
        });

        // When the user clicks on an empty file preview, find the "Select file" button and do the same as
		// if they clicked on that instead.
		$(document).on('click', '.js-wpt-file-preview:not(:has(img))', function() {
			var $this = $(this);

			if ( isFieldReadOnly( $this ) ) {
				return;
			}

			$this.parent().find('button.js-wpt-file-upload').click();
		});
    }
    function _toggleCtl($sortable) {
        var sorting_count;
        var isInAdmin = false;
        if ($('body').hasClass('wp-admin')) {
            sorting_count = $('.js-wpt-field-item', $sortable).length;
            isInAdmin = true;
        } else {
            sorting_count = $('.wpt-repctl', $sortable).length;
        }
        if (sorting_count > 1 && ! isFieldReadOnly( $sortable )) {
			if( ! isInAdmin ) {
				$('.js-wpt-repdelete', $sortable).prop('disabled', false).show();
			}
            $('.js-wpt-repdrag', $sortable).css({opacity: 1, cursor: 'move'}).show();
            if (!$sortable.hasClass('ui-sortable')) {
            	var parameters = {
					stop: function (event, ui) {
						$sortable.find('.js-wpt-repadd').detach().appendTo($sortable);
					}
				};

            	// Items in fields with files need to be draggable in both axes, while other repeatable
				// fields only need the vertical one. We also need them to be draggable only by the specific
				// handle and not by the file preview as well.
            	if(0 === $sortable.parents('.js-wpt-field-with-files').length) {
            		parameters.axis = 'y';
            		parameters.handle = '.js-wpt-repdrag';
				}

                $sortable.sortable(parameters);
            }
        } else {
			if (!isInAdmin) {
				$( '.js-wpt-repdelete', $sortable ).prop( 'disabled', true ).hide();
			}
			$( '.js-wpt-repdrag', $sortable ).css( { opacity: 0.5, cursor: 'default' } ).hide();
			if ($sortable.hasClass( 'ui-sortable' )) {
				$sortable.sortable( 'destroy' );
			}
        }
    }
    function _countIt(_count, id) {
        if (typeof count[id] == 'undefined') {
            count[id] = _count;
            return _count;
        }
        return ++count[id];
    }
    return {
        init: init
    };
})(jQuery);

jQuery(wptRep.init);
