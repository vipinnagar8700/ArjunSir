<?php


class Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Orders_Report_Tab extends Tribe__Tabbed_View__Tab {

	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * Get Slug for Order Report
	 *
	 * @since 4.10
 	 *
	 * @return string
	 */
	public function get_slug() {
		return Tribe__Tickets_Plus__Commerce__EDD__Orders__Report::$tab_slug;
	}

	/**
	 * Get Order Report Label
	 *
	 * @since 4.10
 	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Orders', 'event-tickets-plus' );
	}
}