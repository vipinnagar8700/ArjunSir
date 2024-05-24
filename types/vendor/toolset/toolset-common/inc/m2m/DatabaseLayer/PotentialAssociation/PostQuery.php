<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation;

use InvalidArgumentException;
use IToolset_Element;
use IToolset_Post;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Result\SingleResult;
use RuntimeException;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Relationship_Cardinality;
use Toolset_Relationship_Role;
use WP_Post;

/**
 * When you have a relationship and a specific element in one role, this
 * query class will help you to find elements that can be associated with it.
 *
 * It takes into account all the aspects, like whether the relationship is distinct or not.
 *
 * This class works for querying posts (disregarding the domain of the element to connect to).
 *
 * Note that relationship cardinality limitation is not checked in get_results(). It is assumed that
 * they've been checked before even querying for posts to associate.
 *
 * @since m2m
 */
class PostQuery implements \OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery {


	const POST_STATUS_AVAILABLE = 'is_available';


	/** @var IToolset_Relationship_Definition */
	protected $relationship;

	/** @var RelationshipRole */
	protected $target_role;

	/** @var IToolset_Element */
	protected $for_element;

	/** @var array */
	protected $args;

	/** @var int|null */
	protected $found_results;

	/** @var Toolset_Element_Factory */
	protected $element_factory;

	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	protected $database_layer_factory;

	/**
	 * To be used instead of __return_true() as a filter callback when we're modifying
	 * the WP_Query behaviour.
	 *
	 * @var callable
	 * @return bool
	 * @since 4.0
	 */
	protected $return_true;


