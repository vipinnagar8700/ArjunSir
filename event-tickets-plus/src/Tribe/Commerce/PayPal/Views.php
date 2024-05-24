<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__PayPal__Views
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Commerce__PayPal__Views extends Tribe__Template {
	/**
	 * Tribe__Tickets_Plus__Commerce__PayPal__Views constructor.
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$this->set_template_origin( Tribe__Tickets_Plus__Main::instance() );
		$this->set_template_folder( 'src/views/tpp' );
		$this->set_template_context_extract( true );
	}
}
