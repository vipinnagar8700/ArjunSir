<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery;

use InvalidArgumentException;
use IToolset_Element;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Exception\NotImplementedException;
use OTGS\Toolset\Common\Relationships\API\AssociationQuery;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\Constants;
use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\AssociationQueryCache;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\ElementIdAndDomain;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\HasDomainAndType;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition\RelationshipId;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorProvider;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy\OrderByFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy\OrderByInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation\ResultTransformationFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation\ResultTransformationInterface;
use OTGS\Toolset\Common\Relationships\GenericQuery\Condition\AndOperator;
use OTGS\Toolset\Common\WPML\WpmlService;
use RuntimeException;
use Toolset_Element_Domain;
use Toolset_Field_Definition;
use Toolset_Field_Type_Definition_Numeric;
use Toolset_Query_Comparison_Operator;
use Toolset_Relationship_Definition_Repository;
use Toolset_Relationship_Role;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;
use Toolset_Utils;
use wpdb;

class Query implements AssociationQuery {

	/** @var AssociationQueryCondition[] */
	private $conditions = [];

	/** @var bool */
	private $should_add_default_conditions = true;

	/** @var int|null */
	private $limit;

	/** @var int */
	private $offset = 0;

	/** @var string */
	private $order = Constants::ORDER_ASC;

	/** @var bool */
	private $need_found_rows = false;

	/** @var bool */
	private $was_used = false;

	/** @var null|int */
	private $found_rows;

	/** @var bool */
	private $use_cache = true;

	/** @var null|ResultTransformationInterface */
	private $result_transformation;

	/** @var string|null */
	private $translation_language;

	/** @var null|OrderByInterface */
	private $orderby;

	/** @var bool */
	private $has_active_relationship_condition = false;

	/** @var bool */
	private $has_element_status_condition = false;

	/** @var RelationshipRole[] */
	private $roles_to_maybe_include_auto_drafts = [];

	/** @var wpdb */
	private $wpdb;

	/** @var ConditionFactory */
	private $condition_factory;

	/** @var WpmlService */
	private $wpml_service;

	/** @var AssociationQueryCache */
	private $query_cache;

	/** @var ElementSelectorProvider */
	private $element_selector_provider;

	/** @var UniqueTableAlias */
	private $unique_table_alias;

	/** @var TableJoinManager */
	private $join_manager;

	/** @var SqlExpressionBuilder */
	private $expression_builder;

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var OrderByFactory */
	private $orderby_factory;

	/** @var ResultTransformationFactory */
	private $result_transformation_factory;


	/**
	 * Query constructor.
	 *
	 * @param wpdb $wpdb
	 * @param ConditionFactory $condition_factory
	 * @param WpmlService $wpml_service
	 * @param AssociationQueryCache $query_cache
	 * @param ElementSelectorProvider $element_selector_provider
	 * @param UniqueTableAlias $unique_table_alias
	 * @param TableJoinManager $join_manager
	 * @param SqlExpressionBuilder $expression_builder
	 * @param Toolset_Relationship_Definition_Repository $relationship_definition_repository
	 * @param OrderByFactory $orderby_factory
	 * @param ResultTransformationFactory $result_transformation_factory
	 */
	public function __construct(
		wpdb $wpdb,
		ConditionFactory $condition_factory,
		WpmlService $wpml_service,
		AssociationQueryCache $query_cache,
		ElementSelectorProvider $element_selector_provider,
		UniqueTableAlias $unique_table_alias,
		TableJoinManager $join_manager,
		SqlExpressionBuilder $expression_builder,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository,
		OrderByFactory $orderby_factory,
		ResultTransformationFactory $result_transformation_factory
	) {
		$this->condition_factory = $condition_factory;
		$this->wpdb = $wpdb;
		$this->wpml_service = $wpml_service;
		$this->query_cache = $query_cache;
		$this->element_selector_provider = $element_selector_provider;
		$this->unique_table_alias = $unique_table_alias;
		$this->join_manager = $join_manager;
		$this->expression_builder = $expression_builder;
		$this->relationship_definition_repository = $relationship_definition_repository;
		$this->orderby_factory = $orderby_factory;
		$this->result_transformation_factory = $result_transformation_factory;

		// Some dependencies need to be connected to specific instances of other dependencies
		// used within this query instance.
		$this->join_manager->setup( $this->unique_table_alias );
		$this->expression_builder->setup( $this->join_manager );
		$this->condition_factory->setup(
			$this->element_selector_provider,
			$this->join_manager,
			$this->unique_table_alias
		);
		$this->result_transformation_factory->setup( $this );
	}


