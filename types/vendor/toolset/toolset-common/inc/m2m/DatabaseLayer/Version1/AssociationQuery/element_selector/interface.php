<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;

/**
 * Manages the way element IDs are obtained when building the MySQL query for associations.
 *
 * Generates SELECT clauses for the element IDs. Allows for injecting additional JOIN clauses
 * into the final query.
 *
 * @since 2.5.10
 */
interface IToolset_Association_Query_Element_Selector {


	/**
	 * The element selector needs to be initialized early so that it can interact
	 * with the join manager object, if needed.
	 *
	 * See OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Sql_Expression_Builder::build() for detailed information.
	 *
	 * @return void
	 */
	public function initialize();


	/**
	 * Get an alias for an element ID that will be used in the SELECT clause.
	 *
	 * @param RelationshipRole $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string
	 */
	public function get_element_id_alias(
		RelationshipRole $for_role, $translate_if_possible = true
	);


	/**
	 * Tell whether there may be a different element ID value for the current and the default language.
	 *
	 * @param RelationshipRole $role
	 *
	 * @return mixed
	 */
	public function has_element_id_translated( RelationshipRole $role );


	/**
	 * Get a name of the table and the column that contains an element ID.
	 *
	 * This is different from the alias because it can be used within the query itself
	 * for other purposes.
	 *
	 * @param RelationshipRole $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string Unambiguous "column" or "table.column" that contains ID of the element.
	 */
	public function get_element_id_value(
		RelationshipRole $for_role, $translate_if_possible = true
	);


	/**
	 * Get all the select clauses for all the element IDs.
	 *
	 * Individual clauses must be connected with a comma, but there must not be
	 * a trailing comma present.
	 *
	 * @return string
	 */
	public function get_select_clauses();


	/**
	 * Get all JOIN clauses that need to be included in the query.
	 *
	 * The only assumption these JOINs can make is that there might be the relationships table joined
	 * first (if the element selector requires it). Anything else coming from the join manager
	 * will be joined after.
	 *
	 * @return string
	 */
	public function get_join_clauses();


	/**
	 * @param RelationshipRole $role
	 *
	 * @return void
	 */
	public function request_element_in_results( RelationshipRole $role );


	/**
	 * Call this to make sure the association ID and relationship ID will be included in the SELECT clause.
	 *
	 * @return void
	 * @since 2.6.1
	 */
	public function request_association_and_relationship_in_results();


	/**
	 * Call this to make sure the DISTINCT keyword will be used.
	 *
	 * @return void
	 * @since 2.6.1
	 */
	public function request_distinct_query();


	/**
	 * Get the DISTINCT keyword or an empty string.
	 *
	 * @return string
	 * @since 2.6.1
	 */
	public function maybe_get_distinct_modifier();


	/**
	 * Get roles that have been already requested.
	 *
	 * @return RelationshipRole[]
	 */
	public function get_requested_element_roles();


	/**
	 * Signal whether the intermediary post column can be skipped from the results.
	 *
	 * Note that this is really only concerning the result transformation object, which can then make a more informed
	 * decision about calling request_element_in_results().
	 *
	 * @param bool $skip
	 *
	 * @return void
	 */
	public function skip_intermediary_posts( $skip = true );


	/**
	 * Returns true if the intermediary post column may be skipped in for the result transformation process.
	 *
	 * @return bool
	 * @see self::skip_intermediary_posts()
	 */
	public function should_skip_intermediary_posts();
}
