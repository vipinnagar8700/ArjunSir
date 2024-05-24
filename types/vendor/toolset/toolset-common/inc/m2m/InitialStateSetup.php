<?php

namespace OTGS\Toolset\Common\Relationships;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\DatabaseInterfaceProvider;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\SchemaController;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\DatabaseStructure;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Sets the initial state (when there's no relevant value defined) of the whole relationship
 * functionality and creates required database tables if necessary
 *
 * @since 4.0
 */
class InitialStateSetup {

	/** @var \Toolset_Condition_Plugin_Types_Has_Legacy_Relationships */
	private $has_legacy_relationships;

	/** @var \Toolset_Condition_Plugin_Types_Ready_For_M2M */
	private $is_ready_for_m2m;

	/** @var \Toolset_Relationship_Controller */
	private $relationships_controller;

	/** @var DatabaseLayerMode */
	private $database_layer_mode;

	/** @var \wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;

	/** @var \Toolset_Constants */
	private $constants;


	/**
	 * InitialStateSetup constructor.
	 *
	 * @param MainController $relationships_controller
	 * @param \Toolset_Condition_Plugin_Types_Has_Legacy_Relationships $has_legacy_relationships
	 * @param \Toolset_Condition_Plugin_Types_Ready_For_M2M $is_ready_for_m2m
	 * @param DatabaseLayerMode $database_layer_mode
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 * @param \Toolset_Constants $constants
	 */
	public function __construct(
		MainController $relationships_controller,
		\Toolset_Condition_Plugin_Types_Has_Legacy_Relationships $has_legacy_relationships,
		\Toolset_Condition_Plugin_Types_Ready_For_M2M $is_ready_for_m2m,
		DatabaseLayerMode $database_layer_mode,
		\wpdb $wpdb,
		TableNames $table_names,
		\Toolset_Constants $constants
	) {
		$this->relationships_controller = $relationships_controller;
		$this->has_legacy_relationships = $has_legacy_relationships;
		$this->is_ready_for_m2m = $is_ready_for_m2m;
		$this->wpdb = $wpdb;
		$this->database_layer_mode = $database_layer_mode;
		$this->table_names = $table_names;
		$this->constants = $constants;
	}


	/**
	 * Determine whether relationships should be enabled by default.
	 *
	 * We do that only if there are no legacy post relationships defined. Otherwise, the user needs to
	 * manually trigger the migration (and go through the first version of the database layer and then
	 * migrate to the second one - cumbersome but at this point, whoever wants to seriously use relationships
	 * has migrated from legacy relationships anyway).
	 *
	 * If this runs on a fresh site, this will also create necessary database tables.
	 *
	 * Finally, this method updates the toggle option so we don't need to run this check on each request.
	 *
	 * @return bool True if relationships have been enabled.
	 */
	public function set_initial_state() {
		$is_ready_for_m2m = $this->is_ready_for_m2m->is_met();
		$has_legacy_relationships = $this->has_legacy_relationships->is_met();

		$enable_m2m = ( $is_ready_for_m2m && ! $has_legacy_relationships );

		// If there are no relationships but Toolset is not ready for m2m yet (too old Types), we don't
		// update the option, but keep trying until the update finally comes.
		$should_store_m2m_state = $is_ready_for_m2m;

		if ( $enable_m2m ) {
			$relationships_enabled = $this->enable_relationships();
		} else {
			$relationships_enabled = false;
		}

		if ( $should_store_m2m_state ) {
			$this->store_state( $relationships_enabled );
		}

		return $relationships_enabled;
	}


	/**
	 * Switch directly to the second version of database layer.
	 *
	 * The previous approach using Toolset_Relationship_Migration_Controller::do_native_dbdelta()
	 * is no longer necessary here, as this is the no-migration route (without any data to migrate).
	 *
	 * Note: This is public because of Toolset CLI.
	 *
	 * @return bool
	 */
	public function enable_relationships() {
		$this->relationships_controller->force_autoloader_initialization();

		$schema_controller = new SchemaController();

		$database_structures = new DatabaseStructure(
			$schema_controller,
			new DatabaseInterfaceProvider( $this->wpdb )
		);

		// This fills the schema controller with table definitions.
		$database_structures->initialize();

		// Create tables and verify that they have been indeed created properly.
		$schema_controller->maybe_upgrade_tables();
		$tables_created = $schema_controller->is_everything_up_to_date();

		if ( $tables_created ) {
			$this->database_layer_mode->set( DatabaseLayerMode::FALLBACK );
		}

		return $tables_created;
	}


	/**
	 * Update the option storing the state of the relationship functionality.
	 *
	 * Note: This is public because of Toolset CLI.
	 *
	 * @param bool $enable_m2m
	 */
	public function store_state( $enable_m2m ) {
		$value_to_store = $enable_m2m
			? \OTGS\Toolset\Common\Relationships\MainController::IS_M2M_ENABLED_YES_VALUE
			: \OTGS\Toolset\Common\Relationships\MainController::IS_M2M_ENABLED_NO_VALUE;

		update_option(
			\OTGS\Toolset\Common\Relationships\MainController::IS_M2M_ENABLED_OPTION,
			$value_to_store,
			true
		);
	}


	public function all_tables_exist() {
		$table_names = array_map( function ( $table_slug ) {
			return esc_sql( $this->table_names->get_full_table_name( $table_slug ) );
		}, TableNames::ALL_RELATIONSHIP_TABLES );

		$table_names_flat = '\'' . implode( '\', \'', $table_names ) . '\'';

		$table_count = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM information_schema.tables
			WHERE table_schema = %s
			AND table_name IN ($table_names_flat)",
			$this->constants->constant( 'DB_NAME' )
		) );

		return count( $table_names ) === $table_count;
	}
}

