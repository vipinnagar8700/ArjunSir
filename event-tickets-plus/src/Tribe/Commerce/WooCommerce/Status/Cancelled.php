<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Statuses__Cancelled
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Cancelled extends Tribe__Tickets__Status__Abstract {

	//Cancelled by an admin or the customer – no further action required (Cancelling an order does not affect stock quantity by default)
	public $name          = 'Cancelled';
	public $provider_name = 'wc-cancelled';
	public $post_type     = 'shop_order';

	public $incomplete       = true;
	public $warning          = true;
	public $count_canceled   = true;

}