	/**
	 * Toolset_Potential_Association_Query constructor.
	 *
	 * @param IToolset_Relationship_Definition $relationship Relationship to query for.
	 * @param RelationshipRoleParentChild $target_role Element role. Only parent
	 *     or child are accepted.
	 * @param IToolset_Element $for_element Element that may be connected with the result of the query.
	 * @param array $args Additional query arguments:
	 *     - search_string: string
	 *     - count_results: bool
	 *     - items_per_page: int
	 *     - page: int
	 *     - wp_query_override: array
	 *     - exclude_elements: IToolset_Element[] Elements to exclude from the results and when checking
	 *       whether the target element ($for_element) can accept another association.
	 *     - post_status: string[]|string If provided, it will override the standard value ('publish').
	 *     POST_STATUS_AVAILABLE is also being accepted.
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory|null $database_layer_factory
	 */
	public function __construct(
		IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element,
		$args,
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory,
		Toolset_Element_Factory $element_factory_di = null
	) {
		$this->relationship = $relationship;
		$this->for_element = $for_element;
		$this->target_role = $target_role;
		$this->args = $args;

		if ( ! $relationship->get_element_type( $target_role->other()->get_name() )->is_match( $for_element ) ) {
			throw new InvalidArgumentException( 'The element to connect with doesn\'t belong to the relationship definition provided.' );
		}

		$this->element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory() : $element_factory_di );
		$this->database_layer_factory = $database_layer_factory;
		$this->return_true = static function() { return true; };
	}


	/**
	 * @param bool $check_can_connect_another_element Check wheter it is possible to connect any other element at all,
	 *     and return an empty result if not.
	 * @param bool $check_distinct_relationships Exclude elements that would break the "distinct" property of a
	 *     relationship. You can set this to false if you're overwriting an existing association.
	 *
	 * @return IToolset_Post[]
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_results( $check_can_connect_another_element = true, $check_distinct_relationships = true ) {

		// If the element we want to connect the results to is not accepting any
		// associations (as it may have reached its cardinality limit), there's no point
		// in searching any further.
		if ( $check_can_connect_another_element && ! $this->can_connect_another_element()->is_success() ) {
			return array();
		}

		$query_args = array(

			// Performance optimizations
			//
			//
			'ignore_sticky_posts' => true,
			'cache_results' => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows' => ! $this->needs_found_rows(),

			// Relevant query args
			//
			//
			'post_type' => $this->get_target_post_types(),
			'post_status' => $this->get_post_statuses(),
			// just to make sure in case we mess with post_status in the future
			'perm' => 'readable',
			// the common use case is to get post titles and IDs
			'fields' => 'all',

			'posts_per_page' => $this->get_items_per_page(),
			'paged' => $this->get_page(),
		);

		$search_string = $this->get_search_string();
		if ( ! empty( $search_string ) ) {
			$query_args['s'] = $search_string;
			$query_args['orderby'] = 'title';
			$query_args['order'] = 'ASC';
		}

		$elements_to_exclude = $this->get_exclude_elements();
		if ( ! empty( $elements_to_exclude ) ) {
			$query_args['post__not_in'] = array_map( static function ( IToolset_Post $post ) {
				return $post->get_id();
			}, $elements_to_exclude );
		}

		$query_args = array_merge( $query_args, $this->get_additional_wp_query_args() );

		// This is to prevent JOIN clause duplication between the classes that adjust the WP_Query clauses.
		$join_manager = $this->database_layer_factory->potential_association_table_join_manager(
			$this->relationship, $this->target_role, $this->for_element
		);
		$join_manager->hook();

		// For distinct relationships, we need to make sure that the returned posts
		// aren't already associated with $for_element.
		if ( $check_distinct_relationships ) {
			$augment_query_for_distinct_relationships = $this->database_layer_factory->distinct_relationship_posts(
				$this->relationship,
				$this->target_role,
				$this->for_element,
				$join_manager
			);

			$augment_query_for_distinct_relationships->before_query();
		}

		// Unless we're told not to check for cardinality limits of the target posts, we need to make yet another
		// adjustment. It cannot be implemented directly in OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Distinct_Post_Query because
		// we can (theoretically) have non-distinct relationships where this still needs to be checked.
		if ( $check_can_connect_another_element ) {
			$augment_query_for_cardinality_limits = $this->database_layer_factory->cardinality_query_posts(
				$this->relationship,
				$this->target_role,
				$this->for_element,
				$join_manager
			);

			$augment_query_for_cardinality_limits->before_query();
		}

		// Make sure the order of the results is correct. See the PostResultOrder class for details.
		$augment_query_orderby = $this->database_layer_factory->post_result_order_adjustments(
			$this->relationship,
			$this->target_role,
			$this->for_element,
			$join_manager
		);
		$augment_query_orderby->before_query();

		$this->alter_wpml_query_hooks_before_query();
		$query = $this->database_layer_factory->wp_query( $query_args );
		$results = $query->posts;
		$this->alter_wpml_query_hooks_after_query();

		$augment_query_orderby->after_query();

		if ( $check_distinct_relationships ) {
			$augment_query_for_distinct_relationships->after_query();
		}

		if ( $check_can_connect_another_element ) {
			$augment_query_for_cardinality_limits->after_query();
		}

		$join_manager->unhook();

		$this->found_results = (int) $query->found_posts;

		return $this->transform_results( $results );
	}


	private function get_additional_wp_query_args() {
		return toolset_ensarr( toolset_getarr( $this->args, 'wp_query_override' ) );
	}


	protected function get_target_post_types() {
		return $this->relationship->get_element_type( $this->target_role )->get_types();
	}


	protected function needs_found_rows() {
		return (bool) toolset_getarr( $this->args, 'count_results', false );
	}


	private function get_search_string() {
		return toolset_getarr( $this->args, 'search_string' );
	}


	protected function get_page() {
		return (int) toolset_getarr( $this->args, 'page' );
	}


	protected function get_post_statuses() {
		$post_status = toolset_getarr( $this->args, 'post_status' );
		if ( null === $post_status || empty( $post_status ) ) {
			return array( 'publish' );
		}

		if ( is_string( $post_status ) ) {
			if ( self::POST_STATUS_AVAILABLE === $post_status ) {
				return array( 'publish', 'draft', 'pending', 'future' );
			}

			return array( $post_status );
		}

		return $post_status;
	}


	protected function get_items_per_page() {
		$limit = (int) toolset_getarr( $this->args, 'items_per_page' );
		if ( $limit < 1 ) {
			$limit = 10;
		}

		return $limit;
	}


	private function get_exclude_elements() {
		return array_map( static function ( $element ) {
			if ( ! $element instanceof IToolset_Post ) {
				throw new InvalidArgumentException(
					'Invalid element provided in the exclude_elements query argument. Only posts are accepted.'
				);
			}

			return $element;
		}, toolset_ensarr( toolset_getarr( $this->args, 'exclude_elements' ) ) );
	}


	/**
	 * @param WP_Post[] $wp_posts
	 *
	 * @return IToolset_Post[]
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function transform_results( $wp_posts ) {
		$results = array();
		foreach ( $wp_posts as $wp_post ) {
			$results[] = $this->element_factory->get_post( $wp_post );
		}

		return $results;
	}


	/**
	 * @return int
	 */
	public function get_found_elements() {
		if ( ! $this->needs_found_rows() ) {
			throw new RuntimeException( 'The number of found elements is not available.' );
		}

		return $this->found_results;
	}


	/**
	 * Check whether a specific single element can be associated.
	 *
	 * The relationship, target role and the other element are those provided in the constructor.
	 *
	 * @param IToolset_Element $association_candidate Element that wants to be associated.
	 * @param bool $check_is_already_associated Perform the check that the element is already associated for distinct
	 *     relationships. Default is true. Set to false only if the check was performed manually before.
	 *
	 * @return SingleResult Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function check_single_element( IToolset_Element $association_candidate, $check_is_already_associated = true ) {

		if ( ! $this->relationship->get_element_type( $this->target_role )->is_match( $association_candidate ) ) {
			return new SingleResult( false, __( 'The element has a wrong type or a domain for this relationship.', 'wpv-views' ) );
		}

		if ( $check_is_already_associated
			&& $this->relationship->is_distinct()
			&& $this->is_element_already_associated( $association_candidate )
		) {
			return new SingleResult( false,
				__( 'These two elements are already associated and the relationship doesn\'t allow non-distinct associations.', 'wpv-views' )
			);
		}

		$cardinality_check_result = $this->check_cardinality_for_role( $this->for_element, $this->target_role->other() );
		if ( $cardinality_check_result->is_error() ) {
			return $cardinality_check_result;
		}

		$cardinality_check_result = $this->check_cardinality_for_role( $association_candidate, $this->target_role );
		if ( $cardinality_check_result->is_error() ) {
			return $cardinality_check_result;
		}

		// We also need to check $this->relationship->has_scope() when/if the scope support is implemented.

		/** @var IToolset_Element[] $parent_and_child */
		$parent_and_child = Toolset_Relationship_Role::sort_elements( $association_candidate, $this->for_element, $this->target_role );

		/**
		 * toolset_can_create_association
		 *
		 * Allows for forbidding an association between two elements to be created.
		 * Note that it cannot be used to force-allow an association. The filter will be applied only if all
		 * conditions defined by the relationship are met.
		 *
		 * @param bool $result
		 * @param int $parent_id
		 * @param int $child_id
		 * @param string $relationship_slug
		 *
		 * @since m2m
		 */
		$filtered_result = apply_filters(
			'toolset_can_create_association',
			true,
			$parent_and_child[0]->get_id(),
			$parent_and_child[1]->get_id(),
			$this->relationship->get_slug()
		);

		if ( true !== $filtered_result ) {
			if ( is_string( $filtered_result ) ) {
				$message = esc_html( $filtered_result );
			} else {
				$message = __( 'The association was disabled by a third-party filter.', 'wpv-views' );
			}

			return new SingleResult( false, $message );
		}

		return new SingleResult( true );
	}


	/**
	 * @inheritdoc
	 *
	 * @param IToolset_Element $element
	 *
	 * @return bool
	 */
	public function is_element_already_associated( IToolset_Element $element ) {

		/** @var IToolset_Element[] $parent_and_child */
		$parent_and_child = Toolset_Relationship_Role::sort_elements( $element, $this->for_element, $this->target_role );

		$query = $this->database_layer_factory->association_query();

		$query->add( $query->relationship( $this->relationship ) )
			->add( $query->parent( $parent_and_child[0] ) )
			->add( $query->child( $parent_and_child[1] ) )
			->do_not_add_default_conditions() // include all existing associations
			->limit( 1 ) // because we're not interested in the actual resuls
			->need_found_rows()
			->return_association_uids() // ditto
			->get_results();

		$result_count = $query->get_found_rows();

		return ( $result_count > 0 );
	}


	/**
	 * @param IToolset_Element $element Element to check.
	 * @param RelationshipRoleParentChild $role Provided element's role in the relationship.
	 *
	 * @return SingleResult
	 */
	private function check_cardinality_for_role( IToolset_Element $element, RelationshipRoleParentChild $role ) {
		$maximum_limit = $this->relationship->get_cardinality()->get_limit( $role->other()
			->get_name(), Toolset_Relationship_Cardinality::MAX );

		if ( $maximum_limit !== Toolset_Relationship_Cardinality::INFINITY ) {
			$association_count = $this->get_number_of_already_associated_elements( $role, $element );
			if ( $association_count >= $maximum_limit ) {
				$message = sprintf(
					__( 'The element %s has already the maximum allowed amount of associations (%d) as %s in the relationship %s.', 'wpv-views' ),
					$element->get_title(),
					$maximum_limit, // this will be always a meaningful number - for INFINITY, this block is skipped entirely.
					$this->relationship->get_role_name( $role ),
					$this->relationship->get_display_name()
				);

				return new SingleResult( false, esc_html( $message ) );
			}
		}

		return new SingleResult( true );
	}


	private function get_number_of_already_associated_elements(
		RelationshipRoleParentChild $role, IToolset_Element $element
	) {
		$query = $this->database_layer_factory->association_query();

		return $query
			->add( $query->relationship_slug( $this->relationship->get_slug() ) )
			->add( $query->element( $element, $role ) )
			->add( $query->do_and(
				array_map( static function ( IToolset_Post $post ) use ( $query, $role ) {
					return $query->not( $query->element( $post, $role->other() ) );
				}, $this->get_exclude_elements() )
			) )
			->do_not_add_default_conditions() // include all existing associations
			->get_found_rows_directly();
	}


	/**
	 * Check whether the element provided in the constructor can accept any new association whatsoever.
	 *
	 * @return SingleResult Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function can_connect_another_element() {
		$cardinality_check_result = $this->check_cardinality_for_role( $this->for_element, $this->target_role->other() );
		if ( $cardinality_check_result->is_error() ) {
			return $cardinality_check_result;
		}

		return new SingleResult( true );
	}


	/**
	 * Alter WPML behavior directly before running the query.
	 *
	 * To be overridden when needed.
	 *
	 * @since 4.0
	 */
	protected function alter_wpml_query_hooks_before_query() {
		// WPML in the back-end filters strictly by the current language by default,
		// but we need it to include default language posts, too, if the translation to the current language
		// doesn't exist. This needs to behave consistently in all contexts.
		add_filter( 'wpml_should_use_display_as_translated_snippet', $this->return_true );
	}


	/**
	 * Revert any changes made in alter_wpml_query_hooks_before_query().
	 *
	 * @since 4.0
	 */
	protected function alter_wpml_query_hooks_after_query() {
		remove_filter( 'wpml_should_use_display_as_translated_snippet', $this->return_true );
	}

}
