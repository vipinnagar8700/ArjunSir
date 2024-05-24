<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Simple factory for Snippet viewmodels.
 *
 * @since 3.0.8
 */
class SnippetViewModelFactory {


	private $repository;

	private $code_access_factory;


	public function __construct(
		Repository $snippet_repository,
		CodeAccessFactory $code_access_factory
	) {
		$this->repository = $snippet_repository;
		$this->code_access_factory = $code_access_factory;
	}

	/**
	 * @param Snippet $snippet
	 *
	 * @return SnippetViewModel
	 */
	public function create( Snippet $snippet ) {
		return new SnippetViewModel( $snippet, $this->repository, $this->code_access_factory );
	}


}