<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration;

use OTGS\Toolset\Common\Result\LogLevel;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Basic migration state implementation that can be reused.
 *
 * @since 4.0
 */
abstract class AbstractMigrationState implements MigrationStateInterface {

	/** @var string|null */
	protected $previous_step_identifier;

	/** @var string|null */
	protected $next_step_identifier;

	/** @var int|null */
	protected $progress_value;

	/** @var ResultInterface|null */
	protected $result;

	/** @var array */
	protected $properties = [];

	/** @var int|null */
	protected $previous_step_number;

	/** @var int|null */
	protected $next_step_number;


	/**
	 * @inheritDoc
	 */
	public function serialize() {
		return \base64_encode( \serialize( [
			'previous_step' => $this->previous_step_identifier,
			'previous_step_number' => $this->previous_step_number,
			'next_step' => $this->next_step_identifier,
			'next_step_number' => $this->next_step_number,
			'progress' => $this->progress_value,
			'result' => $this->serialize_result(),
			'properties' => $this->properties,
		] ) );
	}


	private function serialize_result() {
		if ( null === $this->result ) {
			return null;
		}

		return [
			'is_success' => $this->result->is_success(),
			'message' => $this->result instanceof ResultSet
				? $this->result->get_messages( ResultSet::ALL_MESSAGES, LogLevel::INFO )
				: $this->result->get_message()
		];
	}


	/**
	 * @inheritDoc
	 */
	public function unserialize( $serialized ) {
		$unserialized = \unserialize( \base64_decode( $serialized ) );
		$this->previous_step_identifier = (string) toolset_getarr( $unserialized, 'previous_step' );
		$this->previous_step_number = (int) toolset_getarr( $unserialized, 'previous_step_number' );
		$this->next_step_identifier = (string) toolset_getarr( $unserialized, 'next_step' );
		$this->next_step_number = (int) toolset_getarr( $unserialized, 'next_step_number' );
		$this->progress_value = (int) toolset_getarr( $unserialized, 'progress' );
		$this->properties = toolset_ensarr( toolset_getarr( $unserialized, 'properties' ) );
		$result = toolset_getarr( $unserialized, 'result', null );
		$result_message = toolset_getarr( $result, 'message' );
		if ( is_array( $result ) ) {
			$this->result = new SingleResult(
				(bool) toolset_getarr( $result, 'is_success' ),
				is_string( $result_message ) ? $result_message : ''
			);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function set_previous_step( $step_identifier, $step_number ) {
		$this->previous_step_identifier = $step_identifier;
		$this->previous_step_number = (int) $step_number;
	}


	public function get_next_step() {
		return $this->next_step_identifier;
	}


	/**
	 * @inheritDoc
	 */
	public function set_progress( $progress_value ) {
		$this->progress_value = (int) $progress_value;
	}


	/**
	 * @inheritDoc
	 * @return int|null
	 */
	public function get_progress() {
		return $this->progress_value;
	}


	/**
	 * @inheritDoc
	 */
	public function set_next_step( $step_identifier, $step_number ) {
		$this->next_step_identifier = $step_identifier;
		$this->next_step_number = $step_number;
	}


	/**
	 * @inheritDoc
	 */
	public function set_result( ResultInterface $result ) {
		$this->result = $result;
	}


	/**
	 * @inheritDoc
	 */
	public function get_result() {
		return $this->result ? : new SingleResult( true );
	}


	/**
	 * @inheritDoc
	 */
	public function can_continue() {
		return ! empty( $this->next_step_identifier );
	}


	/**
	 * @inheritDoc
	 */
	public function set_property( $key, $value ) {
		$this->properties[ $key ] = $value;
	}


	/**
	 * @inheritDoc
	 */
	public function get_property( $key ) {
		return toolset_getarr( $this->properties, $key, null );
	}


	/**
	 * @inheritDoc
	 */
	public function get_previous_step_number() {
		return $this->previous_step_number;
	}


	/**
	 * @inheritDoc
	 */
	public function get_next_step_number() {
		return $this->next_step_number;
	}


	/**
	 * @inheritDoc
	 */
	public function get_substep_count() {
		return 0;
	}


	/**
	 * @inheritDoc
	 */
	public function get_current_substep() {
		return 0;
	}


}
