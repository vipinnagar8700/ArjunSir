<?php

namespace OTGS\Toolset\Common\Interop;

use OTGS\Toolset\Common\Interop\Handler as handler;
use OTGS\Toolset\Common\Interop\Shared as shared;

/**
 * Interoperability mediator.
 *
 * Handle any interop tasks with non-Toolset software - including Installer, but excluding WPML (WPML plugins are
 * handled in the Toolset_WPML_Compatibility class).
 *
 * This is to be considered a bootstrap code (using DIC).
 *
 * @package OTGS\Toolset\Common\Interop
 * @since 2.8
 */
class Mediator {


	private $constants;


	/** @var \OTGS\Toolset\Common\Auryn\Injector */
	private $dic;


	public function __construct( \Toolset_Constants $constants = null ) {
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	public function initialize() {
		$this->dic = toolset_dic();

		// If the class gets more complex, split it into subclasses, using the same design as in Types_Interop_Mediator.
		$installer_compatibility_reporting = new handler\InstallerCompatibilityReporting();
		$installer_compatibility_reporting->initialize();

		$toolset_page_builder_modules = new shared\PageBuilderModules();
		$toolset_page_builder_modules->load_modules();

		$beaver_builder_integration = new \OTGS\Toolset\Common\Interop\Handler\BeaverBuilder\MainIntegration();
		$beaver_builder_integration->initialize();

		$this->initialize_code_snippet_support();

		$is_woocommerce_active = new \Toolset_Condition_Woocommerce_Active();
		if( $is_woocommerce_active->is_met() ) {
			/** @var handler\WooCommerce $woocommerce_interop */
			/** @noinspection PhpUnhandledExceptionInspection */
			$woocommerce_interop = $this->dic->make( 'OTGS\Toolset\Common\Interop\Handler\WooCommerce' );
			$woocommerce_interop->initialize();
		}
	}


	private function initialize_code_snippet_support() {
		if(
			$this->constants->defined( 'TOOLSET_DISABLE_CODE_SNIPPETS' )
			&& $this->constants->constant( 'TOOLSET_DISABLE_CODE_SNIPPETS' )
		) {
			// Someone may want to disable this feature altogether. Save some performance if they did.
			return;
		}

		/** @var handler\CodeSnippets $code_snippet_support */
		/** @noinspection PhpUnhandledExceptionInspection */
		$code_snippet_support = $this->dic->make( 'OTGS\Toolset\Common\Interop\Handler\CodeSnippets', array(
			':tc_bootstrap' => null
		) );
		$code_snippet_support->initialize();
	}

}
