<?php

namespace OTGS\Toolset\Common\CodeSnippets;

/**
 * Factory for commands on snippet objects.
 *
 * @since 3.0.8
 */
class CommandFactory {


	/** @var Explorer */
	private $snippet_explorer;

	/** @var \Toolset_Files */
	private $files;

	/** @var SnippetBuilder */
	private $snippet_builder;

	/** @var Repository */
	private $snippet_repository;

	/** @var SnippetViewModelFactory */
	private $snippet_view_model_factory;


	/**
	 * CommandFactory constructor.
	 *
	 * @param Explorer $snippet_explorer
	 * @param \Toolset_Files $files
	 * @param SnippetBuilder $snippet_builder
	 * @param Repository $snippet_repository
	 * @param SnippetViewModelFactory $snippet_view_model_factory
	 */
	public function __construct(
		Explorer $snippet_explorer,
		\Toolset_Files $files,
		SnippetBuilder $snippet_builder,
		Repository $snippet_repository,
		SnippetViewModelFactory $snippet_view_model_factory
	) {
		$this->snippet_explorer = $snippet_explorer;
		$this->files = $files;
		$this->snippet_builder = $snippet_builder;
		$this->snippet_repository = $snippet_repository;
		$this->snippet_view_model_factory = $snippet_view_model_factory;
	}


	/**
	 * @return UpdateCommand
	 */
	public function update() {
		return new UpdateCommand();
	}


	/**
	 * @return CreateCommand
	 */
	public function create() {
		return new CreateCommand(
			$this->snippet_explorer, $this->files, $this->snippet_builder, $this->snippet_repository, $this->snippet_view_model_factory
		);
	}


	/**
	 * @return DeleteCommand
	 */
	public function delete() {
		return new DeleteCommand( $this->snippet_repository, $this->files );
	}
}