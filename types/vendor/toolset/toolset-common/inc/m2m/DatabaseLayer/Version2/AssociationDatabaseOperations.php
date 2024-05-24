<?php
/** @noinspection SqlResolve */
/** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2;

use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;
use OTGS\Toolset\Common\Result\ResultUpdated;
use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Relationship_Definition;

/**
 * Various database operations with associations.
 *
 * Intended for highly specific edge cases, cleanup routines, etc.
 *
 * Note: Please try to not make this class grow any further.
 */
class AssociationDatabaseOperations implements \OTGS\Toolset\Common\Relationships\API\AssociationDatabaseOperations {


	/** @var \Toolset_Relationship_Definition_Repository */
	private $definition_repository;

	/** @var \wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;

	/** @var \Toolset_Element_Factory */
	private $element_factory;

	/** @var WpmlService */
	private $wpml_service;

	/** @var ConnectedElementPersistence */
	private $connected_element_persistence;


	/**
	 * AssociationDatabaseOperations constructor.
	 *
	 * @param \Toolset_Relationship_Definition_Repository $definition_repository
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 * @param \Toolset_Element_Factory $element_factory
	 * @param WpmlService $wpml_service
	 * @param ConnectedElementPersistence $connected_element_persistence
	 */
	public function __construct(
		\Toolset_Relationship_Definition_Repository $definition_repository,
		\wpdb $wpdb,
		TableNames $table_names,
		\Toolset_Element_Factory $element_factory,
		WpmlService $wpml_service,
		ConnectedElementPersistence $connected_element_persistence
	) {
		$this->definition_repository = $definition_repository;
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
		$this->element_factory = $element_factory;
		$this->wpml_service = $wpml_service;
		$this->connected_element_persistence = $connected_element_persistence;
	}


