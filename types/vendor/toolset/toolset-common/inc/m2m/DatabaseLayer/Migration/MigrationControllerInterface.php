<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration;

use OTGS\Toolset\Common\Result\ResultInterface;

/**
 * Represents a migration controller that can handle the switch between two different versions
 * of a database layer.
 *
 * @since 4.0
 */
interface MigrationControllerInterface {

	/**
	 * Identifiers of database layer modes which allow for this migration to run.
	 *
	 * @return string[]
	 */
	public function get_required_database_layer_modes();


	/**
	 * Identifier of the database layer mode that will be active after this migration.
	 *
	 * @return string
	 */
	public function get_target_database_layer_mode();


	/**
	 * True if migration can be started under current circumstances.
	 *
	 * @return bool
	 */
	public function can_migrate();


	/**
	 * Return the initial state that can be used in do_next_step() to begin the migration.
	 *
	 * @return MigrationStateInterface
	 */
	public function get_initial_state();


	/**
	 * Perform the next step of the migration, based on the current state, and
	 * return the updated state.
	 *
	 * May throw all sorts of exceptions when things go wrong.
	 *
	 * @param MigrationStateInterface $current_state
	 *
	 * @return MigrationStateInterface
	 * @throws \Exception
	 */
	public function do_next_step( MigrationStateInterface $current_state );


	/**
	 * Produce a correct migration state from the serialized string.
	 *
	 * May throw all sorts of exceptions when things go wrong.
	 *
	 * @param string $serialized
	 *
	 * @return MigrationStateInterface
	 * @throws \Exception
	 */
	public function unserialize_migration_state( $serialized );


	/**
	 * Determine if there are some data left after the migration that can be cleaned up.
	 *
	 * @return bool
	 */
	public function can_do_cleanup();


	/**
	 * Perform the after-migration cleanup.
	 *
	 * @return ResultInterface
	 */
	public function do_cleanup();


	/**
	 * Determine if it's possible to revert the database to the pre-migration state.
	 *
	 * @return bool
	 */
	public function can_do_rollback();


	/**
	 * Perform the rollback to the pre-migration state (assuming nothing relevant had changed on the site
	 * in the meantime).
	 *
	 * @return ResultInterface
	 */
	public function do_rollback();


	/**
	 * Check whether the whole migration can be performed during a single request.
	 *
	 * This will probably include some estimation based on the size of the data to process.
	 *
	 * @return bool
	 */
	public function can_migrate_in_one_shot();


	/**
	 * Perform the whole migration in one request (if possible).
	 *
	 * @return ResultInterface
	 */
	public function migrate_in_one_shot();
}
