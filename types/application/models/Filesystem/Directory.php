<?php

namespace OTGS\Toolset\Types\Filesystem;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Toolset_Filesystem_Exception;

class Directory {

	private $path = false;


	/**
	 * Open file by path and stores it on success.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function open( $path ) {

		if ( ! is_dir( $path ) || ! is_readable( $path ) ) {
			return false;
		}

		$this->path = $path;

		return true;
	}


	/**
	 * @param string $filename
	 * @param bool $recursive
	 *
	 * @return array|bool|DirectoryIterator
	 * @throws Toolset_Filesystem_Exception
	 */
	public function find( $filename, $recursive = false ) {

		// skip if path is not set
		if ( ! $this->path ) {
			throw new Toolset_Filesystem_Exception( 'No directory is selected.' );
		}

		// if using recursive flag
		if ( $recursive ) {
			return $this->find_recursive( $filename );
		}

		// search in directory
		foreach ( new DirectoryIterator( $this->path ) as $file ) {
			// check current is not a directory and match with filename
			if ( $file->getFilename() === $filename && ! is_dir( $file->getRealPath() ) ) {
				return $file;
			}
		}

		// file not found in root directory
		return false;
	}


	/**
	 * @param string $filename
	 *
	 * @return array|bool
	 * @throws Toolset_Filesystem_Exception
	 */
	public function find_recursive( $filename ) {

		// skip if path is not set
		if ( ! $this->path ) {
			throw new Toolset_Filesystem_Exception( 'No directory is selected.' );
		}

		// use $this->path as directory
		$directory = new RecursiveDirectoryIterator( $this->path );

		$files_found = array();

		// search recursive in directory
		foreach ( new RecursiveIteratorIterator( $directory ) as $file ) {
			if ( $file->getFilename() === $filename && ! is_dir( $file->getRealPath() ) ) {
				$files_found[] = $file;
			}
		}

		if ( empty( $files_found ) ) {
			return false;
		}

		return $files_found;
	}
}

/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( Directory::class, 'Toolset_Filesystem_Directory' );
