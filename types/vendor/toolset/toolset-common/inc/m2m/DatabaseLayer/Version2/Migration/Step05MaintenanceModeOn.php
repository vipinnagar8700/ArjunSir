<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;

use OTGS\Toolset\Common\MaintenanceMode\Controller;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Result\SingleResult;


/**
 * Turn on the maintenance mode before doing anything that may affect the site operation.
 *
 * @since 4.0
 */
class Step05MaintenanceModeOn extends MigrationStep {


	const STEP_NUMBER = 5;
	const NEXT_STEP = Step06RenameTables::class;


	/** @var Controller */
	private $maintenance_mode;


	/**
	 * Step01MaintenanceModeOn constructor.
	 *
	 * @param Controller $maintenance_mode
	 */
	public function __construct( Controller $maintenance_mode ) {
		$this->maintenance_mode = $maintenance_mode;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		if ( $this->maintenance_mode->maintenance_file_exists() ) {
			$result = new SingleResult( true, __( 'The maintenance mode had already been enabled before.', 'wpv-views' ) );
		} else {
			$result = $this->maintenance_mode->enable( true, true, true );
		}

		$this->clearState( Step04MigrateAssociations::STATE_PREVIOUS_LAST_ASSOCIATION_ID );
		$this->clearState( Step04MigrateAssociations::STATE_LAST_ASSOCIATION_ID );
		$this->clearState( Step04MigrateAssociations::STATE_ASSOCIATION_RESULTS );

		return new MigrationState(
			self::NEXT_STEP,
			null,
			$result,
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}
}
