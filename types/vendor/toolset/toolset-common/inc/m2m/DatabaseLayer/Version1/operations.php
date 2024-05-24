<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use InvalidArgumentException;
use IToolset_Association;
use IToolset_Element;
use IToolset_Relationship_Definition;
use IToolset_Relationship_Role;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use Toolset_Element;
use Toolset_Element_Domain;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Relationship_Definition;
use Toolset_Relationship_Role;
use Toolset_Relationship_Table_Name;
use Toolset_Relationship_Utils;
use Toolset_Result;
use Toolset_Result_Set;
use Toolset_Result_Updated;
use Toolset_WPML_Compatibility;
use WP_Post;
use wpdb;

/**
 * Holds helper methods related to native Toolset associations.
 *
 * Throughout m2m API, only these classes should directly touch the database:
 *
 * - Toolset_Relationship_Database_Operations
 * - Toolset_Relationship_Migration_Controller
 * - Toolset_Relationship_Driver
 * - Toolset_Relationship_Translation_View_Management
 * - Toolset_Association_Query
 *
 * @since m2m
 */
class Toolset_Relationship_Database_Operations implements \OTGS\Toolset\Common\Relationships\API\AssociationDatabaseOperations {

	private static $instance;


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {

		if ( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );
		$this->wpml_service = $wpml_service_di ? : Toolset_WPML_Compatibility::get_instance();
	}


	/**
	 * Careful. This class is NOT meant to be a singleton. This is a temporary solution for easier transition
	 * from using static methods.
	 *
	 * @return Toolset_Relationship_Database_Operations
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Create new association and persist it.
	 *
	 * From outside of the m2m API, use Toolset_Relationship_Definition::create_association().
	 *
	 * @param Toolset_Relationship_Definition|string $relationship_definition_source Can also contain slug of
	 *     existing relationship definition.
	 * @param int|Toolset_Element|WP_Post $parent_source
	 * @param int|Toolset_Element|WP_Post $child_source
	 * @param int $intermediary_id
	 * @param bool $instantiate Whether to create an instance of the newly created association
	 *     or only return a result on success
	 *
	 * @return IToolset_Association|Toolset_Result
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	public function create_association( $relationship_definition_source, $parent_source, $child_source, $intermediary_id, $instantiate = true ) {

		$relationship_definition = Toolset_Relationship_Utils::get_relationship_definition( $relationship_definition_source );

		if ( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Relationship definition "%s" doesn\'t exist.', 'wpv-views' ),
					is_string( $relationship_definition_source ) ? $relationship_definition_source
						: print_r( $relationship_definition_source, true )
				)
			);
		}

		$driver = $relationship_definition->get_driver();

		return $driver->create_association(
			$parent_source,
			$child_source,
			array(
				'intermediary_id' => $intermediary_id,
				'instantiate' => (bool) $instantiate,
			)
		);
	}


	// The _id columns in the associations table
	const COLUMN_ID = '_id';

	// Columns in the relationships table
	const COLUMN_DOMAIN = '_domain';

	const COLUMN_TYPES = '_types';

	const COLUMN_CARDINALITY_MAX = 'cardinality_%s_max';

	const COLUMN_CARDINALITY_MIN = 'cardinality_%s_min';


	/**
	 * For a given role name, return the corresponding column in the associations table.
	 *
	 * @param string|IToolset_Relationship_Role $role
	 * @param string $column
	 *
	 * @return string
	 * @since m2m
	 */
	public function role_to_column( $role, $column = self::COLUMN_ID ) {

		if ( $role instanceof IToolset_Relationship_Role ) {
			$role_name = $role->get_name();
		} else {
			$role_name = $role;
		}

		// Special cases
		if ( in_array( $column, array( self::COLUMN_CARDINALITY_MAX, self::COLUMN_CARDINALITY_MIN ) ) ) {
			return sprintf( $column, $role_name );
		}

		return $role_name . $column;
	}


	/**
	 * Update the database to support the native m2m implementation.
	 *
	 * Practically that means creating the wp_toolset_associations table.
	 *
	 * @return Toolset_Result_Set
	 * @since m2m
	 */
	public function do_native_dbdelta() {
		$results = new Toolset_Result_Set();

		$results->add( $this->create_associations_table() );
		$results->add( $this->create_relationship_table() );
		$results->add( $this->create_type_set_table() );

		return $results;
	}


	/**
	 * Execute a dbDelta() query, ensuring that the function is available.
	 *
	 * @param string $query MySQL query.
	 *
	 * @return array dbDelta return value.
	 */
	private static function dbdelta( $query ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		return dbDelta( $query );
	}


	/**
	 * Determine if a table exists in the database.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 * @since m2m
	 */
	public function table_exists( $table_name ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );

