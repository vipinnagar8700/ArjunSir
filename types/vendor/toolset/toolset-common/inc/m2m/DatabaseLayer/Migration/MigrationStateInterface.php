<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration;

use OTGS\Toolset\Common\Result\ResultInterface;

/**
 * Represents a state of the database before, during or after a specific migration.
 *
 * It must contain all the necessary information so that the next migration step can
 * be performed.
 *
 * @since 4.0
 */
interface MigrationStateInterface extends \Serializable {

	/**
	 * Store the previous step that had been performed.
	 *
	 * May be relevant for the next step in some cases.
	 *
	 * @param string $step_identifier Unique identifier of the previous step.
	 * @param int $step_number
	 *
	 * @return void
	 */
	public function set_previous_step( $step_identifier, $step_number );


	/**
	 * Store a value representing progress that will be relevant for the next step.
	 *
	 * It may be, for example, the number of processed items.
	 *
	 * @param int $progress_value
	 *
	 * @return void
	 */
	public function set_progress( $progress_value );


	/**
	 * Return the progress value if it has been set.
	 *
	 * @return int|null
	 */
	public function get_progress();


	/**
	 * Define the step that needs to be performed next.
	 *
	 * @param string $step_identifier Unique identifier of the migration step.
	 *
	 * @return void
	 */
	public function set_next_step( $step_identifier, $step_number );


	/**
	 * Get the migration step that needs to be performed next.
	 *
	 * @return string Unique identifier of a migration step.
	 */
	public function get_next_step();


	/**
	 * Return the number of the previous step if available.
	 *
	 * @return int|null
	 */
	public function get_previous_step_number();


	/**
	 * Return the number of the next step if available.
	 *
	 * @return int
	 */
	public function get_next_step_number();


	/**
	 * Set the result of a previous step.
	 *
	 * @param ResultInterface $result
	 *
	 * @return void
	 */
	public function set_result( ResultInterface $result );


	/**
	 * Get the result of the previous step.
	 *
	 * If there has been no previous step, a success result should be returned.
	 *
	 * @return ResultInterface
	 */
	public function get_result();


	/**
	 * Determine whether there is a next migration step.
	 *
	 * @return bool
	 */
	public function can_continue();


	/**
	 * Get the number of the current substep (if the step has substeps).
	 *
	 * Value -1 indicates that substeps exist but the actual number is not known.
	 *
	 * @return int|null
	 */
	public function get_current_substep();


	/**
	 * Get the total number of substeps (if the step has substeps).
	 *
	 * Value -1 indicates that substeps exist but the actual number is not known.
	 *
	 * @return int|null
	 */
	public function get_substep_count();




	/**
	 * Set a custom scalar property.
	 *
	 * @param string $key
	 * @param string|int $value
	 */
	public function set_property( $key, $value );


	/**
	 * Get a custom property.
	 *
	 * @param string $key
	 *
	 * @return mixed|string|int|null
	 */
	public function get_property( $key );
}
