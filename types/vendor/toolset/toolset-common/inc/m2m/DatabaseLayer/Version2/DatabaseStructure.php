<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\DatabaseInterfaceProvider;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\Index;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKey;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\SchemaController;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\SingleColumnIndex;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\Table;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\TableSchema;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\BoolColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\IdColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\IntColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\LongtextColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKeyColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\VarcharColumn;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\RelationshipTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\TypeSetTable;


/**
 * Define the database structure and register it with the provided SchemaController.
 *
 * @codeCoverageIgnore This is more a database structure definition than testable code.
 * @since 4.0
 */
class DatabaseStructure {

	// Maximum lengths of various columns. Do not change.
	//
	//
	const MAX_DOMAIN_LENGTH = 20;

	const LANG_CODE_LENGTH = 7;

	const RELATIONSHIP_SLUG_LENGTH = \OTGS\Toolset\Common\Relationships\API\Constants::MAXIMUM_RELATIONSHIP_SLUG_LENGTH;

	const DISPLAY_NAME_LENGTH = 255;

	const POST_TYPE_SLUG_LENGTH = 20;

	const ORIGIN_LENGTH = 50;


	/** @var SchemaController */
	private $schema_controller;

	/** @var DatabaseInterfaceProvider */
	private $database_interface_provider;


	/**
	 * DatabaseStructure constructor.
	 *
	 * @param SchemaController $schema_controller
	 * @param DatabaseInterfaceProvider $database_interface_provider
	 */
	public function __construct(
		SchemaController $schema_controller,
		DatabaseInterfaceProvider $database_interface_provider
	) {
		$this->schema_controller = $schema_controller;
		$this->database_interface_provider = $database_interface_provider;
	}


	/**
	 * Instantiate the table definitions and register them with the schema controller.
	 */
	public function initialize() {
		$associations_table = $this->build_associations_table();
		$connected_elements_table = $this->build_connected_elements_table();
		$relationships_table = $this->build_relationship_table();
		$type_set_table = $this->build_type_set_table();

		/** @var Table $table */
		foreach (
			[ $associations_table, $connected_elements_table, $relationships_table, $type_set_table ]
			as $table
		) {
			$this->schema_controller->register_table( $table );
		}
	}


	/**
	 * Obtain a table definition.
	 *
	 * @param string $table_name Valid table name.
	 * @return Table
	 * @throws \InvalidArgumentException
	 */
	public function get_table( $table_name ) {
		return $this->schema_controller->get_table( $table_name );
	}


	/**
	 * @return Table
	 */
	private function build_associations_table() {
		$primary_key = new PrimaryKeyColumn();
		$relationship_id = new IdColumn( AssociationTable::RELATIONSHIP_ID );
		$parent_id = new IdColumn( AssociationTable::PARENT_ID );
		$child_id = new IdColumn( AssociationTable::CHILD_ID );
		$intermediary_id = new IdColumn( AssociationTable::INTERMEDIARY_ID );

		return new Table(
			TableNames::ASSOCIATIONS,
			AssociationTable::CURRENT_VERSION,
			$this->database_interface_provider,
			new TableSchema(
				[
					$primary_key,
					$relationship_id,
					$parent_id,
					$child_id,
					$intermediary_id,
				],
				[
					new PrimaryKey( $primary_key ),
					new SingleColumnIndex( $relationship_id ),
					new Index( AssociationTable::PARENT_ID, [ $parent_id, $relationship_id ] ),
					new Index( AssociationTable::CHILD_ID, [ $child_id, $relationship_id ] ),
					new Index( AssociationTable::INTERMEDIARY_ID, [ $intermediary_id, $relationship_id ] ),
				]
			)
		);
	}


	/**
	 * @return Table
	 */
	private function build_connected_elements_table() {
		$primary_key = new PrimaryKeyColumn();
		$group_id = new IdColumn( ConnectedElementTable::GROUP_ID );
		$element_id = new IdColumn( ConnectedElementTable::ELEMENT_ID );
		$domain = new VarcharColumn( ConnectedElementTable::DOMAIN, self::MAX_DOMAIN_LENGTH );

		return new Table(
			TableNames::CONNECTED_ELEMENTS,
			ConnectedElementTable::CURRENT_VERSION,
			$this->database_interface_provider,
			new TableSchema(
				[
					$primary_key,
					$element_id,
					$domain,
					new IdColumn( ConnectedElementTable::WPML_TRID, true ),
					new VarcharColumn( ConnectedElementTable::LANG_CODE, self::LANG_CODE_LENGTH, true ),
					$group_id,
				],
				[
					new PrimaryKey( $primary_key ),
					new SingleColumnIndex( $group_id ),
					new Index( 'element', [ $domain, $element_id ] ),
				]
			)
		);
	}


