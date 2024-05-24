<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\DatabaseStructure;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Create the temporary association table with new structure and the table for connected elements.
 *
 * @since 4.0
 */
class Step03CreateNewTables extends MigrationStep {

	const STEP_NUMBER = 3;
	const NEXT_STEP = Step04MigrateAssociations::class;


	/** @var \wpdb */
	private $wpdb;


	/** @var DatabaseStructure */
	private $database_structure;


	/**
	 * Step03CreateNewTables constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param DatabaseStructure $database_structure
	 */
	public function __construct( \wpdb $wpdb, DatabaseStructure $database_structure ) {
		$this->wpdb = $wpdb;
		$this->database_structure = $database_structure;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$this->database_structure->initialize();
		$results = new ResultSet();
		$results->add( $this->create_table(
			TableNames::ASSOCIATIONS,
			MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME
		) );
		$results->add( $this->create_table( TableNames::CONNECTED_ELEMENTS ) );

		if ( ! $results->is_complete_success() ) {
			return $this->return_error( $results->get_message(), true );
		}

		$next_state = new MigrationState(
			self::NEXT_STEP,
			null,
			$results,
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);

		// Indicate that the next step has substeps. But we don't know how many at this point.
		$next_state->set_substep_info( -1, -1 );

		return $next_state;
	}


	/**
	 * Create one database table.
	 *
	 * @param string $table_name Name of the table (without a prefix) to be created.
	 * @param string|null $name_override If not null, this will override the name (without a prefix) of
	 *     the table while still preserving its structure.
	 *
	 * @return SingleResult
	 */
	private function create_table( $table_name, $name_override = null ) {
		$table = $this->database_structure->get_table( $table_name );

		if ( null !== $name_override ) {
			$table->set_name( $name_override );
		}

		if ( $table->exists() ) {
			return new SingleResult( false, sprintf(
				__( 'The table "%s" already exists, which is not expected.', 'wpv-views' ),
				$table->get_full_name()
			) );
		}

		$is_created = $table->create();

		if ( ! $is_created ) {
			return new SingleResult( false, sprintf(
				__( 'Unable to create table "%s".', 'wpv-views' ),
				$table->get_full_name()
			) );
		}

		return new SingleResult( true, sprintf(
			__( 'Created table "%s".', 'wpv-views' ),
			$table->get_full_name()
		) );
	}
}
