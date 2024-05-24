<?php


class Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab extends Tribe__Tabbed_View__Tab {

	/**
	 * @var bool
	 */
	protected $visible = true;

	public function get_slug() {
		return Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report::$tab_slug;
	}

	public function get_label() {
		return __( 'Orders', 'event-tickets-plus' );
	}
}