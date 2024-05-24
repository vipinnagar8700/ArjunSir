<?php

/**
 * Factory for viewmodels of realtionship definitions, for the purposes of the Relationships page.
 *
 * @since m2m
 */
class Types_Viewmodel_Relationship_Definition_Factory {

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var Toolset_Field_Group_Post_Factory */
	private $post_field_group_factory;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;

	/**
	 * Types_Viewmodel_Relationship_Definition_Factory constructor.
	 *
	 * @param null|Toolset_Relationship_Definition_Repository $relationship_definition_repository_di Dependency injection parameter.
	 * @param Toolset_Field_Group_Post_Factory|null $post_field_group_factory_di
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 */
	public function __construct(
		$relationship_definition_repository_di = null,
		Toolset_Field_Group_Post_Factory $post_field_group_factory_di = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null
	) {

		$this->relationship_definition_repository = ( null === $relationship_definition_repository_di
			? Toolset_Relationship_Definition_Repository::get_instance()
			: $relationship_definition_repository_di );

		$this->post_field_group_factory = (
			null === $post_field_group_factory_di
				? Toolset_Field_Group_Post_Factory::get_instance()
				: $post_field_group_factory_di
		);

		$this->post_type_repository = (
			null === $post_type_repository_di
				? Toolset_Post_Type_Repository::get_instance()
				: $post_type_repository_di
		);
	}


	/**
	 * Create viewmodels for all existing relationships.
	 *
	 * @return Types_Viewmodel_Relationship_Definition[]
	 * @since m2m
	 */
	public function get_viewmodels() {

		$relationship_definitions = $this->relationship_definition_repository->get_definitions();

		$viewmodels = array();
		foreach( $relationship_definitions as $relationship_definition ) {
			if( ! $relationship_definition->get_origin()->show_on_page_relationships() ) {
				// relationship should not be shown on the relationship overview page, continue with next
				continue;
			}
			$viewmodels[] = new Types_Viewmodel_Relationship_Definition(
				$relationship_definition,
				$this->relationship_definition_repository,
				null,
				$this->post_field_group_factory,
				null,
				$this->post_type_repository
			);
		}

		return $viewmodels;
	}


	/**
	 * Create a viewmodel for a single relationship definition.
	 *
	 * @param string $slug Relationship definition slug
	 * @return Types_Viewmodel_Relationship_Definition
	 * @throws InvalidArgumentException If the slug is clearly invalid or if the relationship definition
	 *     doesn't exist.
	 * @since m2m
	 */
	public function get_viewmodel_by_slug( $slug ) {

		$relationship_definition = $this->relationship_definition_repository->get_definition( $slug );

		if( ! $relationship_definition instanceof IToolset_Relationship_Definition ) {
			throw new InvalidArgumentException(
				sprintf( __( 'No relationship with slug "%s" was found.', 'wpcf' ), sanitize_text_field( $slug ) )
			);
		}

		return new Types_Viewmodel_Relationship_Definition(
			$relationship_definition,
			$this->relationship_definition_repository,
			null,
			$this->post_field_group_factory,
			null,
			$this->post_type_repository
		);
	}
}