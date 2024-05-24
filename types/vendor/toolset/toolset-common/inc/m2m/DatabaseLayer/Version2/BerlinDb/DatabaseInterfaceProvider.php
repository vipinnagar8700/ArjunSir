<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;


use OTGS\Toolset\Common\Result\ResultInterface;

class DatabaseInterfaceProvider {


	/** @var \wpdb */
	private $wpdb;


	/**
	 * DatabaseInterfaceProvider constructor.
	 *
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}


	/**
	 * @return \wpdb
	 */
	public function get_wpdb() {
		return $this->wpdb;
	}


	/**
	 * Check if a wpdb operation succeeded.
	 *
	 * @param mixed $result
	 * @param bool $no_rows_means_success
	 *
	 * @return ResultInterface
	 */
	public function parse_result( $result = false, $no_rows_means_success = false ) {
		if ( empty( $result ) ) {
			return new \Toolset_Result( $no_rows_means_success );
		}

		if ( is_wp_error( $result ) ) {
			return new \Toolset_Result( $result );
		}

		if( is_numeric( $result ) ) {
			return new \Toolset_Result_Updated( true, $result );
		}

		return new \Toolset_Result( true );
	}


}
