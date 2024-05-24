<?php

namespace OTGS\Toolset\Common\Interop\Handler;

/**
 * WooCommerce interoperability.
 *
 * @since 3.2
 */
class WooCommerce {


	private $wc_get_order_statuses_exists;


	/**
	 * WooCommerce constructor.
	 *
	 * @param \Toolset_Condition_Function_Exists $function_exists
	 */
	public function __construct( \Toolset_Condition_Function_Exists $function_exists ) {
		$this->wc_get_order_statuses_exists = $function_exists;
	}


	public function initialize() {
		// See \OTGS\Toolset\Common\Interop\Commands\RelatedPosts::get_post_statuses_to_query_by().
		add_filter( 'toolset_accepted_post_statuses_for_api', array( $this, 'add_wc_order_statuses' ) );
		// Also influence the post status filter in the association query (by default, only posts in the "available"
		// category are returned, so this is relevant).
		add_filter( 'toolset_get_available_post_statuses', array( $this, 'add_wc_order_statuses' ) );

		$this->wc_get_order_statuses_exists->configure( 'wc_get_order_statuses' );

		$fields_integrator = new WooCommerce\Fields();
		$fields_integrator->initialize();
	}


	/**
	 * Add WooCommerce order statuses to an array, if possible.
	 *
	 * @param string[] $accepted_post_statuses
	 *
	 * @return string[]
	 */
	public function add_wc_order_statuses( $accepted_post_statuses ) {
		if ( $this->wc_get_order_statuses_exists->is_met() ) {
			/** @noinspection PhpUndefinedFunctionInspection */
			$wc_order_statuses = \wc_get_order_statuses();
			$accepted_post_statuses = array_merge( $accepted_post_statuses, array_keys( $wc_order_statuses ) );
		}

		return $accepted_post_statuses;
	}

}
