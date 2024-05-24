<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Status__Refunded
 *
 * @since 4.10
 *
 */


class Tribe__Tickets_Plus__Commerce__EDD__Status__Refunded extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Refunded';
	public $provider_name = 'refunded';
	public $post_type     = 'download';

	public $warning        = true;
	public $count_refunded = true;

}