<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Refunded
 *
 * @since 4.10
 *
 */


class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Refunded extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Refunded';
	public $provider_name = 'wc-refunded';
	public $post_type     = 'shop_order_refunded';

	public $warning        = true;
	public $count_refunded = true;

}