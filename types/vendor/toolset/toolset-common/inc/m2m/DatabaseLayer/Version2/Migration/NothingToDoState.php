<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration;


use OTGS\Toolset\Common\Result\ResultInterface;

class NothingToDoState extends MigrationState {

	public function __construct( ResultInterface $result, $last_step_identifier = null ) {
		parent::__construct( null, null, $result, $last_step_identifier );
	}

}
