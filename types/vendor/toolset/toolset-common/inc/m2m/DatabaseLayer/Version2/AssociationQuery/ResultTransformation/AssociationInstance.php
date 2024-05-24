<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use IToolset_Association;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\AssociationTranslator;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\BrokenAssociationException;
use Toolset_Relationship_Role;

/**
 * Transform association query results into instances of IToolset_Association.
 *
 * @since 4.0
 */
class AssociationInstance implements ResultTransformationInterface {


	/** @var AssociationTranslator */
	private $association_translator;


	/**
	 * @param AssociationTranslator $association_translator
	 */
	public function __construct( AssociationTranslator $association_translator ) {
		$this->association_translator = $association_translator;
	}


	/**
	 * @inheritdoc
	 *
	 * @param array $database_row
	 *
	 * @return IToolset_Association
	 */
	public function transform( $database_row, ElementSelectorInterface $element_selector ) {
		try {
			return $this->association_translator->from_database_row_query( $database_row, $element_selector );
		} catch ( BrokenAssociationException $e ) {
			return null;
		}
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return void
	 */
	public function request_element_selection( ElementSelectorInterface $element_selector ) {
		// We totally need the association and relationship ID:
		$element_selector->request_association_and_relationship_in_results();

		// Request element IDs so that we can instantiate the association object.
		$roles = ( $element_selector->should_skip_intermediary_posts()
			? Toolset_Relationship_Role::parent_child()
			: Toolset_Relationship_Role::all()
		);
		foreach ( $roles as $role ) {
			$element_selector->request_element_in_results( $role );
		}
	}


	/**
	 * @inheritDoc
	 * @return RelationshipRole[]
	 */
	public function get_maximum_requested_roles() {
		// The optimization from request_element_selection() is not possible because we don't have the element selector
		// at this point.
		return Toolset_Relationship_Role::all();
	}
}
