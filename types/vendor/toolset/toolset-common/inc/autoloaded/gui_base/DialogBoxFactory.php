<?php

namespace OTGS\Toolset\Common\GuiBase;

/**
 * Make the dialog boxes from the Toolset GUI base accessible easily.
 *
 * @since 3.0.8
 */
class DialogBoxFactory {


	/**
	 * DialogBoxFactory constructor.
	 *
	 * @param \Toolset_Gui_Base $gui_base
	 */
	public function __construct( \Toolset_Gui_Base $gui_base ) {
		$gui_base->init();
	}


	/**
	 * Gets a Twig dialog box instance
	 *
	 * @param string $dialog_id Unique ID (at least within the page) used to reference the dialog in JS.
	 * @param \OTGS\Toolset\Twig\Environment $twig_environment Prepared Twig environment.
	 * @param array $context Twig context for the dialog template.
	 * @param string $template_name Twig template name that will be recognized by the provided environment.
	 * @param bool $late_register_assets Whether to run late_register_assets() or not.
	 *
	 * @return \Toolset_Twig_Dialog_Box
	 * @since 2.3
	 */
	public function createTwigDialogBox( $dialog_id,  $twig_environment, $context, $template_name, $late_register_assets = true ) {
		return new \Toolset_Twig_Dialog_Box( $dialog_id, $twig_environment, $context, $template_name, $late_register_assets );
	}


	public function createTemplateDialogBox( $dialog_id, \IToolset_Output_Template $template, $context = array() ) {
		return new \Toolset_Template_Dialog_Box( $dialog_id, $template, $context );
	}

}
