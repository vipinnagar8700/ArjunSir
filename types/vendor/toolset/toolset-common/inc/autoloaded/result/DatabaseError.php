<?php


namespace OTGS\Toolset\Common\Result;

/**
 * Result that represents a database error.
 *
 * For convenience only.
 *
 * @since 4.0
 */
class DatabaseError extends SingleResult {

	public function __construct( $action_description, \wpdb $wpdb, $return_value = null ) {
		parent::__construct( false, sprintf(
			__( 'Database error when trying to perform action "%s": last_error="%s", return="%s", last_query="%s"', 'wpv-views' ),
			$action_description,
			$wpdb->last_error,
			$return_value,
			$wpdb->last_query
		) );
	}


	public function has_warnings() {
		return false;
	}
}
