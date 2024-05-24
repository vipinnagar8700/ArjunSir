<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\WPML\Package as wpml_package;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Handles the persistence of relationship definitions, from IToolset_Relationship_Definition object to a wpdb call.
 *
 * For internal purposes of the m2m API only. Use Toolset_Relationship_Definition_Repository().
 *
 * @since 2.5.2
 */
class Toolset_Relationship_Definition_Persistence {


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Relationship_Definition_Translator */
	private $definition_translator;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;

	/** @var  Toolset_Post_Type_Repository */
	private $post_type_repository;

	/**
	 * WPML package
	 *
	 * @var wpml_package\RelationshipDefinitionTranslationPackage
	 */
	private $wpml_package_definition;

	/**
	 * @var WpmlService
	 */
	private $wpml_service;


	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Definition_Translator $definition_translator_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Post_Type_Repository $post_type_repository = null,
		wpml_package\RelationshipDefinitionTranslationPackage $wpml_package_definition_di = null,
		WpmlService $wpml_compatibility_di = null
	) {

		if ( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->definition_translator = (
		null === $definition_translator_di
			? new Toolset_Relationship_Definition_Translator()
			: $definition_translator_di
		);

		$this->table_name = (
		null === $table_name_di
			? new Toolset_Relationship_Table_Name()
			: $table_name_di
		);

		$this->post_type_repository = (
		null === $post_type_repository
			? Toolset_Post_Type_Repository::get_instance()
			: $post_type_repository
		);

		$this->wpml_package_definition = $wpml_package_definition_di;

		$this->wpml_service = $wpml_compatibility_di;

	}


	/**
	 * Update a single relationship definition.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 *
	 * @return SingleResult
	 */
	public function persist_definition( Toolset_Relationship_Definition $relationship_definition ) {
		$this->register_definition_strings( $relationship_definition );
		$this->abort_if_invalid_element_types_used( $relationship_definition );

		$row = $this->definition_translator->to_database_row( $relationship_definition );
		$row = $this->update_definition_type_sets( $relationship_definition, $row );
		$result = $this->wpdb->update(
			$this->table_name->relationship_table(),
			$row,
			array( 'id' => $relationship_definition->get_row_id() ),
			$this->definition_translator->get_database_row_formats(),
			'%s'
		);

		/**
		 * toolset_relationship_updated
		 *
		 * Executed after a relationship has been updated.
		 *
		 * @param int $row_id Relationship ID.
		 * @param int $slug Relationship slug.
		 * @since Types 3.4.7
		 */
		do_action( 'toolset_relationship_updated', $relationship_definition->get_row_id(), $relationship_definition->get_slug() );

		if ( false === $result ) {
			return new SingleResult(
				false, 'Error when persisting a relationship definition: ' . $this->wpdb->last_error
			);
		}

		return new SingleResult( true );
	}


	/**
	 * Insert a new relationship definition record into the database.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 *
	 * @return null|Toolset_Relationship_Definition
	 */
	public function insert_definition( Toolset_Relationship_Definition $relationship_definition ) {
		$this->register_definition_strings( $relationship_definition );
		$this->abort_if_invalid_element_types_used( $relationship_definition );

		$row = $this->definition_translator->to_database_row( $relationship_definition );
		$row = $this->update_definition_type_sets( $relationship_definition, $row );

		$this->wpdb->insert(
			$this->table_name->relationship_table(),
			$row,
			$this->definition_translator->get_database_row_formats()
		);

		// Create a new relationship definition instance from the exact data that has been sent to the
		// database (row ID and type set IDs).
		$row['id'] = $this->wpdb->insert_id;
		$row['parent_types_set_id'] = $row['parent_types'];
		$row['child_types_set_id'] = $row['child_types'];

		// Work around some differences caused between the data sent when updating relationship
		// rows and data retrieved when querying (and appending the concatenated list of parent
		// and child types).
		$definition_array = $relationship_definition->get_definition_array( false );
		$row['parent_types'] = implode(
			Constants::GROUP_CONCAT_DELIMITER,
			$definition_array[ Toolset_Relationship_Definition::DA_PARENT_TYPE ][ Toolset_Relationship_Element_Type::DA_TYPES ]
		);
		$row['child_types'] = implode(
			Constants::GROUP_CONCAT_DELIMITER,
			$definition_array[ Toolset_Relationship_Definition::DA_CHILD_TYPE ][ Toolset_Relationship_Element_Type::DA_TYPES ]
		);

		/**
		 * Action toolset_relationship_created.
		 *
		 * Executed after a relationship has been created.
		 *
		 * @param int $row_id Relationship ID.
		 * @param int $slug Relationship slug.
		 * @since Types 3.4.7
		 */
		do_action( 'toolset_relationship_created', $relationship_definition->get_row_id(), $relationship_definition->get_slug() );

		return $this->definition_translator->from_database_row( (object) $row );
	}


	/**
	 * Delete a relationship definition record from the database.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 */
	public function delete_definition( Toolset_Relationship_Definition $relationship_definition ) {
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role_name ) {
			$set_id = $relationship_definition->get_element_type_set_id( $role_name );

			if ( 0 === $set_id ) {
				continue;
			}

			$this->wpdb->delete(
				$this->table_name->type_set_table(),
				array( 'set_id' => $set_id ),
				'%d'
			);
		}

		$this->wpdb->delete(
			$this->table_name->relationship_table(),
			array( 'slug' => $relationship_definition->get_slug() ),
			'%s'
		);

		/**
		 * Action toolset_relationship_deleted.
		 *
		 * Executed after a relationship has been deleted.
		 *
		 * @param int $row_id Relationship ID.
		 * @param int $slug Relationship slug.
		 * @since Types 3.4.7
		 */
		do_action( 'toolset_relationship_deleted', $relationship_definition->get_row_id(), $relationship_definition->get_slug() );
	}


	/**
	 * Persist type sets for parent and child roles and update the to-be-saved database row with
	 * a new set_id if it changed.
	 *
	 * @param Toolset_Relationship_Definition $definition
	 * @param array $definition_row
	 *
	 * @return array Updated definition row.
	 */
	private function update_definition_type_sets( $definition, $definition_row ) {
		$definition_row['child_types'] = $this->update_type_set(
			$definition->get_child_type()->get_types(),
			$definition->get_element_type_set_id( Toolset_Relationship_Role::CHILD )
		);

		$definition_row['parent_types'] = $this->update_type_set(
			$definition->get_parent_type()->get_types(),
			$definition->get_element_type_set_id( Toolset_Relationship_Role::PARENT )
		);

		return $definition_row;
	}


	/**
	 * Update a type set in the database.
	 *
	 * @param string[] $types
	 * @param int $set_id Previously used set_id or 0.
	 *
	 * @return int set_id that was used in the end.
	 */
	private function update_type_set( $types, $set_id ) {
		if ( 0 === $set_id ) {
			$set_id = $this->get_next_free_set_id();
		} else {
			// set_id doesn't change but we need to overwrite the whole set
			$this->delete_type_set( $set_id );
		}

		foreach ( $types as $type ) {
			$this->insert_type( $type, $set_id );
		}

		return $set_id;
	}


	/**
	 * Determine a next free set_id in the type set table.
	 *
	 * @return int
	 */
	private function get_next_free_set_id() {
		$type_set_table = $this->table_name->type_set_table();
		$last_set_id = (int) $this->wpdb->get_var( "SELECT MAX(set_id) FROM {$type_set_table}" );

		return ( $last_set_id + 1 );
	}


	/**
	 * Insert a single type record into the type set table.
	 *
	 * @param string $type
	 * @param int $set_id
	 */
	private function insert_type( $type, $set_id ) {
		$this->wpdb->insert(
			$this->table_name->type_set_table(),
			array(
				'set_id' => $set_id,
				'type' => $type,
			),
			array( '%d', '%s' )
		);
	}


	/**
	 * Delete a whole type set from the type set table.
	 *
	 * @param int $set_id
	 */
	private function delete_type_set( $set_id ) {
		$this->wpdb->delete(
			$this->table_name->type_set_table(),
			array( 'set_id' => $set_id ),
			'%d'
		);
	}


	/**
	 * This method proofs that the element types are valid for a new relationship. If not: FATAL ERROR is thrown.
	 *
	 * The decision to check it as late as possible is for better backwards compatiblity and possible edge cases.
	 * "Correctly" this check should be done on the contructor of Toolset_Relationship_Element_Type.
	 * BUT it's not impossible to create an invalid relationship just by using the GUI, for example:
	 * Activate Types, create Relationship, deactivate Types, activate WPML, select for previously used post types
	 * "Translatable - only show translated items", activate Types again and you would get an fatal error if this check
	 * would be on the constrcutor of Toolset_Relationship_Element_Type.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 */
	private function abort_if_invalid_element_types_used( Toolset_Relationship_Definition $relationship_definition ) {
		/**
		 * @var $element_types Toolset_Relationship_Element_Type[]
		 */
		$element_types = array(
			$relationship_definition->get_parent_type(),
			$relationship_definition->get_child_type(),
		);

		$rfg_involved = false;
		$error = false;

		foreach ( $element_types as $element_type ) {
			switch ( $element_type->get_domain() ) {
				case Toolset_Element_Domain::POSTS:
					foreach ( $element_type->get_types() as $post_type_string ) {
						$post_type = $this->post_type_repository->get( $post_type_string );
						if ( null === $post_type ) {
							throw new RuntimeException(
								'The post type "'
								. sanitize_text_field( $post_type_string )
								. '" was not found, so it cannot be used in '
								. 'a relationship, post reference field or in a repeatable field group.'
							);
						}

						if ( $post_type->is_repeating_field_group() ) {
							$rfg_involved = true;
							continue;
						}

						$can_be_used_in_relationship = $post_type->can_be_used_in_relationship();
						if ( ! $rfg_involved && $can_be_used_in_relationship->is_error() ) {
							$message = $can_be_used_in_relationship->get_message();
							$error = strpos( $message, 'WPML' ) !== false
								? 'The post type "'
								. $post_type_string
								. '" uses the "Translatable - only show translated '
								. 'items" WPML translation mode. In order to use it in a relationship, switch to '
								. '"Translatable - use translation if available or fallback to default language mode".'
								: $message;
						}
					}
					break;
				case Toolset_Element_Domain::TERMS:
				case Toolset_Element_Domain::USERS:
					// no restrictions
					break;
			}
		}

		if ( ! $rfg_involved && $error ) {
			// We allow a relationship between RFG and wrong translation mode
			// This exception should never been thrown, while using the GUI of Types to create Relationships
			throw new RuntimeException( $error );
		}
	}


	/**
	 * Register strings for translations using WPML
	 *
	 * @param Toolset_Relationship_Definition $definition Definition.
	 *
	 * @since 3.0
	 */
	private function register_definition_strings( Toolset_Relationship_Definition $definition ) {
		$package = $this->get_wpml_package_definition( $definition )->get_package_definition();
		$wpml_compatibility = $this->get_wpml_service();
		$definition_slug = $definition->get_slug();
		$definition_name = $definition->get_display_name( false );
		// Plural.
		// translators: Definition name.
		$wpml_compatibility->register_string( $definition->get_display_name_plural( false ), "{$definition_slug}-plural", $package, sprintf( __( '%s plural' ), $definition_name ) );
		// Singular.
		// translators: Definition name.
		$wpml_compatibility->register_string( $definition->get_display_name_singular( false ), "{$definition_slug}-singular", $package, sprintf( __( '%s plural' ), $definition_name ) );
		$labels_plural = $definition->get_role_labels_plural( false );
		$labels_singular = $definition->get_role_labels_singular( false );
		// Parent label singular.
		// translators: Definition label.
		$wpml_compatibility->register_string( $labels_plural[ Toolset_Relationship_Role::PARENT ], "{$definition_slug}-parent-plural", $package, sprintf( __( '%s parent label plural' ), $definition_name ) );
		// translators: Definition label.
		$wpml_compatibility->register_string( $labels_singular[ Toolset_Relationship_Role::PARENT ], "{$definition_slug}-parent-singular", $package, sprintf( __( '%s parent label singular' ), $definition_name ) );
		// translators: Definition label.
		$wpml_compatibility->register_string( $labels_plural[ Toolset_Relationship_Role::CHILD ], "{$definition_slug}-child-plural", $package, sprintf( __( '%s child label plural' ), $definition_name ) );
		// translators: Definition label.
		$wpml_compatibility->register_string( $labels_singular[ Toolset_Relationship_Role::CHILD ], "{$definition_slug}-child-singular", $package, sprintf( __( '%s child label singular' ), $definition_name ) );
	}


	/**
	 * Gets WPML package
	 *
	 * @param Toolset_Relationship_Definition $definition
	 *
	 * @return wpml_package\RelationshipDefinitionTranslationPackage
	 * @since 3.0
	 */
	private function get_wpml_package_definition( Toolset_Relationship_Definition $definition ) {
		if ( $this->wpml_package_definition ) {
			return $this->wpml_package_definition;
		}

		return new wpml_package\RelationshipDefinitionTranslationPackage( $definition );
	}


	/**
	 * Gets WPML compatibility class
	 *
	 * @return WpmlService
	 * @since 3.0
	 */
	private function get_wpml_service() {
		if ( ! $this->wpml_service ) {
			$this->wpml_service = WpmlService::get_instance();
		}

		return $this->wpml_service;
	}
}
