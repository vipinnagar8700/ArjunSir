<?php

add_action( 'plugins_loaded', 'cxsac_init_checkout_section', '20' );
function cxsac_init_checkout_section() {
	global $wc_shop_as_customer;
	
	if (
			isset( $wc_shop_as_customer ) &&
			method_exists( $wc_shop_as_customer, 'is_switched' ) &&
			$wc_shop_as_customer->is_switched()
		) {
		
		/**
		 * In switched state.
		 */
		
		// Force showing of the 'admin_bar'
		add_filter( 'show_admin_bar', '__return_true' );
		
		add_action( 'wp_ajax_woocommerce_checkout', 'cxsac_checkout', 1 );
		add_action( 'wp_loaded', 'cxsac_checkout_action', 30 );
		add_filter( 'woocommerce_order_button_html', 'cxsac_render_checkout_page_interface' );
		add_action( 'woocommerce_checkout_order_processed', 'cxsac_redirect_customer_on_order_processed' );
		add_action( 'wp', 'cxsac_add_checkout_success_message' );
		add_action( 'wp', 'cxsac_checkout_handle_emails' );
		
		// Block all emails - when `cxsac_checkout_action_send_emails` is not chosen
		add_action( 'woocommerce_pre_payment_complete', 'cxsac_checkout_block_emails' );
		$order_statuses = wc_get_order_statuses();
		foreach( $order_statuses as $order_status_key => $order_status_key ) {
			$order_status_key = ( 0 === strpos( $order_status_key, 'wc-' ) ) ? substr( $order_status_key, 3) : $order_status_key ;
			add_action( "woocommerce_order_status_{$order_status_key}", 'cxsac_checkout_block_emails', 0 );
		}
	}
}

/**
 * Process ajax checkout form.
 */
function cxsac_checkout() {
	
	if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		define( 'WOOCOMMERCE_CHECKOUT', true );

	if ( ! isset( $_POST['payment_method'] ) ) {
		add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
	}

	$woocommerce_checkout = WC()->checkout();
	$woocommerce_checkout->process_checkout();

	die(0);
}

/**
 * Render Checkout Page Interface.
 */
