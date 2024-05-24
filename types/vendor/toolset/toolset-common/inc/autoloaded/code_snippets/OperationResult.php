<?php

namespace OTGS\Toolset\Common\CodeSnippets;


use OTGS\Toolset\Common\Result\ResultInterface;


/**
 * Result of an operation on a single code snippet.
 *
 * @since 3.0.8
 */
class OperationResult {


	/** @var ResultInterface */
	private $result;


	/** @var SnippetViewModel|null */
	private $snippet_viewmodel;


	/** @var bool */
	private $is_deleted;


	/**
	 * OperationResult constructor.
	 *
	 * @param ResultInterface $result
	 * @param SnippetViewModel|null $snippet_viewmodel
	 * @param bool $is_deleted
	 */
	public function __construct( ResultInterface $result, SnippetViewModel $snippet_viewmodel = null, $is_deleted = false ) {
		$this->result = $result;
		$this->snippet_viewmodel = $snippet_viewmodel;
		$this->is_deleted = (bool) $is_deleted;
	}


	/**
	 * @return ResultInterface
	 */
	public function get_result() {
		return $this->result;
	}


	/**
	 * @return null|SnippetViewModel
	 */
	public function get_viewmodel() {
		return $this->snippet_viewmodel;
	}


	/**
	 * @return bool
	 */
	public function is_deleted() {
		return $this->is_deleted;
	}
}