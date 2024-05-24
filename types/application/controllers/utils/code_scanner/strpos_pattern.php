<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;


/**
 * Scan pattern that performs a simple matching with strpos().
 *
 * The provided string is broken into lines, so that line information can be provided in the result easily.
 *
 * @since 2.3-b5
 */
class StrposPattern implements Pattern {


	/** Maximum length of the occurence excerpt. */
	const MAX_OCCURENCE_LENGTH = 35;


	/** @var string */
	private $search_term;


	/** @var Factory */
	private $factory;


	/**
	 * StrposPattern constructor.
	 *
	 * @param string $search_term
	 * @param Factory $factory
	 */
	public function __construct( $search_term, Factory $factory ) {
		$this->search_term = $search_term;
		$this->factory = $factory;
	}


	/**
	 * @param string $string String where the pattern should be searched for.
	 *
	 * @return Result[]
	 */
	public function apply( $string ) {
		$lines = explode( "\n", $string );

		$results = array();

		foreach( $lines as $line_index => $line ) {
			$position_on_line = strpos( $line, $this->search_term );
			if( false !== $position_on_line ) {
				$result = $this->factory->result();
				$result->set_pattern( 'word: ' . $this->search_term );
				$result->set_occurence( substr( $line, max( 0, $position_on_line ), self::MAX_OCCURENCE_LENGTH ) );
				$result->prepend_location( sprintf( 'line %d, column %d', $line_index + 1, $position_on_line + 1 ) );
				$results[] = $result;
			}
		}

		return $results;
	}


	/**
	 * @return string
	 */
	public function get_search_term() {
		return $this->search_term;
	}

}
