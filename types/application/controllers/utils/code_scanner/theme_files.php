<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

use OTGS\Toolset\Common\Utils\PhpIteratorFactory;


/**
 * Scan currently activated theme's PHP files for a provided set of patterns.
 *
 * If a child theme is active, scans both the parent and the child.
 *
 * @since 2.3-b5
 */
class ThemeFiles implements Scanner {


	/** @var Pattern[] */
	private $patterns;


	/** @var Factory */
	private $factory;


	private $php_iterator_factory;


	/**
	 * Scanner constructor.
	 *
	 * @param Pattern[] $patterns
	 * @param Factory $factory
	 * @param PhpIteratorFactory|null $php_iterator_factory_di
	 */
	public function __construct( $patterns, Factory $factory, PhpIteratorFactory $php_iterator_factory_di = null ) {
		$this->patterns = $patterns;
		$this->factory = $factory;
		$this->php_iterator_factory = $php_iterator_factory_di ? : new PhpIteratorFactory();
	}


	/**
	 * @return Result[]
	 */
	public function scan() {
		$parent_theme_dir = get_template_directory();
		$results = $this->scan_files( $parent_theme_dir );

		// Check for the child theme as well.
		$child_theme_dir = get_stylesheet_directory();
		if ( $parent_theme_dir !== $child_theme_dir ) {
			$child_theme_results = $this->scan_files( $child_theme_dir );
			$results = array_merge( $results, $child_theme_results );
		}

		return $results;
	}


	/**
	 * Scan all PHP files in a given directory.
	 *
	 * @param string $directory
	 *
	 * @return Result[]
	 */
	private function scan_files( $directory ) {
		$php_files_iterator = $this->php_iterator_factory->create( $directory );

		$results = array();
		foreach ( $php_files_iterator as $file_info ) {
			$file_scanner = $this->factory->file( $this->patterns, array_pop( $file_info ), get_theme_root() );
			$results_for_file = $file_scanner->scan();
			$results = array_merge( $results, $results_for_file );
		}

		return array_map( static function ( Result $result ) {
			$result->set_domain( __( 'Theme files', 'wpcf' ) );

			return $result;
		}, $results );
	}
}
