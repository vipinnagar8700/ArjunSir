<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Relationship_Role;

/**
 * Transform association query results grouped by role, it requires another transformation class to return the
 * transformation
 *
 * @since 3.0.9
 */
class Toolset_Association_Query_Result_Transformation_Element_Per_Role implements IToolset_Association_Query_Result_Transformation {


	/** @var Toolset_Association_Query_Result_Transformation_Factory */
	private $result_transformation_factory;


	/** @var IToolset_Association_Query_Result_Transformation[] */
	private $per_role_transformations = array();


	/** @var Toolset_Association_Query_V2 */
	private $query;


	/**
	 * Toolset_Association_Query_Result_Transformation_Per_Role constructor.
	 *
	 * @param Toolset_Association_Query_Result_Transformation_Factory $result_transformation_factory
	 * @param Toolset_Association_Query_V2 $query
	 */
	public function __construct( Toolset_Association_Query_Result_Transformation_Factory $result_transformation_factory, Toolset_Association_Query_V2 $query ) {
		$this->result_transformation_factory = $result_transformation_factory;
		$this->query = $query;
	}


	/**
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return $this
	 */
	public function return_element_ids( IToolset_Relationship_Role $role ) {
		$this->per_role_transformations[ $role->get_name() ] = $this->result_transformation_factory->element_ids( $role );

		return $this;
	}


	/**
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return $this
	 */
	public function return_element_instances( IToolset_Relationship_Role $role ) {
		$this->per_role_transformations[ $role->get_name() ] = $this->result_transformation_factory->element_instances( $role );

		return $this;
	}


	/**
	 * Return the query object by which this transformation class has been created, so that it is possible to continue
	 * method chaining.
	 *
	 * @return Toolset_Association_Query_V2
	 */
	public function done() {
		return $this->query;
	}


	/**
	 * @param object $database_row It is safe to expect only properties that are always
	 *     preset in results of a query from OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Sql_Expression_Builder.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return mixed
	 */
	public function transform( $database_row, IToolset_Association_Query_Element_Selector $element_selector ) {
		$result = array();
		foreach ( $this->per_role_transformations as $role_name => $transformation ) {
			$result[ $role_name ] = $transformation->transform( $database_row, $element_selector );
		}

		return $result;
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return void
	 * @since 2.5.10
	 */
	public function request_element_selection( IToolset_Association_Query_Element_Selector $element_selector ) {
		foreach ( $this->per_role_transformations as $transformation ) {
			$transformation->request_element_selection( $element_selector );
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @return IToolset_Relationship_Role[]
	 */
	public function get_maximum_requested_roles() {
		$result = [];

		foreach ( $this->per_role_transformations as $transformation ) {
			foreach ( $transformation->get_maximum_requested_roles() as $requested_role ) {
				$result[ $requested_role->get_name() ] = $requested_role;
			}
		}

		return $result;
	}
}
