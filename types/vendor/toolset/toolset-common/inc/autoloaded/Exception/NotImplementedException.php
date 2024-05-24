<?php

namespace OTGS\Toolset\Common\Exception;

/**
 * To be thrown when some functionality is not implemented yet.
 *
 * @since 4.0
 */
class NotImplementedException extends \RuntimeException  {

	public function __construct( $message = "", $code = 0, \Exception $previous = null ) {
		if( empty( $message ) ) {
			$message = 'Not implemented.';
		}
		parent::__construct( $message, $code, $previous );
	}


}
