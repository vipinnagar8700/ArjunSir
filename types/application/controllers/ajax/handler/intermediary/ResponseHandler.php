<?php

namespace OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary;

use OTGS\Toolset\Types\Model\Post\Intermediary;

/**
 * @since 3.0
 */
class ResponseHandler implements IResponse {

	private $responses = [];

	/**
	 * @param IResponse $response
	 */
	public function addResponse( IResponse $response ) {
		$this->responses[] = $response;
	}

	/**
	 * @param Intermediary\Request $request
	 *
	 * @param Result $result
	 *
	 * @return Result
	 */
	public function response( Intermediary\Request $request, Result $result ) {
		foreach( $this->responses as $response ) {
			if( $response_result = $response->response( $request, $result ) ) {
				return $response_result;
			}
		}

		// none of the usual responses worked... system error
		$result->setResult( $result::RESULT_SYSTEM_ERROR );
		return $result;
	}
}
