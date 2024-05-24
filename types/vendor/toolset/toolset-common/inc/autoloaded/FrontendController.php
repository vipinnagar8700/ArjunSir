<?php

namespace OTGS\Toolset\Common;

/**
 * Main controller for Toolset Common tasks in the frontend.
 *
 * This class has to be loaded right after the autoloader is initialized.
 *
 * @since BS4
 */
class FrontendController {


	/**
	 * Initialize the frontend controller. This needs to happen during Toolset Common bootstrapping.
	 */
	public function initialize() {
		$bootstrap_loader = new \OTGS\Toolset\Common\BootstrapLoader( \Toolset_Settings::get_instance() );
		$bootstrap_loader->initialize();
	}
}
