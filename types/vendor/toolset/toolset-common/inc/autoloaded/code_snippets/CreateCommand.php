<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Command to create a new snippet file.
 *
 * @since 3.0.8
 */
class CreateCommand {


	const EMPTY_SNIPPET_CODE = "<?php\n/**\n * New custom code snippet (replace this with snippet description).\n */\n\ntoolset_snippet_security_check() or die( 'Direct access is not allowed' );\n\n// Put the code of your snippet below this comment.";
	const INDEX_PHP_CONTENTS = "<?php\n// Don't delete this file for security reasons.";


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
	 * CreateCommand constructor.
	 *
	 * @param Explorer $snippet_explorer
	 * @param \Toolset_Files $files
	 * @param SnippetBuilder $snippet_factory
	 * @param Repository $repository
	 * @param SnippetViewModelFactory $snippet_view_model_factory
	 */
	public function __construct(
		Explorer $snippet_explorer, \Toolset_Files $files, SnippetBuilder $snippet_factory,
		Repository $repository, SnippetViewModelFactory $snippet_view_model_factory
	) {
		$this->snippet_explorer = $snippet_explorer;
		$this->files = $files;
		$this->snippet_builder = $snippet_factory;
		$this->snippet_repository = $repository;
		$this->snippet_view_model_factory = $snippet_view_model_factory;
	}


	/**
	 * Create a new snippet.
	 *
	 * @param array $model Snippet model data coming from the client side. Needs to contain only the 'slug' element.
	 * @return OperationResult
	 */
	public function run( $model ) {
		$slug = toolset_getarr( $model, 'slug' );

		// If the client entered '.php' as a slug, which they shouldn't, we'll strip it in order to prevent a file
		// with extension .php.php. :)
		if( substr( $slug, -4 ) === '.php' ) {
			$slug = substr( $slug, 0, strlen( $slug ) - 4 );
		}
		$slug = sanitize_title( $slug );

		$subpath = '/' . $slug . '.php';

		$basedir = $this->snippet_explorer->get_base_directory();

		// Create a new snippet file
		//
		//
		$file_path = untrailingslashit( $basedir ) . $subpath;

		if( $this->files->file_exists( $file_path ) ) {
			return new OperationResult( new \Toolset_Result( false, sprintf(
				__( 'File "%s" already exists. Please choose a different slug for the snippet.', 'wpcf' ), $file_path
			) ) );
		}

		if( ! $this->files->file_exists( $basedir ) ) {
			$is_directory_created = $this->files->mkdir( $basedir, 0755, true );
			if( ! $is_directory_created ) {
				return new OperationResult( new \Toolset_Result( false, sprintf(
					__( 'Unable to create the base directory for code snippets "%s". Please check permissions on the server.', 'wp-views'),
					$basedir
				) ) );
			}
		}

		// Add index.php if missing.
		$this->prevent_directory_listing( $basedir );

		$is_created = $this->files->touch( $file_path );
		if( ! $is_created ) {
			return new OperationResult( new \Toolset_Result( false, sprintf(
				__( 'Unable to create file "%s". Please check permissions on the server.', 'wpcf' ), $file_path
			) ) );
		}

		$this->files->file_put_contents( $file_path, self::EMPTY_SNIPPET_CODE );

		// Create the model for the new file.
		//
		//
		$snippet = $this->snippet_builder->create_snippet( $file_path, new SnippetOption( $slug, $slug, $subpath, false ) );

		$result = $this->snippet_repository->insert( $snippet );

		if( ! $result->is_success() ) {
			return new OperationResult( $result );
		}

		$viewmodel = $this->snippet_view_model_factory->create( $snippet );

		return new OperationResult( $result, $viewmodel );
	}


	/**
	 * Add index.php to the base directory if it's missing.
	 *
	 * At the moment, we don't allow subdirectories to be created, so we don't need to concern ourselves with preventing
	 * their listing. If anyone creates them manually, it's their responsibility.
	 *
	 * @param $basedir
	 * @since Types 3.1.2
	 */
	private function prevent_directory_listing( $basedir ) {
		$index_file = $basedir . '/index.php';

		if( $this->files->file_exists( $index_file ) ) {
			return;
		}

		$this->files->file_put_contents( $index_file, self::INDEX_PHP_CONTENTS );
	}
}
