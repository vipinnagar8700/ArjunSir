<?php

namespace OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary;

use OTGS\Toolset\Types\Model\Post\Intermediary\Request;
use Toolset_Element_Exception_Element_Doesnt_Exist;

/**
 * @since 3.0
 */
class ResponseAssociationMissingData implements IResponse {

	/**
	 * @param Request $request
	 * @param Result $result
	 *
	 * @return Result|null
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function response( Request $request, Result $result ) {
		$child_id = $request->getChildId();
		$parent_id = $request->getParentId();

		if (
			( empty( $parent_id ) || empty( $child_id ) )
			&& ! $request->getAssociation()
		) {
			// no assocation and no parent and child selected
			$result->setMessage( 'Missing data.' );

			return $result;
		}

		return null;
	}
}
