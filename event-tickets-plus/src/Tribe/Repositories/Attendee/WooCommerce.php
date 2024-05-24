<?php

/**
 * The ORM/Repository class for WooCommerce attendees.
 *
 * @since 4.10.5
 */
class Tribe__Tickets_Plus__Repositories__Attendee__WooCommerce extends Tribe__Tickets_Plus__Attendee_Repository {

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @var string
	 */
	protected $key_name = 'woo';

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		// Remove Easy Digital Downloads
		unset( $this->schema['edd_order'] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		return $this->limit_list( $this->key_name, parent::attendee_types() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_event_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_ticket_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_ticket_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_order_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_order_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_name_keys() {
		/*
		 * This is here to reduce confusion by future developers.
		 *
		 * Purchaser name does not have a meta key stored on the attendee itself
		 * and must be retrieved by order customer for WooCommerce.
		 */
		return parent::purchaser_name_keys();
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_email_keys() {
		/*
		 * This is here to reduce confusion by future developers.
		 *
		 * Purchaser name does not have a meta key stored on the attendee itself
		 * and must be retrieved by order customer for WooCommerce.
		 */
		return parent::purchaser_email_keys();
	}

	/**
	 * {@inheritdoc}
	 */
	public function security_code_keys() {
		return $this->limit_list( $this->key_name, parent::security_code_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_optout_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_optout_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		return $this->limit_list( $this->key_name, parent::checked_in_keys() );
	}

}
