<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Scan a single file for provided patterns.
 *
 * @since 2.3-b5
 */
class File implements Scanner {


	/** @var Pattern[] */
	private $patterns;


	/** @var string */
	private $filename;


	/** @var string */
	private $path_prefix;


	/** @var Factory */
	private $factory;


	/**
	 * Scanner constructor.
	 *
	 * @param Pattern[] $patterns
	 * @param string $filename
	 * @param string $path_prefix
	 * @param Factory $factory
	 */
	public function __construct( $patterns, $filename, $path_prefix, Factory $factory ) {
		$this->patterns = $patterns;
		$this->filename = $filename;
		$this->path_prefix = $path_prefix;
		$this->factory = $factory;
	}


	/**
	 * @return Result[]
	 */
	public function scan() {
		if( ! file_exists( $this->filename ) ) {
			return array();
		}

		$filename = $this->filename;
		$file_content = file_get_contents( $filename );
		if( empty( $file_content ) ) {
			return array();
		}

		$string_scanner = $this->factory->string_scanner( $this->patterns, $file_content );

		$that = $this;
		$results = array_map( function( Result $result ) use( $filename, $that ) {
			$result->prepend_location( 'file: ' .  $that->get_location( $filename ) );
			return $result;
		}, $string_scanner->scan() );

		return $results;
	}


	/**
	 * Remove the path prefix from the full file path (if it's in there) to make the output easier to read.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function get_location( $filename ) {
		if( substr( $filename, 0, strlen( $this->path_prefix ) ) === $this->path_prefix ) {
			return substr( $filename, strlen( $this->path_prefix ) );
		}

		return $filename;
	}

}