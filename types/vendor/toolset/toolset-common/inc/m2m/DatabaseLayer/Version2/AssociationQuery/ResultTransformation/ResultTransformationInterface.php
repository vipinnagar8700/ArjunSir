<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;

/**
 * Object that performs a transformation of a single database row from the
 * association query into a the desired result.
 *
 * @since 4.0
 */
interface ResultTransformationInterface {


	/**
	 * @param array $database_row It is safe to expect only properties that are always
	 *     preset in results of a query from SqlExpressionBuilder.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return mixed
	 */
	public function transform( $database_row, ElementSelectorInterface $element_selector );


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return void
	 */
	public function request_element_selection( ElementSelectorInterface $element_selector );


	/**
	 * Determine what roles *may* need to be included in the results.
	 *
	 * That means, if a role is not returned by this method, it will definitely *not* be needed during the result
	 * transformation. It doesn't work the opposite way, though.
	 *
	 * @return RelationshipRole[]
	 */
	public function get_maximum_requested_roles();

}
