<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\ConnectedElementGroup;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use Toolset_Association_Cleanup_Factory;
use Toolset_Element_Domain;
use Toolset_Relationship_Role;
use wpdb;

/**
 * Appropriately respond to a wpml_translation_update event by updating the connected elements group.
 *
 * @since 4.0
 */
class WpmlTranslationUpdateHandler {


	/** @var string Value of the context key we're interested in. */
	const CONTEXT_POST = 'post';


	/** @var UpdateDescriptionParser */
	private $description_parser;

	/** @var wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;

	/** @var ConnectedElementPersistence */
	private $connected_element_persistence;

	/** @var Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;

	/** @var DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * WpmlTranslationUpdateHandler constructor.
	 *
	 * @param UpdateDescriptionParser $description_parser
	 * @param wpdb $wpdb
	 * @param TableNames $table_names
	 * @param ConnectedElementPersistence $connected_element_persistence
	 * @param Toolset_Association_Cleanup_Factory $cleanup_factory
	 * @param DatabaseLayerFactory $database_layer_factory
	 */
	public function __construct(
		UpdateDescriptionParser $description_parser,
		wpdb $wpdb,
		TableNames $table_names,
		ConnectedElementPersistence $connected_element_persistence,
		Toolset_Association_Cleanup_Factory $cleanup_factory,
		DatabaseLayerFactory $database_layer_factory
	) {
		$this->description_parser = $description_parser;
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
		$this->connected_element_persistence = $connected_element_persistence;
		$this->cleanup_factory = $cleanup_factory;
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * Process the wpml_translation_update event.
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-3237
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-7203
	 *
	 * @param $update_description
	 */
	public function on_wpml_translation_update( $update_description ) {
		if ( ! $this->is_concerning_posts( $update_description ) ) {
			return;
		}

		if (
			! array_key_exists( DescriptionKey::ACTION_TYPE, $update_description )
			|| in_array( $update_description[ DescriptionKey::ACTION_TYPE ], [
				ActionType::DELETE,
				ActionType::RESET,
				ActionType::BEFORE_LANGUAGE_DELETE,
			], true )
		) {
			// Something large has happened, we will try to run a fixup just in case, but this is most probably either
			// irrelevant to relationships or we're completely powerless against it.
			$this->schedule_fixup_routine();
		} elseif ( in_array( $update_description[ DescriptionKey::ACTION_TYPE ], [
			ActionType::UPDATE,
			ActionType::INSERT,
			ActionType::BEFORE_DELETE,
		], true ) ) {
			// A event concerning a single element (a post, specifically) that we absolutely need to handle.
			$this->one_element_action( $update_description );
		} elseif (
			ActionType::INITIALIZE_LANGUAGE_FOR_POST_TYPE === $update_description [ DescriptionKey::ACTION_TYPE ]
			&& array_key_exists( DescriptionKey::POST_TYPE, $update_description )
		) {
			// A post type has newly become translatable. TRIDs have been added to all posts of this type that
			// didn't have them already.
			$this->initialize_language_for_post_type( $update_description[ DescriptionKey::POST_TYPE ] );
		}
	}


	/**
	 * Update the connected elements table with newly added TRIDs.
	 *
	 * @param string $post_type
	 */
	private function initialize_language_for_post_type( $post_type ) {
		// Note: There's no risk on creating duplicate element groups (with the same wpml_trid) because during
		// this action, WPML assigns a new unique TRID to each affected post.
		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_trid = ConnectedElementTable::WPML_TRID;

		$icl_translations_table = $this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS );
		$icl_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_element_type = IclTranslationsTable::ELEMENT_TYPE;
		$icl_trid = IclTranslationsTable::TRID;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// - Because we know what we're doing here.
		$this->wpdb->query( $this->wpdb->prepare(
			"UPDATE {$connected_elements_table} AS destination
			INNER JOIN {$icl_translations_table} AS source ON (
				destination.{$connected_elements_element_id} = source.{$icl_element_id} AND source.{$icl_element_type} LIKE %s
			)
			SET destination.{$connected_elements_trid} = source.{$icl_trid}
			WHERE destination.{$connected_elements_trid} = 0 OR destination.{$connected_elements_trid} IS NULL;",
			IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX . $post_type
		) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}


	/**
	 * Determine if the update even concerns a post or a different element domain.
	 *
	 * If the context is not available, we may still be able to infer it from the element_type value.
	 *
	 * @param array $update_description
	 *
	 * @return bool
	 */
	private function is_concerning_posts( $update_description ) {
		return (
			(
				array_key_exists( DescriptionKey::CONTEXT, $update_description )
				&& self::CONTEXT_POST === $update_description[ DescriptionKey::CONTEXT ]
			)
			|| (
				array_key_exists( DescriptionKey::ELEMENT_TYPE, $update_description )
				&& substr(
					$update_description[ DescriptionKey::ELEMENT_TYPE ], strlen( IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX )
				) === IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX
			)
		);
	}


	/**
	 * Handle an update event concerning a single element.
	 *
	 * @param array $update_description_source
	 */
	private function one_element_action( $update_description_source ) {
		$update_description = $this->description_parser->parse( $update_description_source );

		if (
			$update_description->get_affected_post_id()
			&& (
				wp_is_post_autosave( $update_description->get_affected_post_id() )
				|| wp_is_post_revision( $update_description->get_affected_post_id() )
			)
		) {
			// Filter out obviously irrelevant changes. Careful about the post ID being present: It it isn't there,
			// it doesn't automatically mean the event isn't relevant to us.
			return;
		}

		// One important thing to consider here: These actions concern updating, inserting and deleting
		// *translation information* - rows in the icl_translations table, basically - but not necessarily
		// affected posts themselves. Keep this in mind while reading on.
		switch ( $update_description->get_action_type() ) {
			case ActionType::UPDATE:
				$this->one_element_update( $update_description );
				break;
			case ActionType::INSERT:
				$this->one_element_insert( $update_description );
				break;
			case ActionType::BEFORE_DELETE:
				$this->one_element_before_delete( $update_description );
				break;
		}
	}


	/**
	 * New language information for an element.
	 *
	 * If an element group is affected, update its TRID value.
	 *
	 * @param UpdateDescription $update_description
	 */
	private function one_element_insert( UpdateDescription $update_description ) {
		if (
			! $update_description->get_affected_post_id()
			|| ! $update_description->get_current_trid()
		) {
			return;
		}

		$element_group = $this->connected_element_persistence->get_element_group_by_element_id(
			$update_description->get_affected_post_id(), Toolset_Element_Domain::POSTS
		);

		if ( ! $element_group ) {
			return;
		}

		$this->connected_element_persistence->update_group_trid( $element_group, $update_description->get_current_trid() );
	}


	/**
	 * Existing language information has been updated.
	 *
	 * @param UpdateDescription $update_description
	 */
	private function one_element_update( UpdateDescription $update_description ) {
		if (
			! $update_description->get_previous_trid()
			&& $update_description->get_current_trid()
			&& $update_description->get_affected_post_id()
		) {
			// There's an affected post that (a) isn't referenced in the connected element group or
			// (b) didn't previously have a TRID but now it receives one.
			//
			// We will search for it (by element ID) in the connected element table and
			// update the language information accordingly.
			$element_group = $this->connected_element_persistence->get_element_group_by_element_id(
				$update_description->get_affected_post_id(), Toolset_Element_Domain::POSTS
			);

			if ( ! $element_group ) {
				// This will be the most probable case: The element is not part of any group, not used
				// in associations yet.
				return;
			}

			if (
				! $element_group->get_trid()
				&& $element_group->get_trid() !== $update_description->get_current_trid()
			) {
				// So, an element group without a previous TRID value is getting one.
				//
				// Two options here, when it comes to the current (new) TRID:
				if ( $this->is_trid_already_used( $update_description->get_current_trid() ) ) {
					// (a) The element group doesn't have a previous TRID yet *and* the new TRID
					// is not being used anywhere else. So, we can simply update the group with the new TRID and be done with it.
					// There is no risk of any conflict because the group contains only the one element we're dealing with right now.
					$this->update_associations_group_id(
						$element_group,
						$update_description->get_current_trid()
					);
				} else {
					// (b) The new TRID is already being used in associations, which means
					// the current post joins the same translation group. In which case, we need to update the affected associations
					// with the element group ID with the new TRID.
					$this->connected_element_persistence->update_group_trid(
						$element_group,
						$update_description->get_current_trid()
					);
				}
			}

		} elseif (
			$update_description->get_previous_trid()
			&& ! $update_description->get_current_trid()
			&& $update_description->get_affected_post_id()
			&& $update_description->get_affected_element_group()
		) {
			// We have an existing element group which is affected by the fact that one of its
			// posts is getting its TRID removed.

			$this->connected_element_persistence->remove_element_from_group(
				$update_description->get_affected_element_group(),
				$update_description->get_affected_post_id()
			);
		} elseif (
			$update_description->get_previous_trid()
			&& $update_description->get_current_trid()
			&& $update_description->get_affected_element_group()
			&& $update_description->get_previous_trid() !== $update_description->get_current_trid()
		) {
			// We get to this point when we have an element whose TRID has changed and which is already
			// a part of an existing element group.
			$element_group = $update_description->get_affected_element_group();
			if ( $element_group->has_last_element() ) {
				if ( $this->is_trid_already_used( $update_description->get_current_trid() ) ) {
					$this->update_associations_group_id(
						$element_group,
						$update_description->get_current_trid()
					);
				} else {
					$this->connected_element_persistence->update_group_trid(
						$element_group,
						$update_description->get_current_trid()
					);
				}
			} elseif ( $update_description->get_affected_post_id() ) {
				$this->connected_element_persistence->remove_element_from_group(
					$element_group, $update_description->get_affected_post_id()
				);
			}
		}
	}


	/**
	 * Check if a TRID is already used in any connected element group.
	 *
	 * @param int $trid
	 *
	 * @return bool
	 */
	private function is_trid_already_used( $trid ) {
		$trid_column = ConnectedElementTable::WPML_TRID;
		$usage_count = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
			WHERE {$trid_column} = %d
			LIMIT 1",
			$trid
		) );

