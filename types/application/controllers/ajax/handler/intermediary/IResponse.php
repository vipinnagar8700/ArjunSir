<?php
namespace OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary;

use OTGS\Toolset\Types\Model\Post\Intermediary\Request;

/**
 * Interface IResponse
 * @package OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary
 *
 * @since 3.0
 */
interface IResponse {
	/**
	 * @param Request $request
	 *
	 * @param Result $result
	 *
	 * @return Result
	 */
	public function response( Request $request, Result $result );
}