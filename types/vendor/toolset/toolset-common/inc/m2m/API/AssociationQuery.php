<?php

namespace OTGS\Toolset\Common\Relationships\API;

use InvalidArgumentException;
use IToolset_Association;
use IToolset_Element;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Exception\NotImplementedException;
use RuntimeException;
use Toolset_Element_Domain;
use Toolset_Field_Definition;
use Toolset_Query_Comparison_Operator;

/**
 * Association query class with a more OOP/functional approach.
 *
 * Allows for chaining query conditions and avoiding passing query arguments as associative arrays.
 * It makes it also possible to build queries with nested AND & OR statements in an arbitrary way.
 * The object model may be complex but all the complexity is hidden from the user, they need to know
 * only the methods on this class.
 *
 * Example usage:
 *
 * $query = $factory->association_query()
 *
 * $results = $query
 *     ->add(
 *         $query->has_domain( 'posts', new Toolset_Relationship_Role_Parent() )
 *     )
 *     ->add(
 *         $query->do_or(
 *             $query->has_type( 'attachment', new Toolset_Relationship_Role_Parent() ),
 *             $query->do_and(
 *                 $query->has_type( 'page', new Toolset_Relationship_Role_Child() ),
 *                 $query->has_type( 'post', new Toolset_Relationship_Role_Child() ),
 *             )
 *         )
 *     )
 *     ->add(
 *         $query->search( 'some string', new Toolset_Relationship_Role_Parent() )
 *     )
 *     ->order_by_field_value( $custom_field_definition )
 *     ->order( 'DESC' )
 *     ->limit( 50 )
 *     ->offset( 100 )
 *     ->return_association_instances()
 *     ->get_results();
 *
 * Note about default conditions:
 * - If no element status (element_status() or has_available_elements()) condition is used when constructing the query,
 *   has_available_elements() is used.
 * - If no has_active_relationship() condition is used when constructing the query, has_active_relationship(true)
 *   is used.
 * - This mechanism doesn't recognize where, how and if these conditions are actually applied, so even
 *   $query->do_if( false, $query->has_active_relationship( true ) ) will disable the default
 *   has_active_relationship() condition.
 * - You can prevent the adding of default conditions by $query->do_not_add_default_conditions().
 *
 * @since 4.0
 */
interface AssociationQuery {

	/**
	 * Add another condition to the query.
	 *
	 * @param AssociationQueryCondition $condition
	 *
	 * @return $this
	 */
	public function add( AssociationQueryCondition $condition );


	/**
	 * Prevent the query from adding any default conditions. WYSIWYG.
	 *
	 * @return $this
	 */
	public function do_not_add_default_conditions();


	/**
	 * Apply stored conditions and perform the query.
	 *
	 * @return IToolset_Association[]|int[]|IToolset_Element[]
	 */
	public function get_results();


	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param AssociationQueryCondition[] $conditions
	 *
	 * @return AssociationQueryCondition
	 */
	public function do_or( ...$conditions );


	/**
	 * Chain multiple conditions with AND.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param AssociationQueryCondition[] $conditions
	 *
	 * @return AssociationQueryCondition
	 */
	public function do_and( ...$conditions );


	/**
	 * Choose a query condition depending on a boolean expression.
	 *
	 * @param bool $statement A boolean condition statement.
	 * @param AssociationQueryCondition $if_branch Query condition that will be used
	 *     if the statement is true.
	 * @param AssociationQueryCondition|null $else_branch Query condition that will be
	 *     used if the statement is false. If none is provided, a tautology is used (always true).
	 *
	 * @return AssociationQueryCondition
	 * @since 2.5.6
	 */
	public function do_if( $statement, AssociationQueryCondition $if_branch, AssociationQueryCondition $else_branch = null );


	public function not( AssociationQueryCondition $condition );


	/**
	 * Query by a row ID of a relationship definition.
	 *
	 * @param int $relationship_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function relationship_id( $relationship_id );


	/**
	 * Query by a row intermediary_id of a relationship definition.
	 *
	 * @param int $relationship_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function intermediary_id( $relationship_id );


	/**
	 * Query by a relationship definition.
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition
	 *
	 * @return AssociationQueryCondition
	 */
	public function relationship( IToolset_Relationship_Definition $relationship_definition );


	/**
	 * Query by a relationship definition slug.
	 *
	 * @param string $slug
	 *
	 * @return AssociationQueryCondition
	 */
	public function relationship_slug( $slug );


