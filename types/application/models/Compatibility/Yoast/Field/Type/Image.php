<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class Image
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class Image extends AField {
	protected $type = 'image';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'img' => __( 'Image', 'wpcf' )
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'img';
	}
}