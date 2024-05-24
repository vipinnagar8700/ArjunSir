<?php

namespace OTGS\Toolset\Common\Wordpress;

/**
 * Wrapper for WordPress $wp_version global interaction.
 *
 * @since Views 2.8.1
 * @since Types 3.4.9 moved to Toolset Common.
 *
 * @codeCoverageIgnore
 */
class Version {

	/**
	 * Generate a \WP_Error instance
	 *
	 * @param string $version Version to compare against.
	 * @return int Returns -1 if current version is lower than $version,
	 *     0 if equals,
	 *     1 if current version is higher than $version.
	 */
	public function compare( $version ) {
		global $wp_version;
		return version_compare( $wp_version, $version );
	}

}
