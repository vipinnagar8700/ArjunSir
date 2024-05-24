<?php

namespace OTGS\Toolset\Types\Upgrade\Commands;

use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\Success;
use OTGS\Toolset\Common\Upgrade\UpgradeCommand;
use WPCF_Roles;

/**
 * Upgrade database to 2021600 (Types 2.2.16)
 *
 * Fix types-1142 for non admins with an 'admin' username.
 *
 * @codeCoverageIgnore Production-tested and not to be touched.
 */
class Upgrade2021600CleanMessInAdminUserCaps implements UpgradeCommand {

	/**
	 * @inheritDoc
	 * @return ResultInterface
	 */
	public function run() {
		$roles_manager = WPCF_Roles::getInstance();
		$roles_manager->clean_the_mess_in_nonadmin_user_caps( 'admin' );

		return new Success();
	}
}
