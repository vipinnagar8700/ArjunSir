<?php

/**
 * Factory for instantiating query classes.
 *
 * Should be extendended for association query and all others within the m2m project.
 *
 * @since m2m
 * @deprecated use \OTGS\Toolset\Common\Relationships\API\Factory instead.
 */
class Toolset_Relationship_Query_Factory {

	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory|null */
	private $database_layer_factory;


	private function get_database_layer_factory() {
		if( null === $this->database_layer_factory ) {
			$this->database_layer_factory = toolset_dic_make( '\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory' );
		}

		return $this->database_layer_factory;
	}

	/**
	 * @param $args
	 *
	 * @return Toolset_Relationship_Query
	 * @deprecated Use Toolset_Relationship_Query_V2 instead.
	 */
	public function relationships( $args ) {
		return new Toolset_Relationship_Query( $args );
	}


	/**
	 * @return \OTGS\Toolset\Common\Relationships\API\RelationshipQuery
	 */
	public function relationships_v2() {
		return $this->get_database_layer_factory()->relationship_query();
	}



	/**
	 * @param array $args Query arguments.
	 *
	 * @return WP_Query
	 */
	public function wp_query( $args ) {
		return new WP_Query( $args );
	}


	/**
	 * @param $args
	 *
	 * @return Toolset_Association_Query
	 * @deprecated Use associations_v2() instead.
	 */
	public function associations( $args ) {
		return new Toolset_Association_Query( $args );
	}


	/**
	 * @return \OTGS\Toolset\Common\Relationships\API\AssociationQuery
	 */
	public function associations_v2() {
		return $this->get_database_layer_factory()->association_query();
	}

}
