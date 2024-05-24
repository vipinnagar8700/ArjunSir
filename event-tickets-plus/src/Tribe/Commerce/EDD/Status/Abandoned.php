<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Statuses__Abandoned
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__Status__Abandoned extends Tribe__Tickets__Status__Abstract {

	//If a Pending payment is never completed it becomes Abandoned after a week.
	public $name          = 'Abandoned';
	public $provider_name = 'abandoned';
	public $post_type     = 'download';

	public $incomplete     = true;
	public $warning        = true;
	public $count_canceled = true;

}