<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

/**
 * Class ToolsetElementorWidgetControlsBase
 *
 * The base class for the Toolset Elementor widget controls.
 *
 * @since 3.0.7
 */
abstract class ToolsetElementorWidgetControlsBase {
	protected $widget;

	/** @var \Toolset_Constants */
	protected $constants;

	/** @var \Toolset_Renderer */
	protected $toolset_renderer;

	/** @var \Toolset_Condition_Plugin_Elementor_Pro_Active */
	protected $is_elementor_pro_active;

	public function __construct(
		$widget,
		\Toolset_Constants $constants = null,
		\Toolset_Renderer $toolset_renderer = null,
		\Toolset_Condition_Plugin_Elementor_Pro_Active $is_elementor_pro_active = null
	) {
		$this->widget = $widget;

		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();

		$this->toolset_renderer = $toolset_renderer
			? $toolset_renderer
			: \Toolset_Renderer::get_instance();

		$this->is_elementor_pro_active = $is_elementor_pro_active
			? $is_elementor_pro_active
			: new \Toolset_Condition_Plugin_Elementor_Pro_Active();
	}
}
