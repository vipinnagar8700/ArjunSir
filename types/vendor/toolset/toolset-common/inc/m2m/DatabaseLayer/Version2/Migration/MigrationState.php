<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\AbstractMigrationState;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Standard migration state for the \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\MigrationController.
 *
 * @since 4.0
 */
class MigrationState extends AbstractMigrationState {


	const SUBSTEP_COUNT_KEY = 'substep_count';
	const CURRENT_SUBSTEP_KEY = 'current_substep';

	/**
	 * MigrationState constructor.
	 *
	 * @param string|null $next_step_identifier
	 * @param int|null $progress
	 * @param SingleResult|null $result
	 * @param string|null $previous_step_identifier
	 * @param int|null $previous_step_number
	 * @param int|null $next_step_number
	 */
	public function __construct(
		$next_step_identifier = null,
		$progress = null,
		$result = null,
		$previous_step_identifier = null,
		$previous_step_number = null,
		$next_step_number = null
	) {
		if( $next_step_identifier ) {
			$this->set_next_step( $next_step_identifier, $next_step_number );
		}
		if( $progress ) {
			$this->set_progress( $progress );
		}
		if( $result ) {
			$this->set_result( $result );
		}
		if( $previous_step_identifier ) {
			$this->set_previous_step( $previous_step_identifier, $previous_step_number );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_current_substep() {
		return (int) $this->get_property( self::CURRENT_SUBSTEP_KEY );
	}


	/**
	 * @inheritDoc
	 */
	public function get_substep_count() {
		return (int) $this->get_property( self::SUBSTEP_COUNT_KEY );
	}


	/**
	 * Set the current substep and total substep count.
	 *
	 * @param int $current_substep
	 * @param int $substep_count
	 */
	public function set_substep_info( $current_substep, $substep_count ) {
		$this->set_property( self::CURRENT_SUBSTEP_KEY, (int) $current_substep );
		$this->set_property( self::SUBSTEP_COUNT_KEY, (int) $substep_count );
	}


}
