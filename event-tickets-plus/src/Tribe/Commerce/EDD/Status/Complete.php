<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Statuses__Complete
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__Status__Complete extends Tribe__Tickets__Status__Abstract {

	//This is a payment that has been paid and the product delivered to the customer.
	public $name             = 'Completed';
	public $provider_name    = 'publish';
	public $additional_names = [ 'Complete', 'complete' ];
	public $post_type        = 'download';

	public $trigger_option      = true;
	public $attendee_generation = true;
	public $attendee_dispatch   = true;
	public $stock_reduced       = true;
	public $count_attendee      = true;
	public $count_sales         = true;
	public $count_completed     = true;

}