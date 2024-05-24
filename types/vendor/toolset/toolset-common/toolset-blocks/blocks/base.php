<?php

/**
 * Class Toolset_Gutenberg_Block
 */
abstract class Toolset_Gutenberg_Block implements Toolset_Gutenberg_Block_Interface {
	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * @var Toolset_Assets_Manager
	 */
	protected $toolset_assets_manager;

	/**
	 * @var false|Toolset_Ajax
	 */
	protected $toolset_ajax_manager;

	/**
	 * @var Toolset_Condition_Plugin_Types_Active
	 */
	protected $types_active;

	/**
	 * @var Toolset_Condition_Plugin_Views_Active
	 */
	protected $views_active;

	/**
	 * @var Toolset_Condition_Plugin_Cred_Active
	 */
	protected $cred_active;
	
	/**
	 * Toolset_Gutenberg_Block constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 * @param Toolset_Assets_Manager|null $toolset_assets_manager
	 * @param Toolset_Ajax|null $toolset_ajax_manager
	 * @param Toolset_Condition_Plugin_Types_Active|null $types_active
	 * @param Toolset_Condition_Plugin_Views_Active|null $views_active
	 * @param Toolset_Condition_Plugin_Cred_Active|null $cred_active
	 */
	public function __construct(
		\Toolset_Constants $constants = null,
		\Toolset_Assets_Manager $toolset_assets_manager = null,
		\Toolset_Ajax $toolset_ajax_manager = null,
		\Toolset_Condition_Plugin_Types_Active $types_active = null,
		\Toolset_Condition_Plugin_Views_Active $views_active = null,
		\Toolset_Condition_Plugin_Cred_Active $cred_active = null
	) {
		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();

		$this->toolset_assets_manager = $toolset_assets_manager
			?: \Toolset_Assets_Manager::get_instance();

		$this->toolset_ajax_manager = $toolset_ajax_manager
			?: \Toolset_Ajax::get_instance();

		$this->types_active = $types_active
			?: new Toolset_Condition_Plugin_Types_Active();

		$this->views_active = $views_active
			?: new Toolset_Condition_Plugin_Views_Active();

		$this->cred_active = $cred_active
			?: new Toolset_Condition_Plugin_Cred_Active();
	}
}