	/**
	 * Query by an ID of an element in the selected role.
	 *
	 * Warning: This is an WPML-unaware query.
	 *
	 * @param int $element_id
	 * @param RelationshipRole $for_role
	 * @param bool $need_wpml_unaware_query Set this to true to avoid a _doing_it_wrong notice.
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_id( $element_id, RelationshipRole $for_role, $need_wpml_unaware_query = true );


	/**
	 * Query by an ID of an element in the selected role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 * @param bool $set_its_translation_language If true, the query may try to use the element's language
	 *     to determine the desired language of the results (see determine_translation_language() for details)
	 * @param null|string $element_identification_to_query_by Available only since the second database layer version.
	 *     Must be one of the ElementIdentification values or null, in which case $query_original_element will be used.
	 *     If this is not null, $query_original_element is ignored.
	 *
	 * @return AssociationQueryCondition
	 * @since 2.5.10
	 */
	public function element_id_and_domain(
		$element_id,
		$domain,
		RelationshipRole $for_role,
		$query_original_element = false,
		$translate_provided_id = true,
		$set_its_translation_language = true,
		$element_identification_to_query_by = null
	);


	/**
	 * Query by an element TRID if possible, otherwise fall back to querying by element ID and domain.
	 *
	 * See element_id_and_domain() for further details.
	 *
	 * @param int $trid Element TRID or 0 if it isn't set. Passing a non-zero value for a translatable relationship role
	 *     will filter results by this TRID, in any other case, filtering as in element_id_and_domain() will be used.
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param bool $translate_provided_id
	 * @param bool $set_its_translation_language
	 * @param string $element_identification_to_query_by
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_trid_or_id_and_domain(
		$trid,
		$element_id,
		$domain,
		RelationshipRole $for_role,
		$translate_provided_id = true,
		$set_its_translation_language = true,
		$element_identification_to_query_by = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
	);


	/**
	 * Query by a set of element IDs in the selected role.
	 *
	 * @param int[] $element_ids
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_ids If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 *
	 * @return AssociationQueryCondition
	 * @since 3.0.3
	 */
	public function multiple_elements( $element_ids, $domain, RelationshipRole $for_role, $query_original_element = false, $translate_provided_ids = true );


	/**
	 * Query by an element in the selected role.
	 *
	 * @param IToolset_Element $element
	 * @param RelationshipRole|null $for_role If null is provided, the query will involve all roles.
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 * @param bool $set_its_translation_language If true, the query may try to use the element's language
	 *     to determine the desired language of the results (see determine_translation_language() for details)
	 *
	 * @return AssociationQueryCondition
	 */
	public function element( IToolset_Element $element, RelationshipRole $for_role = null, $query_original_element = false, $translate_provided_id = true, $set_its_translation_language = true );


	/**
	 * Exclude associations with a particular element in the selected role.
	 *
	 * @param IToolset_Element $element
	 * @param RelationshipRole $for_role
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 *
	 * @return AssociationQueryCondition
	 */
	public function exclude_element( IToolset_Element $element, RelationshipRole $for_role, $query_original_element = false, $translate_provided_id = true );


	/**
	 * Query by a parent element.
	 *
	 * @param IToolset_Element $element_source
	 *
	 * @return AssociationQueryCondition
	 */
	public function parent( IToolset_Element $element_source );


	/**
	 * Query by a parent element ID.
	 *
	 * @param int $parent_id
	 * @param string $domain
	 *
	 * @return AssociationQueryCondition
	 */
	public function parent_id( $parent_id, $domain = Toolset_Element_Domain::POSTS );


	/**
	 * Query by a child element.
	 *
	 * @param IToolset_Element $element
	 *
	 * @return AssociationQueryCondition
	 */
	public function child( IToolset_Element $element );


	/**
	 * Query by a child element ID.
	 *
	 * @param int $child_id
	 * @param string $domain
	 *
	 * @return AssociationQueryCondition
	 */
	public function child_id( $child_id, $domain = Toolset_Element_Domain::POSTS );


