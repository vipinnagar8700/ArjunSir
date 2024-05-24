<?php

namespace OTGS\Toolset\Types\Upgrade\Commands;

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\Result\Success;
use OTGS\Toolset\Common\Upgrade\UpgradeCommand;

/**
 * If there is a relationship database layer migration available, and if it can be doine in a single shot, do it.
 *
 * Otherwise, nothing will happen.
 * Any errors will be written to the error log.
 * This assumes the m2m functionality is already enabled.
 *
 * @since 3.4
 */
class Upgrade3040000OneShotRelationshipDatabaseLayerMigration implements UpgradeCommand {

	/** @var Factory|null */
	private $relationships_factory;


	/**
	 * OneShotRelationshipDatabaseLayerMigration constructor.
	 *
	 * @param Factory $relationships_factory
	 */
	public function __construct( Factory $relationships_factory = null ) {
		$this->relationships_factory = $relationships_factory;
	}


	/**
	 * Try to perform the upgrade in one shot if possible.
	 */
	public function run() {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return new Success();
		}

		do_action( 'toolset_do_m2m_full_init' );

		if ( ! $this->relationships_factory ) {
			$this->relationships_factory = new Factory();
		}

		$migration_controller = $this->relationships_factory->low_level_gateway()->get_available_migration_controller();

		if ( ! $migration_controller ) {
			// No migration available, this may be because the database is already migrated.
			return new Success();
		}

		if ( ! $migration_controller->can_migrate_in_one_shot() ) {
			return new Success();
		}

		$migration_result = $migration_controller->migrate_in_one_shot();

		if ( ! $migration_result->is_success() ) {
			// Report the issue in an error log - that's the best we can do in the current context.
			// The migration should be implemented in a way that any abort will leave the site in
			// an usable mode (either rollback is not necessary or it is performed after error).
			//
			// If the issue persists, the admin will encounter it when running the migration manually.
			$separator = "\n\t> ";
			if ( $migration_result instanceof ResultSet ) {
				$message = $migration_result->concat_messages( $separator );
			} else {
				$message = $migration_result->get_message();
			}

			/** @noinspection ForgottenDebugOutputInspection */
			error_log(
				'Attempted one-shot relationship database layer upgrade with some issues: '
				. $separator
				. $message,
				E_USER_NOTICE
			);

			// And now we act as if this succeeded because we don't want to generate an admin notice for this and
			// retry the migration on every page request. Instead, the standard migration prompt will display
			// in relevant places and an action will have to be taken by the site admin.
		}

		return new Success();
	}
}
