<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence;

use IToolset_Element;
use IToolset_Post;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\ConnectedElementGroup;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Handles the persistence of rows in the connected elements table.
 *
 * Needs to be handled as a singleton.
 *
 * @since 4.0
 */
class ConnectedElementPersistence {

	/** @var \wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;

	/** @var WpmlService */
	private $wpml_service;

	/** @var \Toolset_Element_Factory */
	private $element_factory;


	/**
	 * ConnectedElementPersistence constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 * @param WpmlService $wpml_service
	 * @param \Toolset_Element_Factory $element_factory
	 */
	public function __construct(
		\wpdb $wpdb, TableNames $table_names, WpmlService $wpml_service, \Toolset_Element_Factory $element_factory
	) {
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
		$this->wpml_service = $wpml_service;
		$this->element_factory = $element_factory;
	}


	/**
	 * For a given element, obtain its group_id. Caching on the element object is used.
	 *
	 * New group_id will be generated if $create_if_missing is true.
	 *
	 * @param IToolset_Element $element
	 * @param bool $create_if_missing
	 *
	 * @return int|null
	 */
	public function obtain_element_group_id( IToolset_Element $element, $create_if_missing = true ) {
		// If the element already knows its group_id, let's use it. But we must not use caching
		// since that would lead to an infinite recursion (because the implementation in Toolset_Element
		// uses this method).
		$group_id = $element->get_connected_group_id( false, true );
		if ( $group_id ) {
			return $group_id;
		}

		// Query the database if we don't have the value yet.
		$group_id = (int) $this->wpdb->get_var( $this->get_element_group_id_query( $element ) );

		// No group_id yet. We might want to create a new one.
		if ( 0 === $group_id && $create_if_missing ) {
			$group_id = $this->create_element_group_id( $element );
		}

		// If we end up with a specific value, let's also cache it in the element.
		if ( 0 !== $group_id ) {
			$element->set_connected_group_id( $group_id );
		}

		return $group_id;
	}


	/**
	 * A much less optimised version of obtain_element_group_id() that doesn't require
	 * the element model to exist.
	 *
	 * @param int $element_id
	 * @param string $domain
	 *
	 * @return int Zero if the group_id isn't assigned.
	 */
	public function query_element_group_id_directly( $element_id, $domain ) {
		$connected_element_group_id = ConnectedElementTable::GROUP_ID;
		$connected_element_wpml_trid = ConnectedElementTable::WPML_TRID;
		$connected_element_domain = ConnectedElementTable::DOMAIN;
		$connected_element_element_id = ConnectedElementTable::ELEMENT_ID;
		$posts_domain = \Toolset_Element_Domain::POSTS;

		$icl_translations_trid = IclTranslationsTable::TRID;
		$icl_translations_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_element_type = IclTranslationsTable::ELEMENT_TYPE;

		if ( $this->wpml_service->is_wpml_active_and_configured() ) {
			return (int) $this->wpdb->get_var( $this->wpdb->prepare(
				"SELECT connected_element.{$connected_element_group_id}
				FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
					AS connected_element
				LEFT JOIN {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
					AS translation
					ON (
						connected_element.{$connected_element_wpml_trid} = translation.{$icl_translations_trid}
						AND connected_element.{$connected_element_domain} = '{$posts_domain}'
						AND translation.{$icl_translations_element_type} LIKE %s
					)
				WHERE
					connected_element.{$connected_element_domain} = %s
					AND (
						connected_element.{$connected_element_element_id} = %d
						OR translation.{$icl_translations_element_id} = %d
					)",
				'post_%',
				$domain,
				$element_id,
				$element_id
			) );
		}

		return (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT connected_element.{$connected_element_group_id}
				FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
					AS connected_element
				WHERE
					connected_element.{$connected_element_domain} = %s
					AND connected_element.{$connected_element_element_id} = %d",
			$domain,
			$element_id
		) );
	}


	/**
	 * Build the MySQL query for getting the element's group_id.
	 *
	 * @param IToolset_Element $element
	 *
	 * @return string
	 */
	private function get_element_group_id_query( IToolset_Element $element ) {
		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$element_id_column = ConnectedElementTable::ELEMENT_ID;
		$wpml_trid_column = ConnectedElementTable::WPML_TRID;
		$domain_column = ConnectedElementTable::DOMAIN;
		$group_id_column = ConnectedElementTable::GROUP_ID;

		// Note: This can be further optimized, we can check if the trid is already cached, and if not,
		// use a different query with a direct JOIN on the icl_translations table.
		$element_trid = ( $element instanceof IToolset_Post ? $element->get_trid() : 0 );

		$element_condition =
			0 !== $element_trid
				? $this->wpdb->prepare( "{$connected_elements_table}.{$wpml_trid_column} = %d", $element_trid )
				: $this->wpdb->prepare( "{$connected_elements_table}.{$element_id_column} = %d", $element->get_id() );

		return $this->wpdb->prepare(
			"SELECT {$group_id_column}
			FROM {$connected_elements_table}
			WHERE
			    {$connected_elements_table}.{$domain_column} = %s
			    AND {$element_condition}
			LIMIT 1",
			$element->get_domain()
		);
	}


