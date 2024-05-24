<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\Field;

/**
 * Interface IField
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field
 *
 * @since 3.1
 */
interface IField {
	/**
	 * @param string $slug
	 */
	public function setSlug( $slug );

	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @param string $input_name
	 */
	public function setInputName( $input_name );

	/**
	 * @param string $display_as
	 */
	public function setDisplayAs( $display_as );

	/**
	 * @return string[]
	 */
	public function getDisplayAsOptions();

	/**
	 * @return string
	 */
	public function getDefaultDisplayAs();
}