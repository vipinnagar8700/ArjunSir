<?php


namespace OTGS\Toolset\Types\Upgrade;

use OTGS\Toolset\Common\Upgrade\CommandDefinition;
use OTGS\Toolset\Types\Upgrade\Commands\Upgrade2010000CleanMessInNonAdminUserCaps;
use OTGS\Toolset\Types\Upgrade\Commands\Upgrade2021600CleanMessInAdminUserCaps;
use OTGS\Toolset\Types\Upgrade\Commands\Upgrade3040000OneShotRelationshipDatabaseLayerMigration;

/**
 * Upgrade command definition repository for Types.
 *
 * @since 3.4
 */
class CommandDefinitionRepository implements \OTGS\Toolset\Common\Upgrade\CommandDefinitionRepository {

	const COMMAND_NAME_PREFIX = 'types__';

	/**
	 * @inheritDoc
	 * @return CommandDefinition[]
	 */
	public function get_setup_commands() {
		// Setup or not, we just make sure every upgrade has been executed.
		return $this->get_commands();
	}


	/**
	 * @inheritDoc
	 * @return CommandDefinition[]
	 */
	public function get_commands() {
		return [
			$this->definition(
				Upgrade2010000CleanMessInNonAdminUserCaps::class,
				2010000
			),
			$this->definition(
				Upgrade2021600CleanMessInAdminUserCaps::class,
				2021600
			),
			$this->definition(
				Upgrade3040000OneShotRelationshipDatabaseLayerMigration::class,
				3040000
			),
		];
	}


	/**
	 * @param string $command_class_name
	 * @param int $upgrade_version
	 *
	 * @return CommandDefinition
	 */
	private function definition( $command_class_name, $upgrade_version ) {
		return new CommandDefinition( $command_class_name, $upgrade_version );
	}


	/**
	 * @inheritDoc
	 * @return string
	 */
	public function get_prefix() {
		return self::COMMAND_NAME_PREFIX;
	}
}
