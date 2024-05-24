<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\MaintenanceMode\Controller;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\Success;


/**
 * Migration step if something doesn't work out - drop the new version of the association table
 * and replace it by the old, backed-up one. If the backup doesn't exist, don't do anything.
 *
 * @since 4.0
 */
class RollbackStep extends MigrationStep {

	/** @var TableNames */
	private $table_names;


	/** @var \wpdb */
	private $wpdb;


	/** @var DatabaseLayerMode */
	private $database_layer_mode;


	/** @var Controller */
	private $maintenance_mode;


	/**
	 * RollbackStep constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 * @param DatabaseLayerMode $database_layer_mode
	 * @param Controller $maintenance_mode
	 */
	public function __construct(
		\wpdb $wpdb, TableNames $table_names, DatabaseLayerMode $database_layer_mode, Controller $maintenance_mode ) {
		$this->table_names = $table_names;
		$this->wpdb = $wpdb;
		$this->database_layer_mode = $database_layer_mode;
		$this->maintenance_mode = $maintenance_mode;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$backup_association_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME );
		$final_association_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );

		if ( ! $this->table_exists( $backup_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'The backup association table "%s" doesn\'t exist, unable to perform rollback.', 'wpv-views' ),
				$backup_association_table
			), false );
		}

		$this->wpdb->query( "DROP TABLE IF EXISTS {$final_association_table}" );

		if ( $this->table_exists( $final_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'Unable to drop the final association table "%s": %s', 'wpv-views' ),
				$final_association_table,
				$this->wpdb->last_error
			), false );
		}

		$this->wpdb->query( "RENAME TABLE {$backup_association_table} TO {$final_association_table}" );
		if ( ! $this->table_exists( $final_association_table ) || $this->table_exists( $backup_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'Unable to rename the backup association table "%s" to its final name "%s": %s', 'wpv-views' ),
				$backup_association_table,
				$final_association_table,
				$this->wpdb->last_error
			), false );
		}

		$this->database_layer_mode->set( DatabaseLayerMode::VERSION_1 );
		if ( DatabaseLayerMode::VERSION_1 !== $this->database_layer_mode->get() ) {
			return $this->return_error(
				__( 'Unable to set the database layer mode back to version 1.', 'wpv-views' ), false
			);
		}

		$results = new ResultSet( new Success(
			__( 'Rollback to the version 1 of the database layer complete. All changes of associations since then have been discared.', 'toolset-cli' )
		) );

		if ( $this->maintenance_mode->maintenance_file_exists() ) {
			$results->add( $this->maintenance_mode->disable() );
		}

		return new MigrationState( null, null, $results, $this->get_id() );
	}
}
