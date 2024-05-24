<?php

/**
 * Stores relationship settings for related posts columns
 *
 * These options are:
 *   - What fields (columns) will be displayed in related content
 *
 * @since m2md
 */
class Types_Post_Type_Relationship_Related_Posts_Settings extends Types_Post_Type_Relationship_Settings {

	/**
	 * WP option name pattern
	 *
	 * @var string
	 * @since m2m
	 */
	const OPTION_NAME_PATTERN = 'wpcf-listing-related-posts-%s-%d';


	/**
	 * @var Toolset_Relationship_Query_Factory
	 */
	private $query_factory;


	/**
	 * Constructor
	 *
	 * @param IToolset_Post_Type|string $post_type Post type or post type slug
	 * @param IToolset_Relationship_Definition $relationship Relationship
	 * @param Toolset_Relationship_Query_Factory $query_factory_di
	 *
	 * @throws InvalidArgumentException In case of error.
	 * @since m2m
	 */
	public function __construct( $post_type, $relationship, $query_factory_di = null ) {
		$this->query_factory = $query_factory_di ? : new Toolset_Relationship_Query_Factory();
		parent::__construct( $post_type, $relationship );
	}


	/**
	 * Gets all post type fields
	 *
	 * @return string[]
	 * @since m2m
	 */
	protected function get_all_fields() {
		$columns = array();
		$query = $this->query_factory->relationships_v2();

		$query->add(
			$query->has_domain_and_type( $this->current_post_type_slug, 'posts', new Toolset_Relationship_Role_Child() )
		)->add(
			$query->exclude_relationship( $this->relationship )
		);

		foreach ( $query->get_results() as $relationship ) {
			$parent_types = $relationship->get_element_type( new Toolset_Relationship_Role_Parent() )->get_types();
			foreach ( $parent_types as $parent_type ) {
				$columns[] = $parent_type;
			}
		}

		return $columns;
	}
}
