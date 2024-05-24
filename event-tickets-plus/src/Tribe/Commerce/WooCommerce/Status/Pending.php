<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Pending
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Pending extends Tribe__Tickets__Status__Abstract {

	//Order received (unpaid)
	public $name          = 'Pending';
	public $provider_name = 'wc-pending';
	public $post_type     = 'shop_order';

	public $incomplete          = true;
	public $trigger_option      = true;
	public $attendee_generation = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_incomplete    = true;
	public $count_sales         = true;

}