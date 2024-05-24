<?php

namespace OTGS\Toolset\Types\Ajax\Handler\MergeRelationships;


/**
 * Set of results including an information about the batch process phase.
 *
 * @package OTGS\Toolset\Types\Ajax\Handler\MergeRelationships
 */
class PhaseResult extends \Toolset_Result_Set {


	/** @var bool */
	private $is_phase_complete = false;


	/**
	 * @param bool $value
	 */
	public function set_is_phase_complete( $value ) {
		$this->is_phase_complete = (bool) $value;
	}


	/**
	 * @return bool True if the current phase is complete.
	 */
	public function is_phase_complete() {
		return $this->is_phase_complete;
	}


}