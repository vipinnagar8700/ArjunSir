<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Manages JOIN clauses shared between different conditions within one association query.
 *
 * Use methods in this class to obtain aliases for the tables you need. By doing that,
 * those tables will be added to the final JOIN clause. There is no risk of alias
 * conflicts as long as all conditions use the same instance of
 * UniqueTableAlias provided via the setup() method.
 *
 * The setup() method must obviously be called before any further use of the class.
 *
 * @since 4.0
 */
class TableJoinManager {

	const ALIAS_ASSOCIATIONS = 'associations';
	const ALIAS_RELATIONSHIPS = 'relationships';

	/** @var UniqueTableAlias */
	private $unique_table_alias;


	/** @var \wpdb */
	private $wpdb;


	/** @var TableNames */
	private $table_names;


	/** @var string[] Mapping of role names to aliases of JOINed wp_posts table. */
	private $registered_wp_posts_joins = array();


	/** @var string[][] Mapping of role names and meta_keys to aliases of JOINed wp_postmeta table. */
	private $registered_wp_postmeta_joins = array();


	/** @var bool Flag indicating that a relationships table also needs to be JOINed. */
	private $join_relationships = false;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Table_Join_Manager
	 * constructor.
	 *
	 * @param TableNames $table_names
	 * @param \wpdb $wpdb
	 */
	public function __construct(
		TableNames $table_names,
		\wpdb $wpdb
	) {
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}


	/**
	 * Setup the object for use in a particular context.
	 *
	 * Must be called before any further usage.
	 *
	 * @param UniqueTableAlias $unique_table_alias
	 */
	public function setup( UniqueTableAlias $unique_table_alias ) {
		$this->unique_table_alias = $unique_table_alias;
	}


	/**
	 * Get an alias for a wp_posts table JOINed on a particular element role.
	 *
	 * @param RelationshipRole $for_role
	 *
	 * @return string Table alias.
	 */
	public function wp_posts( RelationshipRole $for_role ) {
		if ( ! array_key_exists( $for_role->get_name(), $this->registered_wp_posts_joins ) ) {
			$table_alias = $this->unique_table_alias->generate( $this->wpdb->posts, true );
			$this->registered_wp_posts_joins[ $for_role->get_name() ] = $table_alias;
		}

		return $this->registered_wp_posts_joins[ $for_role->get_name() ];
	}


	/**
	 * Get an alias for a wp_postmeta table JOINed on a particular element role and a meta_key value.
	 *
	 * This creates LEFT JOIN clauses, so that even with missing postmeta, the end results are not affected.
	 *
	 * @param RelationshipRole $for_role
	 * @param string $meta_key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function wp_postmeta( RelationshipRole $for_role, $meta_key ) {
		if ( ! is_string( $meta_key ) || empty( $meta_key ) ) {
			throw new InvalidArgumentException();
		}

		$role_name = $for_role->get_name();

		if (
			null === toolset_getnest(
				$this->registered_wp_postmeta_joins, array( $role_name, $meta_key ), null
			)
		) {
			$table_alias = $this->unique_table_alias->generate( $this->wpdb->postmeta, true );

			if ( ! isset( $this->registered_wp_postmeta_joins[ $role_name ] ) ) {
				$this->registered_wp_postmeta_joins[ $role_name ] = array();
			}

			$this->registered_wp_postmeta_joins[ $role_name ][ $meta_key ] = $table_alias;
		}

		return $this->registered_wp_postmeta_joins[ $role_name ][ $meta_key ];
	}


	/**
	 * Get an alias for a relationships table JOINed on the relationships_id column.
	 *
	 * @return string
	 */
	public function relationships() {
		$this->join_relationships = true;

		return self::ALIAS_RELATIONSHIPS;
	}


	/**
	 * Build the final MySQL query part containing all requested JOIN clauses.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return string
	 */
	public function get_join_clause( ElementSelectorInterface $element_selector ) {

		// The order of JOINing is very important here:
		//
		// The JOINs coming from the element selector might reference the relationships table.
		//
		// Any other JOINs will be most probably referencing the elements, so they
		// must be added only after the JOINs from the element selector.
		//
		// However, we first resolve those additional joins and only after that add the joins
		// from the element selector. This way, the element selector will know exactly what
		// element roles it can skip entirely (and save a lot of database performance if we have
		// WPML active).
		$results = array();

		// JOINs that come after the relationships table and element selector JOINs
		// but need to be determined in advance.
		$additional_joins = array();

		if ( $this->join_relationships ) {
			$results[] = sprintf(
				' JOIN %1$s AS %2$s ON ( %3$s.%4$s = %2$s.id ) ',
				$this->table_names->get_full_table_name( TableNames::RELATIONSHIPS ),
				self::ALIAS_RELATIONSHIPS,
				self::ALIAS_ASSOCIATIONS,
				AssociationTable::RELATIONSHIP_ID
			);
		}

		foreach ( $this->registered_wp_posts_joins as $role_name => $table_alias ) {
			$id_column_alias = $element_selector->get_element_id_value(
				\Toolset_Relationship_Role::role_from_name( $role_name )
			);

			$additional_joins[] = sprintf(
				' JOIN %1$s AS %2$s ON (%2$s.ID = %3$s) ',
				$this->wpdb->posts,
				$table_alias,
				$id_column_alias
			);
		}

		foreach ( $this->registered_wp_postmeta_joins as $role_name => $postmeta_list ) {
			foreach ( $postmeta_list as $meta_key => $table_alias ) {
				$id_column_alias = $element_selector->get_element_id_value(
					\Toolset_Relationship_Role::role_from_name( $role_name )
				);

				$additional_joins[] = sprintf(
					" LEFT JOIN %s AS %s ON (%s.post_id = %s AND %s.meta_key = '%s') ",
					$this->wpdb->postmeta,
					$table_alias,
					$table_alias,
					$id_column_alias,
					$table_alias,
					esc_sql( $meta_key )
				);
			}
		}

		$results[] = $element_selector->get_join_clauses();

		// Append the additonal JOINs after the relationships table and tables
		// for the element ID resolution.
		$results = array_merge( $results, $additional_joins );

		return ' ' . implode( "\n", $results ) . ' ';
	}

}
