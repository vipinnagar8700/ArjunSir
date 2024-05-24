<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager extends Tribe__Tickets__Status__Abstract_Commerce {

	public $completed_status_id = 'wc-completed';

	public $status_names = array(
		'wc-cancelled'  => 'Cancelled',
		'wc-completed'  => 'Complete',
		'wc-failed'     => 'Failed',
		'wc-on-hold'    => 'On_Hold',
		'wc-processing' => 'Processing',
		'wc-pending'    => 'Pending',
		'wc-refunded'   => 'Refunded',
	);

	public function __construct() {

		$this->initialize_status_classes();
	}

	/**
	 * Initialize Commerce Status Class and Get all Statuses
	 */
	public function initialize_status_classes() {

		foreach ( $this->status_names as $key => $name ) {

			$class_name = 'Tribe__Tickets_Plus__Commerce__WooCommerce__Status__' . $name;

			$this->statuses[ $key ] = new $class_name();
		}
	}

}