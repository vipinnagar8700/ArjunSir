<?php

namespace OTGS\Toolset\Types\Post\Import;

use IToolset_Association;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\M2M\Association\Repository as AssociationRepository;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Types\Post\Import\Association\Factory;
use OTGS\Toolset\Types\Wordpress\Post\Storage as StoragePost;
use OTGS\Toolset\Types\Wordpress\Postmeta\Storage as StoragePostmeta;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Relationship_Definition_Repository;
use WP_Post;

/**
 * Class Associations
 * @package OTGS\Toolset\Types\Post\Import
 *
 * @since 3.0
 */
class Associations {

	/** @var \OTGS\Toolset\Types\Post\Meta\Associations */
	private $postmeta_associations;

	/** @var StoragePost */
	private $storage_post;

	/** @var StoragePostmeta */
	private $storage_postmeta;

	/** @var Toolset_Relationship_Definition_Repository  */
	private $repository_relationships;

	/** @var AssociationRepository */
	private $repository_associations;

	/** @var Factory */
	private $factory_association;

	/** @var Association[] */
	private $associations = array();

	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	private $relationships_factory;

	/**
	 * Associations constructor.
	 *
	 * @param \OTGS\Toolset\Types\Post\Meta\Associations $postmeta_associations
	 * @param StoragePost $storage_post
	 * @param StoragePostmeta $storage_postmeta
	 * @param Toolset_Relationship_Definition_Repository $repository_relationships
	 * @param AssociationRepository $repository_associations
	 * @param Factory $factory_association
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	 */
	public function __construct(
		\OTGS\Toolset\Types\Post\Meta\Associations $postmeta_associations,
		StoragePost $storage_post,
		StoragePostmeta $storage_postmeta,
		Toolset_Relationship_Definition_Repository $repository_relationships,
		AssociationRepository $repository_associations,
		Factory $factory_association,
		\OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	) {
		$this->postmeta_associations = $postmeta_associations;
		$this->storage_post = $storage_post;
		$this->storage_postmeta = $storage_postmeta;
		$this->repository_relationships = $repository_relationships;
		$this->repository_associations = $repository_associations;
		$this->factory_association = $factory_association;
		$this->relationships_factory = $relationships_factory;
	}

	/**
	 * Get all loaded associations
	 * @return Association[]
	 */
	public function getAssociations() {
		return $this->associations;
	}

	/**
	 * Reset Associations
	 */
	public function resetAssociations() {
		$this->associations = array();
	}

	/**
	 * @param Association $association
	 * @param bool $delete_empty
	 */
	public function deleteAssociationMeta( Association $association, $delete_empty = true ) {
		$postmeta_associations = $this->postmeta_associations;

		$this->storage_postmeta->deleteStringFromPostMeta(
			$association->getMetaPostId(),
			$association->getMetaKey(),
			$association->getMetaAssociationString(),
			$postmeta_associations::BETWEEN_MULTIPLE_ASSOCIATIONS,
			$delete_empty
		);
	}

	/**
	 * Import previous loaded associations
	 *
	 * @param bool $reset_associations_after_import
	 * @param bool $remove_broken_associations_meta
	 *
	 * @return array
	 *        'success' => array of succesfully imported associations
	 *        'error' => array of associations which could not be imported
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function importAssociations(
		$reset_associations_after_import = true,
		$remove_broken_associations_meta = true
	) {
		$response = [
			'success' => [],
			'error' => []
		];

		foreach ( $this->getAssociations() as $association ) {
			if( $association->isAlreadyImported() ) {
				$this->deleteAssociationMeta( $association );
				$response['success'][] = $association->toArray();
				continue;
			}

			// load elements of association
			$relationship = $association->getRelationship();
			$parent = $association->getParent();
			$child = $association->getChild();
			$intermediary = $association->getIntermediary();

			if( ! $relationship || ! $parent || ! $child || ( $association->hasIntermediary() && ! $intermediary ) ) {
				// some part of the association is missing
				if( $remove_broken_associations_meta ) {
					$this->deleteAssociationMeta( $association );
				}
				$response['error'][] = $association->toArray();
				continue;
			}

			$intermediary_id =  $intermediary ? $intermediary->ID : 0;

			$imported = $this->relationships_factory->database_operations()->create_association(
				$relationship->get_slug(),
				$parent->ID,
				$child->ID,
				$intermediary_id
			);

			if( $imported instanceof ResultInterface ) {
				if( $remove_broken_associations_meta ) {
					$this->deleteAssociationMeta( $association );
				}

				$response['error'][] = $association->toArray();
			} else {
				$this->deleteAssociationMeta( $association );

				$response['success'][] = $association->toArray();
			}
		}

		if( $reset_associations_after_import ) {
			$this->resetAssociations();
		}

		return $response;
	}

	/**
	 * Loads all associations by chunks
	 *
	 * @param int|null $limit  Limit associations to import
	 * @param int|null $offset Offset for assoociations to import
	 */
	public function loadAssociationsByChunks( $limit = null, $offset = null ) {
		$metakey = $this->postmeta_associations->getKeyWithWildcardForMysql();
		$associations_meta_array = $this->storage_postmeta->getLimitedPostMetaByKey( $metakey, $limit, $offset );

		$this->loadAssociationsByMetaArray( $associations_meta_array );
	}

