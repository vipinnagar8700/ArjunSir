<?php

namespace OTGS\Toolset\Common\Relationships\API;

use IToolset_Relationship_Definition;
use IToolset_Relationship_Query_Cardinality_Match;
use RuntimeException;
use Toolset_Relationship_Definition;
use Toolset_Relationship_Query_Cardinality_Match_Factory;

/**
 * Relationship query with a OOP/functional approach.
 *
 * Allows for chaining query conditions and avoiding passing query arguments as associative arrays.
 * It makes it also possible to build queries with nested AND & OR statements in an arbitrary way.
 * The object model may be complex but all the complexity is hidden from the user, they need to know
 * only the methods on this class.
 *
 * Example usage:
 *
 * $query = $factory->relationship_query()
 *
 * $results = $query
 *     ->add(
 *         $query->has_domain( 'posts' )
 *     )
 *     ->add(
 *         $query->do_or(
 *             $query->has_type( 'attachment', $factory->role_parent() ),
 *             $query->do_and(
 *                 $query->has_type( 'page', $factory->role_parent() ),
 *                 $query->is_legacy( false )
 *             )
 *         )
 *     )
 *     ->add( $query->is_active( '*' ) )
 *     ->get_results();
 *
 * Note:
 * - If no is_active() condition is used when constructing the query, is_active(true) is used. To get both
 *     active and non-active relationship definitions, you need to manually add is_active('*').
 * - If no has_active_post_types() condition is used when constructing the query, has_active_post_types(true)
 *     is used for both parent and child role.
 * - If no origin() condition is used, origin( 'wizard' ) is added by default.
 * - This mechanism doesn't recognize where, how and if these conditions are actually applied, so even
 *     $query->do_if( false, $query->is_active( true ) ) will disable the default is_active() condition.
 *
 * @since 4.0
 */
interface RelationshipQuery {

	/**
	 * Add another condition to the query.
	 *
	 * @param RelationshipQueryCondition $condition
	 *
	 * @return $this
	 */
	public function add( RelationshipQueryCondition $condition );


	/**
	 * @return $this
	 */
	public function do_not_add_default_conditions();


	/**
	 * Apply stored conditions and perform the query.
	 *
	 * @return IToolset_Relationship_Definition[]
	 */
	public function get_results();


	/**
	 * Get just the number of found relationships directly.
	 *
	 * @return int
	 * @since 4.0
	 */
	public function get_found_rows_directly();


	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param RelationshipQueryCondition[] $conditions
	 * @return RelationshipQueryCondition
	 */
	public function do_or( ...$conditions );


	/**
	 * Chain multiple conditions with AND.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param RelationshipQueryCondition[] [$condition1, $condition2, ...]
	 * @return RelationshipQueryCondition
	 */
	public function do_and( ...$conditions );


	/**
	 * Condition that the relationship involves a certain domain.
	 *
	 * @param string $domain_name One of the Toolset_Element_Domain values.
	 * @param RelationshipRole|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function has_domain( $domain_name, RelationshipRole $in_role = null );


	/**
	 * Condition that the relationship comes from a certain source
	 *
	 * @param string|null $origin One of the keywords from IToolset_Relationship_Origin or null to include relationships with all origins.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function origin( $origin );


	/**
	 * Condition that the relationship includes a certain intermediary object.
	 *
	 * @param string $intermediary_type An intermediary object slug.
	 *
	 * @return RelationshipQueryCondition
	 *
	 * @since 2.6.7
	 */
	public function intermediary_type( $intermediary_type );


	/**
	 * Condition that the relationship has a certain type in a given role.
	 *
	 * @param string $type
	 * @param RelationshipRoleParentChild|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function has_type( $type, $in_role = null );


	/**
	 * Condition that the relationship has a certain type in a given role.
	 *
	 * @param string $type
	 * @param RelationshipRoleParentChild|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function exclude_type( $type, $in_role = null );


	/**
	 * Condition that the relationship has a certain type and a domain in a given role.
	 *
	 * @param string $type
	 * @param string $domain One of the Toolset_Element_Domain values.
	 * @param RelationshipRole|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function has_domain_and_type( $type, $domain, RelationshipRole $in_role = null );


	/**
	 * Condition that the relationship was migrated from the legacy implementation.
	 *
	 * @param bool $should_be_legacy
	 *
	 * @return RelationshipQueryCondition
	 */
	public function is_legacy( $should_be_legacy = true );


	/**
	 * Condition that the relationship is active.
	 *
	 * @param bool $should_be_active
	 *
	 * @return RelationshipQueryCondition
	 */
	public function is_active( $should_be_active = true );


	/**
	 * Condition that the relationship has at least one active post type in a given role (or another domain than posts).
	 *
	 * @param bool $has_active_post_types
	 * @param RelationshipRoleParentChild|null $in_role
	 *
	 * @return RelationshipQueryCondition
	 */
	public function has_active_post_types( $has_active_post_types = true, RelationshipRoleParentChild $in_role = null );


	/**
	 * Get a factory of cardinality constrains, which can be used as an argument for $this->has_cardinality().
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Factory
	 */
	public function cardinality();

	/**
	 * Condition that a relationship has a certain cardinality.
	 *
	 * Use methods on $this->cardinality() to obtain a valid argument for this method.
	 *
	 * @param IToolset_Relationship_Query_Cardinality_Match $cardinality_match Object
	 *     that holds cardinality constraints.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function has_cardinality( IToolset_Relationship_Query_Cardinality_Match $cardinality_match );


	/**
	 * Choose a query condition depending on a boolean expression.
	 *
	 * @param bool $statement A boolean condition statement.
	 * @param RelationshipQueryCondition $if_branch Query condition that will be used
	 *     if the statement is true.
	 * @param RelationshipQueryCondition|null $else_branch Query condition that will be
	 *     used if the statement is false. If none is provided, a tautology is used (always true).
	 *
	 * @return RelationshipQueryCondition
	 * @since 2.5.6
	 */
	public function do_if(
		$statement,
		RelationshipQueryCondition $if_branch,
		RelationshipQueryCondition $else_branch = null
	);


	/**
	 * Indicate that the query should also determine the total number of found rows.
	 *
	 * This has to be set to true if you plan using get_found_rows().
	 *
	 * @param bool $is_needed
	 *
	 * @since 2.5.8
	 * @return $this
	 */
	public function need_found_rows( $is_needed = true );


	/**
	 * Return a number of found rows.
	 *
	 * This can be called only after get_results() if need_found_rows() was set to true
	 * while building the query. Otherwise, an exception will be thrown.
	 *
	 * @return int
	 * @throws RuntimeException
	 * @since 2.5.8
	 */
	public function get_found_rows();

	/**
	 * Condition that excludes a relationship.
	 *
	 * @param IToolset_Relationship_Definition $relationship Relationship Definition.
	 *
	 * @return RelationshipQueryCondition
	 */
	public function exclude_relationship( $relationship );


	/**
	 * Define whether a cache can be used for this query. Defaults to true.
	 *
	 * @param bool $use_cache
	 * @return $this
	 * @since Types 3.4.7
	 */
	public function use_cache( $use_cache = true );
}
