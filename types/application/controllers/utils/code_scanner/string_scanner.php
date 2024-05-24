<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Scan a particular string for a set of provided scan patterns.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 * @since 2.3-b5
 */
class StringScanner implements Scanner {


	/** @var Pattern[] */
	private $patterns;


	/** @var string */
	private $string;


	/**
	 * Scanner constructor.
	 *
	 * @param Pattern[] $patterns
	 * @param string $string
	 */
	public function __construct( $patterns, $string ) {
		$this->patterns = $patterns;
		$this->string = $string;
	}


	/**
	 * @return Result[]
	 */
	public function scan() {
		$results = array();

		foreach( $this->patterns as $pattern ) {
			// Depending on the pattern, the way of scanning may differ.
			$results_for_pattern = $pattern->apply( $this->string );
			$results = array_merge( $results, $results_for_pattern );
		}

		return $results;
	}




}