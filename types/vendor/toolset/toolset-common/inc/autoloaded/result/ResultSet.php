<?php

namespace OTGS\Toolset\Common\Result;

use Exception;
use InvalidArgumentException;
use WP_Error;

/**
 * Helper for aggregating operation results in various form.
 *
 * Basically, it holds a set of partial results of some operation and allows for easily determining if
 * the operation as a whole was a success or a failure, and collecting all the information in flat arrays
 * even though the input can be more complex.
 *
 * Its add() method will accept a SingleResult instance, another ResultSet, anything that is
 * accepted by SingleResult constructor, or array (even nested one) of any of these things mixed in
 * any arbitrary way.
 *
 * @since 2.3
 */
class ResultSet implements ResultInterface {


	const DEFAULT_SEPARATOR = '; ';

	// Types of messages that can be aggregated.
	const ERROR_MESSAGES = 'error';

	const SUCCESS_MESSAGES = 'success';

	const ALL_MESSAGES = 'all';


	/** @var ResultInterface[] Mixed array of SingleResult and ResultSet instances. */
	private $results = array();

	private $has_errors = false;

	private $has_successes = false;

	private $has_warnings = false;

	private $updated_item_count = 0;


	/**
	 * ResultSet constructor.
	 *
	 * @param array|ResultSet|SingleResult|WP_Error|Exception|bool $results Array of processable results.
	 *
	 * @throws InvalidArgumentException
	 * @since 2.3
	 */
	public function __construct( $results = array() ) {
		if ( ! is_array( $results ) ) {
			$results = array( $results );
		}

		$this->add( $results );
	}


	/**
	 * Recursively process input of results and store them either as SingleResult or
	 * a ResultSet instance.
	 *
	 * @param array|ResultSet|SingleResult|WP_Error|Exception|bool|\OTGS\Toolset\Common\Result\ResultInterface $input
	 *     It accepts a result, a result set, or a "raw" resultt (anything that will be recognized
	 *     by the SingleResult constructor). Or array of any of these things, which will
	 *     be processed recursively.
	 * @param string|null $second_arg If a single boolean result is provided, this may
	 *     hold the additional display message.
	 * @param int|null $code If a single result (other than an already configured SingleResult instance) is
	 *     provided, this may hold the numeric code of the message.
	 *
	 * @since 2.3
	 */
	public function add( $input, $second_arg = null, $code = null ) {
		if ( is_array( $input ) ) {
			foreach ( $input as $single_value ) {
				$this->add( $single_value );
			}
		} elseif ( $input instanceof ResultSet ) {
			$this->results[] = $input;
			$this->has_errors = $this->has_errors || $input->has_errors();
			$this->has_successes = $this->has_successes || $input->has_successes();
			$this->has_warnings = $this->has_warnings || $input->has_warnings();
			$this->updated_item_count += $input->get_updated_item_count();
		} elseif ( $input instanceof SingleResult ) {
			$this->results[] = $input;
			$this->has_errors = $this->has_errors || $input->is_error();
			$this->has_successes = $this->has_successes || $input->is_success();
			$this->has_warnings = $this->has_warnings || $input->has_warnings();
			if ( $input instanceof ResultUpdated ) {
				$this->updated_item_count += $input->get_updated_item_count();
			}
		} elseif ( $input instanceof \OTGS\Toolset\Common\Result\ResultInterface ) {
			$this->results[] = $input;
			$this->has_errors = $this->has_errors || $input->has_errors();
			$this->has_successes = $this->has_successes || $input->has_successes();
			$this->has_warnings = $this->has_warnings || $input->has_warnings();
			$this->updated_item_count += $input->get_updated_item_count();
		} else {
			try {
				$result = new SingleResult( $input, $second_arg, $code );
				$this->add( $result );
			} catch ( Exception $e ) {
				throw new InvalidArgumentException( 'Unable to process the result.', 0, $e );
			}
		}
	}


	/**
	 * Do any results exist in this result set?
	 *
	 * @return bool
	 */
	public function has_results() {
		return ( ! empty( $this->results ) );
	}


	public function has_errors() {
		return $this->has_errors;
	}


	public function has_successes() {
		return $this->has_successes;
	}


	public function has_warnings() {
		return $this->has_warnings;
	}



	public function get_updated_item_count() {
		return $this->updated_item_count;
	}


	/**
	 * Returns true if there are success results as well as errors.
	 *
	 * @return bool
	 */
	public function is_partial_success() {
		return ( $this->has_errors() && $this->has_successes() );
	}


