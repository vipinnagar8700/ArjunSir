<?php

/**
 * Factory for IToolset_Potentional_Association_Query.
 *
 * Detects the target domain and returns the proper factory instance.
 *
 * @since m2m
 * @deprecated Use \OTGS\Toolset\Common\Relationships\API\Factory::potential_association_query() instead.
 */
class Toolset_Potential_Association_Query_Factory {

	private $database_layer_factory;


	public function __construct() {
		$this->database_layer_factory = toolset_dic_make( '\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory' );
	}


	/**
	 * @param IToolset_Relationship_Definition $for_relationship
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $for_role
	 * @param IToolset_Element $for_element
	 * @param array $args
	 *
	 * @return \OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery
	 * @throws RuntimeException
	 */
	public function create(
		IToolset_Relationship_Definition $for_relationship,
		\OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $for_role,
		IToolset_Element $for_element,
		$args = array()
	) {
		return $this->database_layer_factory->potential_association_query(
			$for_relationship,
			$for_role,
			$for_element,
			$args
		);
	}

}
