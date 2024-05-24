<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;

/**
 * Compatibility for the SG Optimizer plugin.
 *
 * Purge cache after selected events that may modify the database outside of standard WordPress hooks.
 *
 * @link https://github.com/SiteGround/sg-cachepress
 * @since 3.3
 */
class SiteGroundOptimizer implements HandlerInterface2 {


	public function initialize() {
		add_action( 'toolset_before_ajax_finish', array( $this, 'purge_cache_after_ajax' ) );
	}


	/**
	 * Purge the cache after AJAX calls.
	 *
	 * @param string $action Name of the AJAX action that is about to finish.
	 */
	public function purge_cache_after_ajax( $action ) {
		if( ! function_exists( 'sg_cachepress_purge_cache' ) ) {
			return;
		}

		$urls = array();

		switch( $action ) {
			case 'types_' . \Types_Ajax::CALLBACK_REPEATABLE_GROUP:
				$urls[] = admin_url( 'post.php' );
				$urls[] = admin_url( 'post-new.php' );
				break;
			default:
				return;
		}

		if( empty( $urls ) ) {
			sg_cachepress_purge_cache();
		} else {
			foreach( $urls as $url ) {
				sg_cachepress_purge_cache( $url );
			}
		}
	}
}
