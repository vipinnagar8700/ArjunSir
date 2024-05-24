<?php

namespace OTGS\Toolset\Common\Wordpress;

/**
 * Wrapper for the WordPress wp_safe_redirect function.
 *
 * @since Views 3.2 in Views.
 * @since Types 3.4.9 moved to Toolset Common.
 */
class WpSafeRedirect {

	/** @var Version */
	private $wp_version;


	/**
	 * WpSafeRedirect constructor.
	 *
	 * @param Version $wp_version
	 */
	public function __construct( Version $wp_version ) {
		$this->wp_version = $wp_version;
	}


	/**
	 * Act depending on whether wp_safe_redirect got its third argument and returns TRUE or FALSe,
	 * which happened in 5.1.0.
	 *
	 * @param string $location The path or URL to redirect to.
	 * @param int $status Optional. HTTP response status code to use. Default '302' (Moved Temporarily).
	 * @param string $x_redirect_by Optional. The application doing the redirect. Default 'WordPress'.
	 *
	 * @return bool False if the redirect was cancelled, true otherwise.
	 */
	public function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'Toolset' ) {
		if ( - 1 === $this->wp_version->compare( '5.1.0' ) ) {
			// WP is on <5.1.0, so just redirect and assume it returns TRUE.
			wp_safe_redirect( $location, $status );

			return true;
		}

		// WP is on 5.1.0+, so pass all the arguments and return as provided.
		return wp_safe_redirect( $location, $status, $x_redirect_by );
	}

}