	/**
	 * @inheritDoc
	 */
	public function create_association(
		$relationship_definition_source,
		$parent_source,
		$child_source,
		$intermediary_id,
		$instantiate = true
	) {
		/** @var Toolset_Relationship_Definition $relationship_definition */
		$relationship_definition = $this->get_relationship_definition( $relationship_definition_source );
		if ( ! $relationship_definition instanceof IToolset_Relationship_Definition ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'Relationship definition "%s" doesn\'t exist.', 'wpv-views' ),
					is_string( $relationship_definition_source ) ? $relationship_definition_source
						: print_r( $relationship_definition_source, true )
				)
			);
		}

		return $relationship_definition->get_driver()->create_association(
			$parent_source,
			$child_source,
			[
				'intermediary_id' => $intermediary_id,
				'instantiate' => (bool) $instantiate,
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function delete_associations_by_element( $relationship, $element_role_name, $element_id ) {
		$role = \Toolset_Relationship_Role::role_from_name( $element_role_name );
		$element_group_id_column = AssociationTable::role_to_column( $role );

		// We are not instantiating the element at all since it is possible it doesn't actually exist
		// and this method is being used to remove dangling associations.
		$element_group_id = $this->connected_element_persistence->query_element_group_id_directly(
			$element_id, $relationship->get_domain( $role )
		);

		if ( null === $element_group_id ) {
			// If the element has no group_id yet, it means it's not in the connected elements table,
			// and that means it's not a part of any association.
			return;
		}
		$this->wpdb->delete(
			$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
			[
				AssociationTable::RELATIONSHIP_ID => $relationship->get_row_id(),
				$element_group_id_column => $element_group_id,
			],
			[ '%d', '%d' ]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function delete_association_by_element_in_any_role( \IToolset_Element $element ) {
		$element_group_id = $this->connected_element_persistence->obtain_element_group_id( $element, false );

		if ( null === $element_group_id ) {
			// If the element has no group_id yet, it means it's not in the connected elements table,
			// and that means it's not a part of any association.
			return new SingleResult( true );
		}

		/**
		 * toolset_before_associations_by_element_delete
		 *
		 * An action that runs before deleting multiple associations that involve a particular element. Since there
		 * may be a very large number of those associations (sky is the limit), we cannot safely iterate through
		 * and do the toolset_before_association_delete action for each of them.
		 *
		 * This action is the next best thing available.
		 *
		 * @param int $element_id
		 * @param int $element_domain
		 * @since 4.0.10
		 */
		do_action( 'toolset_before_associations_by_element_delete', $element->get_id(), $element->get_domain() );

		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$parent_id_column = AssociationTable::PARENT_ID;
		$child_id_column = AssociationTable::CHILD_ID;
		$intermediary_id_column = AssociationTable::INTERMEDIARY_ID;

		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$domain_column = ConnectedElementTable::DOMAIN;
		$group_id_column = ConnectedElementTable::GROUP_ID;

		$deleted_rows = $this->wpdb->query( $this->wpdb->prepare(
			"DELETE association
			FROM $associations_table AS association
			JOIN $connected_elements_table AS parent_elements
				ON ( association.$parent_id_column = parent_elements.$group_id_column )
			JOIN $connected_elements_table AS child_elements
				ON ( association.$child_id_column = child_elements.$group_id_column )
			LEFT JOIN $connected_elements_table AS intermediary_elements
				ON ( association.$intermediary_id_column = intermediary_elements.$group_id_column )
			WHERE
				( parent_elements.$domain_column = %s AND parent_elements.$group_id_column = %d )
				OR ( child_elements.$domain_column = %s AND child_elements.$group_id_column = %d )
				OR ( intermediary_elements.$domain_column = %s AND intermediary_elements.$group_id_column = %d )",
			$element->get_domain(),
			$element_group_id,
			$element->get_domain(),
			$element_group_id,
			$element->get_domain(),
			$element_group_id
		) );

		return new SingleResult( $deleted_rows > 0 );
	}


	/**
	 * @inheritDoc
	 */
	public function delete_associations_by_relationship( $relationship_row_id ) {
		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );

		$result = $this->wpdb->delete(
			$associations_table,
			[ AssociationTable::RELATIONSHIP_ID => (int) $relationship_row_id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			return new ResultUpdated(
				false, 0,
				sprintf( __( 'Database error when deleting associations: "%s"', 'wpv-views' ), $this->wpdb->last_error )
			);
		} else {
			return new ResultUpdated(
				true, $result,
				sprintf( __( 'Deleted all associations for the relationship #%d', 'wpv-views' ), $relationship_row_id )
			);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function delete_association( \IToolset_Association $association ) {
		$rows_updated = $this->wpdb->delete(
			$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
			[ AssociationTable::ID => $association->get_uid() ],
			'%d'
		);

		$is_success = ( 1 === $rows_updated );

		do_action( 'toolset_association_deleted', $association->get_uid() );

		return new SingleResult( $is_success );
	}


	/**
	 * @inheritDoc
	 */
	public function delete_intermediary_posts_by_element( $relationship, $element_role_name, $element_id ) {
		$element_role = \Toolset_Relationship_Role::role_from_name( $element_role_name );

		$element_group_id_column = AssociationTable::role_to_column( $element_role );

		// We are not instantiating the element at all since it is possible it doesn't actually exist
		// and this method is being used to remove dangling associations.
		$element_group_id = $this->connected_element_persistence->query_element_group_id_directly(
			$element_id,
			$relationship->get_domain( $element_role )
		);

		if ( null === $element_group_id ) {
			// If the element has no group_id yet, it means it's not in the connected elements table,
			// and that means it's not a part of any association.
			return;
		}

		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$assiciations_intermediary_id = AssociationTable::INTERMEDIARY_ID;
		$associations_relationship_id = AssociationTable::RELATIONSHIP_ID;

		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$connected_elements_group_id = ConnectedElementTable::GROUP_ID;
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_wpml_trid = ConnectedElementTable::WPML_TRID;

		$icl_translations_table = $this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS );
		$icl_translations_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_trid = IclTranslationsTable::TRID;

		if (
			$this->wpml_service->is_wpml_active_and_configured()
			&& $relationship->is_translatable()
		) {
			$query = $this->wpdb->prepare(
				"SELECT
    			connected_intermediary.$connected_elements_element_id AS element_id,
    			translations.$icl_translations_element_id AS translation_id
			FROM $associations_table AS associations
			JOIN $connected_elements_table AS connected_intermediary
				ON ( connected_intermediary.$connected_elements_group_id = associations.$assiciations_intermediary_id )
			LEFT JOIN $icl_translations_table AS translations
				ON (
				    connected_intermediary.$connected_elements_wpml_trid IS NOT NULL
				    AND connected_intermediary.$connected_elements_wpml_trid = translations.$icl_translations_trid
				)
			WHERE associations.$associations_relationship_id = %d
				AND associations.$element_group_id_column = %d",
				$relationship->get_row_id(),
				$element_group_id
			);

			$results = $this->wpdb->get_results( $query );

			$intermediary_post_ids = array_unique(
				array_filter(
					array_reduce( $results, function ( $carry, $item ) {
						$carry[] = (int) $item->element_id;
						$carry[] = (int) $item->translation_id;

						return $carry;
					}, [] ),
					function ( $element_id ) {
						return ! empty( $element_id );
					}
				)
			);
		} else {
			$results = $this->wpdb->get_col( $this->wpdb->prepare(
				"SELECT connected_intermediary.$connected_elements_element_id AS element_id,
			FROM $associations_table AS associations
			JOIN $connected_elements_table AS connected_intermediary
				ON ( connected_intermediary.$connected_elements_group_id = associations.$assiciations_intermediary_id )
			WHERE associations.$associations_relationship_id = %d
				AND associations.$element_group_id_column = %d",
				$relationship->get_row_id(),
				$element_group_id
			) );

			$intermediary_post_ids = array_filter(
				array_map( 'intval', $results ),
				function ( $post_id ) {
					return ! empty( $post_id );
				}
			);
		}


		foreach ( $intermediary_post_ids as $post_id ) {
			wp_delete_post( $post_id );
		}
	}


	/**
	 * @inheritDoc
	 */
	public function update_associations_on_definition_renaming( IToolset_Relationship_Definition $old_definition, IToolset_Relationship_Definition $new_definition ) {
		$rows_updated = $this->wpdb->update(
			$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
			[ AssociationTable::RELATIONSHIP_ID => $new_definition->get_row_id() ],
			[ AssociationTable::RELATIONSHIP_ID => $old_definition->get_row_id() ],
			'%d',
			'%d'
		);

		$is_success = ( false !== $rows_updated );

		$message = $is_success
			? sprintf(
				__( 'The association table has been updated with the new relationship slug "%s". %d rows have been updated.', 'wpv-views' ),
				$new_definition->get_slug(),
				$rows_updated
			)
			: sprintf(
				__( 'There has been an error when updating the association table with the new relationship slug: %s', 'wpv-views' ),
				$this->wpdb->last_error
			);

		return new SingleResult( $is_success, $message );
	}


	/**
	 * @inheritDoc
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function update_association_intermediary_id( $association_id, $intermediary_id ) {
		if( 0 !== $intermediary_id) {
			$intermediary_post = $this->element_factory->get_post( $intermediary_id );
			$intermediary_post_group_id = $this->connected_element_persistence->obtain_element_group_id(
				$intermediary_post, true
			);
		} else {
			$intermediary_post_group_id = 0;
		}
		$this->wpdb->update(
			$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
			[ AssociationTable::INTERMEDIARY_ID => $intermediary_post_group_id ],
			[ AssociationTable::ID => $association_id ],
			[ '%d' ],
			[ '%d' ]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function count_max_associations( $relationship_id, $role_name ) {
		if ( ! in_array( $role_name, \Toolset_Relationship_Role::parent_child_role_names() ) ) {
			throw new \InvalidArgumentException( 'Wrong role name' );
		}
		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );
		$element_id_column = AssociationTable::role_to_column(
			\Toolset_Relationship_Role::role_from_name( $role_name )
		);
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT max(n) count
					FROM (
						SELECT count(*) n
							FROM {$associations_table}
							WHERE relationship_id = %d
							GROUP BY {$element_id_column}
					) count", $relationship_id ) );

		return (int) $count;
	}


	/**
	 * @inheritDoc
	 */
	public function get_dangling_intermediary_posts( array $intermediary_post_types, array $post_types_to_delete_by ) {
		$ipts = '\'' . implode( '\', \'', esc_sql( $intermediary_post_types ) ) . '\'';
		$limit = (int) Constants::DELETE_POSTS_PER_BATCH;

		$relationship_ipt_column = RelationshipTable::INTERMEDIARY_TYPE;
		$relationship_autodelete_ip_column = RelationshipTable::AUTODELETE_INTERMEDIARY;

		$icl_translations_element_id_column = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_trid = IclTranslationsTable::TRID;
		$icl_translations_element_type = IclTranslationsTable::ELEMENT_TYPE;

		$connected_elements_trid = ConnectedElementTable::WPML_TRID;
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_group_id = ConnectedElementTable::GROUP_ID;
		$connected_elements_domain = ConnectedElementTable::DOMAIN;

		$associations_intermediary_id = AssociationTable::INTERMEDIARY_ID;

		$post_domain = \Toolset_Element_Domain::POSTS;

		// Query posts that aren't used as intermediary posts (either directly or as a translation group)
		// with following restrictions:
		// - the relationship they belong with has the flag to automatically delete intermediary posts
		//   once they're no longer being used (otherwise they're not "dangling" by definition)
		// - they have one of the specified post types.
		// - there is no association connected to them (which makes them "unused")
		if ( $this->wpml_service->is_wpml_active_and_configured() ) {
			$query = "SELECT SQL_CALC_FOUND_ROWS post.ID
				FROM {$this->wpdb->posts} AS post
				JOIN {$this->table_names->get_full_table_name( TableNames::RELATIONSHIPS )}
					AS relationship
					ON (
						post.post_type = relationship.{$relationship_ipt_column}
						AND relationship.{$relationship_autodelete_ip_column} = 1
					)
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
					AS translation
					ON (
					    translation.{$icl_translations_element_id_column} = post.ID
					    AND translation.{$icl_translations_element_type} LIKE 'post_%'
					)
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
					AS connected_element
					ON (
						(
						    connected_element.{$connected_elements_trid} = translation.{$icl_translations_trid}
							OR connected_element.{$connected_elements_element_id} = post.ID
						)
						AND connected_element.{$connected_elements_domain} = '{$post_domain}'
					)
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )}
					AS association
					ON ( connected_element.{$connected_elements_group_id} = association.{$associations_intermediary_id})
				WHERE
					association.{$associations_intermediary_id} IS NULL
					AND post.post_type IN ({$ipts})
				ORDER BY post.ID
				LIMIT {$limit}";
		} else {
			$query = "SELECT SQL_CALC_FOUND_ROWS post.ID
				FROM {$this->wpdb->posts} AS post
				JOIN {$this->table_names->get_full_table_name( TableNames::RELATIONSHIPS )}
					AS relationship
					ON (
						post.post_type = relationship.{$relationship_ipt_column}
						AND relationship.{$relationship_autodelete_ip_column} = 1
					)
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
					AS connected_element
					ON (
					    connected_element.{$connected_elements_element_id} = post.ID
						AND connected_element.{$connected_elements_domain} = '{$post_domain}'
					)
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )}
					AS association
					ON ( connected_element.{$connected_elements_group_id} = association.{$associations_intermediary_id})
				WHERE
					association.{$associations_intermediary_id} IS NULL
					AND post.post_type IN ({$ipts})
				LIMIT {$limit}";
		}

		// Additionally, the caller might have requested to provide ALL intermediary posts of a specific
		// post type (e.g. when deleting a relationship).
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
	 * @param $relationship_definition_source
	 *
	 * @return IToolset_Relationship_Definition|null
	 */
	private function get_relationship_definition( $relationship_definition_source ) {
		if ( $relationship_definition_source instanceof IToolset_Relationship_Definition ) {
			return $relationship_definition_source;
		}

		if ( is_string( $relationship_definition_source ) ) {
			return $this->definition_repository->get_definition( $relationship_definition_source );
		}

		if ( is_int( $relationship_definition_source ) ) {
			return $this->definition_repository->get_definition_by_row_id( $relationship_definition_source );
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function requires_default_language_post() {
		return false;
	}
}
