<?php
namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Factory for the CodeAccess class.
 *
 * @since 3.0.8
 */
class CodeAccessFactory {


	/** @var \Toolset_Files */
	private $files;


	/**
	 * CodeAccessFactory constructor.
	 *
	 * @param \Toolset_Files $files
	 */
	public function __construct( \Toolset_Files $files ) {
		$this->files = $files;
	}


	/**
	 * @param Snippet $snippet
	 *
	 * @return CodeAccess
	 */
	public function create( Snippet $snippet ) {
		return new CodeAccess( $snippet, $this->files );
	}

}