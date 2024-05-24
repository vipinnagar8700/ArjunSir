<?php

use OTGS\Toolset\Types\Ajax\Handler\MergeRelationships as mergeCommand;


/**
 * Batch process AJAX call to merge two one-to-many relationships into one many-to-many.
 *
 * The two relationships must have the same child post type, which will be turned into an intermediary of the new
 * relationship. All intermediary posts that are connected with both original relationships will generate an
 * association in the new relationships. At the end of this process, remaining intermediary posts will be removed
 * together with the two old relationships and all their associations.
 *
 * This call is being used in the MergeRelationships.js dialog from the Relationships page.
 *
 * @since 3.0.4
 */
class Types_Ajax_Handler_Merge_Relationships extends Toolset_Ajax_Handler_Batch_Process {


	const PHASE_SETUP = 1;

	const PHASE_MERGE_ASSOCIATIONS = 2;

	const PHASE_CLEANUP = 3;


	protected function on_start() {
		do_action( 'toolset_do_m2m_full_init' );
	}


	/**
	 * This process has three phases:
	 *
	 * 1. Validate the situation and create a new relationship.
	 * 2. Create associations in the new reationship.
	 * 3. Cleanup.
	 *
	 * See superclass for details about this method:
	 *
	 * @inheritdoc
	 *
	 * @param int $phase
	 * @param int $step_number
	 * @param array $options
	 * @param bool $continue
	 * @param int $next_phase
	 * @param bool $is_fatal_error
	 *
	 * @return Toolset_Result|Toolset_Result_Set
	 */
	protected function do_step( $phase, $step_number, &$options, &$continue, &$next_phase, &$is_fatal_error ) {

		$results = new Toolset_Result_Set();
		switch ( $phase ) {
			case self::PHASE_SETUP:
				/** @var mergeCommand\CreateNewDefinition $create_new_definition */
				$create_new_definition = toolset_dic_make( '\OTGS\Toolset\Types\Ajax\Handler\MergeRelationships\CreateNewDefinition' );
				$results->add( $create_new_definition->run( $options ) );
				$next_phase ++;
				$is_fatal_error = ! $results->is_complete_success();
				break;
			case self::PHASE_MERGE_ASSOCIATIONS:
				/** @var mergeCommand\MigrateAssociations $migrate_associations */
				$migrate_associations = toolset_dic_make( '\OTGS\Toolset\Types\Ajax\Handler\MergeRelationships\MigrateAssociations' );
				$step_results = $migrate_associations->run( $options, $step_number, $this->get_batch_size() );
				$results->add( $step_results );
				if ( $step_results->is_phase_complete() ) {
					$next_phase ++;
				}
				$is_fatal_error = false;
				break;
			case self::PHASE_CLEANUP:
				/** @var mergeCommand\Cleanup $cleanup */
				$cleanup = toolset_dic_make( '\OTGS\Toolset\Types\Ajax\Handler\MergeRelationships\Cleanup' );
				$results->add( $cleanup->run( $options ) );
				$is_fatal_error = ! $results->is_complete_success();
				$continue = false;
				break;
			default:
				throw new RuntimeException( 'Invalid batch process phase number.' );
		}

		return $results;
	}


	/**
	 * @return int
	 */
	private function get_batch_size() {
		/**
		 * types_relationship_merge_batch_size
		 *
		 * Allow overriding the batch size for the merge operation.
		 * We assume that the size is the same for all batches, otherwise some posts may be skipped.
		 *
		 * @param int $batch_size
		 * @return int
		 * @since 3.0.4
		 */
		$batch_size = max(
			(int) apply_filters( 'types_relationship_merge_batch_size', $this->get_default_batch_size() ),
			1
		);

		return (int) $batch_size;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	protected function get_nonce_name() {
		return Types_Ajax::CALLBACK_MERGE_RELATIONSHIPS;
	}
}