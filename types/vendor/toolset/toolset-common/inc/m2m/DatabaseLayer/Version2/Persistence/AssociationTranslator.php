<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence;

use IToolset_Element;
use IToolset_Post;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\SelectedColumnAliases;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;

/**
 * Translate the association data between the IToolset_Association model and a database row.
 *
 * @since 4.0
 */
class AssociationTranslator {

	/** @var \Toolset_Relationship_Definition_Repository */
	private $definition_repository;

	/** @var \Toolset_Association_Factory */
	private $association_factory;

	/** @var WpmlService */
	private $wpml_service;

	/** @var \Toolset_Element_Factory */
	private $element_factory;

	private $connected_element_persistence;


	/**
	 * AssociationTranslator constructor.
	 *
	 * @param \Toolset_Relationship_Definition_Repository $definition_repository
	 * @param \Toolset_Association_Factory $association_factory
	 * @param WpmlService $wpml_service
	 * @param \Toolset_Element_Factory $element_factory
	 * @param ConnectedElementPersistence $connected_element_persistence
	 */
	public function __construct(
		\Toolset_Relationship_Definition_Repository $definition_repository,
		\Toolset_Association_Factory $association_factory,
		WpmlService $wpml_service,
		\Toolset_Element_Factory $element_factory,
		ConnectedElementPersistence $connected_element_persistence
	) {
		$this->definition_repository = $definition_repository;
		$this->association_factory = $association_factory;
		$this->wpml_service = $wpml_service;
		$this->element_factory = $element_factory;
		$this->connected_element_persistence = $connected_element_persistence;
	}


	/**
	 * Instantiate the association from a database row coming from the association query.
	 *
	 * @param array $database_row Database row as an associative array.
	 * @param ElementSelectorInterface $element_selector The element selector used in the query that will provide
	 *     information about selected column names for each element role.
	 * @param bool|null $use_wpml Whether WPML interoperability should be taken into account, or null to
	 *     decide by the plugin status.
	 *
	 * @return \IToolset_Association
	 * @throws BrokenAssociationException Thrown when association data is incomplete or when the association model
	 *     cannot be instantiated.
	 */
	public function from_database_row_query( array $database_row, ElementSelectorInterface $element_selector, $use_wpml = null ) {
		$use_wpml = $use_wpml ?: $this->wpml_service->is_wpml_active_and_configured();

		$relationship = $this->definition_repository->get_definition_by_row_id(
			$database_row[ SelectedColumnAliases::FIXED_ALIAS_RELATIONSHIP_ID ]
		);

		return $this->from_database_row_abstract(
			$database_row,
			function ( array $database_row, RelationshipRole $role ) use (
				$element_selector, $use_wpml, $relationship
			) {
				return $this->get_element_source_for_role(
					$database_row, $role, $element_selector, $use_wpml, $relationship
				);
			},
			$relationship
		);
	}


