<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use IToolset_Element;
use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Element_Domain;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;

/**
 * Transform association query results into instances of elements of the chosen role.
 *
 * Note: At the moment, only the posts domain is supported.
 */
class ElementInstance implements ResultTransformationInterface {


	/** @var RelationshipRole */
	private $role;


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var WpmlService */
	private $wpml_service;


	/**
	 * @param RelationshipRole $role
	 * @param Toolset_Element_Factory $element_factory
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		RelationshipRole $role,
		Toolset_Element_Factory $element_factory,
		WpmlService $wpml_service
	) {
		$this->role = $role;
		$this->wpml_service = $wpml_service;
		$this->element_factory = $element_factory;
	}


	/**
	 * @inheritdoc
	 *
	 * Note: This will require some adjustments when other element domains are supported.
	 * The best course will be to instruct $element_selector to also include the relationships
	 * table in request_element_selection() and then obtain the domain information from there.
	 *
	 * @param array $database_row
	 *
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return IToolset_Element
	 */
	public function transform(
		$database_row, ElementSelectorInterface $element_selector
	) {
		try {
			if (
				$this->wpml_service->is_wpml_active_and_configured()
				&& $element_selector->may_have_element_id_translated( $this->role )
			) {
				// There's a chance of getting two or more language versions of the element, let's try.
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
	 * @param array $database_row
	 * @param ElementSelectorInterface $element_selector
	 *
	 * @return IToolset_Element
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function transform_with_wpml(
		$database_row, ElementSelectorInterface $element_selector
	) {
		$default_language_element_id = $this->get_element_id( $database_row, $element_selector, false );
		$current_language_element_id = $this->get_element_id( $database_row, $element_selector, true );
		$original_language_element_id = $this->get_element_id(
			$database_row, $element_selector, ElementIdentification::ORIGINAL_LANGUAGE
		);

		if ( 0 === $default_language_element_id && 0 === $original_language_element_id ) {
			// Only a secondary language is available
			return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $current_language_element_id );
		}

		if ( $current_language_element_id === $default_language_element_id && 0 === $original_language_element_id ) {
			// Only a default language is available.
			return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $default_language_element_id );
		}

		$element_ids = [ '' => $original_language_element_id ]; // We don't know what would be the language of the original post.
		$element_ids[ $this->wpml_service->get_default_language() ] = $default_language_element_id;
		$element_ids[ $this->wpml_service->get_current_language() ] = $current_language_element_id;
		$element_ids = array_filter(
			$element_ids,
			static function ( $element_id ) { return 0 !== $element_id; }
		);

		return $this->element_factory->get_element( Toolset_Element_Domain::POSTS, $element_ids );
	}


	/**
	 * Read an element ID from the database row.
	 *
	 * @param array $database_row
	 * @param ElementSelectorInterface $element_selector
	 * @param string $which_element One of the ElementIdentification values.
	 *
	 * @return mixed
	 */
	private function get_element_id(
		$database_row, ElementSelectorInterface $element_selector, $which_element
	) {
		$column_name = $element_selector->get_element_id_alias( $this->role, $which_element );

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
