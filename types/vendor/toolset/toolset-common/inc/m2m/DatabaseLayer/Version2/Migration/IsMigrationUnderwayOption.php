<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Wordpress\Option\AOption;

/**
 * If this option is set to a truthy value, it means that the associations are currently being migrated between
 * database layer versions.
 *
 * This can be used to ensure data consistency across the old and new database tables until the migration is completed.
 *
 * @since 4.0.10
 * @codeCoverageIgnore
 */
class IsMigrationUnderwayOption extends AOption {

	public function getKey() {
		return 'toolset_is_migration_underway';
	}
}
