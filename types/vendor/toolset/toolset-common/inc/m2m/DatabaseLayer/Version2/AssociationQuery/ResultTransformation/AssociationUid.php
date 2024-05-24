<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\SelectedColumnAliases;
use Toolset\DynamicSources\ToolsetSources\RelationshipRole;

/**
 * Transforms the association query result into an association UID.
 *
 * @since 4.0
 */
class AssociationUid implements ResultTransformationInterface {

	/**
	 * @inheritdoc
	 *
	 * @param array $database_row
	 *
	 * @return int
	 */
	public function transform(
		$database_row, ElementSelectorInterface $element_selector
	) {
		return (int) toolset_getarr( $database_row, SelectedColumnAliases::FIXED_ALIAS_ID, 0 );
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return void
	 */
	public function request_element_selection( ElementSelectorInterface $element_selector ) {
		// We're only returning the association UID but don't care about its elements.
		$element_selector->request_association_and_relationship_in_results();
	}


	/**
	 * @inheritDoc
	 * @return RelationshipRole[]
	 */
	public function get_maximum_requested_roles() {
		return [];
	}

}
