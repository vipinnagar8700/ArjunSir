<?php


namespace OTGS\Toolset\Common\Relationships\API;


use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use Toolset_Relationship_Definition;

/**
 * This is a gateway to working with relationships at a very low level,
 * to be used only by Toolset itself, instead of referencing the internals
 * of the relationships codebase directly.
 *
 * That rule is, obviously, not being kept in all cases, but any new interaction
 * really should go exclusively through the API.
 *
 * @since 4.0
 */
class LowLevelGateway {


	/** @var DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * LowLevelGateway constructor.
	 *
	 * @param DatabaseLayerFactory $database_layer_factory
	 */
	public function __construct( DatabaseLayerFactory $database_layer_factory ) {
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * @return bool
	 */
	public function can_do_after_migration_cleanup() {
		if ( ! $this->database_layer_factory->database_layer_mode()->is( DatabaseLayerMode::VERSION_2 ) ) {
			return false;
		}

		try {
			$migration_controller = $this->database_layer_factory->migration_controller( DatabaseLayerMode::VERSION_1 );
		} catch ( \Exception $e ) {
			return false;
		}

		return $migration_controller->can_do_cleanup();
	}


	/**
	 * @return \OTGS\Toolset\Common\Result\ResultInterface
	 * @throws \Exception
	 */
	public function do_after_migration_cleanup() {
		if ( ! $this->database_layer_factory->database_layer_mode()->is( DatabaseLayerMode::VERSION_2 ) ) {
			throw new \RuntimeException( 'Wrong database layer mode.' );
		}

		$migration_controller = $this->database_layer_factory->migration_controller( DatabaseLayerMode::VERSION_1 );

		if ( ! $migration_controller->can_do_cleanup() ) {
			throw new \RuntimeException( 'Attempted cleanup while it\'s not available.' );
		}

		return $migration_controller->do_cleanup();
	}


	public function can_do_after_migration_rollback() {
		if ( ! $this->database_layer_factory->database_layer_mode()->is( DatabaseLayerMode::VERSION_2 ) ) {
			return false;
		}

		try {
			$migration_controller = $this->database_layer_factory->migration_controller( DatabaseLayerMode::VERSION_1 );
		} catch ( \Exception $e ) {
			return false;
		}

		return $migration_controller->can_do_rollback();

	}


	public function do_after_migration_rollback() {
		if ( ! $this->database_layer_factory->database_layer_mode()->is( DatabaseLayerMode::VERSION_2 ) ) {
			throw new \RuntimeException( 'Wrong database layer mode.' );
		}

		$migration_controller = $this->database_layer_factory->migration_controller( DatabaseLayerMode::VERSION_1 );

		if ( ! $migration_controller->can_do_rollback() ) {
			throw new \RuntimeException( 'Attempted rollback while it\'s not available.' );
		}

		return $migration_controller->do_rollback();
	}


	/**
	 * @param string|null $from_layer
	 *
	 * @return \OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationControllerInterface|null
	 */
	public function get_available_migration_controller( $from_layer = null ) {
		try {
			return $this->database_layer_factory->migration_controller( $from_layer );
		} catch( \RuntimeException $e ) {
			return null;
		}
	}


	/**
	 * Get the code for the database layer mode that is being used at the moment.
	 *
	 * @return string
	 */
	public function get_current_database_layer_mode() {
		return $this->database_layer_factory->database_layer_mode()->get();
	}


	/**
	 * @param IToolset_Relationship_Definition|null $relationship_definition
	 *
	 * @return IntermediaryPostPersistence
	 */
	public function intermediary_post_persistence( IToolset_Relationship_Definition $relationship_definition = null ) {
		return $this->database_layer_factory->intermediary_post_persistence( $relationship_definition );
	}
}
