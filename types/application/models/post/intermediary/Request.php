<?php

namespace OTGS\Toolset\Types\Model\Post\Intermediary;

use IToolset_Association;
use IToolset_Post;
use IToolset_Post_Type;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\API\Factory;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Post_Type_Repository;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;

class Request {

	/**
	 * @var Toolset_Element_Factory
	 */
	private $element_factory;

	/**
	 * @var Toolset_Post_Type_Repository
	 */
	private $post_type_repository;

	/**
	 * @var Toolset_Relationship_Role_Parent
	 */
	private $role_parent;

	/**
	 * @var Toolset_Relationship_Role_Child
	 */
	private $role_child;

	/**
	 * @var Toolset_Relationship_Role_Intermediary
	 */
	private $role_intermediary;

	/**
	 * @var int $intermediary_id
	 */
	private $intermediary_id;

	/**
	 * @var string|null $post_type_slug
	 */
	private $post_type_slug;

	/**
	 * @var int $parent_id
	 */
	private $parent_id;

	/**
	 * @var int $child_id
	 */
	private $child_id;

	/**
	 * @var IToolset_Post|null
	 */
	private $_intermediary_post;

	/**
	 * @var IToolset_Post_Type|null
	 */
	private $_intermediary_type;

	/**
	 * @var IToolset_Association
	 */
	private $_association;

	/**
	 * @var IToolset_Association
	 */
	private $_association_conflict;

	/**
	 * @var IToolset_Relationship_Definition
	 */
	private $_relationship_definition;


	/** @var Factory */
	private $relationships_factory;


	/**
	 * Request constructor.
	 *
	 * @param Toolset_Element_Factory $element_factory
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 * @param Factory $relationships_factory
	 * @param Toolset_Relationship_Role_Parent $role_parent
	 * @param Toolset_Relationship_Role_Child $role_child
	 * @param Toolset_Relationship_Role_Intermediary $role_intermediary
	 */
	public function __construct(
		Toolset_Element_Factory $element_factory,
		Toolset_Post_Type_Repository $post_type_repository,
		Factory $relationships_factory,
		Toolset_Relationship_Role_Parent $role_parent,
		Toolset_Relationship_Role_Child $role_child,
		Toolset_Relationship_Role_Intermediary $role_intermediary

	) {
		$this->element_factory = $element_factory;
		$this->post_type_repository = $post_type_repository;
		$this->relationships_factory = $relationships_factory;
		$this->role_parent = $role_parent;
		$this->role_child = $role_child;
		$this->role_intermediary = $role_intermediary;
	}


	/**
	 * @param mixed $intermediary_id
	 *
	 * @return Request
	 */
	public function setIntermediaryId( $intermediary_id ) {
		if ( is_int( $intermediary_id ) || is_numeric( $intermediary_id ) ) {
			$this->intermediary_id = $intermediary_id;
		}


		return $this;
	}


	/**
	 * @param string $post_type_slug
	 *
	 * @return Request
	 */
	public function setPostTypeSlug( $post_type_slug ) {
		if ( is_string( $post_type_slug ) ) {
			$this->post_type_slug = $post_type_slug;
		}

		return $this;
	}


	/**
	 * @param int|string $parent_id
	 *
	 * @return Request
	 */
	public function setParentId( $parent_id ) {
		if ( is_int( $parent_id ) || is_numeric( $parent_id ) ) {
			$this->parent_id = $parent_id;
		}

		return $this;
	}


	/**
	 * @param int|string $child_id
	 *
	 * @return Request
	 */
	public function setChildId( $child_id ) {
		if ( is_int( $child_id ) || is_numeric( $child_id ) ) {
			$this->child_id = $child_id;
		}

		return $this;
	}


