<?php

namespace OTGS\Toolset\Types\Compatibility\Gutenberg\View;

/**
 * Class PostEdit
 * @package OTGS\Toolset\Types\Compatibility\Gutenberg\View
 *
 * @since 3.2
 */
class PostEdit {

	/**
	 * Load scripts and pass field data
	 *
	 * @hook admin_enqueue_scripts
	 */
	public function enqueueScripts() {
		wp_enqueue_script(
			'toolset-types-gutenberg',
			TYPES_RELPATH . '/public/js/compatibility/bundle.gutenberg.js',
			array( 'jquery', \Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER ),
			TYPES_VERSION,
			true
		);

		wp_localize_script( 'toolset-types-gutenberg', 'toolsetTypesGutenberg', array(
			'missingOrInvalidFieldData' => __( 'Validation failed. Please, provide correct values for all fields.', 'wpcf' )
		) );
	}
}
