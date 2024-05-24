<?php

namespace OTGS\Toolset\Common\Upgrade;

use OTGS\Toolset\Common\Result\ResultInterface;

/**
 * Interface OTGS\Toolset\Common\Upgrade\UpgradeCommand
 *
 * Command that upgrades the database when a Toolset Common library is upgraded.
 *
 * WARNING: Since it's very difficult to recover from any errors that occur during the upgrade, it is extremely
 * important to have the commands thoroughly covered by unit tests and well-tested.
 *
 * Each command's run() method must be idempotent.
 *
 * @since 2.5.3
 */
interface UpgradeCommand {

	/**
	 * Run the command.
	 *
	 * @return ResultInterface
	 */
	public function run();

}