	/**
	 * Loads all associations of $child_post
	 *
	 * @param WP_Post $child_post
	 */
	public function loadAssociationsByChildPost( WP_Post $child_post ) {
		$metakey = $this->postmeta_associations->getKeyWithWildcardForMysql();
		$associations_meta_array = $this->storage_postmeta->getAllPostMetaByKey( $metakey, $child_post->ID );

		$this->loadAssociationsByMetaArray( $associations_meta_array );
	}


	/**
	 * @param array $associations_meta_array Array of Associations Meta
	 */
	private function loadAssociationsByMetaArray( $associations_meta_array ) {
		if( ! is_array( $associations_meta_array ) ) {
			return;
		}

		// we later need an CONST of it and PHP 5.3 does not support $this->var::CONST, but supports $var::CONST
		$postmeta_associations = $this->postmeta_associations;

		foreach( $associations_meta_array as $meta ) {
			if( ! isset( $meta['post_id'], $meta['meta_key'], $meta['meta_value'] ) ) {
				// this happens when you trust in arrays
				continue;
			}

			// child
			$child = $this->storage_post->getPostById( $meta['post_id'] );

			// relationship
			$relationship_slug = $postmeta_associations->getRelationshipSlugByMeta( $meta['meta_key'] );
			$relationship = $relationship_slug
				? $this->repository_relationships->get_definition( $relationship_slug )
				: null;

			// one meta can contain multiple associations of the child
			$associations_meta = $postmeta_associations->stringToArray( $meta['meta_value'] );

			foreach( $associations_meta as $association_meta ) {
				$association = null;

				// parent
				$parent_title_or_guid = $postmeta_associations->getParentTitleOrGUIDByMeta( $association_meta );
				$parent = $this->getPostByTitleOrGuid( $parent_title_or_guid, $relationship, 'parent' );

				// intermediary
				$intermediary_title_or_guid = $postmeta_associations->getIntermediaryTitleOrGUIDByMeta( $association_meta );
				$intermediary = $this->getPostByTitleOrGuid( $intermediary_title_or_guid, $relationship, 'intermediary' );

				if( $child && $parent && $relationship ) {
					// let's check if the association already is imported
					$qry = $this->repository_associations->getAssociationQuery( 1 );
					$qry->add( $qry->child_id( $child->ID ) )
					    ->add( $qry->parent_id( $parent->ID ) )
					    ->add( $qry->relationship_slug( $relationship->get_slug() ) )
					    ->return_association_instances();

					/** @var IToolset_Association[] $associations */
					$associations = $qry->get_results();

					$association = ! empty( $associations )
						? reset( $associations )
						: null;
				}

				// Let's build the association to import
				// even the already existing as we want to inform the client about it
				$import_association = $this->factory_association->createAssociation(
					$child->ID,
					$meta['meta_key'],
					$association_meta,
					$child,
					$relationship,
					$relationship_slug,
					$parent,
					$parent_title_or_guid,
					$intermediary,
					$intermediary_title_or_guid,
					$association
				);

				if( $import_association->isAlreadyImported() ) {
					$this->deleteAssociationMeta(
						$import_association,
						false // false to keep empty postmeta (this is required for chunk loading)
					);
				}

				$this->associations[] = $import_association;
			}
		}
	}

	/**
	 * Get post by string without knowing if the string is the GUID or the Title of the post
	 *
	 * @param string $post_title_or_guid
	 * @param IToolset_Relationship_Definition|null $relationship
	 *
	 * @param string $parent_or_intermediary
	 *
	 * @return null|WP_Post
	 */
	private function getPostByTitleOrGuid( $post_title_or_guid, $relationship, $parent_or_intermediary = 'parent' ) {
		if( ! $post_title_or_guid ) {
			return null;
		}

		// check by guid first
		if( $post = $this->storage_post->getPostByGUID( $post_title_or_guid ) ) {
			return $post;
		}

		// no post by guid found...

		if( $relationship === null ) {
			// no relationship, without it we cannot find a post by title
			return null;
		}


		// check for intermediary
		if ( $parent_or_intermediary === 'intermediary' ) {
			if( ! $intermediary_post_type = $relationship->get_intermediary_post_type() ) {
				// no intermediary post type found
				return null;
			}

			if( $post = $this->storage_post->getPostByTitle( $post_title_or_guid, $intermediary_post_type ) ) {
				// post found
				return $post;
			}

			// post not found
			return null;
		}

		// check for parent
		foreach ( $relationship->get_parent_type()->get_types() as $post_type ) {
			if( $post = $this->storage_post->getPostByTitle( $post_title_or_guid, $post_type ) ) {
				// parent found by title, no need to continue loop
				return $post;
			}
		}

		// no post
		return null;
	}
}
