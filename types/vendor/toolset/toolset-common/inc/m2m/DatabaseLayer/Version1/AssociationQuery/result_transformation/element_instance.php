<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Element;
use IToolset_Relationship_Role;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use Toolset_Element_Domain;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_WPML_Compatibility;

/**
 * Transform association query results into instances of elements of the chosen role.
 *
 * Note: At the moment, only the posts domain is supported.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Result_Transformation_Element_Instance
	implements IToolset_Association_Query_Result_Transformation {


	/** @var IToolset_Relationship_Role */
	private $role;


	/** @var Toolset_Element_Factory */
	private $element_factory;


	private $wpml_service;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Result_Transformation_Element_Instance
	 * constructor.
	 *
	 * @param RelationshipRole $role
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		RelationshipRole $role,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->role = $role;
		$this->wpml_service = ( null === $wpml_service_di ? Toolset_WPML_Compatibility::get_instance()
			: $wpml_service_di );
		$this->element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory() : $element_factory_di );
	}


	/**
	 * @inheritdoc
	 *
	 * Note: This will require some adjustments when other element domains are supported.
	 * The best course will be to instruct $element_selector to also include the relationships
	 * table in request_element_selection() and then obtain the domain information from there.
	 *
	 * @param object $database_row
	 *
	 * @return IToolset_Element
	 */
	public function transform(
		$database_row, IToolset_Association_Query_Element_Selector $element_selector
	) {
		try {

			if (
				$this->wpml_service->is_wpml_active_and_configured()
				&& $element_selector->has_element_id_translated( $this->role )
				&& $this->wpml_service->get_current_language() !== $this->wpml_service->get_default_language()
			) {
				// There's a chance of getting two language versions of the element, let's try.
				return $this->transform_with_wpml( $database_row, $element_selector );
			}

			$element_id = $this->get_element_id( $database_row, $element_selector, true );
			if ( ! $element_id ) {
				return null;
			}

			return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $element_id );

		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// No element to transform. This may indicate either a missing intermediary post or data corruption
			// but we can't do anything about this at this level.
			return null;
		}
	}


	/**
	 * Determine if the desired element has two language versions and if it does,
	 * pass both of them to the factory object when instantiating the IToolset_Element model.
	 *
	 * @param object $database_row
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return IToolset_Element
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function transform_with_wpml(
		$database_row, IToolset_Association_Query_Element_Selector $element_selector
	) {
		$default_language_element_id = $this->get_element_id( $database_row, $element_selector, false );
		$current_language_element_id = $this->get_element_id( $database_row, $element_selector, true );

		if ( 0 === $default_language_element_id ) {
			throw new Toolset_Element_Exception_Element_Doesnt_Exist( Toolset_Element_Domain::POSTS, $database_row );
		}

		if ( $current_language_element_id === $default_language_element_id ) {
			// Only a default language is available.
			return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $default_language_element_id );
		}

		$element_ids = array(
			$this->wpml_service->get_default_language() => $default_language_element_id,
			$this->wpml_service->get_current_language() => $current_language_element_id,
		);

		return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $element_ids );
	}


	/**
	 * Read an element ID from the database row.
	 *
	 * @param object $database_row
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 * @param bool $translate_if_possible Use the default language version or try using a translation?
	 *
	 * @return mixed
	 */
	private function get_element_id(
		$database_row, IToolset_Association_Query_Element_Selector $element_selector, $translate_if_possible
	) {
		$column_name = $element_selector->get_element_id_alias( $this->role, $translate_if_possible );

		return $database_row->$column_name;
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @since 2.5.10
	 */
	public function request_element_selection( IToolset_Association_Query_Element_Selector $element_selector ) {
		// We need only one element here. Also, we explicitly *don't* want to include association ID
		// so that we can filter out duplicate IDs by the DISTINCT query.
		$element_selector->request_element_in_results( $this->role );
		$element_selector->request_distinct_query();
	}


	/**
	 * @inheritDoc
	 * @return IToolset_Relationship_Role[]
	 */
	public function get_maximum_requested_roles() {
		return [ $this->role ];
	}
}
