<?php

namespace OTGS\Toolset\Common\Upgrade;

use OTGS\Toolset\Common\Upgrade\Command\AddRelationshipTableColumnAutodeleteIntermediaryPosts;
use OTGS\Toolset\Common\Upgrade\Command\AlterToolsetPostGuidIdNullableId;
use OTGS\Toolset\Common\Upgrade\Command\Setup\FontAwesomeVersion;
use Toolset_Upgrade_Command_Delete_Obsolete_Upgrade_Options;
use Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade;
use Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade;

/**
 * Stores upgrade command definitions for Toolset Common.
 *
 * @since 2.5.4
 */
class ToolsetCommonCommandDefinitionRepository implements CommandDefinitionRepository {

	/**
	 * Get commands executed when the database version is 0.
	 *
	 * @return CommandDefinition[]
	 * @since 3.6.0
	 */
	public function get_setup_commands() {
		return [
			$this->definition(
				FontAwesomeVersion::class,
				PHP_INT_MAX
			),
		];
	}


	/**
	 * Get commands for regular upgrades (not just the initial setup).
	 *
	 * @return CommandDefinition[]
	 */
	public function get_commands() {
		return [
			$this->definition(
				Toolset_Upgrade_Command_Delete_Obsolete_Upgrade_Options::class,
				1 ),
			$this->definition(
				Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade::class,
				2 ),
			$this->definition(
				Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade::class,
				3 ),
			$this->definition(
				AddRelationshipTableColumnAutodeleteIntermediaryPosts::class,
				4
			),
			$this->definition(
				AlterToolsetPostGuidIdNullableId::class,
				5
			),
			$this->definition(
				FontAwesomeVersion::class,
				6
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
		return ''; // In Toolset Common, this has to be empty for historical reasons.
	}
}
