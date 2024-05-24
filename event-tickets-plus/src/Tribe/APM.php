<?php


class Tribe__Tickets_Plus__APM {

	/**
	 * @var Tribe__Tickets_Plus__Commerce__Total_Provider_Interface
	 */
	protected $sales_counter;

	/**
	 * @var Tribe__Tickets_Plus__Commerce__Total_Provider_Interface
	 */
	protected $stock_counter;

	/**
	 * @var Tribe__Tickets_Plus__APM__Sales_Filter
	 */
	protected $sales_filter;

	/**
	 * @var Tribe__Tickets_Plus__APM__Stock_Filter
	 */
	protected $stock_filter;

	/**
	 * Tribe__Tickets_Plus__APM constructor.
	 */
	public function __construct() {
		add_action( 'tribe_events_pro_init_apm_filters', [ $this, 'init_apm_filters' ], 9 );
		add_filter( 'tribe_events_pro_apm_filters_fallback_columns', [ $this, 'fallback_columns' ] );
		add_filter( 'tribe_events_pro_apm_filters_args', [ $this, 'filter_args' ] );
		add_filter( 'tribe_apm_column_headers', [ $this, 'column_headers' ] );
	}

	/**
	 * Initializes the APM filter classes.
	 */
	public function init_apm_filters() {
		$this->sales_filter();
		$this->stock_filter();
	}

	/**
	 * Filters the fallback columns that will be used if the user did not set any.
	 *
	 * @param array $fallback_columns
	 *
	 * @return array The modified fallback columns array.
	 */
	public function fallback_columns( array $fallback_columns ) {
		$fallback_columns[] = Tribe__Tickets_Plus__APM__Sales_Filter::$key;
		$fallback_columns[] = Tribe__Tickets_Plus__APM__Stock_Filter::$key;

		return $fallback_columns;
	}

	/**
	 * Filters the events filter args array.
	 *
	 * @param array $filter_args The original filter arguments.
	 *
	 * @return array The modified filter arguments.
	 */
	public function filter_args( array $filter_args ) {
		$filter_args[ Tribe__Tickets_Plus__APM__Sales_Filter::$key ] = [
			'name'        => esc_html( sprintf( __( '%s Sales', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'apm_sales_filter_name' ) ) ),
			'custom_type' => 'custom_ticket_sales',
			'sortable'    => 'true',
			'cast'        => 'NUMERIC',
		];

		$filter_args[ Tribe__Tickets_Plus__APM__Stock_Filter::$key ] = [
			'name'        => esc_html( sprintf( __( '%s Stock', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'apm_stock_filter_name' ) ) ),
			'custom_type' => 'custom_ticket_stock',
			'sortable'    => 'true',
			'cast'        => 'NUMERIC',
		];

		return $filter_args;
	}

	/**
	 * Filters the column headers.
	 *
	 * @param array $headers
	 *
	 * @return array
	 */
	public function column_headers( array $headers = [] ) {
		$headers[ Tribe__Tickets_Plus__APM__Sales_Filter::$key ] = __( 'Sales', 'event-tickets-plus' );
		$headers[ Tribe__Tickets_Plus__APM__Stock_Filter::$key ] = __( 'Stock', 'event-tickets-plus' );

		return $headers;
	}

	/**
	 * Sales filter singleton accessor method.
	 *
	 * @return Tribe__Tickets_Plus__APM__Sales_Filter
	 */
	public function sales_filter() {
		if ( empty( $this->sales_filter ) ) {
			$this->sales_filter = new Tribe__Tickets_Plus__APM__Sales_Filter();
		}

		return $this->sales_filter;
	}

	/**
	 * Stock filter singleton accessor method.
	 *
	 * @return Tribe__Tickets_Plus__APM__Stock_Filter
	 */
	public function stock_filter() {
		if ( empty( $this->stock_filter ) ) {
			$this->stock_filter = new Tribe__Tickets_Plus__APM__Stock_Filter();
		}

		return $this->stock_filter;
	}

	/**
	 * Deprecated Methods
	 */

	/**
	 * Sales counter singleton accessor method.
	 *
	 * @deprecated 4.6
	 *
	 * @return Tribe__Tickets_Plus__Commerce__Sales_Counter|Tribe__Tickets_Plus__Commerce__Total_Provider_Interface
	 */
	public function sales_counter() {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__APM__Sales_Filter::get_total_value()' );
		return new Tribe__Tickets_Plus__Commerce__Sales_Counter( Tribe__Tickets_Plus__Main::instance()->commerce_loader() );
	}

	/**
	 * Stock counter singleton accessor method.
	 *
	 * @deprecated 4.6
	 *
	 * @return Tribe__Tickets_Plus__Commerce__Stock_Counter|Tribe__Tickets_Plus__Commerce__Total_Provider_Interface
	 */
	public function stock_counter() {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__APM__Stock_Filter::get_total_value()' );
		return new Tribe__Tickets_Plus__Commerce__Stock_Counter( Tribe__Tickets_Plus__Main::instance()->commerce_loader() );
	}
}
