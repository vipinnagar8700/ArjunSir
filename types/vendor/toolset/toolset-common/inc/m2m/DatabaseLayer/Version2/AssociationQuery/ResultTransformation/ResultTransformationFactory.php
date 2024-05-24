<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Query;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\AssociationTranslator;

/**
 * Factory for the ResultTransformationInstance implementations.
 *
 * setup() must be called before further use.
 *
 * @since 4.0
 * @codeCoverageIgnore
 */
class ResultTransformationFactory {


	/** @var \Toolset_Element_Factory */
	private $element_factory;


	/** @var \Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var Query */
	private $query;


	/** @var AssociationTranslator */
	private $association_translator;


	/**
	 * ResultTransformationFactory constructor.
	 *
	 * @param \Toolset_Element_Factory $element_factory
	 * @param \Toolset_WPML_Compatibility $wpml_service
	 * @param AssociationTranslator $association_translator
	 */
	public function __construct(
		\Toolset_Element_Factory $element_factory,
		\Toolset_WPML_Compatibility $wpml_service,
		AssociationTranslator $association_translator
	) {
		$this->element_factory = $element_factory;
		$this->wpml_service = $wpml_service;
		$this->association_translator = $association_translator;
	}


	/**
	 * Setup the factory to be used in a particular context.
	 *
	 * @param Query $query
	 */
	public function setup( Query $query ) {
		$this->query = $query;
	}


	/**
	 * @return AssociationInstance
	 */
	public function association_instance() {
		return new AssociationInstance( $this->association_translator );
	}


	/**
	 * @return AssociationUid
	 */
	public function association_uid() {
		return new AssociationUid();
	}


	/**
	 * @return ElementPerRole
	 */
	public function element_per_role() {
		return new ElementPerRole( $this, $this->query );
	}


	/**
	 * @param RelationshipRole $role
	 *
	 * @return ElementId
	 */
	public function element_id( RelationshipRole $role ) {
		return new ElementId( $role );
	}


	/**
	 * @param RelationshipRole $role
	 *
	 * @return ElementInstance
	 */
	public function element_instance( RelationshipRole $role ) {
		return new ElementInstance( $role, $this->element_factory, $this->wpml_service );
	}

}