	/**
	 * @return Table
	 */
	private function build_relationship_table() {
		$primary_key = new PrimaryKeyColumn();
		$slug = new VarcharColumn( RelationshipTable::SLUG, self::RELATIONSHIP_SLUG_LENGTH );
		$is_active = new BoolColumn( RelationshipTable::IS_ACTIVE );
		$needs_legacy_support = new BoolColumn( RelationshipTable::NEEDS_LEGACY_SUPPORT );
		$parent_domain = new VarcharColumn( RelationshipTable::PARENT_DOMAIN, self::MAX_DOMAIN_LENGTH );
		$parent_types = new IdColumn( RelationshipTable::PARENT_TYPES, false, 0 );
		$child_domain = new VarcharColumn( RelationshipTable::CHILD_DOMAIN, self::MAX_DOMAIN_LENGTH );
		$child_types = new IdColumn( RelationshipTable::CHILD_TYPES, false, 0 );

		return new Table(
			TableNames::RELATIONSHIPS,
			RelationshipTable::CURRENT_VERSION,
			$this->database_interface_provider,
			new TableSchema(
				[
					$primary_key,
					$slug,
					new VarcharColumn( RelationshipTable::DISPLAY_NAME_PLURAL, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::DISPLAY_NAME_SINGULAR, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::DRIVER, 50 ),
					$parent_domain,
					$parent_types,
					$child_domain,
					$child_types,
					new VarcharColumn( RelationshipTable::INTERMEDIARY_TYPE, self::POST_TYPE_SLUG_LENGTH ),
					new VarcharColumn( RelationshipTable::OWNERSHIP, 8, false, 'none' ),
					new IntColumn( RelationshipTable::CARDINALITY_PARENT_MAX, false, 10, false, - 1 ),
					new IntColumn( RelationshipTable::CARDINALITY_PARENT_MIN, false, 10, false, 0 ),
					new IntColumn( RelationshipTable::CARDINALITY_CHILD_MIN, false, 10, false, 0 ),
					new IntColumn( RelationshipTable::CARDINALITY_CHILD_MAX, false, 10, false, - 1 ),
					new BoolColumn( RelationshipTable::IS_DISTINCT ),
					new LongtextColumn( RelationshipTable::SCOPE, false ),
					new VarcharColumn( RelationshipTable::ORIGIN, self::ORIGIN_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_NAME_PARENT, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_NAME_CHILD, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_NAME_INTERMEDIARY, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_LABEL_PARENT_SINGULAR, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_LABEL_CHILD_SINGULAR, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_LABEL_PARENT_PLURAL, self::DISPLAY_NAME_LENGTH ),
					new VarcharColumn( RelationshipTable::ROLE_LABEL_CHILD_PLURAL, self::DISPLAY_NAME_LENGTH ),
					$needs_legacy_support,
					$is_active,
					new BoolColumn( RelationshipTable::AUTODELETE_INTERMEDIARY, false, 1 ),
				],
				[
					new PrimaryKey( $primary_key ),
					new SingleColumnIndex( $slug ),
					new SingleColumnIndex( $is_active ),
					new SingleColumnIndex( $needs_legacy_support ),
					new Index( 'parent', [ $parent_domain, $parent_types ] ),
					new Index( 'child', [ $child_domain, $child_types ] ),
				]
			)
		);
	}


	/**
	 * @return Table
	 */
	private function build_type_set_table() {
		$primary_key = new PrimaryKeyColumn();
		$set_id = new IdColumn( TypeSetTable::SET_ID );
		$type = new VarcharColumn( TypeSetTable::TYPE, self::POST_TYPE_SLUG_LENGTH );

		return new Table(
			TableNames::TYPE_SETS,
			TypeSetTable::CURRENT_VERSION,
			$this->database_interface_provider,
			new TableSchema(
				[
					$primary_key,
					$set_id,
					$type,
				],
				[
					new PrimaryKey( $primary_key ),
					new SingleColumnIndex( $set_id ),
					new SingleColumnIndex( $type ),
				]
			)
		);
	}

}