function cxsac_render_checkout_page_interface( $link) {

	ob_start();

	$user_id      = get_current_user_id();
	$current_user = wp_get_current_user();

	$avatar = get_avatar( $user_id, 26 );
	$shopping_as  = sprintf( __('Shopping as %1$s', 'shop-as-customer'), $current_user->display_name );
	$class  = empty( $avatar ) ? '' : ' with-avatar';
	?>
	<div class="cxsac-frontend cxsac-frontend-checkout">

		<div class="cxsac-shopping-as <?php echo $class; ?>">
			<?php echo $shopping_as . " &nbsp;" . $avatar; ?>
		</div>
		
		<div class="cxsac-frontend-action-block">
		
			<div class="cxsac-button-block create-this-order-block">
				<?php if ( 0 !== WC()->cart->total ) { ?>
					<input type="submit" class="cxsac-button" id="cxsac_checkout_action_pay_now" value="<?php _e( "Checkout - Pay Now", "shop-as-customer" ); ?>" />
					<input type="submit" class="cxsac-button" id="cxsac_checkout_action_pay_later" value="<?php _e( "Checkout - Pay Later", "shop-as-customer" ); ?>" />
				<?php } else { ?>
					<input type="submit" class="cxsac-button" id="cxsac_checkout_action_pay_free" value="<?php _e( "Checkout - FREE", "shop-as-customer" ); ?>" />
				<?php } ?>
			</div>
			
			<label class="cxsac-email-block">
				<input type="checkbox" name="cxsac_checkout_action_send_emails" id="shop_as_customer_send_emails" value="yes" checked="checked" /> <?php _e( "Send Email to Customer", "shop-as-customer" ); ?>
			</label>
		
		</div>
		
		<div class="cxsac-info-block">
			<span class="cxsac-info cxsac-checkout-page-tooltip">
				<span class="cxsac-info-icon"></span> <?php _e( 'What do these buttons do?', 'shop-as-customer' ); ?>
			</span>
		</div>
		
        <div class="cxsac-tooltip cxsac-checkout-page-tooltip-html">
        	
           	<?php if ( 0 !== WC()->cart->total ) { ?>
                <span class="cxsac-tip-heading"><?php _e( '"Checkout - Pay Later" button', 'shop-as-customer' ); ?></span>
                <ul>
                    <li><?php _e( 'The customers order will be created with the status Pending Payment', 'shop-as-customer' ); ?></li>
                    <li><?php _e( 'The invoice email will be sent to the customer with a link for them to pay for their order (provided "Send Email to Customer" is checked).', 'shop-as-customer' ); ?></li>
                    <li><?php _e( "The customer will link back to the Checkout page with available payment options presented to them where they can choose one and pay", 'shop-as-customer' ); ?></li>
                    <li><?php _e( "The order will remain as Pending until they successfully Pay where after it will change to Processing", 'shop-as-customer' ); ?></li>
                </ul>
                <span class="cxsac-tip-heading"><?php _e( '"Checkout - Pay Now" button', 'shop-as-customer' ); ?></span>
                <ul>
                    <li><?php _e( "You will proceed, as the customer normally would, with the selected Payment Method.", 'shop-as-customer' ); ?></li>
                    <li><?php _e( "You will be expected to pay 'on behalf' of the customer.", 'shop-as-customer' ); ?></li>
                    <li><?php _e( 'The customers order will be created with the status Processing', 'shop-as-customer' ); ?></li>
                    <li><?php _e( 'The Processing Order email will be sent to the customer (provided "Send Email to Customer" is checked).', 'shop-as-customer' ); ?></li>
                </ul>
            <?php } else { ?>
                <span class="cxsac-tip-heading"><?php _e( '"Checkout - FREE" button', 'shop-as-customer' ); ?></span>
                <ul>
                    <li><?php _e( 'The customers order will be created with the status Processing', 'shop-as-customer' ); ?></li>
                    <li><?php _e( 'The Processing Order email will be sent to the customer (provided "Send Email to Customer" is checked).', 'shop-as-customer' ); ?></li>
                </ul>
            <?php } ?>
            <span class="cxsac-tip-heading"><?php _e( '"Send Email to Customer" checkbox', 'shop-as-customer' ); ?></span>
            <ul>
                <li><?php _e( 'Use this checkbox, as mentioned above, to control whether the automatic transactional emails are sent to the customer when the order is created.', 'shop-as-customer' ); ?></li>
                <li><?php _e( 'The New Order email will be sent as normal.', 'shop-as-customer' ); ?></li>
            </ul>
            
        </div>
        
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Process the checkout form.
 */
function cxsac_checkout_action() {
	
	if ( isset( $_POST['cxsac_checkout_action_type'] ) && '' !== $_POST['cxsac_checkout_action_type'] ) {
		
		if ( 'cxsac_checkout_action_pay_now' == $_POST['cxsac_checkout_action_type'] ) {
			
			/**
			 * Clicked 'Checkout - Pay Now'.
			 */
			
		}
		else if ( 'cxsac_checkout_action_pay_later' == $_POST['cxsac_checkout_action_type'] ) {
			
			/**
			 * Clicked 'Checkout - Pay Later'.
			 */
			
			// Trick the order into behaving as if it doesn't need payment, so it doesn't go to payment-gateway.
			add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
			
			// Bail if the cart is empty.
			if ( sizeof( WC()->cart->get_cart() ) == 0 ) {
				wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
				exit;
			}

			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

			WC()->checkout()->process_checkout();
		}
		else if ( 'cxsac_checkout_action_pay_free' == $_POST['cxsac_checkout_action_type'] ) {
			
			/**
			 * Clicked 'Checkout - FREE'.
			 */
			
		}
	}
}

/**
 * Cut into the Checkout process to check what button was clicked.
 *
 * This happens before the user is sent to the payment gateway, and at this point
 * the order has been created - and is waiting to find out if the payment gateway
 * changes it's status to paid (processing).
 */
function cxsac_redirect_customer_on_order_processed( $order_id, $posted = null ) {
	
	// Debugging
	// if ( isset( $_POST['cxsac_checkout_action_type'] ) ) echo s( $_POST['cxsac_checkout_action_type'] );
	// exit();
	
	$order = new WC_Order( $order_id );
	
	/**
	 * Add a note & post-meta to the order so user knows was created by SAC.
	 */
	if ( $original_user = WC_Shop_As_Customer::get_original_user() ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			$order->add_order_note( sprintf( __( 'Order created by %1$s using Shop as Customer', 'shop-as-customer' ), $original_user->display_name ), 0);
			update_post_meta( $order_id, 'create_by', 'shop_as_customer' );
		}
	}
	
	/**
	 * Checked 'Send Emails'.
	 */
	if ( isset( $_POST['cxsac_checkout_action_send_emails'] ) ) {
		update_post_meta( $order_id, 'cxsac_checkout_action_send_emails', 'yes' );
	}
	
	if ( isset( $_POST['cxsac_checkout_action_type'] ) && '' !== $_POST['cxsac_checkout_action_type'] ) {
		
		// Store what kind of action was clicked on - this will be used as we progress through the checkout.
		update_post_meta( $order_id, 'cxsac_checkout_action_type', $_POST['cxsac_checkout_action_type'] );
		
		if ( 'cxsac_checkout_action_pay_now' == $_POST['cxsac_checkout_action_type'] ) {
			
			/**
			 * Clicked 'Checkout - Pay Now'.
			 */
			
		}
		else if ( 'cxsac_checkout_action_pay_later' == $_POST['cxsac_checkout_action_type'] /*|| ! isset( $_POST['payment_method'] )*/ ) {
			
			/**
			 * Clicked 'Checkout - Pay Later'.
			 */
			
			// Reduce the stock count.
			// a shop_admin has created on order on behalf of a customer so the stock,
			// from this point on, needs to be held for the customer. If a shop_admin
			// retires this order they will manually have to go and increment the stock
			// numbers again - it's not re-incremented automatically.
			$order->reduce_order_stock();
			
			// Zero total orders should immediately be set to 'Processing' as no more action is required on the order, and the Admin should be notified that there's a new order.
			if ( 0 == $order->get_total() ) {
				
				// Change the order to Processing.
				// $order->update_status( 'processing' );
				$order->payment_complete();
			}
			
			$success_page = esc_url_raw( add_query_arg(
				array(
					'key' => cxsac_order_get_order_key( $order ),
				),
				wc_get_endpoint_url(
					'order-received',
					$order_id,
					get_permalink( wc_get_page_id( 'checkout' ) )
				)
			) );

			if ( is_ajax() ) {
				wp_send_json( array(
					'result' => 'success',
					'redirect' => $success_page
				) );
			}
			else {
				wp_redirect( $success_page );
				exit;
			}
		}
		if ( 'cxsac_checkout_action_pay_free' == $_POST['cxsac_checkout_action_type'] ) {
			
			/**
			 * Clicked 'Checkout - FREE'.
			 */
		}
	}
}

