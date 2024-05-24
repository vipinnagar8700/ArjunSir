<?php

namespace OTGS\Toolset\Common;

use Throwable;

/**
 * Exception with a displayable user message.
 *
 * The message will be preserved even with xdebug present (which alters the message value of the Exception class).
 *
 * @package OTGS\Toolset\Common
 * @since 3.0.5
 */
class ExceptionWithMessage extends \RuntimeException {


	/** @var string */
	private $custom_message;


	/**
	 * @return string
	 */
	public function get_custom_message() {
		return $this->custom_message;
	}


	/**
	 * ExceptionWithMessage constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct( $message = "", $code = 0, $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->custom_message = $message;
	}


}