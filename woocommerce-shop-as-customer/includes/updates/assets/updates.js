(function($) {
	$( document ).ready( function() {

		/**
		 * If there is no purchase code registered, which we detect
		 * by checking if the 'Register for Updates' link is output
		 * to the page, then change the words 'update now' to Register
		 * here to get this update.
		 */
		
		var $el = $( '.column-description .cx-plugin-update-row-meta-register-for-updates-inline' );
		
		$el.each( function(){

			$plugin_row = $(this).parents('tr[data-slug]');

			$(this).remove();
			// $(this).find('.cx-plugin-update-row-meta-divider').remove();

			$plugin_row
				.next()
				.find( '.update-message em, .update-message .update-link' )
				.replaceWith( $(this) );
		});
		
		/**
		 * On the 'View version details' popup 'Install Update Now' button if
		 * the site is not yet registered to get updates to our plugin then
		 * help by changing the text to click to 'Please register for updates'
		 */
		var $current_button = $('#plugin-information-footer .button[data-slug]');
		var current_plugin = $current_button.attr('data-slug');
		if (
				$current_button.length &&
				typeof cx_plugin_update_object != 'undefined' &&
				current_plugin == cx_plugin_update_object.current_plugin &&
				! cx_plugin_update_object.current_plugin_is_licenced
			) {

			// Create the new button.
			$new_button = $( '<a target="_parent" class="button button-primary right" href="' + cx_plugin_update_object.plugin_register_url + '">' + cx_plugin_update_object.update_button_text + '</a>' );
			
			// Insert the new button after the existing one.
			$new_button.insertAfter( $current_button );

			//Remove the existing button.
			$current_button.remove();
		}

	});
})( jQuery );