	/**
	 * Instantiate an association from a database row of the associations table directly (using standard
	 * column names to obtain element group IDs).
	 *
	 * @param array $database_row
	 *
	 * @return \IToolset_Association
	 * @throws BrokenAssociationException
	 */
	public function from_database_row_direct( array $database_row ) {
		return $this->from_database_row_abstract(
			$database_row,
			function( array $database_row, RelationshipRole $role ) {
				$group_id = $database_row[ AssociationTable::role_to_column( $role )];
				if ( ! $group_id ) {
					return null;
				}

				try {
					return $this->connected_element_persistence->get_element_by_group_id( $group_id );
				} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
					return null;
				}
			}
		);
	}


	/**
	 * Actual instatiation of the association object, that uses a callable to obtain element group IDs.
	 *
	 * @param array $database_row
	 * @param callable $get_element_source Receives $database_row as a first parameter and the RelationshipRole
	 *     as the second one. Expected to return an element source that is acceptable by the
	 *     Toolset_Association constructor.
	 * @param \Toolset_Relationship_Definition|null $relationship
	 *
	 * @return \IToolset_Association
	 * @throws BrokenAssociationException
	 */
	private function from_database_row_abstract(
		array $database_row, callable $get_element_source, \Toolset_Relationship_Definition $relationship = null
	) {
		$association_uid = (int) $database_row[ SelectedColumnAliases::FIXED_ALIAS_ID ];

		$relationship = $relationship ?: $this->definition_repository->get_definition_by_row_id(
			$database_row[ SelectedColumnAliases::FIXED_ALIAS_RELATIONSHIP_ID ]
		);

		if ( null === $relationship ) {
			throw new BrokenAssociationException( $association_uid, 'The association doesn\'t have any relationship definition.' );
		}

		list( $parent, $child, $intermediary ) = array_map(
			static function ( RelationshipRole $role ) use ( $database_row, $get_element_source ) {
				return $get_element_source( $database_row, $role );
			},
			[
				new Toolset_Relationship_Role_Parent(),
				new Toolset_Relationship_Role_Child(),
				new Toolset_Relationship_Role_Intermediary(),
			]
		);

		try {
			return $this->association_factory->create(
				$relationship, $parent, $child, $intermediary, $association_uid
			);
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			throw new BrokenAssociationException( $association_uid, 'Element missing for association.', 0, $e );
		}
	}


	/**
	 * For a given role, extract the correct element source from an association query database row.
	 *
	 * @param array $database_row
	 * @param RelationshipRole $role
	 * @param ElementSelectorInterface $element_selector
	 * @param $use_wpml
	 * @param IToolset_Relationship_Definition $relationship_definition
	 *
	 * @return int|IToolset_Element|IToolset_Post Element object, element ID or zero if it doesn't exist.
	 */
	private function get_element_source_for_role(
		array $database_row,
		RelationshipRole $role,
		ElementSelectorInterface $element_selector,
		$use_wpml,
		IToolset_Relationship_Definition $relationship_definition
	) {
		if ( $use_wpml ) {
			try {
				return $this->get_element_in_all_languages(
					$database_row, $relationship_definition, $role, $element_selector
				);
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				return 0;
			}
		}

		return (int) toolset_getarr( $database_row, $element_selector->get_element_id_alias( $role ), 0 );
	}


	/**
	 * Get an element which contains all the available language information.
	 *
	 * @param array $database_row
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param RelationshipRole $for_role
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return IToolset_Element|IToolset_Post|int Zero can be returned if there is no
	 *     intermediary post at all.
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function get_element_in_all_languages(
		array $database_row,
		IToolset_Relationship_Definition $relationship_definition,
		RelationshipRole $for_role,
		ElementSelectorInterface $element_selector
	) {
		$element_ids = array_filter(
			[
				$this->wpml_service->get_default_language() => (int) toolset_getarr(
					$database_row,
					$element_selector->get_element_id_alias( $for_role, ElementIdentification::DEFAULT_LANGUAGE ),
					0
				),
				$this->wpml_service->get_current_language() => (int) toolset_getarr(
					$database_row,
					$element_selector->get_element_id_alias( $for_role, ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE ),
					0
				),
				// We don't know what would be the language of the original post.
				'' => (int) toolset_getarr(
					$database_row,
					$element_selector->get_element_id_alias( $for_role, ElementIdentification::ORIGINAL_LANGUAGE ),
					0
				)
			],
			static function ( $element_id ) { return 0 !== $element_id; }
		);

		if ( empty( $element_ids ) ) {
			// This can happen for an intermediary post - no element to instantiate, and
			// the association will survive.
			return 0;
		}

		return $this->element_factory->get_element(
			$relationship_definition->get_element_type( $for_role )->get_domain(),
			$element_ids
		);
	}


	/**
	 * Turn an association to a database row (to be used in $wpdb->insert()).
	 *
	 * Doesn't include the association UID.
	 *
	 * @param \IToolset_Association $association
	 *
	 * @return array
	 * @throws BrokenAssociationException
	 */
	public function to_database_row( \IToolset_Association $association ) {
		$row = [
			AssociationTable::RELATIONSHIP_ID => $association->get_definition()->get_row_id()
		];

		foreach( \Toolset_Relationship_Role::all() as $role ) {
			$element = $association->get_element( $role );

			if ( ! $element instanceof IToolset_Element && ! $role instanceof Toolset_Relationship_Role_Intermediary ) {
				throw new BrokenAssociationException( $association->get_uid() );
			}

			$row[ AssociationTable::role_to_column( $role ) ] = $element
				? $this->connected_element_persistence->obtain_element_group_id( $element, true )
				: 0; // Intermediary post is not obligatory in all cases.
		}

		return $row;
	}


	/**
	 * @return string[] Column formats for columns as returned by to_database_row().
	 */
	public function get_database_row_formats() {
		return array( '%d', '%d', '%d', '%d' );
	}

}
