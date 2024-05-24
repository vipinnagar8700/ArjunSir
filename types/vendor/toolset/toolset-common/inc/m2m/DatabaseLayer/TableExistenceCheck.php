<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

use OTGS\Toolset\Common\Relationships\InitialStateSetup;

/**
 * Checks for the existence of m2m database tables and creates them if they're missing.
 *
 * Optimized not to repeat any actions unless necessary.
 *
 * @since Types 3.3.11
 * @since 4.0 Ported to the Translatable Associations project with support for different database layer versions.
 */
class TableExistenceCheck {


	/** @var DatabaseLayerMode */
	private $database_layer_mode;

	/** @var bool */
	private $did_ensure = false;


	/**
	 * TableExistenceCheck constructor.
	 *
	 * @param DatabaseLayerMode $database_layer_mode
	 */
	public function __construct( DatabaseLayerMode $database_layer_mode ) {
		$this->database_layer_mode = $database_layer_mode;
	}


	/**
	 * After this method is called, relationship tables ought to exist unless:
	 *
	 * - The toolset_m2m_skip_table_existence_check was used.
	 * - There's something wrong with the database that prevents new tables from being created (which is a basic
	 *   requirement of WordPress, so it's safe to assume).
	 */
	public function ensure_tables_exist() {
		if ( $this->did_ensure ) {
			return;
		}

		/**
		 * Filter toolset_m2m_skip_table_existence_check.
		 *
		 * Use it to return true in order to skip checking for table existence and save a little bit of performance
		 * on relevant requests.
		 *
		 * @since Types 3.3.11
		 */
		if ( apply_filters( 'toolset_m2m_skip_table_existence_check', false ) ) {
			$this->did_ensure = true;

			return;
		}

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		do_action( 'toolset_do_m2m_full_init' );

		// This will check for table existence and create them if they're missing.
		// We are still respecting the current database layer mode: This is to handle the situation when
		// m2m has already been initialized but the tables are missing, in which case we don't want to risk
		// an upgrade even with an empty data set (the site may be in the middle of a manual database migration,
		// for example).
		if( $this->database_layer_mode->is( DatabaseLayerMode::VERSION_1 ) ) {
			Version1\Toolset_Relationship_Database_Operations::get_instance()->do_native_dbdelta();
		} elseif( $this->database_layer_mode->is( DatabaseLayerMode::VERSION_2 ) ) {
			$dic = toolset_dic();
			/** @noinspection PhpUnhandledExceptionInspection */
			$initial_state_setup = $dic->make( InitialStateSetup::class );
			if( ! $initial_state_setup->all_tables_exist() ) {
				$initial_state_setup->enable_relationships();
			}
		}

		$this->did_ensure = true;
	}

}
