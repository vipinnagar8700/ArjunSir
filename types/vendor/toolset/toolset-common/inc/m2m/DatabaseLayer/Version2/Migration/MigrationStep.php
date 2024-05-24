<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStepInterface;

/**
 * Standard migration step for \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\MigrationController.
 *
 * @since 4.0
 */
abstract class MigrationStep implements MigrationStepInterface {


	const STEP_NUMBER = 0;

	const STORAGE_KEY_PREFIX = '_toolset_migration_step_state_%s';


	/**
	 * @inheritDoc
	 * @return string
	 */
	public function get_id() {
		return get_class( $this );
	}


	public function get_number() {
		return static::STEP_NUMBER;
	}


	protected function validate_state( MigrationStateInterface $migration_state ) {
		if( ! $migration_state instanceof MigrationState ) {
			throw new \RuntimeException( 'The migration state doesn\'t belong to the current migration controller.' );
		}

		if( $migration_state->get_next_step() !== $this->get_id() ) {
			throw new \RuntimeException( 'The migration state doesn\'t match the executed step.' );
		}
	}

	/**
	 * Determine if a table exists in the database.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	protected function table_exists( $table_name ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );

		return ( strtolower( $wpdb->get_var( $query ) ) === strtolower( $table_name ) );
	}


	public function return_error( $error_message, $do_rollback = true ) {
		return new ErrorState( $error_message, $do_rollback );
	}


	/**
	 * Persists a state variable to database as a WordPress option
	 * @param $key
	 * @param $value
	 */
	protected function persistState( $key, $value ) {
		$storage_key = sprintf( self::STORAGE_KEY_PREFIX, $key);
		add_option( $storage_key, $value, null, 'no');
		update_option($storage_key, $value );
	}

	/**
	 * Returns a state variable used by the migration step
	 *
	 * @param $key
	 * @param bool $defaultValue
	 *
	 * @return bool|mixed|void
	 */
	protected function getState( $key, $defaultValue = false ) {
		$storage_key = sprintf( self::STORAGE_KEY_PREFIX, $key);
		return get_option( $storage_key, $defaultValue );
	}

	/**
	 * Clear state variable
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	protected function clearState( $key ) {
		$storage_key = sprintf( self::STORAGE_KEY_PREFIX, $key);
		return delete_option( $storage_key );
	}

}
