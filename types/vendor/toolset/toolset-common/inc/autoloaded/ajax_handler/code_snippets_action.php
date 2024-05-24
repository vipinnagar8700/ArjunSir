<?php

use OTGS\Toolset\Common\CodeSnippets\CommandFactory;
use OTGS\Toolset\Common\CodeSnippets\OperationResult;
use OTGS\Toolset\Common\CodeSnippets\SnippetBuilder;
use OTGS\Toolset\Common\CodeSnippets\Repository;
use OTGS\Toolset\Common\CodeSnippets\SnippetViewModel;
use OTGS\Toolset\Common\CodeSnippets\SnippetViewModelFactory;


/**
 * Handler for AJAX calls from the Customizations tab of Toolset Settings.
 *
 * @since 3.0.8
 */
class Toolset_Ajax_Handler_Code_Snippets_Action extends Toolset_Ajax_Handler_Abstract {


	/** @var SnippetBuilder */
	private $snippet_builder;


	/** @var Repository */
	private $snippet_repository;


	/** @var CommandFactory */
	private $command_factory;


	private $snippet_view_model_factory;


	/**
	 * Toolset_Ajax_Handler_Code_Snippets_Action constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param SnippetBuilder|null $snippet_builder
	 * @param Repository|null $snippet_repository
	 * @param CommandFactory|null $command_factory
	 * @param SnippetViewModelFactory $snippet_view_model_factory
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager, SnippetBuilder $snippet_builder, Repository $snippet_repository,
		CommandFactory $command_factory, SnippetViewModelFactory $snippet_view_model_factory
	) {
		parent::__construct( $ajax_manager );
		$this->snippet_builder = $snippet_builder;
		$this->snippet_repository = $snippet_repository;
		$this->command_factory = $command_factory;
		$this->snippet_view_model_factory = $snippet_view_model_factory;
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$am = $this->get_ajax_manager();

		$am->ajax_begin( array(
			'nonce' => $am->get_action_js_name( Toolset_Ajax::CALLBACK_CODE_SNIPPETS_ACTION ),
		) );

		$action_name = toolset_getpost( 'action_name' );
		$snippet_models = toolset_ensarr( toolset_getpost( 'snippets' ) );

		$results = new Toolset_Result_Set();
		$updated_snippets = array();
		$deleted_snippets = array();

		if( 'create' === $action_name ) {
			// Handling this separately because we don't want to instantiate the model and viewmodel classes before the
			// snippet is actually created. Also, this involves always only a single snippet.
			$snippet_model = reset( $snippet_models );
			$operation_result = $this->command_factory->create()->run( $snippet_model );
			$this->process_operation_result( $operation_result, $results, $deleted_snippets, $updated_snippets );
		} else {
			foreach ( $snippet_models as $snippet_model ) {
				$snippet = $this->snippet_repository->get( toolset_getarr( $snippet_model, 'originalSlug' ) );
				if ( null === $snippet ) {
					// If the snippet isn't known to the repository (which it doesn't have to be), create at least a model
					// from its file path and slug.
					$snippet = $this->snippet_builder->create_snippet(
						toolset_getarr( $snippet_model, 'filePath' ),
						array( 'slug' => toolset_getarr( $snippet_model, 'slug' ) )
					);
				}
				$viewmodel = $this->snippet_view_model_factory->create( $snippet );

				$operation_result = $this->single_snippet_action( $action_name, $viewmodel, $snippet_model );
				$this->process_operation_result( $operation_result, $results, $deleted_snippets, $updated_snippets );
			}
		}

		// This is necessary if any snippet settings have changed. We run update_option() only once, when the process is complete.
		$this->snippet_repository->maybe_update_option();

		$am->ajax_finish(
			array(
				'messages' => $results->get_messages(),
				'updated_snippets' => $updated_snippets,
				'deleted_snippets' => $deleted_snippets
			),
			$results->is_complete_success()
		);
	}


	/**
	 * Read the OperationResult object and update the set of results and arrays of updated and deleted snippets.
	 *
	 * @param OperationResult $operation_result Result of an operation on a single snippet.
	 * @param Toolset_Result_Set $results
	 * @param array[][] &$deleted_snippets Array of snippet data.
	 * @param array[][] &$updated_snippets Array of snippet data.
	 */
	private function process_operation_result( OperationResult $operation_result, Toolset_Result_Set $results, &$deleted_snippets, &$updated_snippets ) {
		$results->add( $operation_result->get_result() );
		if( null === $operation_result->get_viewmodel() ) {
			return;
		}

		if( $operation_result->is_deleted() ) {
			$deleted_snippets[] = $operation_result->get_viewmodel()->to_array();
		} else {
			$updated_snippets[] = $operation_result->get_viewmodel()->to_array();
		}
	}


	/**
	 * Choose the correct command for an action on a single snippet.
	 *
	 * @param string $action_name
	 * @param SnippetViewModel $snippet_viewmodel
	 * @param array $snippet_model Snippet model data coming from the client.
	 * @return OperationResult
	 */
	private function single_snippet_action( $action_name, SnippetViewModel $snippet_viewmodel, $snippet_model ) {
		switch( $action_name ) {
			case 'update':
				return $this->command_factory->update()->run( $snippet_viewmodel, $snippet_model );
			case 'delete':
				return $this->command_factory->delete()->run( $snippet_viewmodel );
		}

		return new OperationResult( new Toolset_Result( false, __( 'Unrecognized action name.', 'wpv-views' ) ), null );
	}

}