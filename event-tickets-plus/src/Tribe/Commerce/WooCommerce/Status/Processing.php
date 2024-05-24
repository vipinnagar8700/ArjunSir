<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Statuses__Processing
 *
 * @since 4.10
 *
 */


class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__Processing extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Processing';
	public $provider_name = 'wc-processing';
	public $post_type     = 'shop_order';

	public $incomplete          = true;
	public $trigger_option      = true;
	public $attendee_generation = true;
	public $attendee_dispatch   = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_incomplete    = true;
	public $count_sales         = true;

}