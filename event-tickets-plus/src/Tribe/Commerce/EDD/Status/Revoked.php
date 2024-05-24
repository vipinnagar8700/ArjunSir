<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Status__Revoked
 *
 * @since 4.10
 *
 */


class Tribe__Tickets_Plus__Commerce__EDD__Status__Revoked extends Tribe__Tickets__Status__Abstract {

	public $name          = 'Revoked';
	public $provider_name = 'revoked';
	public $post_type     = 'download';

	public $warning = true;

}