	/**
	 * Query by an element status.
	 *
	 * @param string|string[] $statuses Value from ElementStatusCondition or one or more specific status values in an
	 *     array. Meaning of these options is domain-dependant.
	 * @param RelationshipRole|null $for_role
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_status( $statuses, RelationshipRole $for_role = null );


	/**
	 * Query only associations that have both elements available (see element_status()).
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_available_elements();


	/**
	 * Query associations by the activity status of the relationship.
	 *
	 * @param bool $is_active
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_active_relationship( $is_active = true );


	/**
	 * Query associations by the fact whether the relationship was migrated from the legacy implementation.
	 *
	 * @param bool $needs_legacy_support
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_legacy_relationship( $needs_legacy_support = true );


	/**
	 * Query associations by the element domain on a specified role.
	 *
	 * @param string $domain
	 * @param RelationshipRoleParentChild $for_role
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_domain( $domain, RelationshipRoleParentChild $for_role );


	/**
	 * Query associations based on element type.
	 *
	 * Warning: This doesn't query for the domain. Make sure you at least add
	 * a separate element domain condition. Otherwise, the results will be unpredictable.
	 *
	 * The best way is to use the has_domain_and_type() condition instead, which whill allow
	 * for some more advanced optimizations.
	 *
	 * @param string $type Element type.
	 * @param RelationshipRoleParentChild $for_role
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_type( $type, RelationshipRoleParentChild $for_role );


	/**
	 * Query associations based on element domain and type.
	 *
	 * @param string $domain Element domain.
	 * @param string $type Element type
	 * @param RelationshipRoleParentChild $for_role
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_domain_and_type( $domain, $type, RelationshipRoleParentChild $for_role );


	/**
	 * Condition that a relationship has a certain origin.
	 *
	 * @param String $origin Origin.
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_origin( $origin );


	/**
	 * Condition that the association has an intermediary id.
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_intermediary_id();


	/**
	 * Query by a WP_Query arguments applied on an element of a specified role.
	 *
	 * WARNING: It is important that you read the documentation of OTGS\Toolset\Common\Relationships\DatabaseLayer
	 * \Version1\Toolset_Association_Query_Condition_Wp_Query before using this.
	 *
	 * This may not be implemented in all versions of the database layer.
	 *
	 * @param RelationshipRole $for_role
	 * @param array $query_args
	 * @param string|null $confirmation 'i_know_what_i_am_doing'
	 *
	 * @return AssociationQueryCondition
	 *
	 * @throws InvalidArgumentException Thrown if you don't know what you are doing.
	 * @throws RuntimeException Thrown when the query condition is not available.
	 */
	public function wp_query( RelationshipRole $for_role, $query_args, $confirmation = null );


	/**
	 * Query by a string search in elements of a selected role.
	 *
	 * Note that the behaviour may be different per domain.
	 *
	 * @param string $search_string
	 * @param RelationshipRole $for_role
	 * @param bool $is_exact
	 *
	 * @return AssociationQueryCondition
	 */
	public function search( $search_string, RelationshipRole $for_role, $is_exact = false );


	/**
	 * Query by a specific association ID.
	 *
	 * This will also set the limit of the result count to one.
	 *
	 * @param int $association_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function association_id( $association_id );


	public function meta( $meta_key, $meta_value, $domain, RelationshipRole $for_role = null, $comparison = Toolset_Query_Comparison_Operator::EQUALS );


	/**
	 * Query associations by the fact whether they have an intermediary post that can be automatically deleted
	 * together with the association (which is a setting of the relationship definition).
	 *
	 * @param bool $expected_value Value of the condition.
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_autodeletable_intermediary_post( $expected_value = true );


	/**
	 * Indicate that get_results() should return instances of IToolset_Association.
	 *
	 * @return $this
	 */
	public function return_association_instances();


	/**
	 * Indicate that get_results() should return UIDs of associations.
	 *
	 * @return $this
	 */
	public function return_association_uids();


	/**
	 * Indicate that get_results() should return element IDs from a selected role.
	 *
	 * @param RelationshipRole $role
	 *
	 * @return $this
	 */
	public function return_element_ids( RelationshipRole $role );


	/**
	 * Indicate that get_results() should return IToolset_Element instances from a selected role.
	 *
	 * @param RelationshipRole $role
	 *
	 * @return $this
	 */
	public function return_element_instances( RelationshipRole $role );


	/**
	 * Indicate that get_results() should return arrays with elements indexed by their role names.
	 *
	 * This needs further configuration, see OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Result_Transformation_Element_Per_Role for
	 * further details.
	 *
	 * @return \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Result_Transformation_Element_Per_Role
	 * @since 3.0.9
	 */
	public function return_per_role();


	/**
	 * Set an offset for the query.
	 *
	 * @param int $value
	 *
	 * @return $this
	 * @throws InvalidArgumentException Thrown if an invalid value is provided.
	 */
	public function offset( $value );


	/**
	 * Limit a number of results for the query.
	 *
	 * Note that by default, the limit is set at a certain value, and the query can never be unlimited.
	 *
	 * @param int $value
	 *
	 * @return $this
	 * @throws InvalidArgumentException Thrown if an invalid value is provided.
	 */
	public function limit( $value );


