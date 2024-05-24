<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__On_Hold
 *
 * @since 4.10
 *
 */


class Tribe__Tickets_Plus__Commerce__WooCommerce__Status__On_Hold extends Tribe__Tickets__Status__Abstract {

	//Awaiting payment – stock is reduced, but you need to confirm payment - such as BACS (bank transfer) or cheque.
	public $name          = 'On Hold';
	public $provider_name = 'wc-on-hold';
	public $post_type     = 'shop_order';

	public $incomplete          = true;
	public $trigger_option      = true;
	public $attendee_generation = true;
	public $attendee_dispatch   = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_incomplete    = true;
}