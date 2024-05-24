<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Represents a search pattern.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 */
interface Pattern {

	/**
	 * Search for the pattern in the provided string and return any results found.
	 *
	 * @param string $string
	 *
	 * @return Result[]
	 */
	public function apply( $string );

}