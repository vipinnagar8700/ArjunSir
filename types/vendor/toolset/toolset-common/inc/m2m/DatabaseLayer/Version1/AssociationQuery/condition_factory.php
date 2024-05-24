<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use Toolset_Query_Condition_And;
use Toolset_Query_Condition_Contradiction;
use Toolset_Query_Condition_Not;
use Toolset_Query_Condition_Or;
use Toolset_Query_Condition_Tautology;
use Toolset_Relationship_Database_Unique_Table_Alias;

/**
 * A factory for AssociationQueryCondition implementations.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Factory {


	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param AssociationQueryCondition[] $operands
	 *
	 * @return AssociationQueryCondition
	 */
	public function do_or( $operands ) {
		return new Toolset_Query_Condition_Or( $operands );
	}


	/**
	 * Chain multiple conditions with AN.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param AssociationQueryCondition[] $operands
	 *
	 * @return Toolset_Query_Condition_And
	 */
	public function do_and( $operands ) {
		return new Toolset_Query_Condition_And( $operands );
	}


	/**
	 * A condition that is always true.
	 *
	 * @return AssociationQueryCondition
	 */
	public function tautology() {
		return new Toolset_Query_Condition_Tautology();
	}


	/**
	 * A condition that is always false.
	 *
	 * @return AssociationQueryCondition
	 */
	public function contradiction() {
		return new Toolset_Query_Condition_Contradiction();
	}


	/**
	 * Condition to query associations by a specific relationship (row) ID.
	 *
	 * @param int $relationship_id
	 * @param IToolset_Relationship_Definition|null $definition
	 *
	 * @return AssociationQueryCondition
	 */
	public function relationship_id( $relationship_id, IToolset_Relationship_Definition $definition = null ) {
		return new Toolset_Association_Query_Condition_Relationship_Id( $relationship_id, $definition );
	}


	/**
	 * Condition to query associations by a specific intermediary (row) ID.
	 *
	 * @param int $intermediary_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function intermediary_id( $intermediary_id ) {
		return new Toolset_Association_Query_Condition_Intermediary_Id( $intermediary_id );
	}


	/**
	 * Condition to query associations having intermediary id.
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_intermediary_id() {
		return new Toolset_Association_Query_Condition_Has_Intermediary_Id();
	}


	/**
	 * Condition to query associations by a particular element involved in a particular role.
	 *
	 * Warning: WPML-unaware implementation.
	 *
	 * @param int $element_id
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_id(
		$element_id, RelationshipRole $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	) {
		return new Toolset_Association_Query_Condition_Element_Id( $element_id, $for_role, $element_selector_provider );
	}


	/**
	 * Condition to query associations by a particular element involved in a particular role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_id
	 *
	 * @return Toolset_Association_Query_Condition_Element_Id_And_Domain
	 */
	public function element_id_and_domain(
		$element_id,
		$domain,
		RelationshipRole $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_id
	) {
		return new Toolset_Association_Query_Condition_Element_Id_And_Domain(
			$element_id, $domain, $for_role, $element_selector_provider, $query_original_element, $translate_provided_id
		);
	}


	/**
	 * Condition to query associations that do not contain a particular element in a particular role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_id
	 *
	 * @return Toolset_Association_Query_Condition_Element_Id_And_Domain
	 */
	public function exclude_element(
		$element_id,
		$domain,
		RelationshipRole $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_id
	) {
		return new Toolset_Association_Query_Condition_Exclude_Element(
			$element_id, $domain, $for_role, $element_selector_provider, $query_original_element, $translate_provided_id
		);
	}


	/**
	 * Condition to query associations by a status of an element in a particular role.
	 *
	 * @param string|string[] $status
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 *
	 * @param PostStatus $post_status
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_status(
		$status, RelationshipRole $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		PostStatus $post_status
	) {
		return new Toolset_Association_Query_Condition_Element_Status(
			$status, $for_role, $join_manager, $element_selector_provider, $post_status
		);
	}


	/**
	 * Query associations by the activity status of the relationship.
	 *
	 * @param bool $is_active
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_active_relationship( $is_active, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Has_Active_Relationship( $is_active, $join_manager );
	}


	/**
	 * Query associations by the element domain on a specified role.
	 *
	 * @param string $domain Domain name.
	 * @param RelationshipRoleParentChild $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_domain(
		$domain, RelationshipRoleParentChild $for_role, Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Has_Domain( $domain, $for_role, $join_manager );
	}


	/**
	 * @param bool $needs_legacy_support
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_legacy_relationship( $needs_legacy_support, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Has_Legacy_Relationship( $needs_legacy_support, $join_manager );
	}


	/**
	 * Query associations by element type on a given role.
	 *
	 * Warning: This doesn't query for the domain. Make sure you at least add
	 * a separate element domain condition. Otherwise, the results will be unpredictable.
	 *
	 * The best way is to use the has_domain_and_type() condition instead, which whill allow
	 * for some more advanced optimizations.
	 *
	 * @param string $type
	 * @param RelationshipRoleParentChild $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_type(
		$type,
		RelationshipRoleParentChild $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	) {
		return new Toolset_Association_Query_Condition_Has_Type( $for_role, $type, $join_manager, $unique_table_alias );
	}


	/**
	 * @param string $domain
	 * @param string $type
	 * @param RelationshipRoleParentChild $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 *
	 * @return Toolset_Association_Query_Condition_Has_Domain_And_Type
	 */
	public function has_domain_and_type(
		$domain,
		$type,
		RelationshipRoleParentChild $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	) {
		return new Toolset_Association_Query_Condition_Has_Domain_And_Type(
			$for_role, $domain, $type, $join_manager, $unique_table_alias, $this
		);
	}


	/**
	 * @param RelationshipRole $for_role
	 * @param array $query_args
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	 *
	 * @return AssociationQueryCondition
	 */
	public function wp_query(
		RelationshipRole $for_role,
		$query_args,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	) {
		return new Toolset_Association_Query_Condition_Wp_Query( $for_role, $query_args, $join_manager, $table_alias );
	}


	/**
	 * @param string $search_string
	 * @param bool $is_exact_search
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return AssociationQueryCondition
	 */
	public function search(
		$search_string,
		$is_exact_search,
		RelationshipRole $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Search(
			$search_string, $is_exact_search, $for_role, $join_manager
		);
	}


	/**
	 * @param int $association_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function association_id( $association_id ) {
		return new Toolset_Association_Query_Condition_Association_Id( $association_id );
	}


	/**
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $comparison_operator
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return Toolset_Association_Query_Condition_Postmeta
	 */
	public function postmeta(
		$meta_key,
		$meta_value,
		$comparison_operator,
		RelationshipRole $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Postmeta(
			$meta_key,
			$meta_value,
			$comparison_operator,
			$for_role,
			$join_manager
		);
	}


	/**
	 * Condition that a relationship has a certain origin.
	 *
	 * @param string $origin Origin: wizard, ...
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager Join manager.
	 *
	 * @return AssociationQueryCondition
	 */
	public function has_origin( $origin, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Relationship_Origin( $origin, $join_manager );
	}


	/**
	 * @param AssociationQueryCondition $condition
	 *
	 * @return Toolset_Query_Condition_Not
	 */
	public function not( AssociationQueryCondition $condition ) {
		return new Toolset_Query_Condition_Not( $condition );
	}


	/**
	 * @param int[] $element_ids
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param bool $query_original_element
	 * @param bool $translate_provided_ids
	 *
	 * @return Toolset_Association_Query_Condition_Multiple_Elements
	 */
	public function multiple_elements(
		$element_ids,
		$domain,
		RelationshipRole $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_ids
	) {
		return new Toolset_Association_Query_Condition_Multiple_Elements(
			$element_ids, $domain, $for_role, $element_selector_provider, $query_original_element, $translate_provided_ids
		);
	}


	/**
	 * Instantiate HasAutodeletableIntermediaryPost.
	 *
	 * @param bool $expected_value Value of the condition.
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager The join manager object from the association
	 *     query.
	 *
	 * @return \OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition
	 */
	public function has_autodeletable_intermediary_post( $expected_value, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new HasAutodeletableIntermediaryPost( $expected_value, $join_manager );
	}


	/**
	 * @return \OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition
	 */
	public function has_empty_intermediary() {
		return new \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Empty_Intermediary();
	}
}
