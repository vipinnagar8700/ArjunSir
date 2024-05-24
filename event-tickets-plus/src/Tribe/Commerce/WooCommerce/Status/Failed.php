<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Failed
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Failed extends Tribe__Tickets__Status__Abstract {

	//Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (i.e., PayPal)
	public $name          = 'Failed';
	public $provider_name = 'wc-failed';
	public $post_type     = 'shop_order';

	public $incomplete     = true;
	public $warning        = true;
	public $count_canceled = true;
}