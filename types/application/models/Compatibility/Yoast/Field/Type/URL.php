<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class URL
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class URL extends AField {
	protected $type = 'url';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'link' => __( 'Link', 'wpcf' ),
			'link-nofollow' => __( 'Link (nofollow)', 'wpcf' )
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'link';
	}
}