<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Scan result.
 *
 * This holds details about a specific match.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 * @since 2.3-b5
 */
class Result {


	/** @var string */
	private $domain_name;

	/** @var string */
	private $location = '';

	/** @var string */
	private $occurence;

	/** @var string */
	private $pattern;


	/**
	 * Transform the result information into an associative array, to be passed to the client side.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'domainName' => $this->domain_name,
			'location' => $this->location,
			'occurence' => $this->occurence
		);
	}


	/**
	 * @param string $pattern Pattern that has been matched.
	 */
	public function set_pattern( $pattern ) {
		$this->pattern = $pattern;
	}


	/**
	 * Prepend location information before the existing value.
	 *
	 * This is meant to be used when returning from a recursion - each level up means
	 * we probably have more information about the result than in the deeper level.
	 *
	 * @param string $location_to_prepend
	 */
	public function prepend_location( $location_to_prepend ) {
		$this->location = $location_to_prepend . ( empty( $this->location ) ? '' : ' - ' . $this->location );
	}


	/**
	 * @param string $domain_name Domain where the result belongs to (e.g. post content, a file, etc.).
	 */
	public function set_domain( $domain_name ) {
		$this->domain_name = $domain_name;
	}


	/**
	 * @param string $occurence Specific occurence - the value that has caused the match. It may be just an excerpt.
	 */
	public function set_occurence( $occurence ) {
		$this->occurence = $occurence;
	}
}