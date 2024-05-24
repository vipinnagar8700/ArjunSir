<?php

namespace OTGS\Toolset\Common\WpQueryExtension;

/**
 * Apply WP_Query adjustments.
 *
 * Depending on the status of the m2m functionality, a proper adjustment class will be instantiated
 * to allow for querying by post relationship in a sustainable way.
 *
 * @since 2.6.1
 */
class WpQueryExtensionLoader {

	public function initialize() {
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			do_action( 'toolset_do_m2m_full_init' );
			/** @noinspection PhpUnhandledExceptionInspection */
			$database_layer_factory = toolset_dic()->make( \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory::class );
			$adjustments = $database_layer_factory->wp_query_extension();
		} else {
			$adjustments = new LegacyRelationshipsExtension();
		}

		$adjustments->initialize();
	}

}
