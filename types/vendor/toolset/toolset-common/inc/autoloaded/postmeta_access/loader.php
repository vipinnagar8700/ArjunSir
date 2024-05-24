<?php

/**
 * Hijack xxx_postmeta calls.
 *
 * We need this for a number of things, mostly when deprecating any postmeta key we used in the past.
 *
 * @since m2m
 */
class Toolset_Postmeta_Access_Loader {


	public function initialize() {

		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Depending on the status of the m2m functionality, a proper adjustment class will be instantiated,
			// to allow for setting or getting legacy relationship postmeta-based entries.
			$m2m_postmeta_access = new Toolset_Postmeta_Access_M2m();
			$m2m_postmeta_access->initialize();
		}

		if ( defined( 'WOOCOMMERCE_VIEWS_LEGACY_PATH' ) ) {
			// Only loaded after WooCommerce version after removing some legacy postmeta.
			$wooviews_postmeta_access = new \OTGS\Toolset\Common\PostmetaAccess\WooViewsPostmetaAccess();
			$wooviews_postmeta_access->initialize();
		}

	}

}
