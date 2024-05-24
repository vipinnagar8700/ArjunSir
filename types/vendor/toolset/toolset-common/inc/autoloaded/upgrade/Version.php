<?php

namespace OTGS\Toolset\Common\Upgrade;

/**
 * Provides access to stored and current version numbers of a plugin or Toolset Common.
 *
 * @since 4.0
 */
interface Version {


	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 */
	public function get_current_version();


	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 */
	public function get_version_from_database();


	/**
	 * Update the version number stored in the database.
	 *
	 * @param int $version_number
	 *
	 * @return void
	 */
	public function update_database_version( $version_number );

}
