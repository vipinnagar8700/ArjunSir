<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use Exception;
use OTGS\Toolset\Common\Auryn\InjectionException;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationControllerInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Controller for the migration between the first and second version of the database layer.
 *
 * @since 4.0
 */
class MigrationController implements MigrationControllerInterface {

	const TEMPORARY_OLD_ASSOCIATION_TABLE_NAME = 'toolset_associations_old';

	const TEMPORARY_NEW_ASSOCIATION_TABLE_NAME = 'toolset_associations_new';

	/** @var DatabaseLayerMode */
	private $database_layer_mode;

	/** @var \wpdb */
	private $wpdb;

	/** @var BatchSizeHelper */
	private $batch_size_helper;

	private $is_migration_underway_option;


	/**
	 * MigrationController constructor.
	 *
	 * @param DatabaseLayerMode $database_layer_mode
	 * @param \wpdb $wpdb
	 * @param BatchSizeHelper $batch_size_helper
	 * @param IsMigrationUnderwayOption $is_migration_underway_option
	 */
	public function __construct(
		DatabaseLayerMode $database_layer_mode,
		\wpdb $wpdb,
		BatchSizeHelper $batch_size_helper,
		IsMigrationUnderwayOption $is_migration_underway_option
	) {
		$this->database_layer_mode = $database_layer_mode;
		$this->wpdb = $wpdb;
		$this->batch_size_helper = $batch_size_helper;
		$this->is_migration_underway_option = $is_migration_underway_option;
	}


	/**
	 * @inheritDoc
	 * @return string[]
	 */
	public function get_required_database_layer_modes() {
		return [ DatabaseLayerMode::VERSION_1 ];
	}


	/**
	 * @inheritDoc
	 * @return string
	 */
	public function get_target_database_layer_mode() {
		return DatabaseLayerMode::FALLBACK;
	}


	/**
	 * @inheritDoc
	 * @return bool|SingleResult
	 */
	public function can_migrate() {
		if ( ! in_array( $this->database_layer_mode->get(), $this->get_required_database_layer_modes(), true ) ) {
			return new SingleResult(
				false,
				__( 'The current database layer mode doesn\'t allow for this migration to run.', 'wpv-views' )
			);
		}

		return new SingleResult(
			true,
			__( 'The all requirements for the migration match.', 'wpv-views' )
		);
	}


	/**
	 * @inheritDoc
	 */
	public function get_initial_state() {
		return new MigrationState(
			Step01PreMigrationCheck::class,
			null,
			null,
			null,
			null,
			Step01PreMigrationCheck::STEP_NUMBER
		);
	}


	/**
	 * @inheritDoc
	 */
	public function do_next_step( MigrationStateInterface $current_state ) {
		if ( ! $current_state->can_continue() ) {
			throw new \InvalidArgumentException( 'There is no further step, the migration is concluded.' );
		}

		$step_identifier = $current_state->get_next_step();

		if ( ! is_a( $step_identifier, MigrationStep::class, true ) ) {
			throw new \InvalidArgumentException( 'Invalid migration step identifier provided.' );
		}

		// Yes, I know. DIC is basically used as a service locator here, which is an anti-pattern.
		// But since the MigrationStepInterface has clearly defined requirements and this codebase is very
		// compact (won't be used outside of this context), I believe we can afford it here, to make
		// the whole process a bit less painful.
		//
		// Additionally, one could also argue the migration controller is still part of the bootstrapping
		// process and that the MigrationStepInterface implementations are doing the actual work.
		//
		// Note: This may be avoided if we manage to define a constructor for MigrationStep that satisfies
		// the need of all its subclasses.
		$dic = toolset_dic();

		try {
			/** @var MigrationStep $step */
			$step = $dic->make( $step_identifier );
		} catch ( InjectionException $e ) {
			throw new \RuntimeException( 'Unable to resolve the next migration step based on the current state.' );
		}

		$next_state = $step->run( $current_state );

		if ( ! $next_state->can_continue() ) {
			$this->is_migration_underway_option->deleteOption();
		}

		return $next_state;
	}


	/**
	 * @param string $serialized
	 *
	 * @return MigrationState
	 */
	public function unserialize_migration_state( $serialized ) {
		$state = new MigrationState();
		$state->unserialize( $serialized );

		return $state;
	}


	private function old_association_table_exists() {
		$table_name = $this->wpdb->prefix . self::TEMPORARY_OLD_ASSOCIATION_TABLE_NAME;
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		return ( strtolower($result ) === strtolower( $table_name ) );
	}


	/**
	 * @inheritDoc
	 */
	public function can_do_cleanup() {
		return $this->old_association_table_exists();
	}


	/**
	 * @inheritDoc
	 */
	public function do_cleanup() {
		return $this->do_single_step( CleanupStep::class );
	}


	/**
	 * @inheritDoc
	 */
	public function can_do_rollback() {
		return $this->old_association_table_exists();
	}


	/**
	 * Perform a single migration step and safely produce a result object.
	 *
	 * @param string $step_name Class name of the step.
	 *
	 * @return ResultInterface
	 */
	private function do_single_step( $step_name ) {
		$state = new MigrationState( $step_name );

		try {
			$next_step = $this->do_next_step( $state );
			$result = $next_step->get_result() ?: new SingleResult( true, __( 'Operation completed.', 'wpv-views' ) );
		} catch ( Exception $e ) {
			$result = new SingleResult( $e );
		}

		return $result;
	}


	/**
	 * @inheritDoc
	 */
	public function do_rollback() {
		return $this->do_single_step( RollbackStep::class );
	}


	/**
	 * @inheritDoc
	 */
	public function can_migrate_in_one_shot() {
		$batch_size = $this->batch_size_helper->get_batch_size();
		$association_count = $this->batch_size_helper->count_old_associations();

		return $association_count <= $batch_size;
	}


	/**
	 * @inheritDoc
	 */
	public function migrate_in_one_shot() {
		if( ! $this->can_migrate_in_one_shot() ) {
			return new SingleResult( false, 'One-shot migration is not allowed.' );
		}

		$migration_state = $this->get_initial_state();
		$results = new ResultSet( $migration_state->get_result() );

		while( $migration_state && $migration_state->can_continue() ) {
			try {
				$migration_state = $this->do_next_step( $migration_state );
				$results->add( $migration_state->get_result() );
			} catch ( Exception $e ) {
				$results->add( $e );
				$migration_state = null;
			}
		}

		return $results;
	}
}
