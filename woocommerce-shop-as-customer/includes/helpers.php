<?php

/**
 * Retrieves the global WP_Roles instance and instantiates it if necessary.
 *
 * @since 4.3.0
 *
 * @global WP_Roles $wp_roles WP_Roles global instance.
 *
 * @return WP_Roles WP_Roles global instance if not already instantiated.
 */
if ( ! function_exists( 'wp_roles' ) ) {
	function wp_roles() {
		global $wp_roles;
			
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		return $wp_roles;
	}
}

/**
 * Order helper function for methods that were only introduced in WC 3.0.
 */
function cxsac_order_get_id( $order ) {
	return method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id ;
}
function cxsac_order_get_order_key( $order ) {
	return method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->key ;
}
function cxsac_order_get_date_created( $order ) {
	return method_exists( $order, 'get_date_created' ) ? $order->get_date_created() : $order->date;
}
function cxsac_order_get_billing_first_name( $order ) {
	return method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
}
function cxsac_order_get_billing_last_name( $order ) {
	return method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;
}
function cxsac_order_get_billing_email( $order ) {
	return method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
}
function cxsac_order_get_payment_method( $order ) {
	return method_exists( $order, 'get_payment_method' ) ? $order->get_payment_method() : $order->payment_method ;
}
