<?php

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Factory for viewmodels of related content, for the purposes of the Edit pages.
 *
 * @since m2m
 */
class Types_Viewmodel_Related_Content_Factory {

	/** @var Factory */
	private $relationships_factory;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * Types_Viewmodel_Related_Content_Factory constructor.
	 *
	 * @param Factory $relationships_factory
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		Factory $relationships_factory,
		WpmlService $wpml_service
	) {
		$this->relationships_factory = $relationships_factory;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * For a given field domain, return the appropriate related content factory instance.
	 *
	 * @param string                          $role Relationship element role.
	 * @param IToolset_Relationship_Definition $relationship The relationship.
	 *
	 * @return Types_Viewmodel_Related_Content_Post
	 * @throws RuntimeException When the domains is incorrect.
	 * @since m2m
	 */
	public function get_model_by_relationship( $role, $relationship ) {
		$domain = $relationship->get_domain( $role );
		if ( $domain === Toolset_Element_Domain::POSTS ) {
			return new Types_Viewmodel_Related_Content_Post(
				$role, $relationship, $this->relationships_factory, $this->wpml_service
			);
		}

		throw new RuntimeException( 'Not implemented.' );
	}
}
