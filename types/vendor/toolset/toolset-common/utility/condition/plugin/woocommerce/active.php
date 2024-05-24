<?php

/**
 * Check whether WooCommerce is active.
 *
 * @since 3.1
 */
class Toolset_Condition_Woocommerce_Active implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		return defined( 'WC_VERSION' );
	}

}