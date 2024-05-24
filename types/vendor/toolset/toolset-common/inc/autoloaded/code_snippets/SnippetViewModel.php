<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Viewmodel for a Snippet. Responsible for translating data from the JavaScript model and back.
 *
 * @since 3.0.8
 */
class SnippetViewModel {


	/** @var Snippet */
	private $snippet;


	/** @var Repository */
	private $repository;


	/** @var bool */
	private $is_decorated = false;


	/** @var string|null */
	private $previous_slug;


	/** @var CodeAccessFactory */
	private $code_access_factory;


	/** @var CodeAccess|null */
	private $_code_access;


	/**
	 * SnippetViewModel constructor.
	 *
	 * @param Snippet $snippet
	 * @param Repository $repository
	 * @param CodeAccessFactory $code_access_factory
	 */
	public function __construct( Snippet $snippet, Repository $repository, CodeAccessFactory $code_access_factory ) {
		$this->snippet = $snippet;
		$this->repository = $repository;
		$this->code_access_factory = $code_access_factory;
	}


	/**
	 * @return CodeAccess
	 */
	private function get_code_access() {
		if( null === $this->_code_access ) {
			$this->_code_access = $this->code_access_factory->create( $this->snippet );
		}
		return $this->_code_access;
	}


	private function decorate_snippet() {
		if( $this->is_decorated ) {
			return;
		}

		$this->get_code_access()->decorate_snippet();

		$this->is_decorated = true;
	}


	/**
	 * Translate the snippet to a JavaScript model array.
	 *
	 * @return array
	 */
	public function to_array() {
		$this->decorate_snippet();

		$result = array(
			'slug' => $this->snippet->get_slug(),
			'displayName' => $this->snippet->get_name(),
			'description' => $this->snippet->get_description(),
			'isActive' => $this->snippet->is_active(),
			'isEditable' => $this->snippet->is_editable(),
			'filePath' => $this->snippet->get_absolute_file_path(),
			'code' => $this->snippet->get_code(),
			'runMode' => $this->snippet->get_run_mode(),
			'runContexts' => $this->snippet->get_run_contexts(),
			'lastError' => nl2br( esc_textarea( $this->snippet->get_last_error() ) ),
			'hasSecurityCheck' => $this->snippet->has_security_check(),
		);

		// This is needed if the slug has been changed, so that the client side (JS) is able to recognize the old
		// snippet viewmodel and update it.
		if( ! empty( $this->previous_slug ) ) {
			$result['previousSlug'] = $this->previous_slug;
		}

		return $result;
	}


	/**
	 * Update the snippet with the data coming from the client.
	 * Also updates the snippet file if the mode has been changed.
	 *
	 * @param array $model JS snippet model.
	 * @return \Toolset_Result_Set
	 */
	public function apply_array( $model ) {
		$results = new \Toolset_Result_Set();

		if( array_key_exists( 'slug', $model ) && $model['slug'] !== $this->snippet->get_slug() ) {
			$this->previous_slug = $model['slug']; // So that the JS listing viewmodel can identify the correct snippet viewmodel.
			$results->add( $this->repository->rename_snippet_slug( $this->snippet, $model['slug'] ) );
		}

		$this->snippet
			->set_name( sanitize_text_field( toolset_getarr( $model, 'displayName' ) ) )
			->set_is_active( 'true' === toolset_getarr( $model, 'isActive' ) )
			->set_run_mode( toolset_getarr( $model, 'runMode' ) )
			->set_run_contexts( toolset_ensarr( toolset_getarr( $model, 'runContexts' ) ) );

		// Not updating now, it has to be done after all snippets are processed, in the AJAX call handler.
		$this->repository->needs_option_update();

		if( $this->snippet->is_editable() ) {
			// We need this to be able to compare the existing code with the new value.
			$this->decorate_snippet();
			$model_code = stripslashes( toolset_getarr( $model, 'code' ) );

			if( $model_code !== $this->snippet->get_code() ) {

				$is_code_updated = $this->get_code_access()->update_snippet_code( $model_code );
				if( $is_code_updated ) {
					$results->add( true ); // We'll already have a message about the snippet being updated.
				} else {
					$results->add( false, sprintf( __( 'Unable to update the snippet file "%s".', 'wpv-views' ), $this->snippet->get_file_subpath() ) );
				}

				// Force snippet re-decoration next time when sending the snippet to the client, in order to read the updated description.
				$this->is_decorated = false;
			}
		}

		if( ! $results->has_errors() ) {
			$results->add( true, sprintf( __( 'Snippet "%s" has been updated.', 'wpv-views' ), $this->snippet->get_slug() ) );
		}

		return $results;
	}


	/**
	 * @return Snippet
	 */
	public function get_model() {
		return $this->snippet;
	}

}