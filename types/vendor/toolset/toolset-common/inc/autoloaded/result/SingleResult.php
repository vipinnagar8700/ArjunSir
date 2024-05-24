<?php

namespace OTGS\Toolset\Common\Result;

use Exception;
use InvalidArgumentException;
use OTGS\Toolset\Common\ExceptionWithMessage;
use ParseError;
use Throwable;
use WP_Error;

/**
 * Represents a result of a single operation.
 *
 * This is a wrapper for easy handling of results of different types.
 * It can encapsulate a boolean, WP_Error, boolean + message, or an exception.
 *
 * It is supposed to work well with Toolset_Result_Set.
 *
 * @since 2.3
 */
class SingleResult implements ResultInterface {

	/** @var bool */
	protected $is_error;

	/** @var bool|WP_Error|Exception What was passed as a result value. */
	protected $inner_result;

	/** @var string|null Display message, if one was provided. */
	protected $display_message;

	/** @var int */
	protected $code;

	/** @var bool */
	private $is_warning;

	/** @var int One of the LogLevel constants. */
	private $level;


	/**
	 * Toolset_Result constructor.
	 *
	 * @param bool|WP_Error|Exception|Throwable $value Result value. For boolean, true determines a success, false
	 *     determines a failure. WP_Error and Exception are interpreted as failures.
	 * @param string|null $display_message Optional display message that will be used if a boolean result is
	 *     provided. If an exception is provided, it will be used as a prefix of the message from the exception.
	 * @param int|null $code Numeric code that can be set for easier programmatical recognition of the result.
	 * @param bool $is_warning Should be set to true to indicate the result is a success but with a warning.
	 * Some specific classes have a special handling:
	 * - If an ExceptionWithMessage is passed, it uses its specially stored error message to prevent xdebug from
	 *     messing with it.
	 * - For ParseError, we extract a message together with a file and a line number where the error has occurred.
	 * @param int $level One of the LogLevel constants signifying the importance of the message.
	 *
	 * @since 2.3
	 * @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection
	 */
	public function __construct( $value, $display_message = null, $code = null, $is_warning = false, $level = LogLevel::INFO ) {
		$this->inner_result = $value;
		$this->code = (int) $code;
 		$this->is_warning = (bool) $is_warning;
 		$this->level = (int) $level;

		if ( is_bool( $value ) ) {
			$this->is_error = ! $value;
			$this->display_message = ( is_string( $display_message ) ? $display_message : null );
		} elseif ( $value instanceof WP_Error ) {
			$this->is_error = true;
			$this->display_message = $value->get_error_message();
		} elseif ( $value instanceof ExceptionWithMessage ) {
			$this->is_error = true;
			$this->display_message = $value->get_custom_message();
		} elseif ( $value instanceof ParseError ) {
			$this->is_error = true;
			$this->display_message = sprintf( '%s in %s on line %d', $value->getMessage(), $value->getFile(), $value->getLine() );
		} elseif ( $value instanceof Exception ) {
			$this->is_error = true;
			$this->display_message = (
				( is_string( $display_message ) ? $display_message . ': ' : '' )
				. $value->getMessage()
			);
		} elseif ( $value instanceof Throwable ) {
			$this->is_error = true;
			$this->display_message = (
				( is_string( $display_message ) ? $display_message . ': ' : '' )
				. $value->getMessage()
			);
		} else {
			throw new InvalidArgumentException( 'Unrecognized result value.' );
		}
	}


	public function is_error() {
		return $this->is_error;
	}


	public function is_success() {
		return ! $this->is_error;
	}


	public function has_message() {
		return ( null !== $this->display_message );
	}


	public function get_message() {
		return $this->display_message;
	}


	public function get_code() {
		return $this->code;
	}


	/**
	 * Returns the result as an associative array in a standard form.
	 *
	 * That means, it will allways have the boolean element 'success' and
	 * a string 'message', if a display message is set.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function to_array() {
		$result = array( 'success' => $this->is_success() );
		if ( $this->has_message() ) {
			$result['message'] = $this->get_message();
		}

		return $result;
	}


	public function has_warnings() {
		return $this->is_warning;
	}


	/**
	 * @inheritDoc
	 */
	public function get_level() {
		return $this->level;
	}
}


// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( SingleResult::class, '\Toolset_Result' );
