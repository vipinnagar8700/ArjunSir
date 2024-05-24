jQuery( function($){
	$( document ).ready( function() {
		
		/**
		 * Checkout Page.
		 */
		
		// Clone payment method on checkout form.
		var payment_methods_dom = $('.payment_methods.methods').clone();
		$('body').bind('checkout_error', function () {

			if ( ! $('.payment_methods.methods.shop_as_customer_methods').length ) {
				payment_methods_dom.appendTo('.payment_methods_wrap').unwrap();
			}
		});
		
		var button_clicked;
		$('form.checkout').on( 'click', '[id^=cxsac_checkout_action_]', function() {
			button_clicked = $(this).attr('id');
		});

		$('form.checkout').bind('checkout_place_order', function() {
						
			// Remove any previously recorded clicked button.
			$('form.checkout').find('[name^=cxsac_checkout_action_type]').remove();
			$('form.checkout').prepend('<input type="hidden" id="cxsac_checkout_action_type" name="cxsac_checkout_action_type" value="' + button_clicked + '" />');
			// alert( $('form.checkout').find('[name^=cxsac_checkout_action_type]').attr('name') );
			
			if ( button_clicked == 'cxsac_checkout_action_pay_later' ) {
				
				if ( $('.payment_methods.methods').length ) {
					payment_methods_dom = $('.payment_methods.methods').clone();
					$('.payment_methods.methods').addClass('shop_as_customer_methods');
					$('.payment_methods.methods.shop_as_customer_methods').wrap('<ul class="payment_methods_wrap payment_methods methods"></ul>');
					$('.payment_methods.methods.shop_as_customer_methods').remove();
				}
			}
			else if ( button_clicked == 'cxsac_checkout_action_pay_now' ) {
				
			}
			else if ( button_clicked == 'cxsac_checkout_action_pay_free' ) {
				
			}
			
			return true;
		});


		//Load TipTip Tootips
		function loadTipTip(){

			// return false;

			$(".cxsac-checkout-page-tooltip").cxsacTipTip({
				delay: 10,
				fadeIn: 70,
				fadeOut: 70,
				maxWidth: "350px",
				content: $(".cxsac-checkout-page-tooltip-html").html()
			});
			
			$(".cxsac-success-page-tooltip").cxsacTipTip({
				delay: 10,
				fadeIn: 70,
				fadeOut: 70,
				maxWidth: "350px",
				content: $(".cxsac-success-page-tooltip-html").html()
			});
		}

		// Reload TipTip each time the checkout form is updated
		$('body').bind('updated_checkout', function(){
			loadTipTip();
		})

		// Regular load of TipTip
		loadTipTip();
	});
});