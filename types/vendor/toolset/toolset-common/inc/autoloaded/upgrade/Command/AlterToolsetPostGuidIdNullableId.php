<?php

namespace OTGS\Toolset\Common\Upgrade\Command;

use OTGS\Toolset\Common\Result\ResultInterface;
use Toolset_Result;
use Toolset_Result_Set;

/**
 * Makes the post_id column of the table toolset_post_guid_id nullable.
 *
 * @since Types 3.3.6
 */
class AlterToolsetPostGuidIdNullableId extends \Toolset_Wpdb_User implements \OTGS\Toolset\Common\Upgrade\UpgradeCommand {
	/**
	 * Run the command.
	 *
	 * @return ResultInterface
	 */
	public function run() {
		// \WPCF_Guid_Id not available -> check if types WPCF_EMBEDDED_INC_ABSPATH isset and load the image.php library
		// (it should already being loaded by Types at this point... just to be save for some future refactoring)
		if ( ! class_exists( '\WPCF_Guid_Id' )
			 && defined( 'WPCF_EMBEDDED_INC_ABSPATH' )
			 && file_exists( WPCF_EMBEDDED_INC_ABSPATH . '/fields/image.php' ) ) {
			require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields/image.php';
		}

		// Another class exist check, just for the case the class did not exist and the file include also failed.
		if ( ! class_exists( '\WPCF_Guid_Id' ) ) {
			// If the class does not exist, the table also does not exist -> no update needed.
			return new Toolset_Result( true );
		}

		$wpcf_guid_id = \WPCF_Guid_Id::get_instance();
		if ( ! method_exists( $wpcf_guid_id, 'get_table_name' ) ) {
			// If the method is missing the Types version is that new, that this update is not needed anymore.
			return new Toolset_Result( true );
		}

		$table_guid_id = $wpcf_guid_id->get_table_name();

		if ( strtolower( $this->wpdb->get_var( "SHOW TABLES LIKE '$table_guid_id'" ) ) !== strtolower( $table_guid_id ) ) {
			// Table was not created yet. No update needed.
			return new Toolset_Result( true );
		}

		$results = new Toolset_Result_Set();
		$results->add( $this->update_post_guid_id_table( $table_guid_id ) );

		return $results;
	}



	/**
	 * Update the Post Guid Id table to allow the post_id being null.
	 *
	 * @return false|Toolset_Result
	 */
	private function update_post_guid_id_table( $table_name ) {
		$query = "ALTER TABLE `{$table_name}`
			MODIFY post_id bigint(20) DEFAULT NULL";

		$result = $this->wpdb->query( $query );

		if ( true !== $result ) {
			$result = new Toolset_Result( false, $this->wpdb->last_error );
		}

		return $result;
	}

}
