<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate;

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\ConnectedElementGroup;

/**
 * Holds parsed and completed information about a wpml_translation_update event.
 *
 * @since 4.0
 */
class UpdateDescription {


	/** @var int */
	private $previous_trid;

	/** @var int */
	private $current_trid;

	/** @var int */
	private $affected_post_id;

	/** @var ConnectedElementGroup|null */
	private $affected_element_group;

	/** @var string */
	private $action_type;


	/**
	 * UpdateDescription constructor.
	 *
	 * @param string $action_type
	 * @param int $previous_trid
	 * @param int $current_trid
	 * @param int $affected_post_id
	 * @param ConnectedElementGroup $affected_element_group
	 */
	public function __construct(
		$action_type,
		$previous_trid,
		$current_trid,
		$affected_post_id,
		ConnectedElementGroup $affected_element_group = null
	) {
		$this->action_type = $action_type;
		$this->previous_trid = (int) $previous_trid;
		$this->current_trid = (int) $current_trid;
		$this->affected_post_id = (int) $affected_post_id;
		$this->affected_element_group = $affected_element_group;
	}


	/**
	 * @return string One of values from the ActionType pseudo-enum.
	 */
	public function get_action_type() {
		return $this->action_type;
	}


	/**
	 * @return int Zero if not available.
	 */
	public function get_previous_trid() {
		return $this->previous_trid;
	}


	/**
	 * @return int Zero if not available.
	 */
	public function get_current_trid() {
		return $this->current_trid;
	}


	/**
	 * @return int Zero if not available.
	 */
	public function get_affected_post_id() {
		return $this->affected_post_id;
	}


	/**
	 * The element group based on the previous TRID, if it exists.
	 *
	 * @return ConnectedElementGroup|null
	 */
	public function get_affected_element_group() {
		return $this->affected_element_group;
	}

}