	/**
	 * Create a new record for a given element while safely generating a new group_id for it.
	 *
	 * Must not be used if the element already has a group_id.
	 *
	 * @param IToolset_Element $element
	 *
	 * @return int New group_id value for this element.
	 */
	private function create_element_group_id( IToolset_Element $element ) {
		$group_id_column = ConnectedElementTable::GROUP_ID;
		$element_id_column = ConnectedElementTable::ELEMENT_ID;
		$wpml_trid_column = ConnectedElementTable::WPML_TRID;
		$domain_colum =ConnectedElementTable::DOMAIN;
		$lang_code_column = ConnectedElementTable::LANG_CODE;
		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );

		// Unfortunately, there doesn't seem to be a better way to do this. We need the whole table locked and
		// we can't rely on transactions either (the tables could be also using the MyISAM engine).
		$this->wpdb->query( "LOCK TABLES $connected_elements_table WRITE" );

		$last_id = (int) $this->wpdb->get_var( "SELECT MAX($group_id_column) FROM $connected_elements_table" );

		$new_group_id = $last_id + 1;

		$this->wpdb->query( $this->wpdb->prepare(
			"INSERT INTO $connected_elements_table (
                   $group_id_column,
                   $element_id_column,
                   $wpml_trid_column,
                   $domain_colum,
                   $lang_code_column
			) VALUES ( %d, %d, %d, %s, '' )",
			$new_group_id,
			$element->get_id(),
			$element instanceof IToolset_Post ? $element->get_trid() : 0,
			$element->get_domain()
		) );

		$this->wpdb->query( 'UNLOCK TABLES' );

		return $new_group_id;
	}


	/**
	 * From an element group_id, instantiate a IToolset_Element model for it.
	 *
	 * @param int $group_id
	 *
	 * @return IToolset_Element
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_element_by_group_id( $group_id ) {
		$group_id_column = ConnectedElementTable::GROUP_ID;

		$element_data = $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT *
		 	FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
		 	WHERE {$group_id_column} = %d",
			$group_id
		) );

		if ( null === $element_data ) {
			throw new \Toolset_Element_Exception_Element_Doesnt_Exist( '', 0 );
		}

		// Note that this is not domain-agnostic. When that becomes needed eventually,
		// we should handle this inside WpmlService instead.
		return $this->element_factory->get_element(
			$element_data->{ConnectedElementTable::DOMAIN},
			apply_filters( 'wpml_object_id', $element_data->{ConnectedElementTable::ELEMENT_ID}, 'all', true )
		);
	}


	/**
	 * Build a model of a specific element group.
	 *
	 * @param int $group_id
	 *
	 * @return ConnectedElementGroup|null Null if the group ID doesn't correspond with any information.
	 */
	public function get_connected_element_group( $group_id ) {
		$group_id_column = ConnectedElementTable::GROUP_ID;

		// The non-WPML case is rather trivial, we'll always have a single element.
		if ( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			$element_data = $this->wpdb->get_row( $this->wpdb->prepare(
				"SELECT *
				FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
				WHERE {$group_id_column} = %d",
				$group_id
			) );

			if ( null === $element_data ) {
				return null;
			}

			$element_id = (int) $element_data->{ConnectedElementTable::ELEMENT_ID};

			return new ConnectedElementGroup(
				$group_id,
				[ $element_id ],
				$element_data->{ConnectedElementTable::DOMAIN},
				$element_id,
				$element_data->{ConnectedElementTable::WPML_TRID}
			);
		}

		// WPML-aware query.
		//
		// Try to join the icl_translations table on the TRID value and fetch all translation IDs as well.
		$connected_elements_wpml_trid = ConnectedElementTable::WPML_TRID;
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_domain = ConnectedElementTable::DOMAIN;
		$icl_translations_element_type = IclTranslationsTable::ELEMENT_TYPE;
		$icl_translations_trid = IclTranslationsTable::TRID;
		$icl_translations_element_id = IclTranslationsTable::ELEMENT_ID;

		$results = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT
    			connected_elements.{$connected_elements_domain} AS domain,
    			connected_elements.{$connected_elements_element_id} AS connected_element_id,
    			translations.{$icl_translations_element_id} AS translation_id,
				connected_elements.{$connected_elements_wpml_trid} AS wpml_trid
			FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
				AS connected_elements
			LEFT JOIN {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
				AS translations
				ON (
				    translations.{$icl_translations_trid} = connected_elements.{$connected_elements_wpml_trid}
					AND translations.{$icl_translations_element_type} LIKE %s
				    AND connected_elements.{$connected_elements_domain} = %s
				)
			WHERE connected_elements.{$group_id_column} = %d",
			'post_%',
			\Toolset_Element_Domain::POSTS,
			$group_id
		) );

		if ( count( $results ) === 0 ) {
			return null;
		}

		// All results will have the same domain and TRID.
		$domain = reset( $results )->domain;
		$wpml_trid = reset( $results )->wpml_trid;

		// Reduce the results to a flat array of unique IDs where one element will always
		// have the 'directly_stored' key.
		$element_ids = array_unique(
			array_filter(
				array_reduce( $results, function ( array $carry, $item ) {
					// The 'directly_stored' key needs to go first because of array_unique().
					$carry['directly_stored'] = (int) $item->connected_element_id;
					$carry[] = (int) $item->translation_id;

					return $carry;
				}, [] ),
				function ( $element_id ) {
					return ! empty( $element_id );
				}
			)
		);

		return new ConnectedElementGroup( $group_id, $element_ids, $domain, $element_ids['directly_stored'], $wpml_trid );
	}


	/**
	 * Load an element group by a TRID value that's stored in it.
	 *
	 * @param $trid
	 *
	 * @return ConnectedElementGroup|null
	 */
	public function get_element_group_by_trid( $trid ) {
		$group_id_column = ConnectedElementTable::GROUP_ID;
		$trid_column = ConnectedElementTable::WPML_TRID;
		$group_id = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT {$group_id_column}
			FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
			WHERE {$trid_column} = %d
			LIMIT 1",
			$trid
		) );

		if ( ! $group_id ) {
			return null;
		}

		return $this->get_connected_element_group( $group_id );
	}


	/**
	 * Load an element group by the directly stored element ID.
	 *
	 * @param int $element_id
	 * @param string $domain
	 *
	 * @return ConnectedElementGroup|null
	 */
	public function get_element_group_by_element_id( $element_id, $domain ) {
		$group_id_column = ConnectedElementTable::GROUP_ID;
		$element_id_column = ConnectedElementTable::ELEMENT_ID;
		$domain_column = ConnectedElementTable::DOMAIN;
		$group_id = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT {$group_id_column}
			FROM {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS )}
			WHERE {$element_id_column} = %d
				AND {$domain_column} = %s",
			$element_id,
			$domain
		) );

		if ( ! $group_id ) {
			return null;
		}

		return $this->get_connected_element_group( $group_id );
	}


	/**
	 * Remove a given element from an element group.
	 *
	 * Note that all data is assumed valid. The provided $element_id must be part of the group
	 * and the element's domain must match the group's.
	 *
	 * @param ConnectedElementGroup $group
	 * @param $element_id
	 */
	public function remove_element_from_group( ConnectedElementGroup $group, $element_id ) {
		if ( $group->has_last_element() ) {
			$this->delete_group( $group );

			return;
		}

		if ( $group->get_directly_stored_id() !== $element_id ) {
			// The element ID is not stored directly in the connnected elements table,
			// it's just one of the translations. So we don't have to do anything.
			return;
		}

		// It is not strictly necessary in the fallback mode but we want to keep valid element IDs even for
		// element groups where we rely on TRIDs. So, we simply choose any other element ID from the group
		$possible_replacements = array_filter(
			$group->get_element_ids(),
			function ( $replacement_element_id ) use ( $element_id ) {
				return $replacement_element_id !== $element_id;
			} );

		$replacement_element_id = reset( $possible_replacements );
		$this->wpdb->update(
			$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ),
			[ ConnectedElementTable::ELEMENT_ID => $replacement_element_id ],
			[ ConnectedElementTable::GROUP_ID => $group->get_id() ],
			'%d',
			'%d'
		);
	}


	/**
	 * Completely remove given element group from the connected elements table.
	 *
	 * @param ConnectedElementGroup $group
	 */
	public function delete_group( ConnectedElementGroup $group ) {
		$this->wpdb->delete(
			$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ),
			[ ConnectedElementTable::GROUP_ID => $group->get_id() ],
			'%d'
		);
	}


	/**
	 * Set a new TRID for a particular element group.
	 *
	 * @param ConnectedElementGroup $group
	 * @param int $new_trid
	 */
	public function update_group_trid( ConnectedElementGroup $group, $new_trid ) {
		$this->wpdb->update(
			$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ),
			[ ConnectedElementTable::WPML_TRID => (int) $new_trid ],
			[ ConnectedElementTable::GROUP_ID => $group->get_id() ],
			'%d',
			'%d'
		);
	}

}
