<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Statuses__Complete
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Complete extends Tribe__Tickets__Status__Abstract {

	//Order fulfilled and complete – requires no further action
	public $name             = 'Completed';
	public $provider_name    = 'wc-completed';
	public $additional_names = [ 'Completed', 'completed' ];
	public $post_type        = 'shop_order';

	public $trigger_option      = true;
	public $attendee_generation = true;
	public $attendee_dispatch   = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_sales         = true;
	public $count_completed     = true;


}