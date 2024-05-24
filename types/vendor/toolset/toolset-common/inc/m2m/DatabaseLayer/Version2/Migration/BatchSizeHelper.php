<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Functionality related to the migration batch size.
 *
 * @since 4.0
 */
class BatchSizeHelper {

	/** @var int How many posts per batch do we want to have by default. */
	const DEFAULT_BATCH_SIZE = 250;

	/** @var \wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;


	/**
	 * BatchSizeHelper constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct(
		\wpdb $wpdb,
		TableNames $table_names
	) {
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}

	/**
	 * Obtain the expected size of the migration batch.
	 *
	 * @return int
	 */
	public static function get_batch_size() {
		// Produce a safe default value.
		return max(
			1,
			(int) apply_filters( 'toolset/common/relationships/migration/batch_size', self::DEFAULT_BATCH_SIZE )
		);
	}


	/**
	 * Determine how many associations to migrate are there.
	 *
	 * @return int
	 */
	public function count_old_associations( $relationship_constraints = [] ) {
		$relationship_condition = '';
		if ( ! empty( $relationship_constraints ) ) {
			$relationship_condition = ' WHERE relationship_id IN ( '
				. implode( ', ', array_map( 'intval', $relationship_constraints ) )
				. ' ) ';
		}

		// https://dev.mysql.com/doc/refman/8.0/en/information-functions.html#function_found-rows
		return (int) $this->wpdb->get_var(
			"SELECT COUNT(*)
				FROM {$this->table_names->get_full_table_name( TableNames::ASSOCIATIONS )}
				$relationship_condition
				ORDER BY id ASC"
		);
	}
}
