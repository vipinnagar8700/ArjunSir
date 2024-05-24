<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Command to delete an existing snippet (file and options).
 *
 * @since 3.0.8
 */
class DeleteCommand {


	/** @var Repository */
	private $repository;


	/** @var \Toolset_Files */
	private $files;


	/**
	 * DeleteCommand constructor.
	 *
	 * @param Repository $repository
	 * @param \Toolset_Files $files
	 */
	public function __construct( Repository $repository, \Toolset_Files $files ) {
		$this->repository = $repository;
		$this->files = $files;
	}


	/**
	 * Delete the provided snippet.
	 *
	 * @param SnippetViewModel $snippet_view_model
	 *
	 * @return OperationResult
	 */
	public function run( SnippetViewModel $snippet_view_model ) {
		$snippet = $snippet_view_model->get_model();
		$filename = $snippet->get_absolute_file_path();
		if( $this->files->file_exists( $filename ) ) {
			$is_deleted = $this->files->unlink( $filename );
		} else {
			$is_deleted = true;
		}

		if( ! $is_deleted ) {
			return new OperationResult( new \Toolset_Result( false, sprintf(
				__( 'Unable to delete snippet file "%s"', 'wpv-views' ), $filename
			) ) );
		}

		$result = $this->repository->remove( $snippet );

		if( $result->is_error() ) {
			return new OperationResult( new \Toolset_Result( false, sprintf(
				__( 'There has been a problem when deleting a snippet "%s".', 'wpv-views' ), $snippet->get_slug()
			) ) );
		}

		// Success - note the is_delete flag that makes all the difference.
		return new OperationResult( new \Toolset_Result( true, sprintf(
			__( 'Snippet "%s" has been successfully deleted.', 'wpv-views' ), $snippet->get_slug()
		)), $snippet_view_model, true );
	}
}