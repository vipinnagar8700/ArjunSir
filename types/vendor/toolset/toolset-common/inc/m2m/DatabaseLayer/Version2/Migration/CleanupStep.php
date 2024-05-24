<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\Success;


/**
 * Special migration step used for cleaning up the backup association table that is left behind
 * when the migration finishes.
 *
 * After the cleanup, rollback is no longer possible.
 *
 * @since 4.0
 */
class CleanupStep extends MigrationStep {

	/** @var TableNames */
	private $table_names;


	/** @var \wpdb */
	private $wpdb;


	/**
	 * RollbackStep constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct( \wpdb $wpdb, TableNames $table_names ) {
		$this->table_names = $table_names;
		$this->wpdb = $wpdb;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$backup_association_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME );

		if ( ! $this->table_exists( $backup_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'The backup association table "%s" doesn\'t exist, unable to perform cleanup.', 'wpv-views' ),
				$backup_association_table
			), false );
		}

		$this->wpdb->query( "DROP TABLE IF EXISTS {$backup_association_table}" );

		if ( $this->table_exists( $backup_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'Unable to drop the backup association table "%s": %s', 'wpv-views' ),
				$backup_association_table,
				$this->wpdb->last_error
			), false );
		}

		return new MigrationState( null, null, new Success(
			__( 'Backup association table removed.', 'wpv-views' )
		), $this->get_id(), self::STEP_NUMBER );
	}
}
