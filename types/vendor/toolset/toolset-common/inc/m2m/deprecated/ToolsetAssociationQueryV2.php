<?php

use OTGS\Toolset\Common\Relationships\API\AssociationQuery;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;

/**
 * Facade to keep everything working with a direct instantiation of Toolset_Association_Query_V2 all over the
 * Toolset codebase while we've introduced an interface and a factory that should be used instead.
 *
 * @deprecated Use OTGS\Toolset\Common\Relationships\API\Factory::association_query().
 */
class Toolset_Association_Query_V2 implements AssociationQuery {

	/** @var AssociationQuery */
	private $_actual_query;


	private function _get_query() {
		if( null === $this->_actual_query ) {
			$factory = new Factory();
			$this->_actual_query = $factory->association_query();
		}
		return $this->_actual_query;
	}


	/**
	 * @inheritDoc
	 */
	public function add( AssociationQueryCondition $condition ) {
		return call_user_func_array( [ $this->_get_query(), 'add' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function do_not_add_default_conditions() {
		return call_user_func_array( [ $this->_get_query(), 'do_not_add_default_conditions' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function get_results() {
		return call_user_func_array( [ $this->_get_query(), 'get_results' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function do_or( ...$conditions ) {
		return call_user_func_array( [ $this->_get_query(), 'do_or' ], $conditions );
	}


	/**
	 * @inheritDoc
	 */
	public function do_and( ...$conditions ) {
		return call_user_func_array( [ $this->_get_query(), 'do_and' ], $conditions );
	}


	/**
	 * @inheritDoc
	 */
	public function do_if( $statement, AssociationQueryCondition $if_branch, AssociationQueryCondition $else_branch = null ) {
		return call_user_func_array( [ $this->_get_query(), 'do_if' ], func_get_args() );
	}


	public function not( AssociationQueryCondition $condition ) {
		return call_user_func_array( [ $this->_get_query(), 'not' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function relationship_id( $relationship_id ) {
		return call_user_func_array( [ $this->_get_query(), 'relationship_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function intermediary_id( $relationship_id ) {
		return call_user_func_array( [ $this->_get_query(), 'intermediary_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function relationship( IToolset_Relationship_Definition $relationship_definition ) {
		return call_user_func_array( [ $this->_get_query(), 'relationship' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function relationship_slug( $slug ) {
		return call_user_func_array( [ $this->_get_query(), 'relationship_slug' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function element_id( $element_id, RelationshipRole $for_role, $need_wpml_unaware_query = true ) {
		return call_user_func_array( [ $this->_get_query(), 'element_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function element_id_and_domain(
		$element_id,
		$domain,
		RelationshipRole $for_role,
		$query_original_element = false,
		$translate_provided_id = true,
		$set_its_translation_language = true,
		$element_identification_to_query_by = null
	) {
		return call_user_func_array( [ $this->_get_query(), 'element_id_and_domain' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function multiple_elements( $element_ids, $domain, RelationshipRole $for_role, $query_original_element = false, $translate_provided_ids = true ) {
		return call_user_func_array( [ $this->_get_query(), 'multiple_elements' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function element( IToolset_Element $element, RelationshipRole $for_role = null, $query_original_element = false, $translate_provided_id = true, $set_its_translation_language = true ) {
		return call_user_func_array( [ $this->_get_query(), 'element' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function exclude_element( IToolset_Element $element, RelationshipRole $for_role, $query_original_element = false, $translate_provided_id = true ) {
		return call_user_func_array( [ $this->_get_query(), 'exclude_element' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function parent( IToolset_Element $element_source ) {
		return call_user_func_array( [ $this->_get_query(), 'parent' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function parent_id( $parent_id, $domain = Toolset_Element_Domain::POSTS ) {
		return call_user_func_array( [ $this->_get_query(), 'parent_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function child( IToolset_Element $element ) {
		return call_user_func_array( [ $this->_get_query(), 'child' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function child_id( $child_id, $domain = Toolset_Element_Domain::POSTS ) {
		return call_user_func_array( [ $this->_get_query(), 'child_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function element_status( $statuses, RelationshipRole $for_role = null ) {
		return call_user_func_array( [ $this->_get_query(), 'element_status' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_available_elements() {
		return call_user_func_array( [ $this->_get_query(), 'has_available_elements' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_active_relationship( $is_active = true ) {
		return call_user_func_array( [ $this->_get_query(), 'has_active_relationship' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_legacy_relationship( $needs_legacy_support = true ) {
		return call_user_func_array( [ $this->_get_query(), 'has_legacy_relationship' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_domain( $domain, RelationshipRoleParentChild $for_role ) {
		return call_user_func_array( [ $this->_get_query(), 'has_domain' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_type( $type, RelationshipRoleParentChild $for_role ) {
		return call_user_func_array( [ $this->_get_query(), 'has_type' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_domain_and_type( $domain, $type, RelationshipRoleParentChild $for_role ) {
		return call_user_func_array( [ $this->_get_query(), 'has_domain_and_type' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_origin( $origin ) {
		return call_user_func_array( [ $this->_get_query(), 'has_origin' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_intermediary_id() {
		return call_user_func_array( [ $this->_get_query(), 'has_intermediary_id' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function wp_query( RelationshipRole $for_role, $query_args, $confirmation = null ) {
		return call_user_func_array( [ $this->_get_query(), 'wp_query' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function search( $search_string, RelationshipRole $for_role, $is_exact = false ) {
		return call_user_func_array( [ $this->_get_query(), 'search' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function association_id( $association_id ) {
		return call_user_func_array( [ $this->_get_query(), 'association_id' ], func_get_args() );
	}


	public function meta( $meta_key, $meta_value, $domain, RelationshipRole $for_role = null, $comparison = Toolset_Query_Comparison_Operator::EQUALS ) {
		return call_user_func_array( [ $this->_get_query(), 'meta' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function has_autodeletable_intermediary_post( $expected_value = true ) {
		return call_user_func_array( [ $this->_get_query(), 'has_autodeletable_intermediary_post' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function return_association_instances() {
		return call_user_func_array( [ $this->_get_query(), 'return_association_instances' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function return_association_uids() {
		return call_user_func_array( [ $this->_get_query(), 'return_association_uids' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function return_element_ids( RelationshipRole $role ) {
		return call_user_func_array( [ $this->_get_query(), 'return_element_ids' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function return_element_instances( RelationshipRole $role ) {
		return call_user_func_array( [ $this->_get_query(), 'return_element_instances' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function return_per_role() {
		return call_user_func_array( [ $this->_get_query(), 'return_per_role' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function offset( $value ) {
		return call_user_func_array( [ $this->_get_query(), 'offset' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function limit( $value ) {
		return call_user_func_array( [ $this->_get_query(), 'limit' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function order( $value ) {
		return call_user_func_array( [ $this->_get_query(), 'order' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function need_found_rows( $is_needed = true ) {
		return call_user_func_array( [ $this->_get_query(), 'need_found_rows' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function get_found_rows() {
		return call_user_func_array( [ $this->_get_query(), 'get_found_rows' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function dont_order() {
		return call_user_func_array( [ $this->_get_query(), 'dont_order' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_title( RelationshipRole $for_role ) {
		return call_user_func_array( [ $this->_get_query(), 'order_by_title' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_field_value( Toolset_Field_Definition $field_definition, RelationshipRole $for_role ) {
		return call_user_func_array( [ $this->_get_query(), 'order_by_field_value' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_meta( $meta_key, $domain, RelationshipRole $for_role, $is_numeric = false ) {
		return call_user_func_array( [ $this->_get_query(), 'order_by_meta' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function dont_translate_results() {
		return call_user_func_array( [ $this->_get_query(), 'dont_translate_results' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function set_translation_language( $lang_code ) {
		return call_user_func_array( [ $this->_get_query(), 'set_translation_language' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function force_language_per_role( RelationshipRole $role, $lang_code ) {
		return call_user_func_array( [ $this->_get_query(), 'force_language_per_role' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function set_translation_language_by_element_id_and_domain( $element_id, $domain ) {
		return call_user_func_array( [ $this->_get_query(), 'set_translation_language_by_element_id_and_domain' ], func_get_args() );
	}


	/**
	 * @inheritDoc
	 */
	public function get_found_rows_directly() {
		return call_user_func_array( [ $this->_get_query(), 'get_found_rows_directly' ], func_get_args() );
	}


	public function use_cache( $use_cache = true ) {
		return call_user_func_array( [ $this->_get_query(), 'use_cache' ], func_get_args() );
	}


	public function build_cache_key( $query_string ) {
		return call_user_func_array( [ $this->_get_query(), 'build_cache_key' ], func_get_args() );
	}


	public function include_original_language( $include = true ) {
		return call_user_func_array( [ $this->_get_query(), 'include_original_language' ], func_get_args() );
	}


	public function force_display_as_translated_mode( $do_force = true ) {
		return call_user_func_array( [ $this->_get_query(), 'force_display_as_translated_mode' ], func_get_args() );
	}


	public function element_trid_or_id_and_domain( $trid, $element_id, $domain, RelationshipRole $for_role, $translate_provided_id = true, $set_its_translation_language = true, $element_identification_to_query_by = \OTGS\Toolset\Common\Relationships\API\ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE ) {
		return call_user_func_array( [ $this->_get_query(), 'element_trid_or_id_and_domain' ], func_get_args() );
	}
}