function cxsac_checkout_block_emails( $order_id ){
	
	if ( ! get_post_meta( $order_id, 'cxsac_checkout_action_send_emails', TRUE ) ) {
		
		/**
		 * Block emails via the 'Send emails' checkbox option.
		 */
		
		$mails = WC()->mailer()->get_emails();
		if ( ! empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				if ( 0 === strpos( $mail->id, 'customer' ) ) {
					add_filter( "woocommerce_email_enabled_{$mail->id}", '__return_false' );
				}
			}
		}
	}
}

function cxsac_checkout_handle_emails(){
	
	if ( isset( $_GET['cxsac-send-invoice'] ) ) {
		
		// Bail if we can't get an order ID.
		if ( ! get_query_var( 'order-received' ) ) return false;
		
		// Get the order ID, and init an Order.
		$order_id = absint( get_query_var( 'order-received' ) );
		$order = new WC_Order( $order_id );
		
		/**
		 * Send invoice email via 'Send Invoice' or 'Send Request-to-Pay' buttons.
		 */
		
		// Send the email.
		if ( isset( $order ) ) {
			$mailer = WC()->mailer();
			$mails = $mailer->get_emails();
			if ( ! empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( 'customer_invoice' == $mail->id ) {
						$mail->trigger( cxsac_order_get_id( $order ) );
					}
				}
			}
		}
		
		// Add email sent notice.
		wc_add_notice( __('Invoice sent to Customer successfully.', 'shop-as-customer') );
		
		// Redirect after email send.
		$url = remove_query_arg( 'cxsac-send-invoice' );
		wp_redirect( $url );
		exit();
	}
}

/**
 * Hook that calls the next interface.
 */
function cxsac_add_checkout_success_message() {
	
	// Bail if we can;t get an order ID.
	if ( ! get_query_var( 'order-received' ) ) return false;
	
	// Get the order ID, and init an Order.
	$order_id = absint( get_query_var( 'order-received' ) );
	$order = new WC_Order( $order_id );
	
	if ( get_post_meta( $order_id, 'cxsac_checkout_action_type', TRUE ) ) {
		add_action( 'woocommerce_thankyou_' . cxsac_order_get_payment_method( $order ), 'cxsac_render_success_page_interface' );
	}
}

