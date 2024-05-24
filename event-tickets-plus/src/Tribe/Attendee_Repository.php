<?php

/**
 * Class Tribe__Tickets_Plus__Attendee_Repository
 *
 * Extension of the base Attendee repository to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__Attendee_Repository extends Tribe__Tickets__Attendee_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		// Easy Digital Downloads
		$this->schema['edd_order'] = [ $this, 'filter_by_edd_order' ];

		// WooCommerce
		$this->schema['woocommerce_order'] = [ $this, 'filter_by_woocommerce_order' ];

		// Override ET filters.
		$this->schema['purchaser_name'] = [ $this, 'filter_by_purchaser_name' ];
		$this->schema['purchaser_name__not_in'] = [ $this, 'filter_by_purchaser_name__not_in' ];
		$this->schema['purchaser_name__like'] = [ $this, 'filter_by_purchaser_name__like' ];
		$this->schema['purchaser_email'] = [ $this, 'filter_by_purchaser_email' ];
		$this->schema['purchaser_email__not_in'] = [ $this, 'filter_by_purchaser_email__not_in' ];
		$this->schema['purchaser_email__like'] = [ $this, 'filter_by_purchaser_email__like' ];
	}

	/**
	 * Filters the map relating attendee repository slugs to service container bindings.
	 *
	 * @since 4.10.5
	 *
	 * @param array $map A map in the shape [ <repository_slug> => <service_name> ]
	 *
	 * @return array A map in the shape [ <repository_slug> => <service_name> ]
	 */
	public function filter_attendee_repository_map( $map ) {
		// Easy Digital Downloads
		$map['edd'] = 'tickets-plus.attendee-repository.edd';

		// WooCommerce
		$map['woo'] = 'tickets-plus.attendee-repository.woo';

		return $map;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		$types = parent::attendee_types();

		// Easy Digital Downloads
		$types['edd'] = 'tribe_eddticket';

		// WooCommerce
		$types['woo'] = 'tribe_wooticket';

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		$keys = parent::attendee_to_event_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_event';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_event';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_ticket_keys() {
		$keys = parent::attendee_to_ticket_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_product';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_product';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function attendee_to_order_keys() {
		$keys = parent::attendee_to_order_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_order';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_order';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function security_code_keys() {
		$keys = parent::security_code_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_security_code';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_security_code';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_optout_keys() {
		$keys = parent::attendee_optout_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_attendee_optout';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_attendee_optout';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		$keys = parent::checked_in_keys();

		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_checkedin';

		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_checkedin';

		return $keys;
	}

	/**
	 * Returns meta query arguments to filter attendees by an Easy Digital Downloads order ID.
	 *
	 * @since 4.8
	 *
	 * @param int $order An Easy Digital Downloads order post ID.
	 *
	 * @return array
	 */
	public function filter_by_edd_order( $order ) {
		$keys = $this->attendee_to_order_keys();

		return Tribe__Repository__Query_Filters::meta_in( $keys['edd'], $order, 'by-edd-order' );
	}

	/**
	 * Returns meta query arguments to filter attendees by a WooCommerce order ID.
	 *
	 * @since 4.8
	 *
	 * @param int $order A WooCommerce order post ID.
	 *
	 * @return array
	 */
	public function filter_by_woocommerce_order( $order ) {
		$keys = $this->attendee_to_order_keys();

		return Tribe__Repository__Query_Filters::meta_in( $keys['woo'], $order, 'by-woocommerce-order' );
	}

	/**
	 * Filters attendees by purchaser name with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string|array $purchaser_name Purchaser name.
	 * @param string       $type           Type of matching (in, not_in, like).
	 */
	public function filter_by_purchaser_name( $purchaser_name, $type = 'in' ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$keys  = $this->purchaser_name_keys();
		$value = (array) $purchaser_name;

		$keys_in = "'" . implode( "','", array_map( [ $wpdb, '_escape' ], $keys ) ) . "'";

		$value_operator = 'IN';
		$value_clause   = "( '" . implode( "','", array_map( [ $wpdb, '_escape' ], $value ) ) . "' )";

		if ( 'not_in' === $type ) {
			$value_operator = 'NOT IN';
		} elseif ( 'like' === $type ) {
			$value_operator = 'LIKE';

			// Use first value match.
			$value = current( $value );

			$value_clause = "'" . $wpdb->remove_placeholder_escape( $wpdb->_escape( $value ) ) . "'";
		}

		// Join purchaser tables that are needed.
		$this->join_purchaser_tables();

		$where_clauses = [];

		// RSVP / Tribe Commerce clause.
		$where_clauses[] = "
			(
				purchaser_meta.meta_key IN ( {$keys_in} )
				AND purchaser_meta.meta_value {$value_operator} {$value_clause}
			)
		";

		$has_wc  = class_exists( 'WooCommerce' );
		$has_edd = defined( 'EDD_VERSION' ) && class_exists( 'Easy_Digital_Downloads' );

		if ( ! $has_wc && ! $has_edd ) {
			// None found, just use normal.
			$this->filter_by_simple_meta_schema( $value );
		}

		if ( $has_wc ) {
			// WooCommerce support.

			// Join purchaser tables that are needed.
			$this->join_purchaser_tables( 2 );

			$where_clauses[] = "
				(
					purchaser_meta.meta_key = '_tribe_wooticket_order'
					AND purchaser_order_meta.meta_key = '_billing_first_name'
					AND purchaser_order_meta2.meta_key = '_billing_last_name'
					AND
						CONCAT(
							purchaser_order_meta.meta_value,
							' ',
							purchaser_order_meta2.meta_value
						) {$value_operator} {$value_clause}
				)
			";
		}

		if ( $has_edd ) {
			// EDD support.

			$this->filter_query->join( "
				LEFT JOIN {$wpdb->prefix}edd_customers purchaser_edd_customer
				ON purchaser_edd_customer.id = purchaser_order_meta.meta_value
			", 'purchaser-edd-customer' );

			$where_clauses[] = "
				(
					purchaser_meta.meta_key = '_tribe_eddticket_order'
					AND purchaser_order_meta.meta_key = '_edd_payment_customer_id'
					AND purchaser_edd_customer.`name` {$value_operator} {$value_clause}
				)
			";
		}

		$where_clauses = implode( '
			OR
		', $where_clauses );

		$this->filter_query->where( "
			(
				{$where_clauses}
			)
		" );
	}

	/**
	 * Filters attendees that do not have purchaser name with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string|array $purchaser_name Purchaser name.
	 */
	public function filter_by_purchaser_name__not_in( $purchaser_name ) {
		$this->filter_by_purchaser_name( $purchaser_name, 'not_in' );
	}

	/**
	 * Filters attendees that are LIKE a purchaser name with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string $purchaser_name Purchaser name.
	 */
	public function filter_by_purchaser_name__like( $purchaser_name ) {
		$this->filter_by_purchaser_name( $purchaser_name, 'like' );
	}

	/**
	 * Filters attendees by purchaser email with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string|array $purchaser_email Purchaser email.
	 * @param string       $type            Type of matching (in, not_in, like).
	 */
	public function filter_by_purchaser_email( $purchaser_email, $type = 'in' ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$keys  = $this->purchaser_email_keys();
		$value = (array) $purchaser_email;

		$keys_in = "'" . implode( "','", array_map( [ $wpdb, '_escape' ], $keys ) ) . "'";

		$value_operator = 'IN';
		$value_clause   = "( '" . implode( "','", array_map( [ $wpdb, '_escape' ], $value ) ) . "' )";

		if ( 'not_in' === $type ) {
			$value_operator = 'NOT IN';
		} elseif ( 'like' === $type ) {
			$value_operator = 'LIKE';

			// Use first value match.
			$value = current( $value );

			$value_clause = "'" . $wpdb->remove_placeholder_escape( $wpdb->_escape( $value ) ) . "'";
		}

		// Join purchaser tables that are needed.
		$this->join_purchaser_tables();

		$where_clauses = [];

		// RSVP / Tribe Commerce clause.
		$where_clauses[] = "
			(
				purchaser_meta.meta_key IN ( {$keys_in} )
				AND purchaser_meta.meta_value {$value_operator} {$value_clause}
			)
		";

		$has_wc  = class_exists( 'WooCommerce' );
		$has_edd = defined( 'EDD_VERSION' ) && class_exists( 'Easy_Digital_Downloads' );

		if ( ! $has_wc && ! $has_edd ) {
			// None found, just use normal.
			$this->filter_by_simple_meta_schema( $value );
		}

		if ( $has_wc ) {
			// WooCommerce support.

			$where_clauses[] = "
				(
					purchaser_meta.meta_key = '_tribe_wooticket_order'
					AND purchaser_order_meta.meta_key = '_billing_email'
					AND purchaser_order_meta.meta_value {$value_operator} {$value_clause}
				)
			";
		}

		if ( $has_edd ) {
			// EDD support.

			$this->filter_query->join( "
				LEFT JOIN {$wpdb->prefix}edd_customers purchaser_edd_customer
				ON purchaser_edd_customer.id = purchaser_order_meta.meta_value
			", 'purchaser-edd-customer' );

			$this->filter_query->join( "
				LEFT JOIN {$wpdb->prefix}edd_customermeta purchaser_edd_customer_meta
				ON purchaser_edd_customer_meta.customer_id = purchaser_edd_customer.id
			", 'purchaser-edd-customer-meta' );

			$where_clauses[] = "
				(
					purchaser_meta.meta_key = '_tribe_eddticket_order'
					AND purchaser_order_meta.meta_key = '_edd_payment_customer_id'
					AND (
						purchaser_edd_customer.email {$value_operator} {$value_clause}
						OR (
							purchaser_edd_customer_meta.meta_key = 'additional_email'
							AND purchaser_edd_customer_meta.meta_value {$value_operator} {$value_clause}
						)
					)
				)
			";
		}

		$where_clauses = implode( '
			OR
		', $where_clauses );

		$this->filter_query->where( "
			(
				{$where_clauses}
			)
		" );
	}

	/**
	 * Filters attendees that do not have purchaser email with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string|array $purchaser_email Purchaser email.
	 */
	public function filter_by_purchaser_email__not_in( $purchaser_email ) {
		$this->filter_by_purchaser_email( $purchaser_email, 'not_in' );
	}

	/**
	 * Filters attendees that are LIKE a purchaser email with support for EDD / WooCommerce.
	 *
	 * @since 4.10.5
	 *
	 * @param string $purchaser_email Purchaser email.
	 */
	public function filter_by_purchaser_email__like( $purchaser_email ) {
		$this->filter_by_purchaser_email( $purchaser_email, 'like' );
	}

	/**
	 * Handle joining of the tables needed for purchaser filters.
	 *
	 * @param int $number_of_order_meta_joins The number of order meta JOINs to add.
	 *
	 * @since 4.10.5
	 */
	protected function join_purchaser_tables( $number_of_order_meta_joins = 1 ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$this->filter_query->join( "
			LEFT JOIN {$wpdb->postmeta} purchaser_meta
			ON purchaser_meta.post_id = {$wpdb->posts}.ID
		", 'purchaser-meta' );

		for ( $x = 1; $x <= $number_of_order_meta_joins; $x++ ) {
			$affix = '';

			if ( 1 < $x ) {
				$affix = $x;
			}

			$this->filter_query->join( "
				LEFT JOIN {$wpdb->postmeta} purchaser_order_meta{$affix}
				ON purchaser_order_meta{$affix}.post_id = purchaser_meta.meta_value
			", 'purchaser-order-meta' . $affix );
		}
	}

}
