<?php

namespace OTGS\Toolset\Types\Controller\Compatibility;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\Repository as YoastFieldRepository;
use OTGS\Toolset\Types\Compatibility\Yoast\View\GroupEdit as YoastViewGroupEdit;
use OTGS\Toolset\Types\Compatibility\Yoast\View\PostEdit as YoastViewPostEdit;

/**
 * Class Yoast
 *
 * @package OTGS\Toolset\Types\Controller\Compatibility
 *
 * @since 3.x
 */
class Yoast {
	/**
	 * @return bool
	 */
	public function dependenciesLoaded() {
		if ( ! defined( 'TOOLSET_TYPES_YOAST' ) || ! TOOLSET_TYPES_YOAST ) {
			// yoast not active, no need to load integration
			return false;
		}

		return true;
	}

	/**
	 * Injects our fields to Yoast Analyzer
	 *
	 * @hook load-post.php (Post Edit Page)
	 *
	 * @param YoastFieldRepository $field_repository
	 * @param YoastViewPostEdit $view
	 */
	public function postEditScreen(
		YoastFieldRepository $field_repository,
		YoastViewPostEdit $view
	) {
		if( ! $this->dependenciesLoaded() || ! isset( $_GET['post'] ) ) {
			return;
		}

		// pass fields to frontend
		foreach( $field_repository->getFieldsByPost( $_GET['post'] ) as $field ) {
			$view->addField( $field );
		};

		// hook frontend scripts loading to admin_enqueue_scripts
		add_action( 'admin_enqueue_scripts', function() use( $view ) {
			$view->enqueueScripts();
		}, 11 );
	}


	/**
	 * Apply options to each field on the Field Group Edit page
	 * to configure how the field should be treated by Yoast Analyser
	 *
	 * @hook load-toolset_page_wpcf-edit (Field Group Edit page)
	 *
	 * @param YoastViewGroupEdit $view
	 */
	public function groupEditScreen( YoastViewGroupEdit $view ) {
		if( ! $this->dependenciesLoaded() ) {
			return;
		}

		add_filter( 'wpcf_form_field', array( $view, 'addYoastSettingsToFieldGUI' ), 20, 3 );
	}
}
