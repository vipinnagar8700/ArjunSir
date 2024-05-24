<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery;

use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\AssociationId;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ElementId;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ElementIdAndDomain;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ElementStatus;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ElementTridOrIdAndDomain;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ExcludeElement;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasActiveRelationship;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasAutodeletableIntermediaryPost;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasDomain;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasDomainAndType;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasIntermediaryId;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasLegacyRelationship;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasType;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\MultipleElements;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\PostMeta;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\RelationshipId;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\RelationshipOrigin;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\Search;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorProvider;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\AndOperator;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\Contradiction;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\Not;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\OrOperator;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\Tautology;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Factory for AssociationQueryCondition implementations.
 *
 * The setup() method must be called before any further use.
 *
 * @since 4.0
 * @codeCoverageIgnore
 */
class ConditionFactory {


	/** @var ElementSelectorProvider */
	private $element_selector_provider;


	/** @var TableJoinManager */
	private $table_join_manager;


	/** @var PostStatus */
	private $post_status;


	/** @var UniqueTableAlias */
	private $unique_table_alias;


	/** @var TableNames */
	private $table_names;


	/** @var \wpdb */
	private $wpdb;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * ConditionFactory constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param PostStatus $post_status
	 * @param TableNames $table_names
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		\wpdb $wpdb,
		PostStatus $post_status,
		TableNames $table_names,
		WpmlService $wpml_service
	) {
		$this->wpdb = $wpdb;
		$this->post_status = $post_status;
		$this->table_names = $table_names;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * Setup the factory for usage in a particular context.
	 *
	 * Must be called before further use.
	 *
	 * @param ElementSelectorProvider $element_selector_provider
	 * @param TableJoinManager $table_join_manager
	 * @param UniqueTableAlias $unique_table_alias
	 */
	public function setup(
		ElementSelectorProvider $element_selector_provider,
		TableJoinManager $table_join_manager,
		UniqueTableAlias $unique_table_alias
	) {
		$this->element_selector_provider = $element_selector_provider;
		$this->table_join_manager = $table_join_manager;
		$this->unique_table_alias = $unique_table_alias;
	}


	public function do_or( array $operands ) {
		return new OrOperator( $operands );
	}


	public function do_and( array $operands ) {
		return new AndOperator( $operands );
	}


	public function tautology() {
		return new Tautology();
	}


	public function contradiction() {
		return new Contradiction();
	}


	public function not( AssociationQueryCondition $condition ) {
		return new Not( $condition );
	}


	public function association_id( $association_id ) {
		return new AssociationId( $association_id );
	}


	public function element_id( $element_id, RelationshipRole $for_role ) {
		return new ElementId( $element_id, $for_role, $this->element_selector_provider );
	}


	public function element_id_and_domain(
		$element_id, $domain, RelationshipRole $for_role, $element_identification_to_query_by, $translate_original_id
	) {
		return new ElementIdAndDomain(
			$element_id,
			$domain,
			$for_role,
			$this->element_selector_provider,
			$element_identification_to_query_by,
			$translate_original_id,
			$this->wpml_service
		);
	}


	/**
	 * @param int $trid
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param string $element_identification_to_query_by
	 * @param bool $translate_original_id
	 *
	 * @return AssociationQueryCondition
	 */
	public function element_trid_or_id_and_domain(
		$trid,
		$element_id,
		$domain,
		RelationshipRole $for_role,
		$element_identification_to_query_by,
		$translate_original_id
	) {
		return new ElementTridOrIdAndDomain(
			$trid,
			$element_id,
			$domain,
			$for_role,
			$this->element_selector_provider,
			$element_identification_to_query_by,
			$translate_original_id,
			$this
		);
	}


	public function element_status( $statuses, RelationshipRole $for_role ) {
		return new ElementStatus(
			$statuses, $for_role, $this->table_join_manager, $this->post_status
		);
	}


	public function exclude_element(
		$element_id, $domain, RelationshipRole $for_role, $element_identification_to_query_by, $translate_original_id
	) {
		return new ExcludeElement(
			$element_id,
			$domain,
			$for_role,
			$this->element_selector_provider,
			$element_identification_to_query_by,
			$translate_original_id,
			$this->wpml_service
		);
	}


	public function has_active_relationship( $expected_value ) {
		return new HasActiveRelationship( $expected_value, $this->table_join_manager );
	}


	public function has_autodeletable_intermediary( $expected_value ) {
		return new HasAutodeletableIntermediaryPost( $expected_value, $this->table_join_manager );
	}


	public function has_domain( $domain, RelationshipRoleParentChild $for_role ) {
		return new HasDomain( $domain, $for_role, $this->table_join_manager );
	}


	public function has_domain_and_type( $domain, $type, RelationshipRoleParentChild $for_role ) {
		return new HasDomainAndType(
			$for_role, $domain, $type, $this
		);
	}


	public function has_intermediary_id() {
		return new HasIntermediaryId();
	}


	public function has_legacy_relationship( $expected_value ) {
		return new HasLegacyRelationship( $expected_value, $this->table_join_manager );
	}


	public function has_type( $type, RelationshipRoleParentChild $for_role ) {
		return new HasType( $for_role, $type, $this->table_join_manager, $this->unique_table_alias, $this->table_names );
	}


	public function multiple_elements(
		$element_ids, $domain, RelationshipRole $for_role, $query_original_element, $translate_original_id

	) {
		return new MultipleElements(
			$element_ids,
			$domain,
			$for_role,
			$this->element_selector_provider,
			$query_original_element,
			$translate_original_id
		);
	}


	public function post_meta( $meta_key, $meta_value, $comparison_operator, RelationshipRole $for_role ) {
		return new PostMeta( $meta_key, $meta_value, $comparison_operator, $for_role, $this->table_join_manager );
	}


	public function relationship_id( $relationship_id, \IToolset_Relationship_Definition $relationship_definition = null ) {
		return new RelationshipId( $relationship_id, $relationship_definition );
	}


	public function relationship_origin( $expected_value ) {
		return new RelationshipOrigin( $expected_value, $this->table_join_manager );
	}


	public function search( $search_string, $is_exact_search, RelationshipRole $for_role ) {
		return new Search( $search_string, $is_exact_search, $for_role, $this->table_join_manager, $this->wpdb );
	}


}

