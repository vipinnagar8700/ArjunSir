<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Migration\MigrationStateInterface;
use OTGS\Toolset\Common\Result\SingleResult;


/**
 * Update the current database layer mode in site options.
 *
 * @since 4.0
 */
class Step07UpdateDbLayerMode extends MigrationStep {


	const STEP_NUMBER = 7;
	const NEXT_STEP = Step08MaintenanceModeOff::class;


	/** @var DatabaseLayerMode */
	private $database_layer_mode;


	/**
	 * Step07UpdateDbLayerMode constructor.
	 *
	 * @param DatabaseLayerMode $database_layer_mode
	 */
	public function __construct( DatabaseLayerMode $database_layer_mode ) {
		$this->database_layer_mode = $database_layer_mode;
	}


	/**
	 * @inheritDoc
	 */
	public function run( MigrationStateInterface $previous_state ) {
		$this->validate_state( $previous_state );

		$this->database_layer_mode->set( DatabaseLayerMode::FALLBACK );
		$is_updated = ( DatabaseLayerMode::FALLBACK === $this->database_layer_mode->get() );

		if ( ! $is_updated ) {
			return $this->return_error( __( 'Unable to update the database layer mode.', 'wpv-views' ), true );
		}

		return new MigrationState(
			self::NEXT_STEP,
			null,
			new SingleResult( true, __( 'Database layer mode has been updated successfully.', 'wpv-views' ) ),
			$this->get_id(),
			self::STEP_NUMBER,
			self::STEP_NUMBER + 1
		);
	}
}
