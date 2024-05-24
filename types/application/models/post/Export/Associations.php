<?php

namespace OTGS\Toolset\Types\Post\Export;

use OTGS\Toolset\Common\M2M\Association\Repository;
use OTGS\Toolset\Types\Post\Meta\Associations as AssociationsMeta;

/**
 * Class Associations
 * @package OTGS\Toolset\Types\Post\Export
 *
 * @since 3.0
 */
class Associations implements IExport {

	/** @var Repository */
	private $association_repository;

	/** @var AssociationsMeta */
	private $meta;

	/**
	 * Associations constructor.
	 *
	 * @param Repository $association_repository
	 * @param AssociationsMeta $meta
	 */
	public function __construct( Repository $association_repository, AssociationsMeta $meta ) {
		$this->association_repository = $association_repository;
		$this->meta = $meta;
	}

	/**
	 * @param \IToolset_Post $post
	 *
	 * @return array
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function getExportArray( \IToolset_Post $post ) {
		$export_data = array();

		// get all associations
		$associations = $this->association_repository->getAssociationsByChildPost( $post );

		// associations by summed by relationship
		$associations_by_relationship = array();

		foreach( $associations as $association ) {
			$key = $this->meta->getKeyForRelationship( $association->get_definition() );

			if( ! isset( $associations_by_relationship[ $key ] ) ) {
				$associations_by_relationship[ $key ] = array();
			}

			// get parent WP_Post
			$parent_post = $association->get_element( $this->association_repository->getRoleParent() )
			                           ->get_underlying_object();

			// get intermediary WP_Post
			$intermediary_post = $association->get_element( $this->association_repository->getRoleIntermediary() );
			$intermediary_post = $intermediary_post
				? $intermediary_post->get_underlying_object()
				: null;

			// get association string
			$association_string = $this->meta->parentIntermediaryToMeta( $parent_post, $intermediary_post );

			$associations_by_relationship[ $key ][] = $association_string;
		}

		foreach( $associations_by_relationship as $key => $association ){
			// stringify the collection of association strings
			$export_data[ $key ] = $this->meta->arrayToString( $association );
		}

		return $export_data;
	}
}