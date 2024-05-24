<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

use IToolset_Element;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\API\IntermediaryPostPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Cleanup\PostCleanupInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\BatchSizeHelper;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\DuringMigrationIntegrity;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\IsMigrationUnderwayOption;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\AssociationTranslator;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate\UpdateDescriptionParser;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate\WpmlTranslationUpdateHandler;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Element_Factory;
use Toolset_Relationship_Definition_Repository;
use Toolset_Relationship_Table_Name;
use wpdb;
use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\AssociationQuery;
use OTGS\Toolset\Common\Relationships\API\AssociationDatabaseOperations;
use OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery;
use OTGS\Toolset\Common\Relationships\API\RelationshipQuery;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationControllerInterface;


/**
 * Factory for the database layer of the Relationships module.
 *
 * Chooses the correct instance according to the current database layer version.
 *
 * Note: It is rather important that this class is treated as a singleton, it has impact on various caches
 * in classes it provides.
 *
 * @since 4.0
 * @codeCoverageIgnore
 */
class DatabaseLayerFactory {

	/** @var DatabaseLayerMode */
	private $database_layer_mode;

	/** @var wpdb */
	private $wpdb;

	/** @var WpmlService|\Toolset_WPML_Compatibility */
	private $wpml_service;

	/** @var \Toolset_Element_Factory */
	private $element_factory;

	/** @var Version2\Persistence\ConnectedElementPersistence|null */
	private $connected_element_persistence;

	/** @var TableExistenceCheck */
	private $table_existence_check;


	/**
	 * DatabaseLayerFactory constructor.
	 *
	 * @param DatabaseLayerMode $database_layer_mode
	 * @param wpdb $wpdb
	 * @param WpmlService $wpml_service
	 * @param Toolset_Element_Factory $element_factory
	 */
	public function __construct(
		DatabaseLayerMode $database_layer_mode,
		wpdb $wpdb,
		WpmlService $wpml_service,
		Toolset_Element_Factory $element_factory
	) {
		$this->database_layer_mode = $database_layer_mode;
		$this->wpdb = $wpdb;
		$this->wpml_service = $wpml_service;
		$this->element_factory = $element_factory;
	}


