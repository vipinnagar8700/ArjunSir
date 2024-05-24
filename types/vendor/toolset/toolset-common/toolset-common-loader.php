<?php

if ( !defined('TOOLSET_VERSION') ) {
	define('TOOLSET_VERSION', '4.4.3' );
}

if ( ! defined('TOOLSET_COMMON_VERSION' ) ) {
    define( 'TOOLSET_COMMON_VERSION', TOOLSET_VERSION );
}

/** @deprecated This may not be defined at all times. Use TOOLSET_COMMON_VERSION instead */
if ( ! defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
	define( 'WPTOOLSET_FORMS_VERSION', TOOLSET_VERSION );
}

if ( ! defined('TOOLSET_COMMON_PATH' ) ) {
    define( 'TOOLSET_COMMON_PATH', dirname( __FILE__ ) );
}

if ( ! defined('TOOLSET_COMMON_DIR' ) ) {
    define( 'TOOLSET_COMMON_DIR', basename( TOOLSET_COMMON_PATH ) );
}

if( ! defined( 'TOOLSET_DATA_STRUCTURE_VERSION') ) {
	/**
	 * Determines version of Toolset data structures or other changes that need
	 * an upgrade routine to be executed.
	 *
	 * This constant should be used only by OTGS\Toolset\Common\Upgrade\UpgradeController.
	 */
	define( 'TOOLSET_DATA_STRUCTURE_VERSION', 6 );
}

/**
 * Last edit flag shared among Toolset plugins.
 *
 * @since 2.5.7 defined in Toolset Common (this is also redundantly defined in some plugins).
 */
if ( ! defined( 'TOOLSET_EDIT_LAST' ) ) {
	define( 'TOOLSET_EDIT_LAST', '_toolset_edit_last' );
}


if( ! defined( 'TOOLSET_COMMON_VENDOR_PATH' ) ) {

	/**
	 * Vendor directory that holds the composer dependencies of the plugin that is
	 * currently loading this Toolset Common instance.
	 *
	 * Note that this does not point to the actual vendor directory of Toolset Common,
	 * but to the vendor directory where Toolset Common and other dependencies for
	 * the main plugin housing them live.
	 *
	 * @since 2.5.7
	 */
	define( 'TOOLSET_COMMON_VENDOR_PATH', dirname( dirname( TOOLSET_COMMON_PATH ) ) );
}

require_once( TOOLSET_COMMON_PATH . '/bootstrap.php' );

if ( ! function_exists( 'toolset_common_boostrap' ) ) {
    function toolset_common_boostrap() {
        global $toolset_common_bootstrap;
        $toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
    }

	/**
	 * Set Toolset Common constants.
	 *
	 * TOOLSET_COMMON_URL				Base URL for the Toolset Common instance. Note that is does not have a trailing slash.
	 * TOOLSET_COMMON_FRONTEND_URL		Base frontend URL for the Toolset Common instance. Note that is does not have a trailing slash.
	 *
	 * TOOLSET_COMMON_PROTOCOL			Deprecated.
	 * TOOLSET_COMMON_FRONTEND_PROTOCOL	Deprecated.
	 *
	 * @TODO: there is no need to manipulate URL values for http/https if everyone uses plugins_url, but not everyone does, so:
     * this is necessary, but it should be enough to do $url = set_url_scheme( $url ) and the protocol
     * will be calculated by itself.
	 * Note that set_url_scheme( $url ) takes care of FORCE_SSL_AMIN too:
	 * https://developer.wordpress.org/reference/functions/set_url_scheme/
	 *
     * @TODO: no need of TOOLSET_COMMON_URL, TOOLSET_COMMON_PROTOCOL, TOOLSET_COMMON_FRONTEND_URL, TOOLSET_COMMON_FRONTEND_PROTOCOL
	 * In fact, TOOLSET_COMMON_PROTOCOL and TOOLSET_COMMON_FRONTEND_PROTOCOL are not used anywhere and I am maring them as deprecated.
     * define('TOOLSET_COMMON_URL', set_url_scheme( $url ) ); covers everything
	 * although there might be cases where an AJAX call is performed, hence happening on the backend,
	 * and we ned to build a frontend URL based on the Toolset Common URL, while they have different SSL schemas,
	 * so if possible, I would keep those two constants.
	 *
	 * @param string $url
	 * @param int $version_number The "$toolset_common_version" value of the loaded library. Will be used to define
	 *     the TOOLSET_COMMON_VERSION_NUMBER constant.
	 */
	function toolset_common_set_constants_and_start( $url, $version_number = 0 ) {

		// Backwards compatibility: make sure that the URL constants do not include a trailing slash.
		$url = untrailingslashit( $url );

		if (
			is_ssl()
			|| (
				defined( 'FORCE_SSL_ADMIN' )
				&& FORCE_SSL_ADMIN
			)
		) {
			define( 'TOOLSET_COMMON_URL', str_replace( 'http://', 'https://', $url ) );
			define( 'TOOLSET_COMMON_PROTOCOL', 'https' ); // DEPRECATED
		} else {
			define( 'TOOLSET_COMMON_URL', $url );
			define( 'TOOLSET_COMMON_PROTOCOL', 'http' ); // DEPRECATED
	}
		if ( is_ssl() ) {
			define( 'TOOLSET_COMMON_FRONTEND_URL', TOOLSET_COMMON_URL );
			define( 'TOOLSET_COMMON_FRONTEND_PROTOCOL', 'https' ); // DEPRECATED
		} else {
			define( 'TOOLSET_COMMON_FRONTEND_URL', str_replace( 'https://', 'http://', TOOLSET_COMMON_URL ) );
			define( 'TOOLSET_COMMON_FRONTEND_PROTOCOL', 'http' ); // DEPRECATED
		}

		// By preventing a re-definition we're easily allowing helper plugins like tcl-override to function.
		// If nothing fancy is happening on the site, this will have zero impact.
	    if( 0 !== (int) $version_number && ! defined( 'TOOLSET_COMMON_VERSION_NUMBER' ) ) {

		    /**
		     * If defined, this is an integer version number of the common library.
		     *
		     * It can be used for simple version comparison.
		     *
		     * @since 2.5.1
		     */
		    define( 'TOOLSET_COMMON_VERSION_NUMBER', $version_number );
	    }
    }
    // Load early
	// We register scripts and styles that are dependences for Toolset assets
    add_action( 'after_setup_theme', 'toolset_common_boostrap' );
}

