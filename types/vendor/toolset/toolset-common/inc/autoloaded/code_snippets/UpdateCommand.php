<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Command to update a snippet.
 *
 * After update, it can also optionally try running it and checking for errors.
 *
 * @since 3.0.8
 */
class UpdateCommand {

	/**
	 * @param SnippetViewModel $view_model
	 * @param array $model JS model data
	 *
	 * @return OperationResult
	 */
	public function run( SnippetViewModel $view_model, $model ) {
		$results = $view_model->apply_array( $model );

		if( 'true' === toolset_getarr( $model, 'runNow' ) ) {
			// The snippet file has probably just been updated.
			if( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $view_model->get_model()->get_absolute_file_path(), true );
			}

			$run_result = $view_model->get_model()->run();

			$view_model->get_model()->set_last_error( $run_result->is_error() ? $run_result->get_message() : '' );
			if( $run_result->is_success() ) {
				$results->add( true, __( 'The snippet has been re-run without errors.', 'wpv-views' ) );
			} else {
				$results->add( false, __( 'There was error when trying to re-run the snippet:', 'wpv-views' ) . $run_result->get_message() );
			}
		}

		return new OperationResult( $results, $view_model );
	}
}