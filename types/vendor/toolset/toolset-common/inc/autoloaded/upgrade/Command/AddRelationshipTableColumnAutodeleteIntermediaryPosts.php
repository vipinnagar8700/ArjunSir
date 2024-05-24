<?php

namespace OTGS\Toolset\Common\Upgrade\Command;

use OTGS\Toolset\Common\Result\ResultInterface;
use Toolset_Result;
use Toolset_Result_Set;

/**
 * Add a new column to the relationship table as safely as possible.
 *
 * @since Types 3.2
 */
class AddRelationshipTableColumnAutodeleteIntermediaryPosts
	extends \Toolset_Wpdb_User implements \OTGS\Toolset\Common\Upgrade\UpgradeCommand {

	const NEW_COLUMN_NAME = 'autodelete_intermediary';


	/**
	 * Run the command.
	 *
	 * @return ResultInterface
	 */
	public function run() {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Nothing to do here: The tables will be created as soon as m2m is activated for the first time.
			return new Toolset_Result( true );
		}

		if ( $this->is_database_already_up_to_date() ) {
			// Nothing to do here: This happens when Types is activated on a fresh site: It creates
			// the tables according to the new structure but runs the upgrade routine at the same time.
			return new Toolset_Result( true );
		}

		$results = new Toolset_Result_Set();
		$results->add( $this->update_relationships_table() );

		return $results;
	}


	/**
	 * If the autodelete_intermediary column exists in relationship database, it means that we're dealing
	 * with a more recent database structure than this command aims to improve.
	 *
	 * @return bool
	 */
	private function is_database_already_up_to_date() {
		$table_name = $this->get_relationships_table_name();
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SHOW COLUMNS FROM {$table_name} WHERE field = %s", self::NEW_COLUMN_NAME )
		);
		$is_updated = ! empty( $row );
		return $is_updated;
	}


	/**
	 * Get the name of the relationship table with the correct prefix.
	 *
	 * @return string
	 */
	private function get_relationships_table_name() {
		return $this->wpdb->prefix . 'toolset_relationships';
	}


	/**
	 * Update the relationship table structure.
	 *
	 * @return false|Toolset_Result
	 */
	private function update_relationships_table() {
		$query = "ALTER TABLE `{$this->get_relationships_table_name()}`
			ADD `" . self::NEW_COLUMN_NAME . "` TINYINT(1) NOT NULL DEFAULT '1' AFTER `is_active`";

		$result = $this->wpdb->query( $query );

		if ( true !== $result ) {
			$result = new Toolset_Result( false, $this->wpdb->last_error );
		}

		return $result;
	}

}
