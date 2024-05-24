<?php

namespace OTGS\Toolset\Common\Interop\Shared;

use OTGS\Toolset\Common\Condition\Theme\Divi\IsDiviThemeActive;
use OTGS\Toolset\Common\Interop\Handler as handler;

/**
 * Handles the creation and initialization of the all the Page Builder modules.
 *
 * @since 3.0.5
 */
class PageBuilderModules {
	/** @var \Toolset_Condition_Plugin_Views_Version_Greater_Or_Equal */
	private $is_views_active;

	/** @var \Toolset_Condition_Plugin_Cred_Active */
	private $is_forms_active;

	private $page_builder_factory;

	private $page_builder_with_modules;

	public function __construct(
		\Toolset_Condition_Plugin_Views_Active $is_views_active = null,
		\Toolset_Condition_Plugin_Cred_Active $is_forms_active = null,
		PageBuilderModulesFactory $page_builder_factory = null,
		array $page_builder_with_modules = null
	) {
		$views_version_to_support_page_builder_modules = '2.6.2';

		$this->is_views_active = $is_views_active
			? $is_views_active
			: new \Toolset_Condition_Plugin_Views_Version_Greater_Or_Equal( $views_version_to_support_page_builder_modules );

		$this->is_forms_active = $is_forms_active
			? $is_forms_active
			: new \Toolset_Condition_Plugin_Cred_Active();

		$this->page_builder_factory = $page_builder_factory
			? $page_builder_factory
			: new PageBuilderModulesFactory();

		$this->page_builder_with_modules = $page_builder_with_modules
			? $page_builder_with_modules
			: array(
				handler\Elementor\ElementorModules::PAGE_BUILDER_NAME => new \Toolset_Condition_Plugin_Elementor_Active(),
				handler\Divi\DiviModules::PAGE_BUILDER_NAME           => new IsDiviThemeActive(),
			);
	}

	/**
	 * Initializes the Page Builder Modules Integration.
	 */
	public function load_modules() {
		if ( ! $this->maybe_should_load_page_builder_modules() ) {
			return;
		}

		foreach ( $this->page_builder_with_modules as $page_builder_name => $is_active_condition ) {
			if ( ! $is_active_condition->is_met() ) {
				// Do not even load the integration controller.
				continue;
			}

			$page_builder = $this->page_builder_factory->get_page_builder( $page_builder_name );
			if ( $page_builder ) {
				$page_builder->initialize();
			};
		}
	}

	/**
	 * Checks whether Toolset should load the Page Builder modules.
	 *
	 * @return bool
	 */
	private function maybe_should_load_page_builder_modules() {
		if (
			$this->is_views_active->is_met() ||
			$this->is_forms_active->is_met()
		) {
			return true;
		}

		return false;
	}
}
