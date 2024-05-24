<?php

namespace OTGS\Toolset\Common\CodeSnippets;


use OTGS\Toolset\Common\Utils\PhpIteratorFactory;


/**
 * Snippet explorer.
 *
 * Recursively scans the snippet directory and provides a list of snippet files.
 * Also exposes information about the directory itself.
 *
 * @since 3.0.8
 */
class Explorer {


	/** @var PhpIteratorFactory */
	private $php_iterator_factory;


	/** @var \Toolset_Constants */
	private $constants;


	/** @var \Toolset_Files */
	private $files;


	/**
	 * Explorer constructor.
	 *
	 * @param PhpIteratorFactory $php_iterator_factory
	 * @param \Toolset_Constants $constants
	 * @param \Toolset_Files $files
	 */
	public function __construct( PhpIteratorFactory $php_iterator_factory, \Toolset_Constants $constants, \Toolset_Files $files ) {
		$this->php_iterator_factory = $php_iterator_factory;
		$this->constants = $constants;
		$this->files = $files;
	}


	/**
	 * Get the absolute path to the main code snippet directory.
	 *
	 * @return string
	 */
	public function get_base_directory() {
		return untrailingslashit( $this->constants->constant( 'WP_CONTENT_DIR' ) ) . DIRECTORY_SEPARATOR . 'toolset-customizations';
	}


	/**
	 * Check that a filename belongs to the code snippet directory.
	 *
	 * @param string $path Absolute path of the file.
	 * @return bool
	 */
	public function is_in_supported_directory( $path ) {
		$base_directory = wp_normalize_path( $this->get_base_directory() );
		return ( substr( wp_normalize_path( $path ), 0, strlen( $base_directory ) ) === $base_directory );
	}


	/**
	 * Get a subpath relative to the base code snippet directory.
	 *
	 * @param string $path Absolute path of a file
	 * @return bool|string False if the file doesn't belong inside the base code snippet directory, subpath otherwise.
	 */
	public function get_subpath( $path ) {
		return substr( $path, strlen( $this->get_base_directory() ) );
	}


	/**
	 * Get absolute paths of all PHP files inside the code snippet directory (and its subdirectories).
	 *
	 * @return string[]
	 */
	public function get_all_paths() {
		$basedir = $this->get_base_directory();

		if( ! $this->files->file_exists( $basedir ) || ! $this->files->is_dir( $basedir ) ) {
			return array();
		}

		$php_files_iterator = $this->php_iterator_factory->create( $basedir, true );

		$results = array();
		foreach( $php_files_iterator as $file_info ) {
			$file_path = array_pop( $file_info );
			if( 'index.php' === strtolower( substr( $file_path, strlen( $file_path ) - 9 ) ) ) {
				// Always ignore index.php files.
				continue;
			}
			$results[] = $file_path;
		}

		return $results;
	}

}
