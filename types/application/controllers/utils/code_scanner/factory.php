<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;


class Factory {

	/**
	 * @param Pattern[] $patterns
	 * @param string $string
	 *
	 * @return StringScanner
	 */
	public function string_scanner( $patterns, $string ) {
		return new StringScanner( $patterns, $string );
	}


	/**
	 * @param Pattern[] $patterns
	 * @param string $filename
	 * @param string $path_prefix
	 *
	 * @return File
	 */
	public function file( $patterns, $filename, $path_prefix ) {
		return new File( $patterns, $filename, $path_prefix, $this );
	}


	/**
	 * @return Result
	 */
	public function result() {
		return new Result();
	}


	/**
	 * @param Pattern[] $patterns
	 *
	 * @return ThemeFiles
	 */
	public function theme_files( $patterns ) {
		return new ThemeFiles( $patterns, $this );
	}


	/**
	 * @param Pattern[] $patterns
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return PostContent
	 */
	public function post_content( $patterns, $limit, $offset ) {
		return new PostContent( $patterns, $limit, $offset, null, $this );
	}


	/**
	 * @param string $search_term
	 *
	 * @return StrposPattern
	 */
	public function strpos_pattern( $search_term ) {
		return new StrposPattern( $search_term, $this );
	}


	public function post_meta( $patterns, $meta_keys ) {
		return new PostMeta( $patterns, $meta_keys, null, $this );
	}


}