<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use wpdb;

/**
 * A wrapper class around a wpdb instance that redirects all calls to it, and
 * allows to use all its properties, but overrides the value of $wpdb->posts to return
 * an alias instead, that is specific for a selected element role.
 *
 * This is being used by
 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Wp_Query, check it for
 * more information.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Wpdb_Wrapper {


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/** @var RelationshipRole */
	private $for_role;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Wpdb_Wrapper constructor.
	 *
	 * @param wpdb $wpdb
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param RelationshipRole $for_role
	 */
	public function __construct(
		wpdb $wpdb,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		RelationshipRole $for_role
	) {
		$this->wpdb = $wpdb;
		$this->join_manager = $join_manager;
		$this->for_role = $for_role;
	}


	/**
	 * Get a $wpdb property name.
	 *
	 * Override $wpdb->posts.
	 *
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	public function __get( $property_name ) {
		if ( 'posts' === $property_name ) {
			return $this->join_manager->wp_posts( $this->for_role );
		}

		return $this->wpdb->{$property_name};
	}


	/**
	 * Implement empty() and isset() checks for $wpdb properties.
	 *
	 * @param string $property_name
	 *
	 * @return bool
	 */
	public function __isset( $property_name ) {
		return isset( $this->wpdb->{$property_name} );
	}


	/**
	 * Call a method on $wpdb.
	 *
	 * @param string $method_name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call( $method_name, $arguments ) {
		return call_user_func_array( array( $this->wpdb, $method_name ), $arguments );
	}

}
