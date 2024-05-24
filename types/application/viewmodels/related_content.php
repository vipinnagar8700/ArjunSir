<?php

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Page\Extension\RelatedContent\DirectEditStatusRepository;

/**
 * Related Content. Elements related to a specific element.
 *
 * @since m2m
 */
abstract class Types_Viewmodel_Related_Content {


	/**
	 * Relationship
	 *
	 * @var IToolset_Relationship_Definition
	 * @since m2m
	 */
	protected $relationship;

	/**
	 * Role
	 *
	 * @var RelationshipRole
	 * @since m2m
	 */
	protected $role;

	/**
	 * Role of the related element
	 *
	 * @var string
	 * @since m2m
	 */
	protected $related_element_role;

	/** @var Toolset_Constants */
	protected $constants;

	/** @var Factory */
	protected $relationships_factory;

	/** @var DirectEditStatusRepository */
	protected $direct_edit_status_factory;

	/** @var WpmlService */
	protected $wpml_service;


	/**
	 * Constructor
	 *
	 * @param string|Toolset_Relationship_Role $role Relationship role.
	 * @param IToolset_Relationship_Definition $relationship Relationship type.
	 * @param Factory $relationships_factory
	 * @param WpmlService $wpml_service
	 * @param Toolset_Constants|null $constants Constants handler.
	 * @param DirectEditStatusRepository|null $direct_edit_status_factory
	 */
	public function __construct(
		$role,
		$relationship,
		Factory $relationships_factory,
		WpmlService $wpml_service,
		Toolset_Constants $constants = null,
		DirectEditStatusRepository $direct_edit_status_factory = null
	) {
		$this->role = Toolset_Relationship_Role::PARENT === $role
			? new Toolset_Relationship_Role_Parent()
			: new Toolset_Relationship_Role_Child();
		$this->relationship = $relationship;
		$this->constants = ( null === $constants ? new Toolset_Constants() : $constants );

		$this->related_element_role = Toolset_Relationship_Role::other( $this->role );
		$this->direct_edit_status_factory = $direct_edit_status_factory ? : new DirectEditStatusRepository();
		$this->relationships_factory = $relationships_factory;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * Returns the related content
	 *
	 * @return array Related content.
	 * @since m2m
	 */
	abstract public function get_related_content();


	/**
	 * Gets the related content as an array for using in the admin frontend for exporting to JSON format.
	 *
	 * @param int|null $post_id
	 * @param string $post_type
	 * @param int $page_number
	 * @param int $items_per_page
	 * @param string|null $role
	 * @param string $sort
	 * @param string $sort_by
	 * @param string $sort_origin
	 *
	 * @since m2m
	 */
	abstract public function get_related_content_array( $post_id = null, $post_type = '', $page_number = 1, $items_per_page = 0, $role = null, $sort = 'ASC', $sort_by = 'displayName', $sort_origin = 'post_title' );

	/**
	 * Returns the number of rows found.
	 *
	 * @return integer
	 * @since m2m
	 */
	abstract public function get_rows_found();
}
