<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;

/**
 * Transform association query results into element IDs of the chosen role.
 *
 * @since 4.0
 */
class ElementId implements ResultTransformationInterface {


	/** @var RelationshipRole */
	private $role;


	/**
	 * @param RelationshipRole $role
	 */
	public function __construct( RelationshipRole $role ) {
		$this->role = $role;
	}


	/**
	 * @inheritdoc
	 *
	 * @param array $database_row
	 *
	 * @return int
	 */
	public function transform( $database_row, ElementSelectorInterface $element_selector ) {
		$translated_or_default = $this->get_element_id(
			$database_row, $element_selector, ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
		);

		if ( $translated_or_default ) {
			return $translated_or_default;
		}

		return $this->get_element_id(
			$database_row, $element_selector, ElementIdentification::ORIGINAL_LANGUAGE
		);
	}


	private function get_element_id( $database_row, ElementSelectorInterface $element_selector, $element_identification ) {
		$column_name = $element_selector->get_element_id_alias( $this->role, $element_identification );

		return (int) toolset_getarr( $database_row, $column_name, 0 );
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param ElementSelectorInterface $element_selector
	 */
	public function request_element_selection( ElementSelectorInterface $element_selector ) {
		// We need only one element here. Also, we explicitly *don't* want to include association ID
		// so that we can filter out duplicate IDs by the DISTINCT query.
		$element_selector->request_element_in_results( $this->role );
		$element_selector->request_distinct_query();
	}


	/**
	 * @inheritDoc
	 * @return RelationshipRole[]
	 */
	public function get_maximum_requested_roles() {
		return [ $this->role ];
	}
}
