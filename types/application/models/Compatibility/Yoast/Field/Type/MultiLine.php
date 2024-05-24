<?php


namespace OTGS\Toolset\Types\Compatibility\Yoast\Field\Type;

use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;

/**
 * Class SingleLine
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field\Type
 *
 * @since 3.1
 */
class MultiLine extends AField {
	protected $type = 'multi-line';

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions() {
		return array(
			'raw' => __( 'Raw', 'wpcf' ),
			'p'   => __( 'Paragraph', 'wpcf' ),
			'h1'  => __( 'Headline 1', 'wpcf' ),
			'h2'  => __( 'Headline 2', 'wpcf' ),
			'h3'  => __( 'Headline 3', 'wpcf' ),
			'h4'  => __( 'Headline 4', 'wpcf' ),
			'h5'  => __( 'Headline 5', 'wpcf' ),
			'h6'  => __( 'Headline 6', 'wpcf' ),
		);
	}

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs() {
		return 'raw';
	}
}