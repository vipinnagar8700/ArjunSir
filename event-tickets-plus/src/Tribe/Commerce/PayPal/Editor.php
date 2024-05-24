<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__PayPal__Editor
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Commerce__PayPal__Editor extends Tribe__Tickets__Editor {

	/**
	 * Filters the absolute path to the capacity metabox for PayPal tickets.
	 *
	 * The method will load the one that enables full capacity functionalities
	 * on PayPal tickets.
	 *
	 * @since 4.7
	 *
	 * @return string
	 */
	public function filter_tpp_metabox_capacity_file() {
		$file = Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/tpp-metabox-capacity.php';

		return $file;
	}
}