<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\View;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\IField;

/**
 * @since 3.1
 */
class PostEdit {

	/** @var IField[] */
	private $fields = array();


	/**
	 * @param IField $field
	 */
	public function addField( IField $field ) {
		$this->fields[] = $field;
	}


	/**
	 * Load scripts and pass field data
	 *
	 * @hook admin_enqueue_scripts
	 */
	public function enqueueScripts() {
		if ( empty( $this->fields ) ) {
			// no fields with YOAST configuration
			return;
		}

		wp_enqueue_script(
			'toolset-yoast-post',
			TYPES_RELPATH . '/public/js/compatibility/bundle.yoast.js',
			array( 'jquery' ),
			TYPES_VERSION,
			true
		);

		add_action( 'admin_print_scripts', array( $this, '_admin_print_scripts' ) );
	}


	public function _admin_print_scripts() {
		echo '<script id="types_yoast_data" type="text/plain">'
			. wp_json_encode(
				array(
					// 'bootstrap' => TYPES_RELPATH . '/public/yoast.bundle.js',
					'fields' => $this->fields,
				)
			)
			. '</script>';
	}
}
