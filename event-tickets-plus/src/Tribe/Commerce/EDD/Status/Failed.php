<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Status__Failed
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__Status__Failed extends Tribe__Tickets__Status__Abstract {

	//This is a payment where the payment process failed, whether it be a credit card rejection or some other error.
	public $name          = 'Failed';
	public $provider_name = 'failed';
	public $post_type     = 'download';

	public $incomplete     = true;
	public $warning        = true;
	public $count_canceled = true;
}