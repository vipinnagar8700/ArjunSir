<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Code scanner interface.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 * @since 2.3-b5
 */
interface Scanner {


	/**
	 * @return Result[]
	 */
	public function scan();

}