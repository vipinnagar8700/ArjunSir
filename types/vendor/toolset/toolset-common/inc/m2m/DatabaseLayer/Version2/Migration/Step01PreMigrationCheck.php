<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Test the most probable things that may fail even before we start doing anything.
 *
 * @since 4.0
 */
class Step01PreMigrationCheck extends MigrationStep {

	const STEP_NUMBER = 1;

	const NEXT_STEP = Step02DropTemporaryTables::class;

	const TEMPORARY_TABLE_NAME = 'toolset_migration_precondition_test_table';

	const MINIMAL_REQUIRED_WPML_VERSION = '4.4.0';

	/** @var \wpdb */
	private $wpdb;


	/** @var TableNames */
	private $table_names;


	/** @var DatabaseLayerMode */
	private $database_layer_mode;


	/** @var WpmlService */
	private $wpml_service;


	/**
	 * Step01PreMigrationCheck constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 * @param DatabaseLayerMode $database_layer_mode
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		\wpdb $wpdb,
		TableNames $table_names,
		DatabaseLayerMode $database_layer_mode,
		WpmlService $wpml_service
	) {
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
		$this->database_layer_mode = $database_layer_mode;
		$this->wpml_service = $wpml_service;
	}


	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$results = new ResultSet();
		// condition callable, description (for logging purposes only, hence no need to bother with translation), allowed_to_fail
		$preconditions = [
			[
				function () {
					return $this->database_layer_mode->is( DatabaseLayerMode::VERSION_1 );
				},
				'correct database layer mode',
				false,
			],
			[
				function () {
					return $this->table_exists( $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ) );
				},
				'toolset_associations table exists',
				false,
			],
			[
				function () {
					return ! $this->table_exists( $this->table_names->get_full_table_name(
						MigrationController::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME
					) );
				},
				'toolset_associations_old table is not yet present (indicates manual database manipulation)',
				true,
			],
			[
				function () {
					return ! $this->table_exists( $this->table_names->get_full_table_name( MigrationController::TEMPORARY_NEW_ASSOCIATION_TABLE_NAME ) );
				},
				'toolset_associations_new table is not yet present (indicates interrupted upgrade process)',
				true,
			],
			[
				function () {
					return ! $this->table_exists( $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ) );
				},
				'toolset_connected_elements table is not yet present',
				true,
			],
			[
				function () {
					$full_table_name = $this->table_names->get_full_table_name( self::TEMPORARY_TABLE_NAME );
					$this->wpdb->query( "CREATE TABLE $full_table_name ( id bigint(20) unsigned not null auto_increment, PRIMARY KEY (id))" );

					return $this->table_exists( $full_table_name );
				},
				'able to create a new database table',
				false,
			],
			[
				function () {
					$full_table_name = $this->table_names->get_full_table_name( self::TEMPORARY_TABLE_NAME );
					$this->wpdb->query( "DROP TABLE $full_table_name" );

					return ! $this->table_exists( $full_table_name );
				},
				'able to delete a database table',
				false,
			],
			[
				function() {
					if( ! $this->wpml_service->is_wpml_active_and_configured() ) {
						return true;
					}

					return version_compare( $this->wpml_service->get_wpml_version(), self::MINIMAL_REQUIRED_WPML_VERSION, '>=' );
				},
				'WPML not active or in a good version (' . self::MINIMAL_REQUIRED_WPML_VERSION . ' or above)',
				false,
			]
		];

		foreach ( $preconditions as list( $condition, $description, $allowed_to_fail ) ) {
			$result = $this->check_precondition( $condition, $description, $allowed_to_fail );
			$results->add( $result );

			if ( $result->is_error() ) {
				break;
			}
		}

		if ( ! $results->is_complete_success() ) {
			return new MigrationState(
				null,
				null,
				$results,
				self::class,
				self::STEP_NUMBER,
				null
			);
		}

		return new MigrationState(
			self::NEXT_STEP,
			null,
			$results,
			self::class,
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}


	private function check_precondition( $condition, $description, $allowed_to_fail ) {
		$is_condition_met = $condition();

		return new SingleResult(
			$is_condition_met || $allowed_to_fail,
			sprintf(
				__( 'Precondition "%s"... %s', 'wpv-views' ),
				$description,
				$is_condition_met ? 'OK' : ( $allowed_to_fail ? 'WARN' : 'ERR' )
			)
		);
	}
}
