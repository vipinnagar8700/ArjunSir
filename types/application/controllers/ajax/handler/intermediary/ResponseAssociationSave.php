<?php

namespace OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary;

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Types\Model\Post\Intermediary\Request;


/**
 * Class ResponseAssociationConflict
 *
 * @package OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary
 *
 * @since 3.0
 */
class ResponseAssociationSave implements IResponse {

	/** @var Factory */
	private $relationships_factory;

	/**
	 * ResponseAssociationSave constructor.
	 *
	 * @param Factory $relationships_factory
	 */
	public function __construct( Factory $relationships_factory	) {
		$this->relationships_factory = $relationships_factory;
	}


	/**
	 * @param Request $request
	 * @param Result $result
	 *
	 * @return Result|null|false
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function response( Request $request, Result $result ) {
		if ( ! $request->getParentId() || ! $request->getChildId() ) {
			// no association without parent and child
			return null;
		}

		if ( ! $relationship = $request->getRelationshipDefinition() ) {
			// should not happen, probably the DOM is invalid
			$result->setResult( $result::RESULT_DOM_ERROR );

			return $result;
		}


		if ( $prev_association = $request->getAssociation() ) {
			// delete previous association
			add_filter( 'toolset_deleting_association_intermediary_post', function ( $return, $post_id ) use ( $prev_association ) {
				if ( $prev_association->get_intermediary_id() == $post_id ) {
					// do not delete intermediary id
					return false;
				}

				// do nothing
				return $return;
			}, 10, 2 );

			$this->relationships_factory->database_operations()->delete_association( $prev_association );
		}

		$intermediary = $request->getIntermediaryPost();

		$new_association = $relationship->create_association(
			$request->getParentId(),
			$request->getChildId(),
			$intermediary->get_id()
		);

		if( ! $new_association instanceof \IToolset_Association ) {
			$result->setResult( Result::RESULT_SYSTEM_ERROR );
		}

		$result->setMessage( 'New association stored.' );
		return $result;
	}
}