/**
 * Render Success Page Interface.
 */
function cxsac_render_success_page_interface( $order_id) {
	
	$order = new WC_Order( $order_id );
	
	// Record whether the user chose to send-emails, before we remove it on next line.
	$send_emails = (bool) ( 'yes' == get_post_meta( $order_id, 'cxsac_checkout_action_send_emails', TRUE ) );
	
	// We've made it here so we can stop suppressing the email sending.
	delete_post_meta( $order_id, 'cxsac_checkout_action_send_emails' );
	
	// Prep send-email URL.
	$send_invoice_url = esc_url_raw( add_query_arg(
		array(
			'key' => cxsac_order_get_order_key( $order ),
			'cxsac-send-invoice' => 1,
		),
		wc_get_endpoint_url(
			'order-received',
			$order_id,
			get_permalink( wc_get_page_id( 'checkout' ) )
		)
	) );
	
	// Prep switch to admin order URL.
	$admin_order_url = esc_url_raw( add_query_arg(
		array(
			'redirect_to_order' => $order_id,
		),
		WC_Shop_As_Customer::switch_back_url()
	) );

	$user_id      = get_current_user_id();
	$current_user = wp_get_current_user();

	$avatar = get_avatar( $user_id, 26 );
	$shopping_as  = sprintf( __('Shopping as %1$s', 'shop-as-customer'), $current_user->display_name );
	$class  = empty( $avatar ) ? '' : ' with-avatar';
	?>
	<div class="cxsac-frontend cxsac-frontend-complete">

		<div class="cxsac-shopping-as <?php echo $class; ?>">
			<?php echo $shopping_as . " &nbsp;" . $avatar; ?>
		</div>
		
		<div class="cxsac-frontend-action-block">
			<div class="cxsac-button-block send-out-invoice-block">
				<?php
				if ( $order->needs_payment() ) {
					?>
					<a class="cxsac-button cxsac-button-send-invoice" href="<?php echo $send_invoice_url; ?>">
						<?php echo __( 'Send Request-to-Pay to Customer', 'shop-as-customer' ); ?>
					</a>
					<?php
				}
				else {
					?>
					<a class="cxsac-button cxsac-button-send-invoice" href="<?php echo $send_invoice_url; ?>">
						<?php echo __( 'Send the Invoice Email to Customer', 'shop-as-customer' ); ?>
					</a>
					<?php
				}
				?>
				<a class="cxsac-button cxsac-button-switch-back" href="<?php echo $admin_order_url; ?>">
					<?php _e( 'Switch back and View Order', 'shop-as-customer' ); ?>
				</a>
			</div>
		</div>
		
		<div class="cxsac-info-block">
			<span class="cxsac-info cxsac-success-page-tooltip">
				<span class="cxsac-info-icon"></span> <?php _e( 'What do these buttons do?', 'shop-as-customer' ); ?>
			</span>
			<div class="cxsac-tooltip cxsac-success-page-tooltip-html">
				<?php
				if ( $order->needs_payment() ) {
					?>
					<span class="cxsac-tip-heading"><?php _e( '"Send Request-to-Pay to Customer" button', 'shop-as-customer' ); ?></span>
					<ul>
						<li><?php _e( "Send the Customer Invoice email to the customer with a link to pay", 'shop-as-customer' ); ?></li>
						<li><?php _e( "The customer will link back to the Checkout page with available payment options presented to them where they can choose one and pay", 'shop-as-customer' ); ?></li>
						<li><?php _e( "The order will remain as Pending until they successfully Pay where after it will change to Processing", 'shop-as-customer' ); ?></li>
					</ul>
					<?php
				}
				else {
					?>
					<span class="cxsac-tip-heading"><?php _e( '"Send the Invoice Email to Customer" button', 'shop-as-customer' ); ?></span>
					<ul>
						<li><?php _e( "Send the Customer Invoice email to the customer.", 'shop-as-customer' ); ?></li>
					</ul>
					<?php
				}
				?>
				<span class="cxsac-tip-heading"><?php _e( '"Switch Back and View Order" button', 'shop-as-customer' ); ?></span>
				<ul>
					<li><?php _e( "Switch back to your user profile and take you to this order in the WooCommerce admin.", 'shop-as-customer' ); ?></li>
				</ul>
			</div>
		</div>
		
	</div>
	<?php
}
