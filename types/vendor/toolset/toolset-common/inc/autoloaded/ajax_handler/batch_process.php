<?php

/**
 * Generic AJAX callback handler for batch processes.
 *
 * The process runs in phases (starting from 1) and within each phase, it can have an arbitrary number of steps (starting from 0).
 *
 * In each call, following POST variables are expected:
 * - phase (int), default is 1
 * - step (int), default is 0
 * - options (array), arbitrary options specific to the particular batch operation
 *
 * This handler parses this input and calls the (abstract) do_step() method which is supposed to perform the action and return
 * certain results. Then, it builds an AJAX response and sends it.
 *
 * Check do_step() for further details.
 *
 * The response has the following structure:
 * - message (string): Log output from the current step. The separator can be adjusted by overriding get_result_message_separator().
 * - continue (bool): True if this is not the last AJAX call that is supposed to happen.
 * - previous_phase (int): Number of the previous phase (the one passed in $_POST['phase']), to allow the client side process the result of this call.
 * - status (string): success|warning|error. Result of the current step. "warning" will be sent if there are errors in the do_step() results but
 *     no $is_fatal_error flag. If that flag is set to true, "error" will be returned (which means the batch process has failed completely).
 * - ajax_arguments (array):
 *     Arguments that should be passed to the next step as POST variables. Values of "phase" and "options" can be adjusted by do_step().
 *     If the new phase value is different from previous_phase, step will be reset to 0, otherwise it will be incremented.
 *     - step
 *     - phase
 *     - options
 *
 * This AJAX handler is especially designed to work in conjunction with the BatchProcessDialog.js mixin from the Toolset GUI Base.
 * Using it in other contexts is not recommended.
 *
 * @since 3.0.5
 */
abstract class Toolset_Ajax_Handler_Batch_Process extends Toolset_Ajax_Handler_Abstract {


	/** @var int If the batch process is handling some items, this is the default number of items per batch. */
	const DEFAULT_BATCH_SIZE = 500;


	/**
	 * Processes the Ajax call.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()->ajax_begin(
			array( 'nonce' => $this->get_nonce_name() )
		);

		$this->on_start();

		$step_number = (int) toolset_getarr( $_POST, 'step', 0 );
		$options = toolset_ensarr( toolset_getarr( $_POST, 'options' ) );

		// If this is set to false, the migration process halts (there will not be another AJAX call)
		$continue = true;

		$current_phase = (int) toolset_getarr( $_POST, 'phase', 1 );

		// Phase for the next AJAX call
		$next_phase = $current_phase;
		$is_fatal_error = false;

		$results = new Toolset_Result_Set();
		$results->add( $this->do_step(
			$current_phase, $step_number, $options,$continue, $next_phase, $is_fatal_error
		) );
		$continue = $continue && ! $is_fatal_error;

		$result_status = 'success';
		if( ! $results->is_complete_success() ) {
			// Something went wrong but we can still continue.
			$result_status = 'warning';
		}
		if( $is_fatal_error ) {
			// Something went wrong and the process cannot continue anymore,
			$result_status = 'error';
		}

		// Reset the next step if the current phase is over.
		$next_step = ( $current_phase === $next_phase ? $step_number + 1 : 0 );

		$this->on_end();

		$this->ajax_finish(
			array(
				'message' => $results->concat_messages( $this->get_result_message_separator() ),
				'continue' => $continue,
				'previous_phase' => $current_phase,
				'status' => $result_status,
				'ajax_arguments' => array(
					'step' => $next_step,
					'phase' => $next_phase,
					// Pass the options so that they're available for all migration steps.
					'options' => $options
				)
			),
			true
		);
	}


	/**
	 * Will be called on each AJAX call, even before processing the batch info.
	 *
	 * Can be used for validation and an early exit.
	 *
	 * @return void
	 */
	protected function on_start() { }


	/**
	 * Will be called on each AJAX call, directly before sending the response.
	 *
	 * Can be used for cleanup, for example.
	 *
	 * @return void
	 */
	protected function on_end() { }


	/**
	 * Get name of the nonce (usually it's the name of the AJAX action as defined in Toolset_Ajax or its subclass).
	 *
	 * @return string
	 */
	abstract protected function get_nonce_name();


	/**
	 * Perform a single step of the batch process.
	 *
	 * @param int $phase Current phase.
	 * @param int $step_number Current step.
	 * @param array &$options Options of the batch process. Can be modified, and will be used for the next AJAX call.
	 * @param bool &$continue If this is set to false, there will be no next AJAX call.
	 * @param int &$next_phase This can be used to set the phase of the next AJAX call.
	 * @param bool $is_fatal_error Indicate whether errors created by this call (if there are any) are fatal and should
	 *     terminate the whole batch process. If there are no errors, it has no significance.
	 *
	 * @return Toolset_Result|Toolset_Result_Set One or more results of this step. If they have a message, it should be
	 *     one that can be displayed to the users directly.
	 */
	abstract protected function do_step( $phase, $step_number, &$options, &$continue, &$next_phase, &$is_fatal_error );


	/**
	 * Get a separator to be used for result messages.
	 *
	 * @return string
	 */
	protected function get_result_message_separator() {
		return "\n> ";
	}


	/**
	 * Get a default (filtered) batch size.
	 *
	 * @return int
	 */
	protected function get_default_batch_size() {
		/**
		 * toolset_batch_process_size
		 *
		 * Allows overriding a default size of the batch, if applicable.
		 *
		 * @param int Number of items to process in one call.
		 */
		return (int) max(
			(int) apply_filters( 'toolset_batch_process_size', self::DEFAULT_BATCH_SIZE ),
		1
		);
	}

}