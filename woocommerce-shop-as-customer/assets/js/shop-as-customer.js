jQuery( function($){
	$( document ).ready( function() {
		
		/**
		 * WooCommerce Order page.
		 */
		
		// Move the Order notice to under the h2 (same method as WC uses)
		$("#shop-as-customer-message").insertAfter('.wrap > h2');
		
		/**
		 * Main SAC modal functions.
		 */
		
		// 'Switch To Customer' buttons.
		jQuery(document).on( 'click', '.cxsac-shop-as-customer, .sac-settings-shop-as, [href*="page=shop_as_customer_button"], #wp-admin-bar-switch-to-customer-backend', function() {
			
			open_sac_modal();
			return false;
		});
		
		// Hash linking to the SAC modal, so user can link to modal and switch from anywhere.
		if ( '#shop-as-customer' == window.location.hash ) {
			setTimeout( function(){
				open_sac_modal();
			}, 300 );
		}
		
		// Function to open the main SAC modal.
		function open_sac_modal() {
			
			// Open the SAC modal.
			open_modal(
				'.cxsac-switch-form',
				{
					position            : 'center',
					close_button        : true,
					close_click_outside : false,
				}
			);
			
			// Add custom class to the Select2 dropdown.
			setTimeout(function(){
				
				$('#cxsac-select2-user-search').data('select2').$dropdown.addClass('cxsac-select2-dropdown');
			}, 350 );
			
			// Autofocus Customer Search (user option).
			if ( 'yes' == woocommerce_shop_as_customer_params.autofocus_search ) {
				setTimeout(function() {
					
					$('#cxsac-select2-user-search').select2('open');
				}, 350 );
			}
		}
		
		// Handle when user is chosen in Select2.
		jQuery('.cxsac-switch-form .wc-customer-search.enhanced').on( 'change', function(e){
			if ( $(this).val() != '' ) {
				
				// Compile the switch URL.
				link = woocommerce_shop_as_customer_params.shop_as_url + '&user_id=' + $(this).val();
		
				// Auto switch to the new user when selected.
				document.location = link;
			}
		});
		
		// jQuery('.wc-customer-search.enhanced').prop( 'allowClear', true );
		// setTimeout(function() {
		
		// 	jQuery('.wc-customer-search.enhanced').select2( 'allowClear', true );
		// }, 1000 );
		
		
		// POPUP TOP-RIGHT
		// open_modal( '#cxsac-save-share-cart-modal', { position: 'top-right', cover: false, close_button: false } );
		
		// POPUP TOP-CENTER
		// open_modal( '#woocommerce_product_categories-3, #woocommerce_widget_cart-2', { position: 'top-center', close_button: true, close_click_outside: false } );
		
		// open_modal( '#cxsac-save-share-cart-modal', { position: 'center', close_button: false } );
		
		
		/**
		 * RE-USABLE COMPONENTS.
		 */
			
		// Helper function to check if we are in responsive/mobile.
		function is_mobile() {
			return ( $( window ).width() < 610 );
		}
		
		/**
		 * Modal Popups.
		 */
		
		function init_modal( $close_button ) {

			// Add the required elements if they not in the page yet.
			if ( ! $('.cxsac-component-modal-popup').length ) {

				// Add the required elements to the dom.
				$('body').append( '<div class="cxsac-component-modal-temp component-modal-hard-hide"></div>' );
				$('body').append( '<div class="cxsac-component-modal-cover component-modal-hard-hide"></div>' );

				$popup_html = '';
				$popup_html += '<div class="cxsac-component-modal-wrap cxsac-component-modal-popup component-modal-hard-hide">';
				$popup_html += '	<div class="cxsac-component-modal-container">';
				$popup_html += '		<div class="cxsac-component-modal-content">';
				$popup_html += '			<span class="cxsac-component-modal-cross cxsac-top-bar-cross cxsac-icon-cancel"></span>';
				$popup_html += '		</div>';
				$popup_html += '	</div>';
				$popup_html += '</div>';
				$('body').append( $popup_html );
				
				// Handle `close_button`.
				$( document ).on( 'click', '.cxsac-component-modal-cross', function() {
					close_modal();
					return false;
				});

				// Handle `close_click_outside`.
				$('html').click(function(event) {
					if (
							0 === $('[class*="component-modal-popup"]').filter('[class*="component-modal-hard-hide"]').length &&
							0 !== $('[class*="-close-click-outside"]').length &&
							0 === $(event.target).parents('[class*="component-modal-content"]').length
						) {
						close_modal();
						return false;
					}
				});
			}
		}
		function open_modal( $content, $settings ) {
			
			// Set defaults
			$defaults = {
				position            : 'center',
				cover               : true,
				close_button        : true,
				close_click_outside : true,
			};
			$settings = $.extend( true, $defaults, $settings );

			// Init modal - incase this is first run.
			init_modal( $settings.close_button );

			// Move any elements that may already be in the modal out, to the temp holder, as well as the close cross.
			$('.cxsac-component-modal-temp').append( $('.cxsac-component-modal-content').children().not('.cxsac-component-modal-cross') );

			// Get content to load in modal.
			if ( 'string' == typeof $content ) {
				$content = $( $content );
			}

			// If content to load doesn't exist then rather close the whole modal and bail.
			if ( ! $content.length ) {
				close_modal();
				console.log( 'Content to load into modal does not exists.' );
				return;
			}

			// Enable whether to close when clicked outside the modal.
			if ( $settings.close_click_outside )
				$('.cxsac-component-modal-popup').addClass('cxsac-close-click-outside');
			else
				$('.cxsac-component-modal-popup').removeClass('cxsac-close-click-outside');

			// Show/Hide the close button.
			if ( $settings.close_button )
				$('.cxsac-component-modal-content').find('.cxsac-component-modal-cross').show();
			else
				$('.cxsac-component-modal-content').find('.cxsac-component-modal-cross').hide();

			// Add the intended content into the modal.
			$('.cxsac-component-modal-content').prepend( $content );
			
			// Make sure this modal has the highest z-index, and hide any others that are open (incase we open 2 of our modals at once).
			var $open_modals = $('[class*="component-modal-cover"], [class*="component-modal-wrap"]').not('[class*="component-modal-hard-hide"], [class*="cxsac-"]'); // Only check our 'open' modals.
			if ( $open_modals.length ) {
				
				$z_index = 0;
				$open_modals.each(function(){
					if ( $z_index < $(this).css('z-index') ) {
						// Loop the open modals and see which has the highest z-index.
						$z_index = $(this).css('z-index');
					}
				});
				
				// Set the current modal (modal & cover) to higher z-index than the existing one.
				$('.cxsac-component-modal-cover, .cxsac-component-modal-wrap').css( 'z-index', $z_index + 1 );
				
				// Hide the other open modal - only temporarily, by not adding the `hard-hide`.
				$open_modals.removeClass('component-modal-play-in').addClass('component-modal-play-out');
			}

			// Remove the class that's hiding the modal.
			$content.removeClass( 'component-modal-hard-hide' );

			// Apply positioning.
			// $('.cxsac-component-modal-popup')
			// 	.removeClass( 'cxsac-modal-position-center cxsac-modal-position-top-right cxsac-modal-position-top-center' )
			// 	.addClass( 'cxsac-modal-position-' + $settings.position );

			// Move to top of page if Mobile.
			// if ( is_mobile() ) {
			// 	$('.cxsac-component-modal-popup').css({ top: $(document).scrollTop() + 80 });
			// 	console.log( $(document).scrollTop() );
			// }

			// Control the overflow of long page content.
			$('html').css({ 'margin-right': '17px', 'overflow': 'hidden' });

			// Set a tiny defer timeout so that CSS fade-ins happen correctly.
			setTimeout(function() {

				// Move elements into the viewport by removing hard-hide, then fade in the elements.
				$('.cxsac-component-modal-popup').removeClass( 'component-modal-hard-hide' );
				$('.cxsac-component-modal-popup').addClass( 'component-modal-play-in' );
			}, 1 );

			// Optionally show the back cover. (not when in mobile)
			if ( $settings.cover ) {
				$('.cxsac-component-modal-cover').removeClass( 'component-modal-hard-hide' );
				$('.cxsac-component-modal-cover').addClass( 'component-modal-play-in' );
			}
			else {
				// If not showing then make sure to fade it out.
				$('.cxsac-component-modal-cover').removeClass( 'component-modal-play-in' );
				setTimeout(function() {
					$('.cxsac-component-modal-cover').addClass( 'component-modal-hard-hide' );
				}, 200 );
			}
		}
		function close_modal() {

			// Close the select 2 lip when clicking outside the modal to close.
			$('#cxsac-select2-user-search').select2('close');

			// Fade out the elements.
			$('.cxsac-component-modal-cover, .cxsac-component-modal-popup').removeClass( 'component-modal-play-in' );
			
			// Hide the other open modals that were soft hidden by the openning of this one.
			var $open_modals = $('[class*="component-modal-cover"], [class*="component-modal-wrap"]').not('[class*="component-modal-hard-hide"], [class*="cxsac-"]'); // Only check our 'open' modals.
			$open_modals.removeClass( 'component-modal-play-out' ).addClass( 'component-modal-play-in' );
			
			// Move elements out the viewport by adding hard-hide.
			setTimeout(function() {
				$('.cxsac-component-modal-cover, .cxsac-component-modal-popup').addClass( 'component-modal-hard-hide' );

				// Remove specific positioning.
				$('.cxsac-component-modal-popup')
					.removeClass( 'cxsac-modal-position-center cxsac-modal-position-top-right cxsac-modal-position-top-center' )
					.css({ top : '' });

				// Control the overflow of long page content - return it to normal.
				if ( ! $('[class*="component-modal-popup"]').filter('[class*="component-modal-play-in"]').length ) {
					$('html').css({ 'margin-right': '', 'overflow': '' });
				}

			}, 200 );
		}
		function resize_modal( $to_height ) {

			// Init modal - incase this is first run.
			init_modal();

			// Cache elements.
			$modal_popup = $('.cxsac-component-modal-popup');

			// Get the intended heights.
			var $to_height = ( $to_height ) ? $to_height : $modal_popup.outerHeight();
			var $margin_top = ( $to_height / 2 );

			// Temporarily enable margin-top transition, do the height-ing/margin-ing, then remove the transtion.
			$modal_popup.css({ height: $to_height, marginTop: -$margin_top, transitionDelay: '0s', transition: 'margin .3s' });
			setTimeout( function(){
				$modal_popup.css({ height: '', transitionDelay: '', transition: '' });
			}, 1000 );
		}

	});
});