	/**
	 * @inheritDoc
	 */
	public function add( AssociationQueryCondition $condition ) {
		$this->conditions[] = $condition;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function do_not_add_default_conditions() {
		$this->should_add_default_conditions = false;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function get_results() {
		if ( $this->was_used ) {
			_doing_it_wrong(
				__FUNCTION__,
				'The association query object should not be reused. Create a new instance if you need to run another query.',
				TOOLSET_COMMON_VERSION
			);
		}

		$this->was_used = true;

		// Default value if no result transformation was selected.
		if ( null === $this->result_transformation ) {
			$this->return_association_instances();
		}

		// Sometimes it's not as straightforward as "get current language"
		$this->determine_translation_language();

		$root_condition = $this->build_root_condition();

		if ( null === $this->orderby ) {
			$this->dont_order();
		}

		$this->orderby->set_order( $this->order );

		if ( null === $this->limit ) {
			throw new RuntimeException(
				'The query limit has not been set. This is necessary to ensure the scalability.'
			);
		}

		$post_type_constraints = $this->get_post_type_constraints( $root_condition );

		$this->element_selector_provider->create_selector(
			$this->unique_table_alias,
			$this->join_manager,
			$this->get_unnecessary_wpml_table_joins( $post_type_constraints ),
			$this->can_skip_intermediary_post_join( $root_condition ),
			$this->roles_to_maybe_include_auto_drafts,
			$post_type_constraints
		);

		$query = $this->expression_builder->build(
			$root_condition,
			$this->offset,
			$this->limit,
			$this->orderby,
			$this->element_selector_provider->get_selector(),
			$this->need_found_rows,
			$this->result_transformation
		);

		$cache_key = '';
		if ( $this->use_cache ) {
			$cached_result_exists = false;
			$cache_key = $this->build_cache_key( $query );
			$cached_result = $this->query_cache->get( $cache_key, $cached_result_exists );

			if ( $cached_result_exists ) {

				if ( $this->need_found_rows ) {
					$this->found_rows = (int) count( $cached_result ); // @codeCoverageIgnore
				}

				return $cached_result;
			}
		}

		$rows = toolset_ensarr( $this->wpdb->get_results( $query, ARRAY_A ) );

		if ( $this->need_found_rows ) {
			$this->found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}

		$results = array();
		foreach ( $rows as $row ) {
			$result = $this->result_transformation->transform( $row, $this->element_selector_provider->get_selector() );

			if ( null !== $result ) {
				$results[] = $result;
			}
		}

		if ( $this->use_cache ) {
			$this->query_cache->push( $cache_key, $results );
		}

		return $results;
	}


	/**
	 * @return AndOperator MySQL WHERE clause for the query.
	 */
	private function build_root_condition() {
		$this->add_default_conditions();

		return $this->condition_factory->do_and( $this->conditions );
	}


	/**
	 * Basically, this sets default query parameters.
	 *
	 * The method needs to stay idempotent.
	 */
	private function add_default_conditions() {
		if ( ! $this->should_add_default_conditions ) {
			return;
		}

		if ( ! $this->has_element_status_condition ) {
			$this->add( $this->has_available_elements() );
		}

		if ( ! $this->has_active_relationship_condition ) {
			$this->add( $this->has_active_relationship() );
		}
	}


	/**
	 * @inheritDoc
	 */
	public function do_or( ...$conditions ) {
		return $this->condition_factory->do_or( $conditions );
	}


	/**
	 * @inheritDoc
	 */
	public function do_and( ...$conditions ) {
		return $this->condition_factory->do_and( $conditions );
	}


	/**
	 * @inheritDoc
	 */
	public function do_if(
		$statement, AssociationQueryCondition $if_branch, AssociationQueryCondition $else_branch = null
	) {
		if ( $statement ) {
			return $if_branch;
		}

		if ( null === $else_branch ) {
			return $this->condition_factory->tautology();
		}

		return $else_branch;
	}


	public function not( AssociationQueryCondition $condition ) {
		return $this->condition_factory->not( $condition );
	}


	/**
	 * @inheritDoc
	 */
	public function relationship_id( $relationship_id ) {
		return $this->condition_factory->relationship_id( $relationship_id );
	}


	/**
	 * @inheritDoc
	 */
	public function intermediary_id( $element_id ) {
		return $this->element_id( $element_id, new Toolset_Relationship_Role_Intermediary() );
	}


	/**
	 * @inheritDoc
	 */
	public function relationship( IToolset_Relationship_Definition $relationship_definition ) {
		return $this->condition_factory->relationship_id(
			$relationship_definition->get_row_id(), $relationship_definition
		);
	}


	/**
	 * @inheritDoc
	 */
	public function relationship_slug( $slug ) {
		$definition = $this->relationship_definition_repository->get_definition( $slug );
		if ( null === $definition ) {
			return $this->condition_factory->contradiction();
		}

		return $this->relationship( $definition );
	}


	/**
	 * @inheritDoc
	 */
	public function element_id( $element_id, RelationshipRole $for_role, $need_wpml_unaware_query = true ) {
		if ( ! $need_wpml_unaware_query ) {
			// This is to ensure a smooth transition from using element_id() everywhere to doing it only
			// in cases where it's explicitly needed. We can remove this after the final release.
			// @codeCoverageIgnoreStart
			trigger_error(
				'You are using the element_id() condition in the association query. '
				. 'However, this condition is WPML-unaware. Consider using element_id_and_domain() instead '
				. 'or, if you really need to ignore element translations, set the new $need_wpml_unaware_query to true.',
				E_NOTICE
			);
			// @codeCoverageIgnoreEnd
		}

		return $this->condition_factory->element_id( $element_id, $for_role );
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
		if ( $set_its_translation_language ) {
			$this->set_translation_language_by_element_id_and_domain( $element_id, $domain );
		}

		if ( null === $element_identification_to_query_by ) {
			$element_identification_to_query_by = ElementIdentification::parse(
				is_bool( $query_original_element ) ? ! $query_original_element : $query_original_element
			);
		}

		return $this->condition_factory->element_id_and_domain(
			$element_id,
			$domain,
			$for_role,
			$element_identification_to_query_by,
			$translate_provided_id
		);
	}


	/**
	 * @inheritDoc
	 */
	public function element_trid_or_id_and_domain(
		$trid,
		$element_id,
		$domain,
		RelationshipRole $for_role,
		$translate_provided_id = true,
		$set_its_translation_language = true,
		$element_identification_to_query_by = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
	) {
		if ( $set_its_translation_language ) {
			$this->set_translation_language_by_element_id_and_domain( $element_id, $domain );
		}

		return $this->condition_factory->element_trid_or_id_and_domain(
			$trid,
			$element_id,
			$domain,
			$for_role,
			$element_identification_to_query_by,
			$translate_provided_id
		);
	}


	/**
	 * @inheritDoc
	 */
	public function multiple_elements(
		$element_ids, $domain, RelationshipRole $for_role, $query_original_element = false, $translate_provided_ids = true
	) {
		return $this->condition_factory->multiple_elements(
			$element_ids,
			$domain,
			$for_role,
			$query_original_element,
			$translate_provided_ids
		);
	}


	/**
	 * @inheritDoc
	 */
	public function element(
		IToolset_Element $element,
		RelationshipRole $for_role = null,
		$query_original_element = false,
		$translate_provided_id = true,
		$set_its_translation_language = true
	) {
		if ( $set_its_translation_language ) {
			$this->set_translation_language_by_element_id_and_domain( $element->get_id(), $element->get_domain() );
		}

		if ( null === $for_role ) {
			$conditions = array();
			foreach ( Toolset_Relationship_Role::all() as $role ) {
				$conditions[] = $this->element(
					$element, $role, $query_original_element, $translate_provided_id, false
				);
			}

			return $this->do_or( $conditions );
		}

		return $this->element_id_and_domain(
			$element->get_id(),
			$element->get_domain(),
			$for_role,
			$query_original_element,
			$translate_provided_id
		);

	}


	/**
	 * @inheritDoc
	 */
	public function exclude_element(
		IToolset_Element $element, RelationshipRole $for_role, $query_original_element = false, $translate_provided_id = true
	) {
		return $this->condition_factory->exclude_element(
			$element->get_id(),
			$element->get_domain(),
			$for_role,
			ElementIdentification::parse(
				is_bool( $query_original_element ) ? ! $query_original_element : $query_original_element
			),
			$translate_provided_id
		);
	}


	/**
	 * @inheritDoc
	 */
	public function parent( IToolset_Element $element_source ) {
		return $this->element( $element_source, new Toolset_Relationship_Role_Parent() );
	}


	/**
	 * @inheritDoc
	 */
	public function parent_id( $parent_id, $domain = Toolset_Element_Domain::POSTS ) {
		return $this->element_id_and_domain( $parent_id, $domain, new Toolset_Relationship_Role_Parent() );
	}


	/**
	 * @inheritDoc
	 */
	public function child( IToolset_Element $element ) {
		return $this->element( $element, new Toolset_Relationship_Role_Child() );
	}


	/**
	 * @inheritDoc
	 */
	public function child_id( $child_id, $domain = Toolset_Element_Domain::POSTS ) {
		return $this->element_id_and_domain( $child_id, $domain, new Toolset_Relationship_Role_Child() );
	}


	/**
	 * @inheritDoc
	 */
	public function element_status( $statuses, RelationshipRole $for_role = null ) {
		$this->has_element_status_condition = true;

		if ( null === $for_role ) {
			$that = $this;

			return $this->do_and( array_map( static function ( RelationshipRole $for_role ) use ( $that, $statuses ) {
				return $that->element_status( $statuses, $for_role );
			}, Toolset_Relationship_Role::all() ) );
		}

		$condition = $this->condition_factory->element_status( $statuses, $for_role );

		if ( $condition->includes_auto_draft() ) {
			$this->roles_to_maybe_include_auto_drafts[] = $for_role;
		}

		return $condition;
	}


	/**
	 * @inheritDoc
	 */
	public function has_available_elements() {
		$conditions = array();

		foreach ( Toolset_Relationship_Role::parent_child() as $role ) {
			$conditions[] = $this->element_status(
				ElementStatusCondition::STATUS_AVAILABLE,
				$role
			);
		}

		return $this->do_and( $conditions );

	}


	/**
	 * @inheritDoc
	 */
	public function has_active_relationship( $is_active = true ) {
		$this->has_active_relationship_condition = true;

		return $this->condition_factory->has_active_relationship( $is_active );
	}


	/**
	 * @inheritDoc
	 */
	public function has_legacy_relationship( $needs_legacy_support = true ) {
		return $this->condition_factory->has_legacy_relationship( $needs_legacy_support );
	}


	/**
	 * @inheritDoc
	 */
	public function has_domain( $domain, RelationshipRoleParentChild $for_role ) {
		return $this->condition_factory->has_domain( $domain, $for_role );
	}


	/**
	 * @inheritDoc
	 */
	public function has_type( $type, RelationshipRoleParentChild $for_role ) {
		return $this->condition_factory->has_type( $type, $for_role );
	}


	/**
	 * @inheritDoc
	 */
	public function has_domain_and_type( $domain, $type, RelationshipRoleParentChild $for_role ) {
		return $this->condition_factory->has_domain_and_type( $domain, $type, $for_role );
	}


	/**
	 * @inheritDoc
	 */
	public function has_origin( $origin ) {
		return $this->condition_factory->relationship_origin( $origin );
	}


	/**
	 * @inheritDoc
	 */
	public function has_intermediary_id() {
		return $this->condition_factory->has_intermediary_id();
	}


	/**
	 * @inheritDoc
	 */
	public function wp_query( RelationshipRole $for_role, $query_args, $confirmation = null ) {
		throw new RuntimeException( 'Not implemented.' );
	}


	/**
	 * @inheritDoc
	 */
	public function search( $search_string, RelationshipRole $for_role, $is_exact = false ) {
		return $this->condition_factory->search( $search_string, $is_exact, $for_role );
	}


	/**
	 * @inheritDoc
	 */
	public function association_id( $association_id ) {
		$this->limit( 1 );

		return $this->condition_factory->association_id( $association_id );
	}


	public function meta( $meta_key, $meta_value, $domain, RelationshipRole $for_role = null, $comparison = Toolset_Query_Comparison_Operator::EQUALS ) {
		if ( Toolset_Element_Domain::POSTS !== $domain ) {
			throw new RuntimeException( 'The meta query condition is supported only for the posts domain at the moment.' );
		}

		if ( null === $for_role ) {
			$queries_per_role = array();
			foreach ( Toolset_Relationship_Role::all() as $role ) {
				$queries_per_role[] = $this->meta( $meta_key, $meta_value, $domain, $role, $comparison );
			}

			return $this->condition_factory->do_and( $queries_per_role );
		}

		return $this->condition_factory->post_meta( $meta_key, $meta_value, $comparison, $for_role );
	}


	/**
	 * @inheritDoc
	 */
	public function has_autodeletable_intermediary_post( $expected_value = true ) {
		return $this->condition_factory->has_autodeletable_intermediary( $expected_value );
	}


	/**
	 * @inheritDoc
	 */
	public function return_association_instances() {
		$this->result_transformation = $this->result_transformation_factory->association_instance();

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function return_association_uids() {
		$this->result_transformation = $this->result_transformation_factory->association_uid();

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function return_element_ids( RelationshipRole $role ) {
		$this->result_transformation = $this->result_transformation_factory->element_id( $role );

		return $this;

	}


	/**
	 * @inheritDoc
	 */
	public function return_element_instances( RelationshipRole $role ) {
		$this->result_transformation = $this->result_transformation_factory->element_instance( $role );

		return $this;

	}


	/**
	 * @inheritDoc
	 */
	public function return_per_role() {
		$this->result_transformation = $this->result_transformation_factory->element_per_role();

		return $this->result_transformation;
	}


	/**
	 * @inheritDoc
	 */
	public function offset( $value ) {
		if ( ! Toolset_Utils::is_nonnegative_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Invalid offset value.' );
		}
		$this->offset = (int) $value;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function limit( $value ) {
		if ( ! Toolset_Utils::is_natural_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Invalid limit value.' );
		}
		$this->limit = (int) $value;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function order( $value ) {
		if ( ! in_array( $value, [ Constants::ORDER_ASC, Constants::ORDER_DESC ], true ) ) {
			throw new InvalidArgumentException( 'Invalid order value.' );
		}
		$this->order = $value;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function need_found_rows( $is_needed = true ) {
		$this->need_found_rows = (bool) $is_needed;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function get_found_rows() {
		if ( null === $this->found_rows ) {
			throw new RuntimeException(
				'Cannot return the number of found rows because the query was not instructed to obtain them.'
			);
		}

		return $this->found_rows;
	}


	/**
	 * @inheritDoc
	 */
	public function dont_order() {
		$this->orderby = $this->orderby_factory->nothing();

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_title( RelationshipRole $for_role ) {
		$this->orderby = $this->orderby_factory->title( $for_role, $this->join_manager );

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_field_value( Toolset_Field_Definition $field_definition, RelationshipRole $for_role ) {
		/** @noinspection DegradedSwitchInspection */
		switch ( $field_definition->get_domain() ) {
			case Toolset_Element_Domain::POSTS:
				$cast_to_numeric = $field_definition->get_type() instanceof Toolset_Field_Type_Definition_Numeric
					? 'SIGNED'
					: null;
				$this->orderby = $this->orderby_factory->postmeta(
					$field_definition->get_meta_key(),
					$for_role,
					$this->join_manager,
					$cast_to_numeric
				);
				break;
			default:
				throw new RuntimeException( 'Element domain not supported.' );
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function order_by_meta( $meta_key, $domain, RelationshipRole $for_role, $is_numeric = false ) {
		if ( Toolset_Element_Domain::POSTS !== $domain ) {
			throw new RuntimeException( 'Element domain not supported.' );
		}

		$cast_to = ( $is_numeric ? 'SIGNED' : null );

		$this->orderby = $this->orderby_factory->postmeta( $meta_key, $for_role, $this->join_manager, $cast_to );

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function dont_translate_results() {
		$this->element_selector_provider->attempt_translating_elements( false );

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function set_translation_language( $lang_code ) {
		if ( ! is_string( $lang_code ) ) {
			throw new InvalidArgumentException( 'Invalid language code.' );
		}

		$this->translation_language = $lang_code;

		return $this;

	}


	/**
	 * @inheritDoc
	 * @depecated
	 */
	public function force_language_per_role( RelationshipRole $role, $lang_code ) {
		throw new NotImplementedException();
	}


	/**
	 * @inheritDoc
	 */
	public function set_translation_language_by_element_id_and_domain( $element_id, $domain ) {
		if ( Toolset_Element_Domain::POSTS !== $domain ) {
			// no language information there
			return $this;
		}

		$post_language = $this->wpml_service->get_post_language( $element_id );
		if ( ! empty( $post_language ) ) {
			$this->set_translation_language( $post_language );
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function get_found_rows_directly() {
		$this->need_found_rows()
			->limit( 1 )
			->return_association_uids()
			->get_results();

		return $this->get_found_rows();
	}


	public function use_cache( $use_cache = true ) {
		$this->use_cache = (bool) $use_cache;

		return $this;
	}


	public function build_cache_key( $query_string ) {
		$normalized_query_string = Toolset_Utils::trim_deep( $query_string );
		$transformation_class = get_class( $this->result_transformation );
		$key_source = "$normalized_query_string|$transformation_class";

		return md5( $key_source );
	}


	/**
	 * Determine an alternative to the translation language (what language version of the results should be chosen).
	 *
	 * This will be used only if applicable - if WPML is active and the current language is set to "All languages",
	 * in which case we're forced to pick one.
	 *
	 * If we have a valid lang code, we'll pass it to the element selector. Otherwise, it will use the default language.
	 */
	private function determine_translation_language() {
		if ( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return;
		}

		if ( ! $this->wpml_service->is_showing_all_languages() ) {
			return;
		}

		if ( null === $this->translation_language ) {
			// Here, we may try to determine the language by some other means.
			return;
		}

		$this->element_selector_provider->set_translation_language( $this->translation_language );
	}


	/**
	 * Extract a query condition matching certain parameters from the top level of conditions for this query,
	 * while ensuring it is a single condition matching them.
	 *
	 * @param AndOperator $root_condition
	 * @param callable $condition Callable that will accept an AssociationQueryCondition as a first parameter
	 *     and return true if it should be selected.
	 *
	 * @return AssociationQueryCondition|null The condition object if it's the single one matching the $condition in
	 *     the top level of the root condition. Null otherwise.
	 */
	private function get_singular_top_level_condition( AndOperator $root_condition, callable $condition ) {
		$first_matched_condition = null;
		foreach ( $root_condition->get_inner_conditions() as $top_level_condition ) {
			$matched_condition = null;
			if ( $condition( $top_level_condition ) ) {
				$matched_condition = $top_level_condition;
			} elseif ( $top_level_condition instanceof AndOperator ) {
				// Nested AND condition is equal to flat AND.
				$matched_condition = $this->get_singular_top_level_condition( $top_level_condition, $condition );
			}

			// Accept exactly one match.
			if ( $matched_condition ) {
				if ( null === $first_matched_condition ) {
					$first_matched_condition = $matched_condition;
				} else {
					// More than one condition of the requested type.
					return null;
				}
			}
		}

		return $first_matched_condition;
	}


	/**
	 * Determine for which roles we don't need to join WPML tables for the current query.
	 *
	 * @param string[] $post_type_constraints
	 *
	 * @return RelationshipRole[]
	 */
	private function get_unnecessary_wpml_table_joins( $post_type_constraints ) {
		if ( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return Toolset_Relationship_Role::all();
		}

		// For each role that might be requested by the result transformation object,
		// check if we have a clearly defined post type, and if this post type is translatable or not.
		$results = [];

		foreach ( $this->result_transformation->get_maximum_requested_roles() as $requested_role ) {
			if ( ! array_key_exists( $requested_role->get_name(), $post_type_constraints ) ) {
				continue;
			}

			if ( $this->wpml_service->is_post_type_translatable( $post_type_constraints[ $requested_role->get_name() ] ) ) {
				continue;
			}

			$results[] = $requested_role;
		}

		return $results;
	}


	/**
	 * @param AndOperator $root_condition
	 *
	 * @return string[]
	 */
	private function get_post_type_constraints( AndOperator $root_condition ) {
		// First, try to determine the constraints for post types in each role.
		$post_type_constraints = [];

		// If there is a condition for a specific relationship, we've won, since it clearly defines the involved
		// element types.
		/** @var RelationshipId|null $relationship_condition */
		$relationship_condition = $this->get_singular_top_level_condition(
			$root_condition,
			static function ( AssociationQueryCondition $condition ) {
				return (
					$condition instanceof RelationshipId
					&& null !== $condition->get_relationship_definition()
				);
			}
		);

		if ( null !== $relationship_condition ) {
			/** @var IToolset_Relationship_Definition $relationship_definition */
			$relationship_definition = $relationship_condition->get_relationship_definition();
			foreach ( Toolset_Relationship_Role::all() as $role ) {
				$element_type = $relationship_definition->get_element_type( $role );
				if ( Toolset_Element_Domain::POSTS !== $element_type->get_domain() ) {
					continue;
				}
				$post_type_constraints[ $role->get_name() ] = $element_type->get_types()[0];
			}

			return $post_type_constraints;
		}

		// We can also have conditions for element type and domain which we might be able to harvest.
		foreach ( Toolset_Relationship_Role::all() as $role ) {
			/** @var HasDomainAndType|null $type_domain_condition_for_role */
			$type_domain_condition_for_role = $this->get_singular_top_level_condition(
				$root_condition,
				static function ( AssociationQueryCondition $condition ) use ( $role ) {
					return (
						$condition instanceof HasDomainAndType
						&& $condition->get_for_role()->get_name() === $role->get_name()
						&& Toolset_Element_Domain::POSTS === $condition->get_domain()
					);
				}
			);

			if ( null !== $type_domain_condition_for_role ) {
				$post_type_constraints[ $role->get_name() ] = $type_domain_condition_for_role->get_type();
				continue;
			}

			/** @var ElementIdAndDomain|null $element_id_condition_for_role */
			$element_id_condition_for_role = $this->get_singular_top_level_condition(
				$root_condition,
				static function ( AssociationQueryCondition $condition ) use ( $role ) {
					return (
						$condition instanceof ElementIdAndDomain
						&& $condition->get_domain() === Toolset_Element_Domain::POSTS
						&& $condition->get_role()->equals( $role )
					);
				}
			);

			if ( null !== $element_id_condition_for_role ) {
				$post_type_constraints[ $role->get_name() ] = get_post_type( $element_id_condition_for_role->get_element_id() );
			}
		}

		return $post_type_constraints;
	}


	/**
	 * Decide whether intermediary ID column can be skipped entirely from the MySQL query.
	 *
	 * This will be an optimization in case the intermediary ID is required by the result transformation object but
	 * we know for sure that there will be any non-zero values in the results: That happens for sure when there's
	 * a top-level condition for a specific relationship which doesn't have the intermediary post type.
	 *
	 * Obviously, this is just a signal for the result transformation object that it may skip requesting the
	 * intermediary ID column and use zeros instead. It may not be respected and it may be overridden by a query
	 * condition, for example.
	 *
	 * @param AndOperator $root_condition
	 *
	 * @return bool
	 */
	private function can_skip_intermediary_post_join( AndOperator $root_condition ) {
		/** @var RelationshipId|null $single_relationship_id_condition */
		$single_relationship_id_condition = $this->get_singular_top_level_condition(
			$root_condition,
			static function ( AssociationQueryCondition $condition ) {
				return $condition instanceof RelationshipId;
			}
		);

		if ( null === $single_relationship_id_condition ) {
			// There's not a single top-level condition for the relationship ID.
			return false;
		}

		$relationship_definition = $single_relationship_id_condition->get_relationship_definition();
		if ( null === $relationship_definition ) {
			// The condition just has the relationship ID but not its definition, so we can't tell.
			return false;
		}

		return ( null === $relationship_definition->get_intermediary_post_type() );
	}


	/**
	 * @inheritDoc
	 */
	public function include_original_language( $include = true ) {
		$this->element_selector_provider->include_original_language( $include );

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function force_display_as_translated_mode( $do_force = true ) {
		$this->element_selector_provider->force_display_as_translated_mode( $do_force );

		return $this;
	}

}
