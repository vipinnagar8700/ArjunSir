<?php

namespace OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary;

use OTGS\Toolset\Types\Model\Post\Intermediary\Request;
use Toolset_Element_Exception_Element_Doesnt_Exist;


/**
 * @since 3.0
 */
class ResponseAssociationConflict implements IResponse {

	/**
	 * @param Request $request
	 * @param Result $result
	 *
	 * @return Result|null
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function response( Request $request, Result $result ) {
		if ( ! $intermediary = $request->getIntermediaryPost() ) {
			return null;
		}

		if ( ! $conflicting_association = $request->getPossibleAssociationConflict() ) {
			// no conflicting association
			return null;
		}

		if ( $intermediary->get_id() === $conflicting_association->get_intermediary_id() ) {
			// normally this should not happen as the frontend shouldn't trigger the save action if there is
			// no change done by the user... but better an extra check than an error
			$result->setMessage( 'No change.' );

			return $result;
		}

		// we have a conflict
		$result->setResult( $result::RESULT_CONFLICT );
		$result->setMessage( 'Conflict with another intermediary.' );
		$result->setConflictId( $conflicting_association->get_intermediary_id() );
		$result->setConflictUrl( get_edit_post_link( $conflicting_association->get_intermediary_id(), 'raw' ) );

		return $result;
	}
}