	/**
	 * Returns true when there are some results and all of them are success ones.
	 *
	 * @return bool
	 */
	public function is_complete_success() {
		return ( $this->has_results() && $this->has_successes() && ! $this->has_errors() );
	}


	/**
	 * Recursively aggregate existing messages of chosen type from all results.
	 *
	 * @param string $type One of the *_MESSAGES constants.
	 * @param int $level Lowest level for which the messages should be returned.
	 *
	 * @return string[] Display messages.
	 * @since 2.3
	 */
	public function get_messages( $type = self::ALL_MESSAGES, $level = LogLevel::UNDEFINED ) {
		$messages = array();

		foreach ( $this->results as $result ) {
			if ( $result->get_level() < $level ) {
				continue;
			}
			if ( $result instanceof self ) {
				// Merge messages from a nested result set
				$result_messages = $result->get_messages( $type, $level );
				$messages = array_merge( $messages, $result_messages );
			} elseif ( $result instanceof SingleResult && $result->has_message() ) {
				// Add a single result message if its type matches.
				if (
					( self::ALL_MESSAGES === $type )
					|| ( self::ERROR_MESSAGES === $type && $result->is_error() )
					|| ( self::SUCCESS_MESSAGES === $type && $result->is_success() )
				) {
					$messages[] = $result->get_message();
				}
			}
		}

		return $messages;
	}


	/**
	 * Get all display messages in one string.
	 *
	 * @param string $separator
	 * @param string $type One of the *_MESSAGES constants.
	 *
	 * @param int $level
	 *
	 * @return string
	 * @since 2.3
	 */
	public function concat_messages( $separator = '; ', $type = self::ALL_MESSAGES, $level = LogLevel::UNDEFINED ) {
		$messages = $this->get_messages( $type, $level );
		$messages = implode( $separator, $messages );

		return $messages;
	}


	/**
	 * Flatten the results into an one-dimensional array.
	 *
	 * @return SingleResult[]
	 * @since 2.3
	 */
	public function get_results_flat() {
		$results_flat = array();

		foreach ( $this->results as $result ) {
			if ( $result instanceof ResultSet ) {
				$flattened = $result->get_results_flat();
				$results_flat = array_merge( $results_flat, $flattened );
			} else {
				$results_flat[] = $result;
			}
		}

		return $results_flat;
	}


	/**
	 * Turn the whole result set into a (simplified) result.
	 *
	 * @param string $separator
	 *
	 * @return SingleResult
	 * @since 2.3
	 */
	public function aggregate( $separator = self::DEFAULT_SEPARATOR ) {
		return new SingleResult( $this->is_complete_success(), $this->concat_messages( $separator ) );
	}


	/**
	 * Get the latest result object that represents an error and has a code set.
	 *
	 * @return null|SingleResult
	 */
	public function get_last_error_with_code() {
		/** @var SingleResult[] $results_flat */
		$results_flat = array_reverse( $this->get_results_flat() );
		foreach ( $results_flat as $result ) {
			if ( $result->is_error() && 0 !== $result->get_code() ) {
				return $result;
			}
		}

		// No error code set.
		return null;
	}


	// ResultInterface methods.


	/**
	 * @return bool
	 */
	public function is_error() {
		return ! $this->is_complete_success();
	}


	/**
	 * @return bool
	 */
	public function is_success() {
		return $this->is_complete_success();
	}


	/**
	 * @return bool
	 */
	public function has_message() {
		$messages = $this->get_messages();

		return ( ! empty( $messages ) );
	}


	/**
	 * @return string
	 */
	public function get_message() {
		return $this->concat_messages();
	}


	/**
	 * @return int
	 */
	public function get_code() {
		$last_error_with_code = $this->get_last_error_with_code();
		return $last_error_with_code ? $last_error_with_code->get_code() : 0;
	}


	/**
	 * Returns the result as an associative array in a standard form.
	 *
	 * That means, it will allways have the boolean element 'success' and
	 * a string 'message', if a display message is set.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->aggregate()->to_array();
	}


	public function get_level() {
		$highest_level = LogLevel::UNDEFINED;
		foreach( $this->results as $result ) {
			$level = $result->get_level();
			if ( $level > $highest_level ) {
				$highest_level = $level;
			}
		}

		return $highest_level;
	}
}


// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( \OTGS\Toolset\Common\Result\ResultSet::class, '\Toolset_Result_Set' );
