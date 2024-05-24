<?php

/**
 * Modifier for the query to populate the selectors for associating to an existing post, based on the author of offered posts.
 *
 * @since m2m
 */
class Types_Potential_Association_Query_Filter_Posts_Author_For_New_Association
	extends Toolset_Potential_Association_Query_Filter_Posts_Author {

	/**
	 * @var IToolset_Relationship_Definition
	 */
	protected $relationship_definition;

	/**
	 * @var IToolset_Relationship_Role_Parent_Child
	 */
	protected $target_role;

	public function __construct(
		IToolset_Relationship_Definition $relationship_definition,
		IToolset_Relationship_Role_Parent_Child $target_role
	) {
		$this->relationship_definition = $relationship_definition;
		$this->target_role = $target_role;
	}

	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Decides whether a filter by post author needs to be set by cascading a series of filters:
	 * - types_force_author_in_{requested_role}_post_relationship_by_slug_{relationshup_slug}
	 * - types_force_author_in_post_relationship_by_slug_{relationshup_slug} | gets also the target role name
	 * - types_force_author_in_post_relationship | gets also the relationship slug and the target role name
	 * - types_force_author_in_related_post
	 *
	 * Those filters should return either a post author ID or the keyword '$current', which is a placeholder
	 * for the currently logged in user; in case no user is logged in, we force empty query results.
	 *
	 * @param mixed $force_role_author
	 *
	 * @return mixed
	 *
	 * @since m2m
	 */
	protected function filter_by_plugin( $force_role_author ) {
		/**
		 * Force a post author on all Types interfaces to set an association, by relationship slug and requested role name.
		 *
		 * Include here extra data, for granularity:
		 * - The requested role name.
		 *
		 * @since m2m
		 */
		$force_role_author = apply_filters(
			'types_force_author_in_' . $this->target_role->get_name() . '_post_relationship_by_slug_' . $this->relationship_definition->get_slug(),
			$force_role_author
		);
		/**
		 * Force a post author on all Types interfaces to set an association, by relationship slug.
		 *
		 * Include here extra data, for granularity:
		 * - The requested role name.
		 *
		 * @since m2m
		 */
		$force_role_author = apply_filters(
			'types_force_author_in_post_relationship_by_slug_' . $this->relationship_definition->get_slug(),
			$force_role_author,
			$this->target_role->get_name()
		);
		/**
		 * Force a post author on all Types interfaces to set an association.
		 *
		 * Include here extra data, for granularity:
		 * - The relationship slug.
		 * - The requested role name.
		 *
		 * @since m2m
		 */
		$force_role_author = apply_filters(
			'types_force_author_in_post_relationship',
			$force_role_author,
			$this->relationship_definition->get_slug(),
			$this->target_role->get_name()
		);
		/**
		 * Force a post author on all Types interfaces to set a related post.
		 *
		 * This is also used in the backend post edit page when setting a related post.
		 *
		 * @since m2m
		 */
		return apply_filters(
			'types_force_author_in_related_post',
			$force_role_author
		);
	}

}