/**
* @todo this should be in the WPML compatibility class :-(
*/

if( !function_exists('toolset_disable_wpml_admin_lang_switcher') ){
	add_filter( 'wpml_show_admin_language_switcher', 'toolset_disable_wpml_admin_lang_switcher' );
	function toolset_disable_wpml_admin_lang_switcher( $state ) {
		global $pagenow;

		$toolset_pages = array(
			'toolset-settings', 'toolset-help', 'toolset-debug-information'
		);

		$toolset_pages = apply_filters( 'toolset_filter_disable_wpml_lang_switcher_in_admin', $toolset_pages );

		if (
			$pagenow == 'admin.php'
			&& isset( $_GET['page'] )
			&& in_array( $_GET['page'], $toolset_pages )
		) {
			$state = false;
		}
		return $state;
	}

}


/*
 * Hotfix for Chrome crashing when clicking on "Preview" of a drafted post.
 * Problem: WooCommerce is adding "no-store" to Cache-Control to nearly any request
 * and Chrome can't handle it in the preview process.
 *
 * Can be removed once this WooCommerce Ticket is resolved:
 * https://wordpress.org/support/topic/chrome-crashes-when-previewing-drafted-posts-while-wc-is-active/
 *
 * Our ticket:
 * https://onthegosystems.myjetbrains.com/youtrack/issue/toolsetga-118?p=toolsetblocks-1193
 */
if( ! function_exists( 'tc_fix_toolsetga_118' ) ) {
	function tc_fix_toolsetga_118( $headers ) {
		if( isset( $headers['Cache-Control'] ) ) {
			$cache_control = strtolower( $headers['Cache-Control'] );
			if( strpos( $cache_control, 'no-store') !== false ) {
				$headers['Cache-Control'] = str_replace(
					[ ', no-store', ',no-store', 'no-store, ', 'no-store,', 'no-store' ],
					'',
					$cache_control
				);
			}
		}

		return $headers;
	}

	// Only add filter to remove 'no-store' on ./wp-admin/post.php?post=146&action=edit&message=4 calls.
	// (That http request is only triggered when the "Preview" button is clicked.)
	if(
		isset( $_REQUEST['action'] ) &&
		isset( $_REQUEST['message'] ) &&
		$_REQUEST['action'] === 'edit' &&
		$_REQUEST['message'] === '4'
	) {
		// Remove 'no-store' from HTTP header Cache-Controls. WordPress does not use it at all, but WooCommerce adds it.
		add_filter( 'nocache_headers', 'tc_fix_toolsetga_118', PHP_INT_MAX );
	}
}

