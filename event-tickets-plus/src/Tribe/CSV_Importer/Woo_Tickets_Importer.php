<?php


/**
 * Class Tribe__Tickets_Plus__CSV_Importer__Woo_Tickets_Importer
 *
 * A WooCommerce specific ticket importer implementation.
 */
class Tribe__Tickets_Plus__CSV_Importer__Woo_Tickets_Importer extends Tribe__Tickets_Plus__CSV_Importer__Tickets_Importer {

	/**
	 * Returns an instance of the commerce provider.
	 *
	 * This method should be overridden in extending classes.
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Main
	 */
	protected function get_commerce_engine_instance() {
		return Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
	}

	/**
	 * Modify the data for the specific commerce engine ticket implementation.
	 *
	 * This method should be overridden in extending classes.
	 *
	 * @param array  $record
	 * @param  array $data
	 *
	 * @return array
	 */
	protected function modify_data( array $record, array $data ) {
		$stock = $this->get_value_by_key( $record, 'ticket_stock' );
		$capacity = $this->get_value_by_key( $record, 'ticket_capacity' );

		if ( empty( $capacity ) ) {
			$capacity = $stock;
		}

		$data['tribe-ticket']['capacity'] = $capacity;
		$data['tribe-ticket']['stock'] = $stock;
		$data['ticket_sku'] = $this->get_value_by_key( $record, 'ticket_sku' );

		return $data;
	}

}
