<?php

use OTGS\Toolset\Common\Field\Group\FilterDisplayResult;
use OTGS\Toolset\Common\Field\Group\GroupDisplayResult;

/**
 * Factory for the Toolset_Field_Group_Post class.
 *
 * @since 2.0
 */
class Toolset_Field_Group_Post_Factory extends Toolset_Field_Group_Factory {


	const POST_TYPE_ASSIGNMENTS_CACHE_KEY = 'post_type_assignments';

	/**
	 * @return Toolset_Field_Group_Post_Factory
	 * @noinspection SenselessProxyMethodInspection
	 */
	public static function get_instance() {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::get_instance();
	}

	protected function __construct() {
		parent::__construct();

		add_action( 'wpcf_group_updated', array( $this, 'on_group_updated' ), 10, 2 );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @param bool $force_query_by_name
	 *
	 * @return null|Toolset_Field_Group_Post Field group or null if it can't be loaded.
	 */
	public static function load( $field_group, $force_query_by_name = false ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Post_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();

		/** @noinspection PhpIncompatibleReturnTypeInspection Because this will always be a post field group. */
		return static::get_instance()->load_field_group( $field_group, $force_query_by_name );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 * @param String $status Post status
	 * @param String $purpose Purpose.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '', $status = 'draft', $purpose = Toolset_Field_Group_Post::PURPOSE_GENERIC ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return static::get_instance()->create_field_group( $name, $title, $status, $purpose );
	}


	public function get_post_type() {
		return Toolset_Field_Group_Post::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Toolset_Field_Group_Post';
	}


	/**
	 * Get all field groups sorted by their association with post types.
	 *
	 * @return Toolset_Field_Group_Post[][] For each (registered) post type, there will be an array element, which is
	 *     an array of post field groups associated to it.
	 * @since m2m
	 */
	public function get_groups_by_post_types() {
		$post_type_assignment_cache = $this->cache->get( static::class, static::POST_TYPE_ASSIGNMENTS_CACHE_KEY );
		if( null === $post_type_assignment_cache ) {
			// We need also special-purpose groups; Everything will be filtered by $group->is_assigned_by_post_type.
			$groups = $this->query_groups( array( 'purpose' => '*' ) );

			$post_type_query = new Toolset_Post_Type_Query(
				array(
					Toolset_Post_Type_Query::HAS_SPECIAL_PURPOSE => null,
					Toolset_Post_Type_Query::RETURN_TYPE => 'slug'
				)
			);

			/** @var string[] $post_types */
			$post_types = $post_type_query->get_results();

			$post_type_assignment_cache = [];
			foreach( $post_types as $post_type_slug ) {
				$groups_for_post_type = array();

				foreach( $groups as $group ) {
					if( $group instanceof Toolset_Field_Group_Post
						&& $group->is_active()
						&& $group->is_assigned_to_type( $post_type_slug )
					) {
						$groups_for_post_type[] = $group;
					}
				}

				$post_type_assignment_cache[ $post_type_slug ] = $groups_for_post_type;
			}

		}

		$this->cache->set( $post_type_assignment_cache, static::class, static::POST_TYPE_ASSIGNMENTS_CACHE_KEY );
		return $post_type_assignment_cache;
	}


	/**
	 * @param $post_type_slug
	 *
	 * @return Toolset_Field_Group_Post[]
	 */
	public function get_groups_for_new_post( $post_type_slug ) {
		$groups_for_post_type = $this->get_groups_by_post_type( $post_type_slug );

		return array_filter( $groups_for_post_type, static function( Toolset_Field_Group_Post $group ) use( $post_type_slug ) {
			$template_filters = $group->get_assigned_to_templates();
			if( empty( $template_filters ) ) {
				// The group is assigned based only on the post type. The template filter cannot disqualify it.
				return true;
			}

			foreach( $template_filters as $template_filter ) {
				if( $template_filter->is_default_for_post_type( $post_type_slug ) ) {
					return true;
				}
			}

			return false;
		} );
	}


	/**
	 * Get array of groups that are associated with given post type.
	 *
	 * @param string $post_type_slug Slug of the post type.
	 *
	 * @return Toolset_Field_Group_Post[] Associated post field groups.
	 */
	public function get_groups_by_post_type( $post_type_slug ) {
		$groups_by_post_types = $this->get_groups_by_post_types();
		return toolset_ensarr( toolset_getarr( $groups_by_post_types, $post_type_slug ) );
	}


	/**
	 * This needs to be executed whenever a post field group is updated.
	 *
	 * Hooked into the wpcf_group_updated action.
	 * Erases cache for the get_groups_by_post_types() method.
	 *
	 * @param int $group_id Ignored
	 * @param Toolset_Field_Group $group Field group that has been just updated.
	 */
	public function on_group_updated( /** @noinspection PhpUnusedParameterInspection */ $group_id = null, $group = null ) {
		$this->cache->clear( static::class, static::POST_TYPE_ASSIGNMENTS_CACHE_KEY );
	}

	/**
	 * @inheritdoc
	 * @return string
	 * @since 3.4
	 */
	public function get_domain() {
		return Toolset_Element_Domain::POSTS;
	}


	/*
	 * @refactoring
	 * The code below is related to determining field groups that should be displayed for a particular post.
	 * It contains some pretty complex logic and deserves to be extracted into a separate class(es) and covered
	 * by thorough unit tests.
	 */

	/**
	 * Apply given filters on field groups based on their filter operator.
	 *
	 * The filter operator can be either 'all' or 'any'.
	 *
	 * @param Toolset_Field_Group_Post[] $all_groups All groups to filter.
	 * @param callable[] $filters Regular filters that are applied according to the filter operator. For 'all', a field
	 *     group will be disqualified if it doesn't pass any single filter. For 'any', it will be selected if it passes
	 *     a single filter.
	 * @param callable[] $force_filters Filters that are applied on all groups (after applying the regular filters),
	 *     disregarding their filter operator.
	 *
	 * @return GroupDisplayResult[] Results with all groups which have the is_selected() flag set.
	 */
	private function filter_groups( $all_groups, $filters, $force_filters ) {

		// First, sort groups according to their filter operator.
		/** @var GroupDisplayResult[] $groups_requiring_all_filters */
		$groups_requiring_all_filters = array();
		/** @var GroupDisplayResult[] $groups_requiring_any_filter */
		$groups_requiring_any_filter = array();
		foreach( $all_groups as $group ) {
			if( 'all' === $group->get_filter_operator() ) {
				$groups_requiring_all_filters[ $group->get_slug() ] = new GroupDisplayResult( $group );
			} else {
				$groups_requiring_any_filter[ $group->get_slug() ] = new GroupDisplayResult( $group );
			}
		}

		// Disqualify groups requiring all filters which don't match all.
		//
		// That means, for a group to be selected, every filter must either return MATCH or INDIFFERENT.
		// Any occurence of FAIL will disqualify the group.
		foreach( $filters as $filter ) {
			foreach( $groups_requiring_all_filters as $group_display ) {
				/** @var FilterDisplayResult $filter_result */
				$filter_result = $filter( $group_display->get_group(), true );
				$group_display->add_filter_result( $filter_result );

				// We always need to run the filter (to get additional information, like if a page refresh is needed
				// to re-evaluate the group visibility), but we only apply the value if there have been no previous fails.
				$group_display->is_selected(
					$group_display->is_selected() !== false // null (= not set previously) or true are fine
					&& ( $filter_result->get_value() !== FilterDisplayResult::FAIL )
				);
			}
		}

		$results = $groups_requiring_all_filters;

		// Select groups that match any filter.
		//
		// That means, either at least one filter returns MATCH, or all filters are INDIFFERENT.
		// Filters returning FAIL will be ignored as long as there's at least one MATCH.
		foreach( $groups_requiring_any_filter as $group_display ) {
			$has_match = false;
			$has_fail = false;

			foreach( $filters as $filter ) {
				/** @var FilterDisplayResult $filter_result */
				$filter_result = $filter( $group_display->get_group(), false );
				$group_display->add_filter_result( $filter_result );
				switch( $filter_result->get_value() ) {
					case FilterDisplayResult::MATCH:
						$has_match = true;
						// We already decided to select the group, but we have to run other filters to
						// make sure additinonal information (like the need to refresh the page to reevaluate
						// group visibility) is included.
						break;
					case FilterDisplayResult::FAIL:
						// After this, the group can be still selected if there's a MATCH in another filter.
						$has_fail = true;
						break;
				}
			}

			// Select the group if there has been a MATCH *or* we at least have no FAILs (all are INDIFFERENT).
			$group_display->is_selected( $has_match || ! $has_fail );

			// Always include it in results, because we also need to pass on additional information,
			// like if a page refresh is needed to re-evaluate group visibility.
			$results[ $group_display->get_group()->get_slug() ] = $group_display;
		}

		// Process filters that apply on all groups.
		//
		// These filters just return a boolean.
		foreach( $force_filters as $filter ) {
			$results = array_filter( $results, $filter );
		}

		return $results;
	}


	/**
	 * Retrieve groups that should be displayed with a certain element, taking all possible conditions into account.
	 *
	 * @param IToolset_Element $element Element of the domain matching the field group.
	 * @param bool $return_group_display_results Set this to true to get an array of group display results instead of
	 *     the field groups themselves.
	 *
	 * @return Toolset_Field_Group_Post[]|GroupDisplayResult[]
	 */
	public function get_groups_for_element( IToolset_Element $element, $return_group_display_results = false ) {
		if( ! $element instanceof IToolset_Post ) {
			throw new InvalidArgumentException( 'Wrong element domain.' );
		}

		// Regular filter by a post type.
		$post_type = $element->get_type();
		$filter_by_post_type = static function(
			Toolset_Field_Group_Post $group, /** @noinspection PhpUnusedParameterInspection */ $require_all
		) use( $post_type ) {
			if( ! $group->is_assigned_to_type( $post_type ) ) {
				// The group explicitly doesn't belong to this post type.
				return new FilterDisplayResult( FilterDisplayResult::FAIL, false, false );
			}

			// If the field group is explicitly/strictly assigned to the given post type,
			// we actively select it. Otherwise, it is meant for all post types by default, in which case
			// we won't influence the final result.
			$is_assigned_strictly = $group->is_assigned_to_type( $post_type, true );
			return new FilterDisplayResult(
				( $is_assigned_strictly ? FilterDisplayResult::MATCH : FilterDisplayResult::INDIFFERENT ),
				false,
				false
			);
		};

		// Regular filter by post terms (term_taxonomy IDs)
		$term_taxonomy_ids = $element->get_term_taxonomy_ids();
		$filter_by_terms = static function( Toolset_Field_Group_Post $group, $require_all ) use( $term_taxonomy_ids ) {
			$terms_for_group = $group->get_assigned_to_terms();
			if( empty( $terms_for_group ) ) {
				// Empty means there are no rules regarding terms and the filter should not
				// influence the final result (unlike a filter by post type).
				//
				// If the group needs all filters to pass in order to be selected, this one will not stand in the way.
				// If it needs a single filter to pass, this will not be the one.
				return new FilterDisplayResult( FilterDisplayResult::INDIFFERENT, false, false );
			}

			// Stored term IDs match the default language: adjust them here.
			$current_language = apply_filters( 'wpml_current_language', false );
			$default_language = apply_filters( 'wpml_default_language', false );
			if ( $current_language !== $default_language ) {
				$terms_for_group = array_map(
					// Note that field group conditions are based on term_taxonomy_id and not on term_id.
					static function( $term_taxonomy_id ) {
						$term_object = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );
						if ( ! $term_object instanceof WP_Term ) {
							// Term is not well defined, or belongs to a non existing taxonomy.
							return $term_taxonomy_id;
						}
						$translated_term_id = apply_filters( 'wpml_object_id', $term_object->term_id, $term_object->taxonomy );
						$translated_term_object = get_term( $translated_term_id );
						if ( ! $translated_term_object instanceof WP_Term ) {
							// Term is not well defined, or belongs to a non existing taxonomy.
							return $term_taxonomy_id;
						}
						return $translated_term_object->term_taxonomy_id;
					},
					$terms_for_group
				);
			}

			if( $require_all ) {
				// Get terms required by the field group but not present in the post.
				$missing_terms = array_diff( $terms_for_group, $term_taxonomy_ids );
				return new FilterDisplayResult(
					count( $missing_terms ) === 0 ? FilterDisplayResult::MATCH : FilterDisplayResult::FAIL,
					false,
					true
				);
			}

			// Require at least one match.
			$intersection = array_intersect( $terms_for_group, $term_taxonomy_ids );
			return new FilterDisplayResult(
				count( $intersection ) > 0 ? FilterDisplayResult::MATCH : FilterDisplayResult::FAIL,
				false,
				true
			);
		};

		// Regular filter by assigned post template(s).
		$filter_by_templates = static function(
			Toolset_Field_Group_Post $group, /** @noinspection PhpUnusedParameterInspection */ $require_all
		) use( $element ) {
			$template_filters = $group->get_assigned_to_templates();
			if( empty( $template_filters ) ) {
				// Empty means there are no rules regarding templates and the filter should not
				// influence the final result (unlike a filter by post type).
				//
				// If the group needs all filters to pass in order to be selected, this one will not stand in the way.
				// If it needs a single filter to pass, this will not be the one.
				return new FilterDisplayResult( FilterDisplayResult::INDIFFERENT, false, false );
			}
			foreach( $template_filters as $template_filter ) {
				if( $template_filter->is_match_for_post( $element ) ) {
					return new FilterDisplayResult( FilterDisplayResult::MATCH, false, true );
				}
			}
			return new FilterDisplayResult( FilterDisplayResult::FAIL, false, true );
		};

		// Regular filter by data-dependent condition.
		// This is only a pre-evaluation, the rest happens in the browser.
		$filter_by_data_dependent_condition = static function( Toolset_Field_Group_Post $group ) {
			if( $group->has_conditional_display_conditions() ) {
				// We actively select the group, so that the condition can be evaluated dynamically in the browser.
				return new FilterDisplayResult( FilterDisplayResult::MATCH, true, false );
			}

			// The data-dependent one is not set, we're not going to influence the final result.
			return new FilterDisplayResult( FilterDisplayResult::INDIFFERENT, false, false );

			// Note: There's no FAIL scenario, because if the condition is set but isn't fulfilled,
			// the group will be removed dynamically in the browser.
		};

		// Forced filter (needs to pass for all selected groups) that allows a field group
		// with a RFG or a PRF only if its post type assignment matches.
		$field_group_service = new Types_Field_Group_Repeatable_Service();
		$force_post_type_filter_for_relationships = static function(
			GroupDisplayResult $group_display
		) use( $field_group_service, $post_type ) {
			/** @var Toolset_Field_Group_Post $group */
			$group = $group_display->get_group();
			if( 'all' === $group->get_filter_operator() ) {
				// This filter handles only a special case when filtering by ANY condition
				return true;
			}

			if( ! $field_group_service->group_contains_rfg_or_prf( $group->get_id() ) ) {
				// The group doesn't contain neither a RFG nor a PRF and will be handled by other filters.
				return true;
			}

			// In this case, we strictly require the group to be assigned to the post type of this element.
			return $group->is_assigned_to_type( $post_type, true );
		};

		// Forced filter that allows excluding selected groups.
		$allow_disabling_group_by_wp_filter = static function( GroupDisplayResult $group_display ) use( $element ) {

			/** @var Toolset_Field_Group_Post $group */
			$group = $group_display->get_group();

			/**
			 * toolset_show_field_group_for_post
			 *
			 * Allows preventing a field group being displayed for a particular post.
			 *
			 * @param bool Initial result value
			 * @param int Post ID where the group is about to be displayed.
			 * @param WP_Post Post where the group is about to be displayed.
			 * @param string Slug of the field group.
			 * @param int ID of the field group.
			 */
			return apply_filters(
				'toolset_show_field_group_for_post',
				true,
				$element->get_id(),
				$element->get_underlying_object(),
				$group->get_slug(),
				$group->get_id()
			);
		};

		// Magic!
		/** @var Toolset_Field_Group_Post[] $all_groups */
		$all_groups = $this->query_groups( array( 'purpose' => '*', 'is_active' => true ) );
		$group_display_results = $this->filter_groups(
			$all_groups,
			array(
				$filter_by_post_type,
				$filter_by_terms,
				$filter_by_templates,
				$filter_by_data_dependent_condition,
			),
			array(
				$force_post_type_filter_for_relationships,
				$allow_disabling_group_by_wp_filter,
			)
		);

		// Return all group display results, including the ones whose group was not selected.
		if( $return_group_display_results ) {
			return $group_display_results;
		}

		// By default, return only field groups which have been actually selected.
		/** @var Toolset_Field_Group_Post[] $results */
		$results = array_map(
			static function( GroupDisplayResult $group_display ) {
				return $group_display->get_group();
			},
			array_filter( $group_display_results, static function( GroupDisplayResult $group_display ) {
				return $group_display->is_selected();
			} )
		);

		return $results;
	}

}
