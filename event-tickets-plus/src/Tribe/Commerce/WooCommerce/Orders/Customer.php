<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer
 *
 * Models an order customer.
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer {

	/**
	 * @var array
	 */
	protected static $store = array();

	/**
	 * @var string The customer name
	 */
	protected $name = '';

	/**
	 * @var string The customer email
	 */
	protected $email;

	/**
	 * Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer constructor.
	 *
	 * @param array $item An array of order and customer data.
	 */
	public function __construct( array $item ) {
		$customer = ! empty( $item['customer'] ) ? $item['customer'] : array();

		$this->name  = $this->get_customer_name( $item );
		$this->email = $this->get_customer_email( $item );
	}

	/**
	 * A factory method to return a customer instance from an item.
	 *
	 * @param array $item
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer
	 */
	public static function make_from_item( array $item ) {
		ksort( $item );

		$hash = md5( serialize( $item ) );

		if ( isset( self::$store[ $hash ] ) ) {
			return self::$store[ $hash ];
		}

		$customer = new self( $item );

		self::$store[ $hash ] = $customer;

		return $customer;
	}

	/**
	 * Returns the customer name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	protected function get_customer_name( array $item ) {
		$customer = ! empty( $item['customer'] ) ? $item['customer'] : array();

		if ( empty( $customer['first_name'] ) && empty( $customer['last_name'] ) ) {
			$customer_name = "{$item['billing_address']['first_name']} {$item['billing_address']['last_name']}";
		} else {
			$customer_name = empty( $customer['first_name'] ) ? '' : $customer['first_name'];
			$customer_name .= empty( $customer['last_name'] ) ? '' : ' ' . $customer['last_name'];
		}

		return trim( $customer_name );
	}

	/**
	 * Returns the customer email address.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	protected function get_customer_email( array $item ) {
		if ( empty( $item['customer']['email'] ) ) {
			return '';
		}

		$email = $item['customer']['email'];

		return sprintf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
	}
}