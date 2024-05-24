<?php

namespace OTGS\Toolset\Types\Condition;

use OTGS\Toolset\Types\AdminNotice\DatabaseMigrationNoticeController;

/**
 * Tests that a database migration process is underway.
 *
 * @since 3.4
 */
class IsDatabaseMigrationUnderway implements \Toolset_Condition_Interface {

	/**
	 * If a single upgrade step hasn't been executed in five minutes, we assume an error must have happened and the
	 * option value is just a leftover after unfinished migration. Each step usually takes much less time.
	 */
	const SINGLE_UPGRADE_STEP_TIMEOUT_SECONDS = 300;

	public function is_met() {
		$last_update_step = get_option( DatabaseMigrationNoticeController::IS_UPGRADING_OPTION, null );

		if ( ! is_numeric( $last_update_step ) ) {
			// The upgrade is not underway.
			return false;
		}

		return $last_update_step > time() - self::SINGLE_UPGRADE_STEP_TIMEOUT_SECONDS;
	}
}
