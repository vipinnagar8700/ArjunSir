<?php

namespace OTGS\Toolset\Common\CodeSnippets;


use OTGS\Toolset\Common\Utils\RequestMode;

class SnippetFactory {


	private $files;
	private $constants;
	private $request_mode;

	public function __construct( \Toolset_Files $files, \Toolset_Constants $constants, RequestMode $request_mode ) {
		$this->files = $files;
		$this->constants = $constants;
		$this->request_mode = $request_mode;
	}

	public function create( $slug ) {
		return new Snippet( $slug, $this->files, $this->constants, $this->request_mode );
	}
}