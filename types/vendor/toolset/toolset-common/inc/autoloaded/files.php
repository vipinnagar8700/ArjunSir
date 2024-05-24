<?php

/**
 * Wrapper for a mockable access to file functions.
 *
 * The principle is similar to Toolset_Constants.
 *
 * Note: Use this *only* if you need it in unit tests!
 *
 * @since 2.5.7
 */
class Toolset_Files {


	/**
	 * is_file()
	 *
	 * @link http://php.net/manual/en/function.is-file.php
	 * @param string $path
	 * @return bool
	 */
	public function is_file( $path ) {
		return is_file( $path );
	}


	/**
	 * is_dir()
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function is_dir( $path ) {
		return is_dir( $path );
	}


	/**
	 * is_readable()
	 *
	 * @link http://php.net/manual/en/function.is-readable.php
	 * @param $path
	 * @return bool
	 */
	public function is_readable( $path ) {
		return is_readable( $path );
	}


	/**
	 * is_writable()
	 *
	 * @link http://php.net/manual/en/function.is-writable.php
	 * @param string $path
	 * @return bool
	 */
	public function is_writable( $path ) {
		return is_writeable( $path );
	}


	/**
	 * fopen()
	 *
	 * @link http://php.net/manual/en/function.fopen.php
	 * @param string $path
	 * @param string $mode
	 * @return bool|resource
	 */
	public function fopen( $path, $mode ) {
		return fopen( $path, $mode );
	}


	/**
	 * fclose()
	 *
	 * @link http://php.net/manual/en/function.fclose.php
	 * @param resource $handle
	 * @return bool
	 */
	public function fclose( $handle ) {
		return fclose( $handle );
	}


	/**
	 * Extract provided context variable, include the file and return the output.
	 *
	 * @param string $filename
	 * @param array $context_vars
	 *
	 * @return string
	 */
	public function get_include_file_output( $filename, $context_vars = array() ) {
		ob_start();
		extract( $context_vars );
		include $filename;
		$output = ob_get_clean();
		return $output;
	}


	/**
	 * file_get_contents()
	 *
	 * @link http://php.net/manual/en/function.file-get-contents.php
	 * @param string $filename
	 * @return bool|string
	 */
	public function file_get_contents( $filename ) {
		return file_get_contents( $filename );
	}


	/**
	 * file_put_contents()
	 *
	 * @link http://php.net/manual/en/function.file-put-contents.php
	 * @param string $filename
	 * @param string $contents
	 * @return bool|int
	 */
	public function file_put_contents( $filename, $contents ) {
		return file_put_contents( $filename, $contents );
	}


	/**
	 * file_exists()
	 *
	 * @link http://php.net/manual/en/function.file-exists.php
	 * @param string $filename
	 * @return bool
	 */
	public function file_exists( $filename ) {
		return file_exists( $filename );
	}


	/**
	 * touch()
	 *
	 * @link http://php.net/manual/en/function.touch.php
	 * @param string $filename
	 * @return bool
	 */
	public function touch( $filename ) {
		return touch( $filename );
	}


	/**
	 * unlink()
	 *
	 * @link http://php.net/manual/en/function.unlink.php
	 * @param string $filename
	 * @return bool
	 */
	public function unlink( $filename ) {
		return unlink( $filename );
	}

	/**
	 * @unlink()
	 *
	 * @link http://php.net/manual/en/function.unlink.php
	 * @param string $filename
	 * @return bool
	 */
	public function unlink_silent( $filename ) {
		return @unlink( $filename );
	}

	/**
	 * include
	 *
	 * @link http://php.net/manual/en/function.include.php
	 *
	 * @param $filename
	 *
	 * @return mixed
	 */
	public function include_file( $filename ) {
		return include $filename;
	}


	/**
	 * mkdir
	 *
	 * @param string $pathname
	 *
	 * @param int $mode
	 * @param bool $recursive
	 *
	 * @return bool
	 */
	public function mkdir( $pathname, $mode = null, $recursive = false ) {
		if( null === $mode ) {
			$mode = $mode & ~umask();
		}
		return mkdir( $pathname , $mode, $recursive );
	}

}