		return ( strtolower( $wpdb->get_var( $query ) ) === strtolower( $table_name ) );
	}


	private function get_charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}


	/**
	 * Create the table for storing associations.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @return Toolset_Result
	 * @since m2m
	 */
	private function create_associations_table() {

		$association_table_name = $this->table_name->association_table();

		if ( $this->table_exists( $association_table_name ) ) {
			return new Toolset_Result( true );
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$association_table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				relationship_id bigint(20) UNSIGNED NOT NULL,
				parent_id bigint(20) UNSIGNED NOT NULL,
				child_id bigint(20) UNSIGNED NOT NULL,
				intermediary_id bigint(20) UNSIGNED NOT NULL,
				PRIMARY KEY  id (id),
				KEY relationship_id (relationship_id),
				KEY parent_id (parent_id, relationship_id),
				KEY child_id (child_id, relationship_id)
			) " . $this->get_charset_collate() . ";";

		return $this->do_dbdelta_and_return( $query, $association_table_name );
	}


	/**
	 * @param string $query
	 * @param string $table_to_check
	 *
	 * @return Toolset_Result
	 * @since 3.0.2
	 */
	private function do_dbdelta_and_return( $query, $table_to_check ) {
		self::dbdelta( $query );
		$wpdb_error = $this->wpdb->last_error;

		if ( ! $this->table_exists( $table_to_check ) ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Unable to create table %s due to a MySQL Error: %s', 'wpv-views' ),
					$table_to_check,
					$wpdb_error
				)
			);
		} elseif ( ! empty( $wpdb_error ) ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'MySQL error when creating table %s: %s', 'wpv-views' ),
					$table_to_check,
					$wpdb_error
				)
			);
		}

		return new Toolset_Result( true );
	}


	/**
	 * Create the table for the relationship definitions.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @return Toolset_Result
	 * @since m2m
	 */
	private function create_relationship_table() {

		$table_name = $this->table_name->relationship_table();

		if ( $this->table_exists( $table_name ) ) {
			return new Toolset_Result( true );
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug varchar(" . \OTGS\Toolset\Common\Relationships\API\Constants::MAXIMUM_RELATIONSHIP_SLUG_LENGTH . ") NOT NULL DEFAULT '',
			display_name_plural varchar(255) NOT NULL DEFAULT '',
			display_name_singular varchar(255) NOT NULL DEFAULT '',
			driver varchar(50) NOT NULL DEFAULT '',
			parent_domain varchar(20) NOT NULL DEFAULT '',
			parent_types bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			child_domain varchar(20) NOT NULL DEFAULT '',
			child_types bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			intermediary_type varchar(20) NOT NULL DEFAULT '',
			ownership varchar(8) NOT NULL DEFAULT 'none',
			cardinality_parent_max int(10) NOT NULL DEFAULT -1,
			cardinality_parent_min int(10) NOT NULL DEFAULT 0,
			cardinality_child_max int(10) NOT NULL DEFAULT -1,
			cardinality_child_min int(10) NOT NULL DEFAULT 0,
			is_distinct tinyint(1) NOT NULL DEFAULT 0,
			scope longtext NOT NULL DEFAULT '',
			origin varchar(50) NOT NULL DEFAULT '',
			role_name_parent varchar(255) NOT NULL DEFAULT '',
			role_name_child varchar(255) NOT NULL DEFAULT '',
			role_name_intermediary varchar(255) NOT NULL DEFAULT '',
			role_label_parent_singular VARCHAR(255) NOT NULL DEFAULT '',
			role_label_child_singular VARCHAR(255) NOT NULL DEFAULT '',
			role_label_parent_plural VARCHAR(255) NOT NULL DEFAULT '',
			role_label_child_plural VARCHAR(255) NOT NULL DEFAULT '',
			needs_legacy_support tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 0,
			autodelete_intermediary tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  id (id),
			KEY slug (slug),
			KEY is_active (is_active),
			KEY needs_legacy_support (needs_legacy_support),
			KEY parent_type (parent_domain, parent_types),
			KEY child_type (child_domain, child_types)
		) " . $this->get_charset_collate() . ";";

		return $this->do_dbdelta_and_return( $query, $table_name );
	}


	/**
	 * @return Toolset_Result
	 */
	private function create_type_set_table() {
		$table_name = $this->table_name->type_set_table();
		if ( $this->table_exists( $table_name ) ) {
			return new Toolset_Result( true );
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			set_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			type varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY  id (id),
			KEY set_id (set_id),
			KEY type (type)
		) " . $this->get_charset_collate() . ";";

		return $this->do_dbdelta_and_return( $query, $table_name );
	}


	/**
	 * When a relationship definition slug is renamed, update the association table (where the slug is used as a
	 * foreign key).
	 *
	 * The usage of this method is strictly limited to the m2m API, always change the slug via
	 * Toolset_Relationship_Definition_Repository::change_definition_slug().
	 *
	 * @param IToolset_Relationship_Definition $old_definition
	 * @param IToolset_Relationship_Definition $new_definition
	 *
	 * @return Toolset_Result
	 *
	 * @since m2m
	 */
	public function update_associations_on_definition_renaming(
		IToolset_Relationship_Definition $old_definition,
		IToolset_Relationship_Definition $new_definition
	) {
		$associations_table = new Toolset_Relationship_Table_Name;

		$rows_updated = $this->wpdb->update(
			$associations_table->association_table(),
			array( 'relationship_id' => $new_definition->get_row_id() ),
			array( 'relationship_id' => $old_definition->get_row_id() ),
			'%d',
			'%d'
		);

		$is_success = ( false !== $rows_updated );

		$message = (
		$is_success
			? sprintf(
			__( 'The association table has been updated with the new relationship slug "%s". %d rows have been updated.', 'wpv-views' ),
			$new_definition->get_slug(),
			$rows_updated
		)
			: sprintf(
			__( 'There has been an error when updating the association table with the new relationship slug: %s', 'wpv-views' ),
			$this->wpdb->last_error
		)
		);

		return new Toolset_Result( $is_success, $message );
	}


	/**
	 * Delete all associations from a given relationship.
	 *
	 * @param int $relationship_row_id
	 *
	 * @return Toolset_Result_Updated
	 */
	public function delete_associations_by_relationship( $relationship_row_id ) {

		$associations_table = $this->table_name->association_table();

		$result = $this->wpdb->delete(
			$associations_table,
			array( 'relationship_id' => $relationship_row_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new Toolset_Result_Updated(
				false, 0,
				sprintf( __( 'Database error when deleting associations: "%s"', 'wpv-views' ), $this->wpdb->last_error )
			);
		} else {
			return new Toolset_Result_Updated(
				true, $result,
				sprintf( __( 'Deleted all associations for the relationship #%d', 'wpv-views' ), $relationship_row_id )
			);
		}
	}


	/**
	 * Updates association intermediary post
	 *
	 * @param int $association_id Association trID
	 * @param int $intermediary_id New intermediary ID
	 *
	 * @since m2m
	 */
	public function update_association_intermediary_id( $association_id, $intermediary_id ) {
		$this->wpdb->update(
			$this->table_name->association_table(),
			array(
				'intermediary_id' => $intermediary_id,
			),
			array(
				'id' => $association_id,
			),
			array( '%d' )
		);
	}


	/**
	 * Returns the maximun number of associations of a relationship for a parent id and a child id
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param string $role_name Role name.
	 *
	 * @return int
	 * @throws InvalidArgumentException In case of error.
	 */
	public function count_max_associations( $relationship_id, $role_name ) {
		if ( ! in_array( $role_name, Toolset_Relationship_Role::parent_child_role_names() ) ) {
			throw new InvalidArgumentException( 'Wrong role name' );
		}
		$associations_table = Toolset_Relationship_Table_Name::associations();
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT max(n) count
					FROM (
						SELECT count(*) n
							FROM {$associations_table}
							WHERE relationship_id = %d
							GROUP BY {$role_name}_id
					) count", $relationship_id ) );

		return (int) $count;
	}


	/**
	 * Delete all associations of a given relationships that have the given element in the given role.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	public function delete_associations_by_element( $relationship, $element_role_name, $element_id ) {
		$element_id_column = $this->role_to_column( $element_role_name, Toolset_Relationship_Database_Operations::COLUMN_ID );

		$this->wpdb->delete(
			$this->table_name->association_table(),
			array(
				'relationship_id' => $relationship->get_row_id(),
				$element_id_column => $element_id,
			),
			array( '%d', '%d' )
		);
	}


	public function delete_association_by_element_in_any_role( IToolset_Element $element ) {
		// Documented in OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationDatabaseOperations.
		do_action( 'toolset_before_associations_by_element_delete', $element->get_id(), $element->get_domain() );

		$associations = $this->table_name->association_table();
		$relationships = $this->table_name->relationship_table();
		$can_delete_intermediary = ( Toolset_Element_Domain::POSTS === $element->get_domain() ? ' 1 = 1 ' : ' 0 = 1 ' );

		$query = $this->wpdb->prepare(
			"DELETE association
			FROM {$associations} AS association
			JOIN {$relationships} AS relationship
				ON ( association.relationship_id = relationship.id )
			WHERE
				( relationship.parent_domain = %s AND parent_id = %d )
				OR ( relationship.child_domain = %s AND child_id = %d )
				OR ( {$can_delete_intermediary} AND intermediary_id = %d )",
			$element->get_domain(),
			$element->get_id(),
			$element->get_domain(),
			$element->get_id(),
			$element->get_id()
		);

		$deleted_rows = $this->wpdb->query( $query );

		return new Toolset_Result( $deleted_rows > 0 );
	}


	/**
	 * Delete intermediary posts from all associations in a given relationship that have
	 * the given element in the given role.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	public function delete_intermediary_posts_by_element( $relationship, $element_role_name, $element_id ) {
		$element_id_column = $this->role_to_column( $element_role_name, Toolset_Relationship_Database_Operations::COLUMN_ID );

		$intermediary_post_ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT intermediary_id FROM {$this->table_name->association_table()}
				WHERE relationship_id = %d AND {$element_id_column} = %d",
				$relationship->get_row_id(),
				$element_id
			)
		);

		foreach ( $intermediary_post_ids as $post_id ) {
			wp_delete_post( $post_id );
		}
	}


	/**
	 * @inheritDoc
	 */
	public function delete_association( \IToolset_Association $association ) {
		$rows_updated = $this->wpdb->delete(
			$this->table_name->association_table(),
			array( 'id' => $association->get_uid() ),
			'%d'
		);

		$is_success = ( false !== $rows_updated || 1 === $rows_updated );

		return new Toolset_Result( $is_success );
	}


	public function get_dangling_intermediary_posts( array $intermediary_post_types, array $post_types_to_delete_by ) {
		$ipts = '\'' . implode( '\', \'', esc_sql( $intermediary_post_types ) ) . '\'';
		$limit = (int) Constants::DELETE_POSTS_PER_BATCH;

		if ( $this->wpml_service->is_wpml_active_and_configured() ) {
			$icl_translations = $this->wpdb->prefix . 'icl_translations';
			$default_language = esc_sql( $this->wpml_service->get_default_language() );

			// Main goal: Query posts of given types that are neither used as intermediary posts directly
			// nor as translations of intermediary posts.
			// We achieve that by doing a LEFT JOINs and then checking if the columns in
			// joined tables are NULL (no match).
			$query = "
				SELECT SQL_CALC_FOUND_ROWS post.ID
				FROM {$this->wpdb->posts} AS post
					# Exclude intermediary posts belonging to a relationship that isn't supposed to delete them automatically
					JOIN {$this->table_name->relationship_table()} AS relationship
						ON (
							post.post_type = relationship.intermediary_type
							AND relationship.autodelete_intermediary = 1
						)
					LEFT JOIN {$this->table_name->association_table()} AS association
						ON (post.ID = association.intermediary_id)
					LEFT JOIN {$icl_translations} AS translation
						ON (
							post.ID = translation.element_id
							AND translation.element_type LIKE 'post_%'
							AND translation.language_code != '{$default_language}'
						)
					LEFT JOIN {$icl_translations} AS default_language_translation
						ON (
							translation.trid = default_language_translation.trid
							AND default_language_translation.language_code = '{$default_language}'
						)
					LEFT JOIN {$this->table_name->association_table()} AS default_language_association
						ON(
							default_language_association.intermediary_id = default_language_translation.element_id
						)
				WHERE
					association.intermediary_id IS NULL
					AND default_language_association.intermediary_id IS NULL
					AND post.post_type IN ({$ipts})
				LIMIT {$limit}";

		} else {
			// Ditto but without the WPML part, as the icl_translations table probably doesn't
			// even exist.
			$query = "
				SELECT SQL_CALC_FOUND_ROWS post.ID
				FROM {$this->wpdb->posts} AS post
					# Exclude intermediary posts belonging to a relationship that isn't supposed to delete them automatically
					JOIN {$this->table_name->relationship_table()} AS relationship
						ON (
							post.post_type = relationship.intermediary_type
							AND relationship.autodelete_intermediary = 1
						)
					LEFT JOIN {$this->table_name->association_table()} AS association
						ON (post.ID = association.intermediary_id)
				WHERE
					association.intermediary_id IS NULL
					AND post.post_type IN ({$ipts})
				LIMIT {$limit}";
		}

		if ( ! empty( $post_types_to_delete_by ) ) {
			$in_ipts = '\'' . implode( '\', \'', esc_sql( $post_types_to_delete_by ) ) . '\'';
			$query = "($query) UNION (
				SELECT post_by_type.ID
				FROM {$this->wpdb->posts} AS post_by_type
				WHERE post_by_type.post_type IN ({$in_ipts})
			) LIMIT {$limit}";
		}

		$post_ids = $this->wpdb->get_col( $query );

		$found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return [ $post_ids, $found_rows ];
	}


	/**
	 * @inheritDoc
	 */
	public function requires_default_language_post() {
		return true;
	}
}
