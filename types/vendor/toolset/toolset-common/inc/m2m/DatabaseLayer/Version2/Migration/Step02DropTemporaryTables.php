<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Second step: Drop temporary tables that might have been created during a previous migration attempt.
 *
 * Note that the association table with original data is never deleted during the migration process,
 * so it should be always safe to revert, unless someone messes with the database directly.
 *
 * We handle the toolset_connected_elements table as a temporary, too, because the only way it could
 * have been created until now was during a previous migration attempt.
 *
 * @since 4.0
 */
class Step02DropTemporaryTables extends MigrationStep {


	const STEP_NUMBER = 2;
	const NEXT_STATE = Step03CreateNewTables::class;


	/** @var \wpdb */
	private $wpdb;


	/** @var TableNames */
	private $table_names;


	/**
	 * Step02DropTemporaryTables constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct( \wpdb $wpdb, TableNames $table_names ) {
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$results = new ResultSet();

		$this->wpdb->query(
			"DROP TABLE IF EXISTS {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}"
		);
		$this->wpdb->query(
			"DROP TABLE IF EXISTS {$this->table_names->get_full_table_name( MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME )}"
		);
		$this->wpdb->query(
			"DROP TABLE IF EXISTS {$this->table_names->get_full_table_name( MigrationController::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME )}"
		);

		$results->add( true, __( 'Temporary migration tables have been removed if they existed.', 'wpv-views' ) );

		$this->persistState( Step04MigrateAssociations::STATE_ASSOCIATION_RESULTS, null );
		$this->persistState( Step04MigrateAssociations::STATE_LAST_ASSOCIATION_ID, null );
		$this->persistState( Step04MigrateAssociations::STATE_PREVIOUS_LAST_ASSOCIATION_ID, null );

		$results->add( true, __( 'Clearing persisted migration state if it exists from previous attempts.', 'wpv-views' ) );

		return new MigrationState(
			self::NEXT_STATE,
			null,
			$results,
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}
}
