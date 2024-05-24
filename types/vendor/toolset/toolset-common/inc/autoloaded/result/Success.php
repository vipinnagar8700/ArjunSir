<?php


namespace OTGS\Toolset\Common\Result;

/**
 * Result that represents a success with an optional message.
 *
 * For convenience only.
 *
 * @since 4.0
 */
class Success extends SingleResult {

	public function __construct( $message = null, $level = LogLevel::INFO ) {
		parent::__construct( true, $message, null, false, $level );
	}
}
