<?php

/**
 * Handle a missing element that might be involved in a number of associations.
 *
 * This will delete all affected associations and also intermediary posts of such associations.
 * If invalid parameters are provided, the method does nothing.
 *
 * @since 2.5.6
 */
class Toolset_Relationship_Database_Issue_Missing_Element implements IToolset_Relationship_Database_Issue {


	/** @var wpdb  */
	private $wpdb;

	/** @var string */
	private $domain;

	/** @var int */
	private $element_id;

	private $relationships_factory;


	/**
	 * Toolset_Relationship_Database_Issue_Missing_Element constructor.
	 *
	 * @param string $domain Element domain.
	 * @param int $element_id ID of the missing element.
	 * @param wpdb|null $wpdb_di
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory|null $relationships_factory
	 */
	public function __construct(
		$domain, $element_id,
		wpdb $wpdb_di = null,
		\OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory = null
	) {
		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->relationships_factory = $relationships_factory ?: new \OTGS\Toolset\Common\Relationships\API\Factory();

		if(
			! in_array( $domain,  Toolset_Element_Domain::all(), true )
			|| ! Toolset_Utils::is_natural_numeric( $element_id )
		) {
			throw new InvalidArgumentException();
		}

		$this->domain = $domain;
		$this->element_id = $element_id;
	}


	/**
	 * Handle the issue.
	 */
	public function handle() {
		// Gather in relationships that have the correct domain in one and in the other role.
		$results = array();
		foreach( Toolset_Relationship_Role::parent_child() as $role ) {
			$query = $this->relationships_factory->relationship_query();
			$relationships = $query->do_not_add_default_conditions()
				->add( $query->has_domain( $this->domain, $role ) )
				->get_results();

			$results[ $role->get_name() ] = $relationships;
		}

		// Delete what needs to be deleted. Note that we might be performing a lot of MySQL queries here,
		// but it's an one-time thing, so I prefer a cleaner, safer implementation over performance.
		foreach( $results as $role_name => $relationships ) {
			/** @var Toolset_Relationship_Definition $relationship */
			foreach( $relationships as $relationship ) {
				$this->delete_intermediary_posts( $relationship, $role_name, $this->element_id );
				$this->delete_associations( $relationship, $role_name, $this->element_id );
			}
		}
	}


	/**
	 * Delete intermediary posts from all associations in a given relationship that have
	 * the given element in the given role.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	private function delete_intermediary_posts( $relationship, $element_role_name, $element_id ) {
		$this->relationships_factory->database_operations()->delete_intermediary_posts_by_element(
			$relationship, $element_role_name, $element_id
		);
	}


	/**
	 * Delete all associations of a given relationships that have the given element in the given role.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	private function delete_associations( $relationship, $element_role_name, $element_id ) {
		$this->relationships_factory->database_operations()->delete_associations_by_element(
			$relationship, $element_role_name, $element_id
		);
	}

}
