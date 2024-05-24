<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration;

/**
 * Represents a particular migration step (that belongs to a specific migration controller).
 *
 * @since 4.0
 */
interface MigrationStepInterface {

	/**
	 * Get the unique identifier of this step.
	 *
	 * @return string
	 */
	public function get_id();


	/**
	 * Step number (for the purpose of understanding the progress by a GUI).
	 *
	 * Doesn't have to be unique or consecutive in all cases.
	 *
	 * @return int
	 */
	public function get_number();


	/**
	 * Perform the step based on the provided current state of the database.
	 *
	 * May throw all sorts of exceptions when things go wrong.
	 *
	 * @param MigrationStateInterface $previous_state Current state of the database.
	 *
	 * @return MigrationStateInterface Current state of the database after the step had been run.
	 * @throws \Exception
	 */
	public function run( MigrationStateInterface $previous_state );

}
