<?php

// phpcs:ignoreFile

namespace OTGS\Toolset\Common;

use OTGS\Toolset\Common\Admin\TroubleshootingSections;

/**
 * Main controller for Toolset Common tasks in the backend.
 *
 * This class has to be loaded only after the autoloader is initialized.
 *
 * @since 2.5.7
 */
class AdminController {


	/** @var TroubleshootingSections */
	private $troubleshooting_sections;


	/**
	 * AdminController constructor.
	 *
	 * @param TroubleshootingSections $troubleshooting_sections
	 */
	public function __construct( TroubleshootingSections $troubleshooting_sections ) {
		$this->troubleshooting_sections = $troubleshooting_sections;
	}


	/**
	 * Initialize the admin (backend) controller. This needs to happen during Toolset Common bootstrapping.
	 */
	public function initialize() {
		$this->troubleshooting_sections->initialize();
	}


//	/**
//	 * Show a customized WHIP notice for PHP 5.2 users.
//	 */
//	private function load_whip() {
//		if ( 'index.php' !== $GLOBALS['pagenow'] && current_user_can( 'manage_options' ) ) {
//			return;
//		}
//
//		require_once TOOLSET_COMMON_PATH . '/lib/whip/src/facades/wordpress.php';
//
//		add_filter( 'whip_hosting_page_url_wordpress', '__return_true' );
//		whip_wp_check_versions( array( 'php' => '>=5.2' ) );
//	}


//	private function init_page_extensions() {
//
//	}

}
