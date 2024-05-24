<?php

namespace OTGS\Toolset\Common\PostmetaAccess;

/**
 * Hijack get_post_meta native functions for legacy WooCommerce Views fields.
 */
class WooViewsPostmetaAccess {

	const LEGACY_STOCK_FIELD = 'views_woo_in_stock';
	const LEGACY_ONSALE_FIELD = 'views_woo_on_sale';
	const LEGACY_PRICE_FIELD = 'views_woo_price';

	const LEGACY_ON_STOCK = '1';
	const LEGACY_OUT_OF_STOCK = '0';

	const STOCK_FIELD = '_stock_status';

	const STOCK_IN_STOCK = 'instock';
	const STOCK_OUT_OF_STOCK = 'outofstock';
	const STOCK_ON_BACKORDER = 'onbackorder';

	public function initialize() {
		/**
		 * Init the legacy postmeta access hooks, at init:11 so Types can register its post types and field groups at init:10.
		 *
		 * @since m2m
		 */
		add_action( 'init', array( $this, 'init' ), 11 );
	}

	/**
	 * Init the legacy postmeta access hooks.
	 *
	 * Note that this legacy layer is only available after init:11.
	 */
	public function init() {
		// Hijack the native get postmeta functions
		add_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );

		// Deactivate this class hijacking in case there is nothing to hijack
		add_action( 'toolset_deactivate_postmeta_access_woocommerce_views', array( $this, 'deactivate_postmeta_access' ) );
	}

	/**
	 * Remove the hooks to postmeta functions, on demand.
	 */
	public function deactivate_postmeta_access() {
		remove_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
	}

	/**
	 * Get the legacy meta keys.
	 *
	 * @return string[]
	 */
	private function get_legacy_keys() {
		return array(
			self::LEGACY_STOCK_FIELD,
			self::LEGACY_ONSALE_FIELD,
			self::LEGACY_PRICE_FIELD,
		);
	}

	/**
	 * Adjust one individual value form the native stock status field into the legacy boolish value.
	 *
	 * Fos historical reasons, he boolish value was zero for out of stock and 1 otherwise.
	 *
	 * @param string $value
	 * @return string
	 */
	private function adjust_to_legacy_stock_bool_value( $value ) {
		switch ( $value ) {
			case self::STOCK_IN_STOCK:
			case self::STOCK_ON_BACKORDER:
				return self::LEGACY_ON_STOCK;
			case self::STOCK_OUT_OF_STOCK:
				return self::LEGACY_OUT_OF_STOCK;
		}

		return self::LEGACY_OUT_OF_STOCK;
	}

	/**
	 *	Adjust the new values for the stock status field into booleanish form the legacy field.
	 *
	 * @param mixed|mixed[] $meta_value
	 * @return mixed|mixed[]
	 */
	private function adjust_to_legacy_stock_bool_meta( $meta_value ) {
		if ( is_array( $meta_value ) ) {
			foreach ( $meta_value as $key => $value ) {
				$meta_value[ $key ] = $this->adjust_to_legacy_stock_bool_value( $value );
			}

			return $meta_value;
		}

		return $this->adjust_to_legacy_stock_bool_value( $meta_value );
	}

	/**
	 * Adjust the rturnig values depending on the $single boolean flag.
	 *
	 * @param mixed $value
	 * @param bool $single
	 * @return mixed|mixed[]
	 */
	private function return_by_single( $value, $single ) {
		return $single
			? $value
			: array( $value );
	}

	/**
	 * Transform a legacy postmeta getting action to provide the right outcome.
	 *
	 * @param mixed $return The value to return
	 * @param int $object_id iID of the object metadata is for
	 * @param string @meta_key Metadata key
	 * @param bool $single Whether to return only the first value of the specified $meta_key
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_post_metadata( $return, $object_id, $meta_key, $single ) {
		$legacy_keys = $this->get_legacy_keys();
		if ( false === in_array( $meta_key, $legacy_keys, true ) ) {
			return $return;
		}

		switch ( $meta_key ) {
			case self::LEGACY_STOCK_FIELD:
				remove_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
				$field_value = get_post_meta( $object_id, self::STOCK_FIELD, $single );
				add_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
				return $this->adjust_to_legacy_stock_bool_meta( $field_value );
			case self::LEGACY_ONSALE_FIELD:
				$product = ( function_exists( 'wc_get_product' ) )
					? \wc_get_product( $object_id )
					: null;
				$value = ( $product && is_callable( array( $product, 'is_on_sale' ) ) )
					? $product->is_on_sale()
					: false;
				$value = (int) $value;
				$value = (string) $value;
				return $this->return_by_single( $value, $single );
			case self::LEGACY_PRICE_FIELD:
				$product = ( function_exists( 'wc_get_product' ) )
					? \wc_get_product( $object_id )
					: null;
				$value = ( $product && function_exists( 'wc_get_price_to_display' ) )
					? \wc_get_price_to_display( $product )
					: '0';
				return $this->return_by_single( $value, $single );

		}

		return $return;
	}

}
