<?php

namespace OTGS\Toolset\Common\Upgrade;


/**
 * Interface for storing upgrade command definitions.
 *
 * @since 2.5.4
 */
interface CommandDefinitionRepository {

	/**
	 * Get commands executed when the database version is 0.
	 *
	 * @return CommandDefinition[]
	 * @since 3.6.0
	 */
	public function get_setup_commands();


	/**
	 * Get commands for regular upgrades (not just the initial setup).
	 *
	 * @return CommandDefinition[]
	 */
	public function get_commands();


	/**
	 * Prefix to prevent conflicts between commands from different plugins and from Toolset Common.
	 *
	 * @return string
	 * @since 4.0
	 */
	public function get_prefix();
}
