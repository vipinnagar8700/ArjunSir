<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class SingleLine
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class SingleLine extends AField {
	protected $type = 'single-line';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'raw' => __( 'Raw', 'wpcf' ),
			'p'   => __( 'Paragraph', 'wpcf' ),
			'h1'  => __( 'Heading 1', 'wpcf' ),
			'h2'  => __( 'Heading 2', 'wpcf' ),
			'h3'  => __( 'Heading 3', 'wpcf' ),
			'h4'  => __( 'Heading 4', 'wpcf' ),
			'h5'  => __( 'Heading 5', 'wpcf' ),
			'h6'  => __( 'Heading 6', 'wpcf' ),
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'raw';
	}
}