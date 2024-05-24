<?php

namespace OTGS\Toolset\Common\Relationships\API;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory;

/**
 * Factory for providing access to instances of objects from the Relationship API.
 *
 * Actually, this may combine a factory and a repository pattern.
 *
 * You can instantiate it directly (in which case don't use any constructor parameters, as they may change),
 * but the preferred way is to do it via DIC during bootstrap phase.
 *
 * @since 4.0
 */
class Factory {

	/** @var DatabaseLayerFactory|null */
	private $_database_layer_factory;


	/** @var LowLevelGateway */
	private $low_level_gateway;


	/**
	 * Factory constructor.
	 *
	 * If using outside of the Relationships API codebase, never provide any parameters.
	 *
	 * @param DatabaseLayerFactory|null $database_layer_factory
	 */
	public function __construct( DatabaseLayerFactory $database_layer_factory = null ) {
		$this->_database_layer_factory = $database_layer_factory;
	}


	/**
	 * @return DatabaseLayerFactory
	 */
	private function get_database_layer_factory() {
		if( null === $this->_database_layer_factory ) {
			$this->_database_layer_factory = toolset_dic_make( '\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory' );
		}

		return $this->_database_layer_factory;
	}


	/**
	 * @return RelationshipQuery
	 */
	public function relationship_query() {
		return $this->get_database_layer_factory()->relationship_query();
	}


	/**
	 * @return AssociationQuery
	 */
	public function association_query() {
		return $this->get_database_layer_factory()->association_query();
	}


	/**
	 * @param \IToolset_Relationship_Definition $for_relationship
	 * @param RelationshipRoleParentChild $for_role
	 * @param \IToolset_Element $for_element
	 * @param array $args
	 *
	 * @return PotentialAssociationQuery
	 */
	public function potential_association_query(
		\IToolset_Relationship_Definition $for_relationship,
		RelationshipRoleParentChild $for_role,
		\IToolset_Element $for_element,
		$args = array()
	) {
		return $this->get_database_layer_factory()->potential_association_query(
			$for_relationship, $for_role, $for_element, $args
		);
	}


	/**
	 * @return AssociationDatabaseOperations
	 */
	public function database_operations() {
		return $this->get_database_layer_factory()->association_database_operations();
	}


	/**
	 * @return RelationshipRole
	 */
	public function role_parent() {
		return new \Toolset_Relationship_Role_Parent();
	}

	/**
	 * @return RelationshipRole
	 */
	public function role_child() {
		return new \Toolset_Relationship_Role_Child();
	}

	/**
	 * @return RelationshipRole
	 */
	public function role_intermediary() {
		return new \Toolset_Relationship_Role_Intermediary();
	}


	/**
	 * Gateway to low-level operations.
	 *
	 * Within Toolset plugins, CONSULT BEFORE USING THIS IN YOUR CODE.
	 * Outside of Toolset, NEVER USE THIS.
	 *
	 * @return LowLevelGateway
	 */
	public function low_level_gateway() {
		if( ! $this->low_level_gateway ) {
			$this->low_level_gateway = new LowLevelGateway( $this->get_database_layer_factory() );
		}
		return $this->low_level_gateway;
	}

}
