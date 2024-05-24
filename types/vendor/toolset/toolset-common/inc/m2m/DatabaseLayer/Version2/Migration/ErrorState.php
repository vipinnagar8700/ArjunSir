<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\AbstractMigrationState;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Represents a migration state after an error.
 *
 * @since 4.0
 */
class ErrorState extends AbstractMigrationState {


	const ROLLBACK_STEP = RollbackStep::class;


	/**
	 * ErrorState constructor.
	 *
	 * @param string $message Error message.
	 * @param bool $do_rollback Whether the next step should be a rollback (or nothing). True by default.
	 */
	public function __construct( $message, $do_rollback = true ) {
		$this->result = new SingleResult( false, $message );
		if ( $do_rollback ) {
			$this->next_step_identifier = self::ROLLBACK_STEP;
		}
	}

}
