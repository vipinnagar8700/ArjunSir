<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Status_Manager
 *
 * @since 4.10
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__Status_Manager extends Tribe__Tickets__Status__Abstract_Commerce {

	public $completed_status_id = 'Complete';

	public $status_names = array(
		'Abandoned',
		'Complete',
		'Failed',
		'Pending',
		'Processing',
		'Refunded',
		'Revoked',
	);

	public $statuses = array();

	public function __construct() {

		$this->initialize_status_classes();
	}

	/**
	 * Initialize Commerce Status Class and Get all Statuses
	 */
	public function initialize_status_classes() {

		foreach ( $this->status_names as $name ) {

			$class_name = 'Tribe__Tickets_Plus__Commerce__EDD__Status__' . $name;

			$this->statuses[ $name ] = new $class_name();
		}
	}
}