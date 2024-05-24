<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Status__Processing
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__Status__Processing extends Tribe__Tickets__Status__Abstract {

	//This is a payment has been deprecated by EDD, but is still available to use. It is included here for compatibility.
	public $name          = 'Processing';
	public $provider_name = 'processing';
	public $post_type     = 'download';

	public $incomplete          = true;
	public $trigger_option      = true;
	public $attendee_generation = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_incomplete    = true;
	public $count_sales         = true;

}