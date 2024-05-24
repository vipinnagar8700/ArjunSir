<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Start using the new table.
 *
 * The current association table will be backed up with an '_old' prefix and the temporary '_new' table
 * takes its place (the '_new' prefix will be removed).
 *
 * @since 4.0
 */
class Step06RenameTables extends MigrationStep {


	const STEP_NUMBER = 6;
	const NEXT_STEP = Step07UpdateDbLayerMode::class;


	/** @var TableNames */
	private $table_names;


	/** @var \wpdb */
	private $wpdb;


	/**
	 * Step06RenameTables constructor.
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

		$final_association_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$migrated_association_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME );
		$backup_association_table = $this->table_names->get_full_table_name( MigrationController::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME );

		if ( $this->table_exists( $backup_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'Backup table "%s" already exists, cannot move forward with the migration.', 'wpv-views' ),
				$backup_association_table
			), true );
		}

		if ( ! $this->table_exists( $migrated_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'The new association table "%s" doesn\'t exist but it was expected, cannot move forward with the migration.', 'wpv-views' ),
				$migrated_association_table
			), false );
		}

		if ( ! $this->table_exists( $final_association_table ) ) {
			return $this->return_error( sprintf(
				__( 'The final association table "%s" doesn\'t exist but it was expected, , cannot move forward with the migration.', 'wpv-views' ),
				$final_association_table
			), false );
		}

		$this->wpdb->query( "RENAME TABLE {$final_association_table} TO {$backup_association_table}" );
		$renamed_backup_table_exists = $this->table_exists( $backup_association_table );

		if ( ! $renamed_backup_table_exists ) {
			return $this->return_error( sprintf(
				__( 'Failed to rename the previous version of the association table ("%s" to "%s").', 'wpv-views' ),
				$final_association_table,
				$backup_association_table
			), false );
		}

		$this->wpdb->query( "RENAME TABLE {$migrated_association_table} TO {$final_association_table}" );
		$renamed_final_table_exists = $this->table_exists( $final_association_table );

		if ( ! $renamed_final_table_exists ) {
			return $this->return_error( sprintf(
				__( 'Failed to rename the new version of the association table ("%s" to "%s").', 'wpv-views' ),
				$migrated_association_table,
				$final_association_table
			), true );
		}

		return new MigrationState(
			self::NEXT_STEP,
			null,
			new SingleResult( true, sprintf(
				__( 'Old and new association tables have been renamed successfully ("%s" to "%s" and "%s" to "%s").', 'wpv-views' ),
				$final_association_table,
				$backup_association_table,
				$migrated_association_table,
				$final_association_table
			) ),
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}

}
