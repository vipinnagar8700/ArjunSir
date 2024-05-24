<?php
/**
 * Base Custom Database Table Class.
 *
 * @package     Database
 * @subpackage  Table
 * @copyright   Copyright (c) 2019
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;


use OTGS\Toolset\Common\Result\ResultInterface;

/**
 * A base database table class, which facilitates the creation of (and schema
 * changes to) individual database tables.
 *
 * This class is intended to be extended for each unique database table,
 * including global tables for multisite, and users tables.
 *
 * It exists to make managing database tables as easy as possible.
 *
 * Extending this class comes with several automatic benefits:
 * - Activation hook makes it great for plugins
 * - Tables store their versions in the database independently
 * - Tables upgrade via independent upgrade abstract methods
 * - Multisite friendly - site tables switch on "switch_blog" action
 *
 * @since 4.0
 */
class Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @var   string
	 */
	private $name = '';


	/**
	 * Database version.
	 *
	 * @var   mixed
	 */
	private $current_version = '';

	/**
	 * Is this table for a site, or global.
	 *
	 * @var bool
	 */
	private $is_global = false;

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private $db_version_key = '';

	/**
	 * Current database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	private $db_version = 0;

	/**
	 * Table name as stored in the database.
	 *
	 * @var  string
	 */
	private $full_table_name = '';


	/** @var TableSchema */
	private $schema = '';


	/**
	 * Key => value array of versions => methods.
	 *
	 * @var callable[]
	 */
	private $upgrade_routines = [];


	/** @var DatabaseInterfaceProvider */
	private $database_interface_provider;


	/** @var \wpdb */
	private $wpdb;


	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @param string $name
	 * @param int|string $current_version
	 * @param DatabaseInterfaceProvider $database_interface_provider
	 * @param TableSchema $schema
	 */
	public function __construct(
		$name,
		$current_version,
		DatabaseInterfaceProvider $database_interface_provider,
		TableSchema $schema
	) {
		$this->database_interface_provider = $database_interface_provider;
		$this->current_version = $current_version;
		$this->wpdb = $database_interface_provider->get_wpdb();
		if( ! $this->wpdb instanceof \wpdb ) {
			throw new \InvalidArgumentException( 'Invalid database interface.' );
		}
		$this->schema = $schema;

		// Setup the database table
		$this->setup_names( $name );
	}


	/**
	 * Maybe upgrade the database table. Handles creation & schema changes.
	 *
	 * Hooked to the `admin_init` action.
	 *
	 * @since 1.0.0
	 */
	public function maybe_upgrade() {

		// Bail if not upgradeable
		if ( ! $this->is_upgradeable() ) {
			return;
		}

		// Bail if upgrade not needed
		if ( ! $this->needs_upgrade() ) {
			return;
		}

		// Upgrade
		if ( $this->exists() ) {
			$this->upgrade();

			// Install
		} else {
			$this->install();
		}
	}


	/**
	 * Return whether this table needs an upgrade.
	 *
	 * @param mixed $version Database version to check if upgrade is needed
	 *
	 * @return bool True if table needs upgrading. False if not.
	 */
	public function needs_upgrade( $version = false ) {

		// Use the current table version if none was passed
		if ( empty( $version ) ) {
			$version = $this->current_version;
		}

		// Get the current database version
		$this->db_version = $this->is_global()
			? get_network_option( get_main_network_id(), $this->db_version_key, false )
			: get_option( $this->db_version_key, false );

		// Is the database table up to date?
		$is_current = version_compare( $this->db_version, $version, '>=' );

		// Return false if current, true if out of date
		return ( true === $is_current )
			? false
			: true;
	}


	/**
	 * Return whether this table can be upgraded.
	 *
	 * @return bool True if table can be upgraded. False if not.
	 */
	private function is_upgradeable() {
		// Bail if global and upgrading global tables is not allowed
		if ( $this->is_global() && ! wp_should_upgrade_global_tables() ) {
			return false;
		}

		return true;
	}


	/**
	 * Return the current table version from the codebase.
	 * For obtaining the version from the database, use get_database_version().
	 *
	 * @return string
	 */
	public function get_current_version() {
		return $this->current_version;
	}


	/**
	 * Install a database table by creating the table and setting the version.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		$created = $this->create();

		// Set the DB version if create was successful
		if ( true === $created ) {
			$this->set_db_version();
		}
	}


	/**
	 * Destroy a database table by dropping the table and deleting the version.
	 *
	 * @since 1.0.0
	 */
	public function uninstall() {
		$dropped = $this->drop();

		// Delete the DB version if drop was successful
		if ( true === $dropped ) {
			$this->delete_db_version();
		}
	}

	/**
	 * Check if table already exists.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function exists() {
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->wpdb->esc_like( $this->full_table_name ) )
		);

		return $this->database_interface_provider
			->parse_result( $result, false )
			->is_success();
	}


	/**
	 * Check if table already exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function column_exists( $name = '' ) {
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"SHOW COLUMNS FROM {$this->full_table_name} LIKE %s",
				$this->wpdb->esc_like( $name )
			)
		);

		return $this->database_interface_provider
			->parse_result( $result, false )
			->is_success();
	}


	/**
	 * Create the table.
	 *
	 * @return bool
	 */
	public function create() {
		$result = $this->wpdb->query(
			"CREATE TABLE {$this->full_table_name} ("
			."{$this->schema->to_string()}"
			.") {$this->get_charset_collation()};"
		);

		return $this->database_interface_provider
			->parse_result( $result, false )
			->is_success();
	}


	/**
	 * Drop the database table.
	 *
	 * @return bool
	 */
	public function drop() {
		$result = $this->wpdb->query( "DROP TABLE {$this->full_table_name}" );

		return $this->database_interface_provider
			->parse_result( $result, false )
			->is_success();
	}


	/**
	 * Truncate the database table.
	 *
	 * @return bool
	 */
	public function truncate() {
		$result = $this->wpdb->query( "TRUNCATE TABLE {$this->full_table_name}" );

		return $this->database_interface_provider
			->parse_result( $result, true )
			->is_success();
	}


	/**
	 * Delete all items from the database table.
	 *
	 * @return mixed
	 */
	public function delete_all() {
		/** @noinspection SqlWithoutWhere */
		$result = $this->wpdb->query( "DELETE FROM {$this->full_table_name}" );

		return $this->database_interface_provider
			->parse_result( $result, true )
			->is_success();
	}


	/**
	 * Count the number of items in the database table.
	 *
	 * @return int
	 */
	public function count() {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->full_table_name}" );
	}

	/**
	 * Upgrade this database table.
	 *
	 * @since 1.0.0
	 * @return ResultInterface
	 */
	public function upgrade() {
		// Remove all upgrades that have already been completed
		$upgrades = array_filter( (array) $this->upgrade_routines, function ( $value ) {
			return version_compare( $value, $this->db_version, '>' );
		} );

		// Bail if no upgrades or database version is missing
		if ( empty( $upgrades ) || empty( $this->db_version ) ) {
			$this->set_db_version();
			return new \Toolset_Result( true );
		}

		// Try to do all known upgrades
		foreach ( $upgrades as $version => $method ) {
			$result = $this->upgrade_to( $version, $method );

			// Bail if an error occurs, to avoid skipping ahead
			if ( ! $result->is_success() ) {
				return $result;
			}
		}

		return new \Toolset_Result( true );
	}


	/**
	 * Upgrade to a specific database version.
	 *
	 * @param mixed $version Database version to check if upgrade is needed
	 * @param callable $method
	 *
	 * @return ResultInterface
	 */
	private function upgrade_to( $version, $method ) {
		// Bail if no upgrade is needed
		if ( ! $this->needs_upgrade( $version ) ) {
			return new \Toolset_Result( false, 'No upgrade needed.' );
		}

		// Is the method callable?
		if( ! is_callable( $method ) ) {
			throw new \InvalidArgumentException( 'Invalid callback provided for table structure upgrade.' );
		}

		// Do the upgrade
		$result = new \Toolset_Result( $method() );

		// Bail if upgrade failed
		if ( ! $result->is_success() ) {
			return $result;
		}

		$this->set_db_version( $version );

		return $result;
	}


	/**
	 * Setup the necessary table variables.
	 *
	 * @param string $name
	 */
	private function setup_names( $name ) {
		$this->name = $this->sanitize_table_name( $name );

		// Bail if database table name was garbage
		if ( false === $this->name ) {
			throw new \InvalidArgumentException( 'Invalid table name.' );
		}

		if ( empty( $this->db_version_key ) ) {
			$this->db_version_key = "wpdb_{$this->name}_version";
		}

		$site_id = $this->is_global() ? 0 : null;
		$table_prefix = $this->wpdb->get_blog_prefix( $site_id );
		$this->full_table_name = $table_prefix . $this->name;
	}


	/**
	 * Provide the MySQL code for setting charset and collation when creating a table.
	 *
	 * This must be done directly because the database interface does not
	 * have a common mechanism for manipulating them safely.
	 */
	private function get_charset_collation() {
		$result = '';
		if ( ! empty( $this->wpdb->charset ) ) {
			$result .= "DEFAULT CHARACTER SET {$this->wpdb->charset}";
		}

		if ( ! empty( $this->wpdb->collate ) ) {
			$result .= " COLLATE {$this->wpdb->collate}";
		}

		return $result;
	}


	/**
	 * Set the database version for the table.
	 *
	 * @param mixed $version Database version to set when upgrading/creating
	 *
	 * @since 1.0.0
	 *
	 */
	private function set_db_version( $version = '' ) {

		// If no version is passed during an upgrade, use the current version
		if ( empty( $version ) ) {
			$version = $this->current_version;
		}

		// Update the DB version
		$this->is_global()
			? update_network_option( get_main_network_id(), $this->db_version_key, $version )
			: update_option( $this->db_version_key, $version );

		// Set the DB version
		$this->db_version = $version;
	}


	/**
	 * Get the table version from the database.
	 */
	public function get_database_version() {
		return $this->db_version;
	}


	/**
	 * Delete the table version from the database.
	 *
	 * @since 1.0.0
	 */
	private function delete_db_version() {
		$this->db_version = $this->is_global()
			? delete_network_option( get_main_network_id(), $this->db_version_key )
			: delete_option( $this->db_version_key );
	}



	/**
	 * Check if table is global.
	 *
	 * @return bool
	 */
	private function is_global() {
		return $this->is_global;
	}


	/**
	 * Sanitize a table name string.
	 *
	 * Used to make sure that a table name value meets MySQL expectations.
	 *
	 * Applies the following formatting to a string:
	 * - Trim whitespace
	 * - No accents
	 * - No special characters
	 * - No hyphens
	 * - No double underscores
	 * - No trailing underscores
	 *
	 * @param string $name The name of the database table
	 *
	 * @return string Sanitized database table name
	 * @since 1.0.0
	 *
	 */
	protected function sanitize_table_name( $name = '' ) {

		// Bail if empty or not a string
		if ( empty( $name ) || ! is_string( $name ) ) {
			return false;
		}

		// Trim spaces off the ends
		$unspace = trim( $name );

		// Only non-accented table names (avoid truncation)
		$accents = remove_accents( $unspace );

		// Only lowercase characters, hyphens, and dashes (avoid index corruption)
		$lower = sanitize_key( $accents );

		// Replace hyphens with single underscores
		$under = str_replace( '-', '_', $lower );

		// Single underscores only
		$single = str_replace( '__', '_', $under );

		// Remove trailing underscores
		$clean = trim( $single, '_' );

		// Bail if table name was garbaged
		if ( empty( $clean ) ) {
			return false;
		}

		// Return the cleaned table name
		return $clean;
	}


	/**
	 * Name of the table, without a prefix.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Full name of the table as it's defined in the MySQL database.
	 *
	 * @return string
	 */
	public function get_full_name() {
		return $this->full_table_name;
	}


	public function set_name( $name ) {
		$this->setup_names( $name );
	}

}