	/**
	 * @return AssociationQuery
	 */
	public function association_query() {
		$this->table_existence_check()->ensure_tables_exist();

		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Association_Query_V2();

			case DatabaseLayerMode::FALLBACK:
				$table_names = new Version2\TableNames( $this->wpdb );
				$is_wpml_active_and_configured = new \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured();

				return new Version2\AssociationQuery\Query(
					$this->wpdb,
					new Version2\AssociationQuery\ConditionFactory(
						$this->wpdb,
						new PostStatus(),
						$table_names,
						$this->wpml_service
					),
					$this->wpml_service,
					AssociationQueryCache::get_instance(),
					new Version2\AssociationQuery\ElementSelector\ElementSelectorProvider(
						$is_wpml_active_and_configured,
						$this->wpml_service,
						$this->wpdb,
						$table_names
					),
					new UniqueTableAlias(),
					new Version2\AssociationQuery\TableJoinManager( $table_names, $this->wpdb ),
					new Version2\AssociationQuery\SqlExpressionBuilder( $table_names ),
					\Toolset_Relationship_Definition_Repository::get_instance(),
					new Version2\AssociationQuery\OrderBy\OrderByFactory(),
					new Version2\AssociationQuery\ResultTransformation\ResultTransformationFactory(
						new Toolset_Element_Factory( $is_wpml_active_and_configured, $this->wpml_service ),
						$this->wpml_service,
						new Version2\Persistence\AssociationTranslator(
							\Toolset_Relationship_Definition_Repository::get_instance(),
							new \Toolset_Association_Factory(),
							$this->wpml_service,
							$this->element_factory,
							$this->connected_element_persistence()
						)
					)
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @return RelationshipQuery
	 */
	public function relationship_query() {
		$this->table_existence_check()->ensure_tables_exist();

		return new \Toolset_Relationship_Query_V2();
	}


	/**
	 * @param \IToolset_Relationship_Definition $for_relationship
	 * @param RelationshipRoleParentChild $for_role
	 * @param \IToolset_Element $for_element
	 * @param array $args
	 *
	 * @return PotentialAssociationQuery
	 */
	public function potential_association_query(
		IToolset_Relationship_Definition $for_relationship,
		RelationshipRoleParentChild $for_role,
		IToolset_Element $for_element,
		$args = array()
	) {
		$this->table_existence_check()->ensure_tables_exist();

		$target_domain = $for_relationship->get_element_type( $for_role->get_name() )->get_domain();
		$cardinality = $for_relationship->get_cardinality();

		switch ( $target_domain ) {
			case \Toolset_Element_Domain::POSTS:
				switch( $this->database_layer_mode->get() ) {
					case DatabaseLayerMode::VERSION_1:
						if ( $cardinality->is_one_to_many() ) {
							return new PotentialAssociation\O2MPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						if ( $cardinality->is_one_to_one() ) {
							return new PotentialAssociation\O2OPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						if ( $cardinality->is_many_to_one() ) {
							return new PotentialAssociation\M2OPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						return new PotentialAssociation\PostQuery(
							$for_relationship, $for_role, $for_element, $args, $this
						);
					case DatabaseLayerMode::FALLBACK:
						if ( $cardinality->is_one_to_many() ) {
							return new Version2\PotentialAssociationQuery\O2MPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						if ( $cardinality->is_one_to_one() ) {
							return new Version2\PotentialAssociationQuery\O2OPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						if ( $cardinality->is_many_to_one() ) {
							return new Version2\PotentialAssociationQuery\M2OPostQuery( $for_relationship, $for_role, $for_element, $args, $this );
						}

						return new Version2\PotentialAssociationQuery\PostQuery(
							$for_relationship, $for_role, $for_element, $args, $this
						);
				}
				break;
		}

		throw new \RuntimeException( 'Not implemented.' );
	}


	/**
	 * @return AssociationDatabaseOperations
	 */
	public function association_database_operations() {
		$this->table_existence_check()->ensure_tables_exist();

		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return Version1\Toolset_Relationship_Database_Operations::get_instance();
			case DatabaseLayerMode::FALLBACK:
				return new Version2\AssociationDatabaseOperations(
					\Toolset_Relationship_Definition_Repository::get_instance(),
					$this->wpdb,
					new TableNames( $this->wpdb ),
					$this->element_factory,
					$this->wpml_service,
					$this->connected_element_persistence()
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @return RelationshipDatabaseOperations
	 */
	public function relationship_database_operations() {
		$this->table_existence_check()->ensure_tables_exist();

		return new RelationshipDatabaseOperations();
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param IToolset_Element $for_element
	 *
	 * @return PotentialAssociation\JoinManager
	 */
	public function potential_association_table_join_manager(
		IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element
	) {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\PotentialAssociation\JoinManager(
					$relationship, $target_role, $for_element
				);
			case DatabaseLayerMode::FALLBACK:
				return new Version2\PotentialAssociationQuery\JoinManager(
					$target_role,
					$relationship,
					$for_element,
					$this->wpdb,
					$this->wpml_service,
					new Version2\TableNames( $this->wpdb )
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role Target role of the relationships (future role of
	 *     the posts that are being queried)
	 * @param IToolset_Element $for_element ID of the element to check against.
	 * @param PotentialAssociation\JoinManager $join_manager
	 *
	 * @return PotentialAssociation\WpQueryAdjustment
	 */
	public function distinct_relationship_posts(
		IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element,
		PotentialAssociation\JoinManager $join_manager
	) {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Relationship_Distinct_Post_Query(
					$relationship,
					$target_role,
					$for_element,
					$join_manager,
					$this->wpml_service,
					new Toolset_Relationship_Table_Name( $this->wpdb ),
					$this->wpdb
				);
			case DatabaseLayerMode::FALLBACK;
				return new Version2\PotentialAssociationQuery\DistinctPostQuery(
					$relationship,
					$target_role,
					$for_element,
					$join_manager,
					$this->wpml_service,
					$this->wpdb
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param IToolset_Element $for_element
	 * @param PotentialAssociation\JoinManager $join_manager
	 *
	 * @return PotentialAssociation\WpQueryAdjustment
	 */
	public function cardinality_query_posts(
		IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element,
		PotentialAssociation\JoinManager $join_manager
	) {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\PotentialAssociation\CardinalityPostQuery(
					$relationship,
					$target_role,
					$for_element,
					$join_manager,
					new Toolset_Relationship_Table_Name( $this->wpdb ),
					$this->wpdb,
					$this->wpml_service
				);
			case DatabaseLayerMode::FALLBACK:
				if ( ! $join_manager instanceof Version2\PotentialAssociationQuery\JoinManager ) {
					throw new \InvalidArgumentException();
				}

				return new Version2\PotentialAssociationQuery\CardinalityPostQuery(
					$relationship,
					$target_role,
					$for_element,
					$join_manager,
					$this->wpml_service,
					$this->wpdb,
					new Version2\TableNames( $this->wpdb )
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param IToolset_Element $for_element
	 * @param PotentialAssociation\JoinManager $join_manager
	 *
	 * @return PotentialAssociation\PostResultOrder
	 */
	public function post_result_order_adjustments(
		IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element,
		PotentialAssociation\JoinManager $join_manager
	) {
		return new PotentialAssociation\PostResultOrder(
			$relationship,
			$target_role,
			$for_element,
			$join_manager
		);
	}


	/**
	 * @param array $args Query arguments.
	 *
	 * @return \WP_Query
	 */
	public function wp_query( $args ) {
		return new \WP_Query( $args );
	}


	/**
	 * @return AssociationPersistence
	 */
	public function association_persistence() {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Association_Persistence();
			case DatabaseLayerMode::FALLBACK:
				return new Version2\Persistence\AssociationPersistence(
					$this->wpdb,
					$this->connected_element_persistence(),
					new TableNames( $this->wpdb ),
					\Toolset_Relationship_Definition_Repository::get_instance(),
					new AssociationTranslator(
						\Toolset_Relationship_Definition_Repository::get_instance(),
						new \Toolset_Association_Factory(
							\Toolset_Relationship_Definition_Repository::get_instance(),
							$this->element_factory,
							$this->wpml_service
						),
						$this->wpml_service,
						$this->element_factory,
						$this->connected_element_persistence()
					),
					new \Toolset_Association_Cleanup_Factory( $this )
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @return \OTGS\Toolset\Common\WpQueryExtension\AbstractRelationshipsExtension
	 */
	public function wp_query_extension() {
		$this->table_existence_check()->ensure_tables_exist();

		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Wp_Query_Adjustments_M2m(
					$this->wpdb,
					$this->element_factory,
					\Toolset_Relationship_Definition_Repository::get_instance(),
					$this
				);
			case DatabaseLayerMode::FALLBACK:
				return new Version2\WpQueryExtension\Extension(
					$this->wpdb,
					$this->element_factory,
					\Toolset_Relationship_Definition_Repository::get_instance(),
					$this
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @return Version1\Toolset_Wp_Query_Adjustments_Table_Join_Manager|Version2\WpQueryExtension\JoinManager
	 */
	public function join_manager_for_wp_query_extension() {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Wp_Query_Adjustments_Table_Join_Manager();
			case DatabaseLayerMode::FALLBACK:
				return new Version2\WpQueryExtension\JoinManager(
					new UniqueTableAlias(),
					new TableNames( $this->wpdb ),
					\Toolset_Relationship_Definition_Repository::get_instance(),
					$this->wpml_service,
					$this->wpdb
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * Get an instance of a relevant migration controller.
	 *
	 * @param string|null $from_layer The database layer from which to migrate. By default, the current
	 *     layer will be used. Be careful that during the migration process, the database layer mode will
	 *     likely change and obtaining the controller by using the default will stop working.
	 *
	 * @return MigrationControllerInterface
	 * @throws \RuntimeException When no migration controller can be provided.
	 */
	public function migration_controller( $from_layer = null ) {
		if ( null === $from_layer ) {
			$from_layer = $this->database_layer_mode->get();
		}
		switch ( $from_layer ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version2\Migration\MigrationController(
					$this->database_layer_mode,
					$this->wpdb,
					new BatchSizeHelper( $this->wpdb, new TableNames( $this->wpdb ) ),
					new IsMigrationUnderwayOption()
				);
		}

		throw new \RuntimeException( 'No migration controller available at the moment.' );
	}


	/**
	 * @return DatabaseLayerMode
	 */
	public function database_layer_mode() {
		return $this->database_layer_mode;
	}


	/**
	 * Note that this is available only in the second version of the database layer.
	 *
	 * @return ConnectedElementPersistence
	 * @throws \RuntimeException
	 */
	public function connected_element_persistence() {
		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::FALLBACK:
				if ( null === $this->connected_element_persistence ) {
					$this->connected_element_persistence = new ConnectedElementPersistence(
						$this->wpdb,
						new TableNames( $this->wpdb ),
						$this->wpml_service,
						$this->element_factory
					);
				}

				return $this->connected_element_persistence;
		}

		throw new \RuntimeException();
	}


	/**
	 * @param \Toolset_Association_Cleanup_Factory|null $cleanup_factory
	 *
	 * @return PostCleanupInterface
	 */
	public function post_cleanup( \Toolset_Association_Cleanup_Factory $cleanup_factory = null ) {
		$this->table_existence_check()->ensure_tables_exist();

		switch ( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Cleanup\Toolset_Association_Cleanup_Post(
					$cleanup_factory,
					$this,
					$this->element_factory,
					$this->wpdb,
					\Toolset_Cron::get_instance(),
					new \Toolset_Association_Intermediary_Post_Persistence(
						null,
						$this->wpml_service,
						$this
					)
				);
			case DatabaseLayerMode::FALLBACK:
				return new Version2\Cleanup\PostCleanupHandler(
					$cleanup_factory,
					$this,
					$this->element_factory,
					\Toolset_Cron::get_instance(),
					new \Toolset_Association_Intermediary_Post_Persistence(
						null,
						$this->wpml_service,
						$this
					)
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * Only valid for the second version of the database layer.
	 *
	 * @return WpmlTranslationUpdateHandler
	 */
	public function wpml_translation_update_handler() {
		$this->table_existence_check()->ensure_tables_exist();

		if ( DatabaseLayerMode::FALLBACK !== $this->database_layer_mode->get() ) {
			throw new \RuntimeException();
		}

		return new WpmlTranslationUpdateHandler(
			new UpdateDescriptionParser(
				$this->connected_element_persistence(),
				$this->wpml_service,
				$this->wpdb,
				new TableNames( $this->wpdb )
			),
			$this->wpdb,
			new TableNames( $this->wpdb ),
			$this->connected_element_persistence(),
			new \Toolset_Association_Cleanup_Factory( $this ),
			$this
		);
	}


	/**
	 * @param IToolset_Relationship_Definition|null $relationship_definition
	 *
	 * @return IntermediaryPostPersistence
	 */
	public function intermediary_post_persistence( IToolset_Relationship_Definition $relationship_definition = null ) {
		switch( $this->database_layer_mode->get() ) {
			case DatabaseLayerMode::VERSION_1:
				return new Version1\Toolset_Association_Intermediary_Post_Persistence(
					$relationship_definition,
					$this->wpml_service,
					$this
				);
			case DatabaseLayerMode::FALLBACK:
				return new Version2\Persistence\IntermediaryPostPersistence(
					$this,
					$this->wpml_service,
					$relationship_definition
				);
		}

		throw new \RuntimeException();
	}


	/**
	 * @return TableExistenceCheck
	 */
	public function table_existence_check() {
		if( ! $this->table_existence_check ) {
			$this->table_existence_check = new TableExistenceCheck( $this->database_layer_mode );
		}

		return $this->table_existence_check;
	}


	/**
	 * @return DuringMigrationIntegrity
	 * @since 4.0.10
	 */
	public function during_migration_compatibility() {
		$table_names = new TableNames( $this->wpdb );
		return new DuringMigrationIntegrity(
			new IsMigrationUnderwayOption(),
			$table_names,
			$this->wpdb,
			// Note that we're not using the conected_element_persistence() method because this needs to work
			// while we're currently in the version1 database layer mode.
			new ConnectedElementPersistence( $this->wpdb, $table_names, $this->wpml_service, $this->element_factory ),
			Toolset_Relationship_Definition_Repository::get_instance()
		);
	}
}
