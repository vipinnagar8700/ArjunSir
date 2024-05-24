<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\ConnectedElementGroup;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Read the data provided by the wpml_translation_update action and turn them into an UpdateDescription instance.
 *
 * The action provides a varying amount and specificity of information in numerous contexts. Here, we add the
 * missing parts whenever it's possible.
 *
 * Note that this works only for contexts that involve a particular element, not site-wide actions which
 * may need special handling.
 *
 * @since 4.0
 */
class UpdateDescriptionParser {


	/** @var ConnectedElementPersistence */
	private $connected_element_persistence;

	/** @var WpmlService */
	private $wpml_service;

	/** @var \wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;


	/**
	 * UpdateDescriptionParser constructor.
	 *
	 * @param ConnectedElementPersistence $connected_element_persistence
	 * @param WpmlService $wpml_service
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct(
		ConnectedElementPersistence $connected_element_persistence,
		WpmlService $wpml_service,
		\wpdb $wpdb,
		TableNames $table_names
	) {
		$this->connected_element_persistence = $connected_element_persistence;
		$this->wpml_service = $wpml_service;
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}


	/**
	 * Parse the update description as given by the wpml_translation_update action.
	 *
	 * @param $update_description
	 *
	 * @return UpdateDescription
	 */
	public function parse( $update_description ) {
		$previous_trid = null;
		$current_trid = null;
		$post_id = null;
		$affected_element_group = null;

		$has_trid = array_key_exists( DescriptionKey::TRID, $update_description );
		$has_element_id = array_key_exists( DescriptionKey::ELEMENT_ID, $update_description );
		$has_translation_id = array_key_exists( DescriptionKey::TRANSLATION_ID, $update_description );

		$action_type = toolset_getarr( $update_description, DescriptionKey::ACTION_TYPE );

		// Try to figure out the current TRID (as is now in the icl_translations table),
		// going from the easies to the most difficult method.
		if ( $has_trid ) {
			$current_trid = $update_description[ DescriptionKey::TRID ];
		} elseif( $has_element_id ) {
			$current_trid = $this->get_current_trid_by_post_id( $update_description[ DescriptionKey::ELEMENT_ID ] );
		} elseif( $has_translation_id ) {
			$current_trid = $this->get_current_trid_by_translation_id( $update_description[ DescriptionKey::TRANSLATION_ID ] );
		}

		if ( $has_element_id ) {
			$post_id = $update_description[ DescriptionKey::ELEMENT_ID ];
 		} elseif( $has_translation_id ) {
			$post_id = $this->get_element_id_by_translation_id( $update_description[ DescriptionKey::TRANSLATION_ID ] );
		} elseif( toolset_getpost( 'icl_ajx_action' ) === 'connect_translations' ) {
			// When connecting a post to a different translation while also setting it as a source element,
			// the wpml_translation_update action doesn't provide the element ID, but it is available in $_POST.
			$post_id = (int) toolset_getpost( 'post_id' );
		}
		$has_element_id = ! empty( $post_id );

		if ( $has_element_id && ActionType::UPDATE === $action_type ) {
			// When not updating, there will be no previous TRID.
			$previous_trid = $this->get_previous_trid_by_post_id( $post_id );
		}

		if ( $previous_trid ) {
			$affected_element_group = $this->connected_element_persistence->get_element_group_by_trid( $previous_trid );
		}
		if ( ! $affected_element_group && $has_element_id ) {
			$affected_element_group = $this->get_previous_element_group_by_post_id( $post_id );
		}

		// Some TRIDs might have changed, better to clear the cache at this point.
		// Note that this is not an actual responsibility of the UpdateDescriptionParser. WpmlService is
		// doing that as well, on wpml_translation_update:11, but we might need it sooner. Better safe than sorry.
		$this->wpml_service->clear_post_trid_cache();

		return new UpdateDescription(
			$action_type,
			$previous_trid,
			$current_trid,
			$post_id,
			$affected_element_group
		);
	}