	/**
	 * Set the sorting order.
	 *
	 * @param string $value 'ASC'|'DESC'
	 *
	 * @return $this
	 */
	public function order( $value );


	/**
	 * Indicate whether the query should also retrieve the total number of results.
	 *
	 * This is required for get_found_rows() to work.
	 *
	 * @param bool $is_needed
	 *
	 * @return $this
	 */
	public function need_found_rows( $is_needed = true );


	/**
	 * Return the total number of found results after get_results() was called.
	 *
	 * For this to work, need_found_rows() needs to be called when building the query.
	 *
	 * @return int
	 * @throws RuntimeException
	 */
	public function get_found_rows();


	/**
	 * Indicate that no result ordering is needed.
	 *
	 * @return $this
	 */
	public function dont_order();


	/**
	 * Order results by a title of element of given role.
	 *
	 * Note that ordering by intermediary posts will cause the associations without those to be excluded from results.
	 *
	 * @param RelationshipRole $for_role
	 *
	 * @return $this
	 */
	public function order_by_title( RelationshipRole $for_role );


	/**
	 * Order results by a value of a certain custom field on a selected element role.
	 *
	 * @param Toolset_Field_Definition $field_definition
	 * @param RelationshipRole $for_role
	 *
	 * @return $this
	 * @throws RuntimeException Thrown if the element domain is not supported.
	 */
	public function order_by_field_value( Toolset_Field_Definition $field_definition, RelationshipRole $for_role );


	/**
	 * Order results by a value of the element metadata.
	 *
	 * @param string $meta_key Meta key that should be used for ordering.
	 * @param string $domain Valid element domain. At the moment, only posts are supported.
	 * @param RelationshipRole $for_role Role of the element whose metadata should be used for ordering.
	 * @param bool $is_numeric If true, numeric ordering will be used.
	 *
	 * @return $this
	 * @throws RuntimeException If unsupported element domain is used.
	 * @throws InvalidArgumentException
	 * @since 2.6.1
	 */
	public function order_by_meta( $meta_key, $domain, RelationshipRole $for_role, $is_numeric = false );


	/**
	 * Make sure that the elements in results will never get translated.
	 *
	 * @return $this
	 * @since 2.6.4
	 */
	public function dont_translate_results();


	/**
	 * Set the preferred translation language.
	 *
	 * See determine_translation_language() for details.
	 *
	 * @param string $lang_code Valid language code.
	 *
	 * @return $this
	 */
	public function set_translation_language( $lang_code );


	/**
	 * Allow forcing a particular language for a given role.
	 *
	 * That means, only associations with translated posts will be used, and those without translations
	 * will be skipped from the results. Use with great caution.
	 *
	 * @deprecated Do not use, it may not be implemented in all database layer versions.
	 *
	 * @param RelationshipRole $role
	 * @param string $lang_code Default language, current language or '*'.
	 */
	public function force_language_per_role( RelationshipRole $role, $lang_code );


	/**
	 * Set the preferred translation language from a given element ID and domain.
	 *
	 * See determine_translation_language() for details.
	 *
	 * @param int $element_id ID of the element to take the language from.
	 * @param string $domain Element domain.
	 *
	 * @return $this
	 * @since 2.6.8
	 */
	public function set_translation_language_by_element_id_and_domain( $element_id, $domain );


	/**
	 * Perform the query to only return the number of found rows, if we're not interested in
	 * the actual results.
	 *
	 * @return int Number of results matching the query.
	 */
	public function get_found_rows_directly();


	public function use_cache( $use_cache = true );


	public function build_cache_key( $query_string );


	/**
	 * For translatable element roles, include the original language element ID, if it exists.
	 *
	 * Note that this is implemented only in the second version of the database layer (and above).
	 *
	 * @param bool $include
	 * @return $this
	 * @throws NotImplementedException
	 */
	public function include_original_language( $include = true );


	/**
	 * Treat all translatable post types as "display as translated" regardless of their actual translation mode.
	 *
	 * Non-translatable post types won't be affected. This is useful for querying in the admin where we might
	 * want to fall back to the default language if it exists despite of the post type settings.
	 *
	 * Note: This does nothing in the first version of the database layer, since only post types in the "display as
	 * translated" modes are supported there.
	 *
	 * @param bool $do_force
	 * @return $this
	 * @since 4.0
	 */
	public function force_display_as_translated_mode( $do_force = true );
}
