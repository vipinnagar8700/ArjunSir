<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;


class SchemaController {

	private $plugin_file;

	/** @var Table[] */
	private $tables = [];


	public function __construct( $plugin_file = '' ) {
		$this->plugin_file = $plugin_file;
	}


	public function register_hooks() {
		register_activation_hook( $this->plugin_file, array( $this, 'maybe_upgrade_tables' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade_tables' ) );

		// This would be needed for multisite support (originally implemented in Table)
		// add_action( 'switch_blog', array( $this, 'switch_blog' ) );
	}


	/**
	 * @param Table $table
	 */
	public function register_table( Table $table ) {
		$this->tables[ $table->get_name() ] = $table;
	}


	public function maybe_upgrade_tables() {
		foreach( $this->tables as $table ) {
			$table->maybe_upgrade();
		}
	}


	/**
	 * Obtain a table definition, if it exists.
	 *
	 * @param string $table_name Name of an existing table (without prefix).
	 * @return Table
	 * @throws \InvalidArgumentException
	 */
	public function get_table( $table_name ) {
		if( ! array_key_exists( $table_name, $this->tables ) ) {
			throw new \InvalidArgumentException( 'Invalid table requested.' );
		}

		return $this->tables[ $table_name ];
	}


	/**
	 * Returns true if all registered tables exist in the database and are up-to-date
	 * with the current schema.
	 *
	 * @return bool
	 */
	public function is_everything_up_to_date() {
		foreach( $this->tables as $table ) {
			if ( ! $table->exists() || $table->needs_upgrade() ) {
				return false;
			}
		}

		return true;
	}

}
