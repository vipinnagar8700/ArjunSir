<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class URL
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class PostReferenceField extends AField {
	protected $type = 'post';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'link' => __( 'Link to the Post', 'wpcf' ),
			'link-nofollow' => __( 'Link to the Post (nofollow)', 'wpcf' )
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'link';
	}
}