		return $usage_count > 0;
	}


	/**
	 * Update associations that use the given element group in any of their roles with a different group
	 * defined by a TRID value (so, it is assumed that the given TRID is used in an element group).
	 *
	 * The previous group is then deleted, as well as duplicate associations that may have emerged by this update.
	 *
	 * @param ConnectedElementGroup $current_group
	 * @param int $new_trid
	 */
	private function update_associations_group_id( ConnectedElementGroup $current_group, $new_trid ) {
		$new_group = $this->connected_element_persistence->get_element_group_by_trid( $new_trid );
		if ( ! $new_group ) {
			return;
		}
		foreach ( Toolset_Relationship_Role::all() as $role ) {
			$group_id_column = AssociationTable::role_to_column( $role );
			$this->wpdb->update(
				$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS ),
				[ $group_id_column => $new_group->get_id() ],
				[ $group_id_column => $current_group->get_id() ],
				'%d',
				'%d'
			);
		}

		$this->connected_element_persistence->delete_group( $current_group );

		$this->prevent_duplicate_associations_after_merging( $new_group->get_id() );
	}


	/**
	 * Check all associations that involve a provided element group ID for duplicates (connecting the same two
	 * posts withing the same relationships; intermediary posts don't count here - all relationships are distinct
	 * by definition at the time of writing).
	 *
	 * @param int $new_group_id
	 */
	private function prevent_duplicate_associations_after_merging( $new_group_id ) {
		$parent_id_column = AssociationTable::PARENT_ID;
		$child_id_column = AssociationTable::CHILD_ID;
		$id_column = AssociationTable::ID;
		$relationship_id_column = AssociationTable::RELATIONSHIP_ID;

		// See https://stackoverflow.com/a/25206828.
		// There are some scalability concerns, however we can reduce the load by limiting the rows to
		// associations that involve the particular element group ID.
		//
		// Note that this will have to be adjusted/extended to accomodate for non-distinct relationships.
		$associations_to_delete = $this->wpdb->get_col( $this->wpdb->prepare(
			"SELECT b.{$id_column}
			FROM {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )} AS a,
			    {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )} AS b
			WHERE
			    -- Limit the results to associations with the affected group IDs only
				( a.{$parent_id_column} = %d OR a.{$child_id_column} = %d )
				AND ( b.{$parent_id_column} = %d OR b.{$child_id_column} = %d )
			  	-- Make sure one association is preserved.
				AND a.{$id_column} < b.{$id_column}
				-- Select duplicates
				AND a.{$relationship_id_column} = b.{$relationship_id_column}
				AND a.{$parent_id_column} = b.{$parent_id_column}
				AND a.{$child_id_column} = b.{$child_id_column}",
			$new_group_id,
			$new_group_id,
			$new_group_id,
			$new_group_id
		) );

		if ( empty( $associations_to_delete ) ) {
			return;
		}

		// We need to delete associations in PHP because it may involve other processes, like cleaning up
		// an intermediary post type, for example.
		$association_cleanup = $this->cleanup_factory->association();
		$association_persistence = $this->database_layer_factory->association_persistence();
		foreach ( $associations_to_delete as $association_uid ) {
			$association = $association_persistence->load_association_by_uid( (int) $association_uid );

			if ( null === $association ) {
				continue;
			}

			$association_cleanup->delete( $association );
		}
	}


	/**
	 * Called before deleting language information for one element.
	 *
	 * Note that this may happen for newly created elements or before another update of the same element
	 * (delete + re-insert).
	 *
	 * @param UpdateDescription $update_description
	 */
	private function one_element_before_delete( UpdateDescription $update_description ) {
		$affected_element_group = $this->connected_element_persistence->get_element_group_by_trid(
			$update_description->get_current_trid()
		);
		if ( ! $affected_element_group ) {
			return;
		}

		if ( $affected_element_group->get_trid() === 0 ) {
			// WPML is attempting to delete a record of a post that already doesn't have any language
			// information. This might mean that the post will actually receive its TRID
			// with a following update. In any case, there's nothing to do.
			return;
		}

		if ( $affected_element_group->has_last_element() ) {
			// The element is last in its group, which means the group loses all language information.
			$this->connected_element_persistence->update_group_trid(
				$affected_element_group, 0
			);
		} else {
			// The element group persists, we just need to make sure this element's ID is not directly stored there.
			$this->connected_element_persistence->remove_element_from_group(
				$affected_element_group,
				$update_description->get_affected_post_id()
			);
		}
	}


	private function schedule_fixup_routine() {
		// TODO probably irrelevant, just to be 110% sure we don't miss anything.
	}
}
