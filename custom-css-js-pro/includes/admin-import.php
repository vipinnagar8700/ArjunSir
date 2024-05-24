<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_Import
 */
class CustomCSSandJS_Import {

	/**
	 * Constructor
	 */
	public function __construct() {
		/* TODO: check if is_plugin_active returns false for multi-install networks */
		if ( ! is_plugin_active( 'wordpress-importer/wordpress-importer.php' ) ) {
			return;
		}
		add_action( 'import_end', array( $this, 'import_end' ) );
	}

	/**
	 * Add action for import_end
	 */
	function import_end() {
		ccj_build_search_tree();

		$this->create_files();
	}

	/**
	 * Create the files when the codes are imported
	 */
	function create_files() {

		// Retrieve all the custom-css-js codes
		$posts = query_posts( 'post_type=custom-css-js&post_status=publish&nopaging=true' );

		$tree = array();
		foreach ( $posts as $_post ) {
			$options = ccj_get_options( $_post->ID );

			$filename = $_post->ID . '.' . $options['language'];
			ccj_save_code_file( $_post->post_content, $options, $filename, $_post->ID );
		}

	}


}

return new CustomCSSandJS_Import();
