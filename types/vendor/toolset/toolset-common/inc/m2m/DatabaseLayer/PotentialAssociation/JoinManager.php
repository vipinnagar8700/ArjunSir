<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation;


/**
 * Handle the MySQL JOIN clause construction when augmenting the WP_Query in
 * \OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\PostQuery.
 *
 * Make sure that JOINs come in the right order and are not duplicated.
 *
 * Note that hook() and unhook() must be called around the WP_Query usage for proper function.
 *
 * @since 2.8
 */
interface JoinManager {

	public function hook();


	public function unhook();


	/**
	 * Indicate that a certain table (or tables) need to be joined.
	 *
	 * @param string $table_keyword One of the JOIN_ constants
	 */
	public function register_join( $table_keyword );


	/**
	 * Add all registered JOINs to the JOIN clause.
	 *
	 * Note that this has to be idempotent since the filter may be applied several times within a single WP_Query
	 * instance.
	 *
	 * @param string $join
	 *
	 * @return string
	 */
	public function add_join_clauses( $join );
}