	/**
	 * @return IToolset_Post|null
	 */
	public function getIntermediaryPost() {
		if ( $this->_intermediary_post !== null ) {
			return $this->_intermediary_post;
		}

		if ( $this->intermediary_id !== null ) {
			try {
				$this->_intermediary_post = $this->element_factory->get_post( $this->intermediary_id );
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				// Nothing to do, we just don't set the intermediary post value.
			}
		}

		return $this->_intermediary_post;
	}


	/**
	 * @return IToolset_Post_Type|null
	 */
	public function getIntermediaryType() {
		if ( $this->_intermediary_type !== null ) {
			return $this->_intermediary_type;
		}

		if ( $this->post_type_slug !== null ) {
			// 1. priority is the users defined slug
			$_intermediary_type = $this->post_type_repository->get( $this->post_type_slug );
			$this->post_type_slug = null;
		} elseif ( $intermediary_post = $this->getIntermediaryPost() ) {
			// 2. priority use self::getIntermediaryElement
			$_intermediary_type = $this->post_type_repository->get( $intermediary_post->get_type() );
		}

		if (
			isset( $_intermediary_type )
			&& $_intermediary_type instanceof IToolset_Post_Type
			&& $_intermediary_type->is_intermediary()
		) {
			$this->_intermediary_type = $_intermediary_type;
		}

		return $this->_intermediary_type;
	}


	/**
	 * @return IToolset_Association|null
	 */
	public function getAssociation() {
		if ( $this->_association !== null ) {
			return $this->_association;
		}

		if ( ! $intermediary_post = $this->getIntermediaryPost() ) {
			return null;
		}

		if ( ! $relationship = $this->getRelationshipDefinition() ) {
			return null;
		}

		$association_query = $this->relationships_factory->association_query();

		$results = $association_query
			->add( $association_query->element( $intermediary_post, $this->role_intermediary ) )
			->add( $association_query->relationship( $relationship ) )
			->do_not_add_default_conditions()
			->limit( 1 )
			->get_results();

		if ( count( $results ) === 1 ) {
			$this->_association = reset( $results );
		}

		return $this->_association;
	}


	/**
	 * @return IToolset_Association|null
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function getPossibleAssociationConflict() {
		if ( $this->_association_conflict !== null ) {
			return $this->_association_conflict;
		}

		if ( $this->child_id === null || $this->parent_id === null || ! $this->getRelationshipDefinition() ) {
			return null;
		}

		try {
			$child_element = $this->element_factory->get_post( $this->child_id );
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {	return null;
		}

		try {
			$parent_element = $this->element_factory->get_post( $this->parent_id );
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {	return null;
		}

		if ( $this->intermediary_id !== null ) {
			$this->_intermediary_post = $this->element_factory->get_post( $this->intermediary_id );
		}

		$association_query = $this->relationships_factory->association_query();

		$results = $association_query
			->add( $association_query->element( $parent_element, $this->role_parent ) )
			->add( $association_query->element( $child_element, $this->role_child ) )
			->add( $association_query->relationship( $this->getRelationshipDefinition() ) )
			->limit( 1 )
			->get_results();

		if ( count( $results ) === 1 ) {
			$this->_association_conflict = reset( $results );
		}

		return $this->_association_conflict;
	}


	/**
	 * @return IToolset_Relationship_Definition|null
	 */
	public function getRelationshipDefinition() {
		if ( $this->_relationship_definition !== null ) {
			return $this->_relationship_definition;
		}

		$intermediary_type = $this->getIntermediaryType();
		if ( ! $intermediary_type ) {	return null;
		}

		// load relationship
		$query = $this->relationships_factory->relationship_query();
		$results = $query
			->add( $query->intermediary_type( $intermediary_type->get_slug() ) )
			->get_results();

		if ( count( $results ) === 1 ) {
			$this->_relationship_definition = reset( $results );
		}

		return $this->_relationship_definition;
	}


	/**
	 * @return int
	 */
	public function getChildId() {
		return $this->child_id;
	}


	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->parent_id;
	}

}
