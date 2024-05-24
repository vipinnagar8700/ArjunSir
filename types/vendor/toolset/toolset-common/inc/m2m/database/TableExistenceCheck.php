<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

/**
 * Checks for the existence of m2m database tables and creates them if they're missing.
 *
 * Optimized not to repeat any actions unless necessary.
 *
 * @since Types 3.3.11
 */
class TableExistenceCheck {


	/** @var bool */
	private $did_ensure = false;


	/**
	 * After this method is called, relationship tables ought to exist unless:
	 *
	 * - The toolset_m2m_skip_table_existence_check was used.
	 * - There's something wrong with the database that prevents new tables from being created (which is a basic
	 *   requirement of WordPress, so it's safe to assume).
	 */
	public function ensure_tables_exist() {
		if ( $this->did_ensure ) {
			return;
		}

		/**
		 * Filter toolset_m2m_skip_table_existence_check.
		 *
		 * Use it to return true in order to skip checking for table existence and save a little bit of performance
		 * on relevant requests.
		 *
		 * @since Types 3.3.11
		 */
		if ( apply_filters( 'toolset_m2m_skip_table_existence_check', false ) ) {
			$this->did_ensure = true;

			return;
		}

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		do_action( 'toolset_do_m2m_full_init' );

		// This will check for table existence and create them if they're missing.
		\Toolset_Relationship_Database_Operations::get_instance()->do_native_dbdelta();

		$this->did_ensure = true;
	}

}
