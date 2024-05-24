var wptColorpicker = ( function( $ ) {
    function init( parent ) {
        $( parent ).find('input.js-wpt-colorpicker').each( function() {
			var $currentInstance = $( this );
			if ( '' !== $currentInstance.val() ) {
				$currentInstance.css( { "box-shadow": 'inset -25px 0 0 ' + $currentInstance.val() } );
			}
			$currentInstance
				.addClass( 'js-wpt-colorpicker-inited' )
				.iris({
					change: function(event, ui) {
						if ( 'function' == typeof ( $(event.target).data('_bindChange') ) ) {
							$(event.target).data('_bindChange')();
						}
						$currentInstance.css( { "box-shadow": 'inset -25px 0 0 ' + ui.color.toString() } );
					}
				});
		})

    }
    return {
        init: init
    };
})(jQuery);

jQuery(function() {
    wptColorpicker.init('body');
	jQuery( document ).on( 'click', function( e ) {
		if (
			! jQuery( e.target ).is( "input.js-wpt-colorpicker, .iris-picker, .iris-picker-inner" )
			&& jQuery( 'input.js-wpt-colorpicker.js-wpt-colorpicker-inited' ).length > 0
		) {
			jQuery( 'input.js-wpt-colorpicker.js-wpt-colorpicker-inited' ).iris( 'hide' );
		}
	});
	jQuery( document ).on( 'click', 'input.js-wpt-colorpicker.js-wpt-colorpicker-inited', function( event ) {
		jQuery( this ).iris('hide');
		jQuery( this ).iris('show');
		return false;
	});
});
wptCallbacks.reset.add(function(parent) {
    wptColorpicker.init(parent);
});
/**
 * add for new repetitive field
 */
wptCallbacks.addRepetitive.add(wptColorpicker.init);
