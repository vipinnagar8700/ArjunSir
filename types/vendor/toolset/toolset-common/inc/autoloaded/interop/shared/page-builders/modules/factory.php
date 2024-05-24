<?php

namespace OTGS\Toolset\Common\Interop\Shared;

use OTGS\Toolset\Common\Interop\Handler as handler;

/**
 * Page Builder Modules factory.
 *
 * @since 3.0.5
 */
class PageBuilderModulesFactory {

	/** @var \Toolset_Condition_Plugin_Views_Active $views_active */
	private $views_active;

	/** @var \Toolset_Condition_Plugin_Cred_Active $form_active */
	private $form_active;

	public function __construct(
		\Toolset_Condition_Plugin_Views_Active $views_active = null,
		\Toolset_Condition_Plugin_Cred_Active $form_active = null
	) {
		$this->views_active = $views_active ?: new \Toolset_Condition_Plugin_Views_Active();
		$this->form_active  = $form_active ?: new \Toolset_Condition_Plugin_Cred_Active();
	}


	/**
	 * Get the Page Builder with modules.
	 *
	 * @param string $page_builder The page builder name.
	 *
	 * @return bool|handler\Elementor\ElementorModules
	 */
	public function get_page_builder( $page_builder ) {
		$return_page_builder = null;

		switch ( $page_builder ) {
			case handler\Elementor\ElementorModules::PAGE_BUILDER_NAME:
				if (
					$this->views_active->is_met() ||
					$this->form_active->is_met()
				) {
					$return_page_builder = new handler\Elementor\ElementorModules();
				} else {
					$return_page_builder = null;
				}
				break;

			case handler\Divi\DiviModules::PAGE_BUILDER_NAME:
				if ( $this->views_active->is_met() ) {
					$return_page_builder = new handler\Divi\DiviModules();
				}
				break;
		}

		return $return_page_builder;
	}
}