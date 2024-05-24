<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class WYSIWYG
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class WYSIWYG extends AField {
	protected $type = 'wysiwyg';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'raw' => __( 'Raw (respects formatting of Visual Editor)', 'wpcf' )
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'raw';
	}
}