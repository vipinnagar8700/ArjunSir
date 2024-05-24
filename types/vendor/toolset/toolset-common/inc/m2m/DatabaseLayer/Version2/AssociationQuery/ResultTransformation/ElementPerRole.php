<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Query;

/**
 * Transform association query results grouped by role.
 *
 * Encapsulates other transformation objects to produce the results.
 *
 * @since 4.0
 */
class ElementPerRole implements ResultTransformationInterface {


	/** @var ResultTransformationFactory */
	private $result_transformation_factory;


	/** @var ResultTransformationInterface[] */
	private $per_role_transformations = array();


	/** @var Query */
	private $query;


	/**
	 * Toolset_Association_Query_Result_Transformation_Per_Role constructor.
	 *
	 * @param ResultTransformationFactory $result_transformation_factory
	 * @param Query $query
	 */
	public function __construct( ResultTransformationFactory $result_transformation_factory, Query $query ) {
		$this->result_transformation_factory = $result_transformation_factory;
		$this->query = $query;
	}


	/**
	 * @param RelationshipRole $role
	 *
	 * @return $this
	 */
	public function return_element_ids( RelationshipRole $role ) {
		$this->per_role_transformations[ $role->get_name() ] = $this->result_transformation_factory->element_id( $role );

		return $this;
	}


	/**
	 * @param RelationshipRole $role
	 *
	 * @return $this
	 */
	public function return_element_instances( RelationshipRole $role ) {
		$this->per_role_transformations[ $role->get_name() ] = $this->result_transformation_factory->element_instance( $role );

		return $this;
	}


	/**
	 * Return the query object by which this transformation class has been created, so that it is possible to continue
	 * method chaining.
	 *
	 * @return Query
	 */
	public function done() {
		return $this->query;
	}


	/**
	 * @param array $database_row It is safe to expect only properties that are always
	 *     preset in results of a query from OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Sql_Expression_Builder.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return mixed
	 */
	public function transform( $database_row, ElementSelectorInterface $element_selector ) {
		$result = array();
		foreach ( $this->per_role_transformations as $role_name => $transformation ) {
			$result[ $role_name ] = $transformation->transform( $database_row, $element_selector );
		}

		return $result;
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return void
	 */
	public function request_element_selection( ElementSelectorInterface $element_selector ) {
		foreach ( $this->per_role_transformations as $transformation ) {
			$transformation->request_element_selection( $element_selector );
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @return RelationshipRole[]
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