	/**
	 * Get the TRID the involved post currently has in the icl_translations table.
	 *
	 * @param int $post_id
	 * @return int|null
	 */
	private function get_current_trid_by_post_id( $post_id ) {
		return $this->wpml_service->get_post_trid( $post_id, false, false );
	}


	/**
	 * Retrieve the current TRID value for a specific icl_translations row.
	 *
	 * @param int $translation_id
	 * @return int|null
	 */
	private function get_current_trid_by_translation_id( $translation_id ) {
		$translation_id_column = IclTranslationsTable::TRANSLATION_ID;
		$trid_column = IclTranslationsTable::TRID;
		$element_type_column = IclTranslationsTable::ELEMENT_TYPE;

		return (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT translations.{$trid_column}
			FROM {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )} AS translations
			WHERE translations.{$translation_id_column} = %d
				AND translations.{$element_type_column} LIKE %s
			LIMIT 1",
			$translation_id,
			IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX . '%'
		) );
	}


	/**
	 * Retrieve the affected element ID from a specific icl_translations row.
	 *
	 * @param int $translation_id
	 * @return int|null
	 */
	private function get_element_id_by_translation_id( $translation_id ) {
		$translation_id_column = IclTranslationsTable::TRANSLATION_ID;
		$element_type_column = IclTranslationsTable::ELEMENT_TYPE;
		$element_id_column = IclTranslationsTable::ELEMENT_ID;

		return (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT translations.{$element_id_column}
			FROM {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
			    AS translations
			WHERE translations.{$translation_id_column} = %d
				AND translations.{$element_type_column} LIKE %s
			LIMIT 1",
			$translation_id,
			IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX . '%'
		) );
	}


	/**
	 * Retrieve the previous TRID of an element by looking to the connected elements table.
	 *
	 * If the element (post, specifically) hasn't been used in an association before, this will fail. But in that
	 * case, we don't really care about it failing.
	 *
	 * @param int $post_id
	 * @return int|null
	 */
	private function get_previous_trid_by_post_id( $post_id ) {
		$connected_elements_trid = ConnectedElementTable::WPML_TRID;
		$connected_elements_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_domain = ConnectedElementTable::DOMAIN;
		$icl_translations_trid = IclTranslationsTable::TRID;
		$icl_translations_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_element_type = IclTranslationsTable::ELEMENT_TYPE;

		// Note that the provided $post_id may not be directly stored in the connected elements table. So,
		// in order to get the *previous* TRID, we first must connect all translations of the given post by
		// it's *current* TRID and then connect post IDs to the element_id values in the connected elements table.
		// That will finally lead us to the previous TRID value.
		return (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT connected_elements.{$connected_elements_trid}
			FROM {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
			    AS translations
			JOIN {$this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS )}
			    AS source_translation
				ON ( translations.{$icl_translations_trid} = source_translation.{$icl_translations_trid} )
			JOIN {$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ) }
				AS connected_elements
				ON (
				    connected_elements.{$connected_elements_element_id} = translations.{$icl_translations_element_id}
				    AND connected_elements.{$connected_elements_domain} = %s
				    AND translations.{$icl_translations_element_type} LIKE %s
				)
			WHERE source_translation.{$icl_translations_element_id} = %d
			LIMIT 1
			",
			\Toolset_Element_Domain::POSTS,
			IclTranslationsTable::POST_ELEMENT_TYPE_PREFIX . '%',
			$post_id
		) );
	}


	/**
	 * Obtain a connected element group from the given post ID, using the route of the post's previously assigned TRID
	 * that may still be stored in the connected elements table.
	 *
	 * @param int $post_id
	 * @return ConnectedElementGroup|null
	 */
	private function get_previous_element_group_by_post_id( $post_id ) {
		$previous_trid = $this->get_previous_trid_by_post_id( $post_id );

		if( ! $previous_trid ) {
			return null;
		}

		return $this->connected_element_persistence->get_element_group_by_trid( $previous_trid );
	}
}
