<?php

/**
 * Modifier for the query to populate the selectors for associating to an existing post, based on the status of offered posts.
 *
 * @since 3.0.1
 */
class Types_Potential_Association_Query_Filter_Posts_Status extends Toolset_Potential_Association_Query_Filter_Posts_Status {

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


	protected function filter_by_plugin( $post_status ) {

		$post_status = array( 'publish', 'draft', 'pending', 'future' );

		$post_status = apply_filters(
			'types_force_post_status_' . $this->target_role->get_name() . '_post_relationship_by_slug_' . $this->relationship_definition->get_slug(),
			$post_status
		);

		$post_status = apply_filters(
			'types_force_post_status_in_post_relationship_by_slug_' . $this->relationship_definition->get_slug(),
			$post_status,
			$this->target_role->get_name()
		);

		$post_status = apply_filters(
			'types_force_post_status_in_post_relationship',
			$post_status,
			$this->relationship_definition->get_slug(),
			$this->target_role->get_name()
		);

		$post_status = apply_filters(
			'types_force_post_status_in_related_post',
			$post_status
		);

		return $post_status;
	}


}
