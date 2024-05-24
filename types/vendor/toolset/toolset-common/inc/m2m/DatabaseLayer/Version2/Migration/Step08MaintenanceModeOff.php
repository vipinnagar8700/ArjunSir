<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\MaintenanceMode\Controller;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Result\SingleResult;

/**
 * Turn off the maintenance mode, which concludes the migration.
 *
 * @since 4.0
 */
class Step08MaintenanceModeOff extends MigrationStep {


	const STEP_NUMBER = 8;

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

		if ( ! $this->maintenance_mode->maintenance_file_exists() ) {
			$result = new SingleResult( true, __( 'The maintenance mode had already been disabled before.', 'wpv-views' ) );
		} else {
			$result = $this->maintenance_mode->disable();
		}

		return new MigrationState(
			null,
			null,
			$result,
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}
}
