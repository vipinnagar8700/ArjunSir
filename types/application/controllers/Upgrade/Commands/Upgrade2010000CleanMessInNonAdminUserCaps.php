<?php

namespace OTGS\Toolset\Types\Upgrade\Commands;

use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\Upgrade\UpgradeCommand;
use WP_User_Query;
use WPCF_Roles;

/**
 * Upgrade database to 2010000 (Types 2.1)
 *
 * Batch fix types-768 for all non-superadmin users.
 *
 * @codeCoverageIgnore Production-tested and not to be touched.
 */
class Upgrade2010000CleanMessInNonAdminUserCaps implements UpgradeCommand {

	/**
	 * @inheritDoc
	 * @return ResultInterface
	 */
	public function run() {
		$roles_manager = WPCF_Roles::getInstance();

		global $wpdb;

		// Will find users without the administrator roles but with one of the Types management roles.
		// A sign of the types-768 bug.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$user_query = new WP_User_Query(
			[
				'meta_query' => [
					'relation' => 'AND',
					[
						'key' => $wpdb->prefix . 'capabilities',
						'value' => '"administrator"',
						'compare' => 'NOT LIKE',
					],
					[
						'key' => $wpdb->prefix . 'capabilities',
						'value' => '"wpcf_custom_post_type_view"',
						'compare' => 'LIKE',
					],
				],
			]
		);

		$users = $user_query->get_results();

		foreach ( $users as $user ) {
			$roles_manager->clean_the_mess_in_nonadmin_user_caps( $user );
		}

		return new SingleResult( true );
	}
}
