<?php

namespace OTGS\Toolset\Types\Post\Import\Association\View;

/**
 * Class Page
 * @package OTGS\Toolset\Types\Post\Import\Association\View
 *
 * @since 3.0
 */
class Page {
	/** @var \Types_Helper_Twig  */
	private $twig;

	/**
	 * Page constructor.
	 *
	 * @action load-toolset_page_toolset-export-import
	 * @param \Types_Helper_Twig $twig
	 */
	public function __construct( \Types_Helper_Twig $twig ) {
		$this->twig = $twig;

		// register page on Toolset Export/Import
		add_filter( 'toolset_filter_register_export_import_section', array( $this, 'registerPage' ) );

		// scripts loading
		$this->registerScripts();
	}

	/**
	 * Load page on Toolset Import/Export menu
	 *
	 * @filter toolset_filter_register_export_import_section
	 *
	 * @param array $sections
	 *
	 * @return mixed
	 */
	public function registerPage( $sections ) {
		$sections['associations'] = array(
			'slug'      => 'associations',
			'title'     => __( 'Associations', 'textdomain' ),
			'icon'      => '<i class="icon-types-logo ont-icon-16"></i>',
			'items'     => array(
				'import'    => array (
					'slug'   => 'associations_import',
					'title'  => __( 'Import', 'textdomain' ),
					'callback' => array( $this, 'render' )
				),
			)
		);

		return $sections;
	}

	/**
	 * Scripts (and Styles)
	 */
	public function registerScripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scriptsAndStyles' ) );
	}

	/**
	 * Render Import Section
	 */
	public function render() {
		echo $this->twig->render(
			'/page/extension/associations-import/dialog.twig',
			array(
				'url_self' => admin_url( 'admin.php?page=toolset-export-import&tab=associations' ),
				'url_wp_export' => admin_url( 'export.php' )
			)
		);
	}

	public function scriptsAndStyles() {
		$handle = 'types-associations-import';

		// style
		wp_enqueue_style(
			$handle,
			TYPES_RELPATH . '/public/page/extension/associations-import/associations-import-dialog.css',
			array(
				\Toolset_Gui_Base::STYLE_GUI_BASE
			),
			TYPES_VERSION
		);

		// script
		wp_enqueue_script(
			$handle,
			TYPES_RELPATH . '/public/page/extension/associations-import/associations-import-dialog.js',
			array(
				\Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER,
				\Types_Asset_Manager::SCRIPT_KNOCKOUT_MAPPING,
			),
			TYPES_VERSION,
			true
		);

		wp_localize_script(
			$handle,
			'types_page_extension_associations_import_dialog',
			array(
				'ajax' => array(
					'action' => \Types_Ajax::get_instance()->get_action_js_name( \Types_Ajax::CALLBACK_ASSOCIATIONS_IMPORT ),
					'nonce' => wp_create_nonce( \Types_Ajax::CALLBACK_ASSOCIATIONS_IMPORT ),
				),
				'toolsetImportExport' => array(
					'activeTab' => isset( $_GET['tab'] ) ? $_GET['tab'] : 'none'
				)
			)
		);
	}
}